<?php

namespace App\Services;

use App\Http\Controllers\SslController;
use App\Models\Account;
use App\Models\CpanelMigration;
use App\Models\CronJob;
use App\Models\Database;
use App\Models\Domain;
use App\Models\EmailAccount;
use App\Models\Package;
use App\Models\Server;
use App\Models\SslCertificate;

class MigrationService
{
    private ProvisioningService $provisioning;

    public function __construct(ProvisioningService $provisioning)
    {
        $this->provisioning = $provisioning;
    }

    /**
     * Parse a cPanel backup and return the manifest.
     */
    public function parse(Server $server, string $path): array
    {
        $response = AgentService::for($server)->postLong('/migration/parse', [
            'path' => $path,
        ], 300);

        if (!$response || !$response->successful()) {
            $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
            return ['success' => false, 'error' => $error];
        }

        return [
            'success'  => true,
            'manifest' => $response->json('manifest'),
        ];
    }

    /**
     * Execute the full migration.
     */
    public function execute(CpanelMigration $migration): void
    {
        $migration->markRunning();
        $server = $migration->server;
        $manifest = $migration->manifest;
        $options = $migration->options ?? [];
        $result = [];
        $backupDir = $manifest['backup_dir'] ?? '';

        // ── Step 1: Create account (5%) ──
        $migration->updateProgress(5, 'Creating account...');

        $package = Package::where('name', $options['package'] ?? '')->first()
            ?? Package::where('is_default', true)->first()
            ?? Package::first();

        if (!$package) {
            $migration->markFailed('No package found. Create a package first.');
            return;
        }

        $createResult = $this->provisioning->createAccount([
            'server_id'       => $migration->server_id,
            'username'        => $migration->target_username,
            'domain'          => $migration->main_domain,
            'package_id'      => $package->id,
            'owner_user_id'   => $migration->initiated_by,
            'created_via'     => 'migration',
            'skip_auto_setup' => true,
        ]);

        if (!$createResult['success'] && !$createResult['account']) {
            $migration->markFailed('Account creation failed: ' . $createResult['error']);
            return;
        }

        $account = $createResult['account'];
        $migration->update(['account_id' => $account->id]);
        $result['account'] = ['status' => $createResult['success'] ? 'success' : 'warning', 'error' => $createResult['error']];

        // ── Step 2: Restore files (15-40%) ──
        if ($options['import_files'] ?? true) {
            $migration->updateProgress(15, 'Restoring files...');
            $result['files'] = $this->restoreFiles($server, $backupDir, $migration, $manifest);
        }

        // ── Step 2b: Create subdomains (35-40%) ──
        $subdomains = $manifest['subdomains'] ?? [];
        if (! empty($subdomains)) {
            $migration->updateProgress(35, 'Creating subdomains...');
            $result['subdomains'] = $this->createSubdomains($server, $account, $subdomains, $migration->main_domain);
        }

        // ── Step 3: Restore databases (40-55%) ──
        if ($options['import_databases'] ?? true) {
            $migration->updateProgress(40, 'Importing databases...');
            $result['databases'] = $this->restoreDatabases($server, $backupDir, $account, $manifest);
        }

        // ── Step 4: Restore email (55-70%) ──
        if ($options['import_email'] ?? true) {
            $migration->updateProgress(55, 'Importing email accounts...');
            $result['email'] = $this->restoreEmail($server, $backupDir, $account, $manifest);
        }

        // ── Step 5: Restore DNS (70-80%) ──
        if ($options['import_dns'] ?? true) {
            $migration->updateProgress(70, 'Importing DNS zones...');
            $result['dns'] = $this->restoreDns($server, $backupDir, $migration->main_domain);
        }

        // ── Step 6: Restore SSL (80-90%) ──
        if ($options['import_ssl'] ?? true) {
            $migration->updateProgress(80, 'Installing SSL certificates...');
            $result['ssl'] = $this->restoreSsl($server, $backupDir, $account, $migration->main_domain);
        }

        // ── Step 7: Restore cron (90-95%) ──
        if ($options['import_cron'] ?? true) {
            $migration->updateProgress(90, 'Importing cron jobs...');
            $result['cron'] = $this->restoreCron($server, $backupDir, $account, $manifest);
        }

        // ── Step 8: Set password + Cleanup (95-100%) ──
        $migration->updateProgress(95, 'Finishing up...');
        $this->setPassword($server, $backupDir, $migration->target_username);

        AgentService::for($server)->postLong('/migration/cleanup', [
            'backup_dir' => $backupDir,
        ]);

        ActivityLogger::log('migration.completed', 'account', $account->id, $account->username,
            "cPanel migration completed for {$migration->main_domain}",
            ['server_id' => $server->id, 'source' => $migration->source_path]);

        $migration->markCompleted($result);
    }

    /**
     * Create subdomains from the cPanel manifest. The files are already in
     * place from the restoreFiles step — this just creates the Nginx vhost,
     * PHP-FPM pool, and panel Domain row for each subdomain.
     *
     * cPanel stores subdomains as full FQDNs (e.g. "blog.pivlu.com") with
     * their document root under the main domain's homedir. We map each one
     * to an Opterius subdomain with parent_id → main domain.
     */
    private function createSubdomains(Server $server, Account $account, array $subdomains, string $mainDomain): array
    {
        $mainDomainModel = $account->domains()->whereNull('parent_id')->first();
        if (! $mainDomainModel) {
            return ['status' => 'skipped', 'reason' => 'No main domain found'];
        }

        $created = 0;
        $failed  = 0;
        $details = [];

        foreach ($subdomains as $sub) {
            $sub = trim($sub);
            if (empty($sub) || $sub === $mainDomain) {
                continue;
            }

            // Skip if this subdomain already exists
            if (Domain::where('domain', $sub)->exists()) {
                $details[] = ['domain' => $sub, 'status' => 'skipped', 'reason' => 'already exists'];
                continue;
            }

            // cPanel subdomain document roots are typically:
            //   /home/user/subdomain.domain.com   (separate dir)
            //   /home/user/public_html/subdomain   (inside public_html)
            // Opterius uses: /home/user/subdomain.domain.com/public_html
            // The files from cPanel are already under the account homedir,
            // so we just create the vhost pointing to wherever the files are.
            $docRoot = "/home/{$account->username}/{$sub}/public_html";

            // If the cPanel backup put the files under public_html/<prefix>
            // instead, check for that and use it if it exists.
            $altRoot = "/home/{$account->username}/{$mainDomain}/public_html/{$sub}";

            $response = AgentService::for($server)->post('/domains/create', [
                'domain'        => $sub,
                'parent_domain' => $mainDomain,
                'document_root' => $docRoot,
                'username'      => $account->username,
                'php_version'   => $mainDomainModel->php_version ?? '8.3',
            ]);

            if ($response && $response->successful()) {
                $subDomain = Domain::create([
                    'server_id'    => $server->id,
                    'account_id'   => $account->id,
                    'parent_id'    => $mainDomainModel->id,
                    'domain'       => $sub,
                    'document_root'=> $docRoot,
                    'php_version'  => $mainDomainModel->php_version ?? '8.3',
                    'status'       => 'active',
                ]);

                // Auto-issue SSL for the subdomain (async — doesn't block the import)
                SslController::autoIssue($subDomain);

                $created++;
                $details[] = ['domain' => $sub, 'status' => 'success'];
            } else {
                $error = $response?->json('error') ?? 'agent unreachable';
                $failed++;
                $details[] = ['domain' => $sub, 'status' => 'failed', 'error' => $error];
            }
        }

        return [
            'status'  => $failed === 0 ? 'success' : ($created > 0 ? 'partial' : 'failed'),
            'created' => $created,
            'failed'  => $failed,
            'details' => $details,
        ];
    }

    private function restoreFiles(Server $server, string $backupDir, CpanelMigration $migration, array $manifest): array
    {
        $response = AgentService::for($server)->postLong('/migration/restore-files', [
            'backup_dir'  => $backupDir,
            'username'    => $migration->target_username,
            'main_domain' => $migration->main_domain,
        ], 600);

        if ($response && $response->successful()) {
            return ['status' => 'success', 'size_mb' => $manifest['disk_usage_mb'] ?? 0];
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Agent unreachable';
        return ['status' => 'failed', 'error' => $error];
    }

    private function restoreDatabases(Server $server, string $backupDir, Account $account, array $manifest): array
    {
        $databases = $manifest['databases'] ?? [];
        if (empty($databases)) {
            return ['status' => 'skipped', 'reason' => 'No databases found'];
        }

        // Pre-generate fresh passwords for each DB user and remember them in
        // memory so we can persist them in the panel after the agent confirms
        // the database was created. Without this, the user has no way to ever
        // discover the password the migration generated for them.
        $dbList    = [];
        $generated = []; // db_name => plaintext password
        foreach ($databases as $db) {
            $password = bin2hex(random_bytes(12));
            $dbList[] = [
                'name'     => $db['name'],
                'username' => $db['name'], // cPanel uses same name for user
                'password' => $password,
            ];
            $generated[$db['name']] = $password;
        }

        $response = AgentService::for($server)->postLong('/migration/restore-databases', [
            'backup_dir' => $backupDir,
            'databases'  => $dbList,
        ], 600);

        $details = [];
        if ($response && $response->successful()) {
            $imported = $response->json('databases', []);
            foreach ($imported as $db) {
                if (($db['status'] ?? '') === 'success') {
                    Database::create([
                        'account_id'         => $account->id,
                        'server_id'          => $account->server_id,
                        'name'               => $db['name'],
                        'db_username'        => $db['username'] ?? $db['name'],
                        'encrypted_password' => $generated[$db['name']] ?? null,
                        'remote'             => false,
                        'status'             => 'active',
                    ]);

                    // Surface the credentials in the import result so the user
                    // sees them on the migration result screen and can update
                    // their app's config file.
                    $db['db_user']     = $db['username'] ?? $db['name'];
                    $db['db_password'] = $generated[$db['name']] ?? null;
                }
                $details[] = $db;
            }

            $hasFailure = collect($details)->contains(fn ($d) => ($d['status'] ?? '') !== 'success');
            return ['status' => $hasFailure ? 'partial' : 'success', 'details' => $details];
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Agent unreachable';
        return ['status' => 'failed', 'error' => $error];
    }

    private function restoreEmail(Server $server, string $backupDir, Account $account, array $manifest): array
    {
        $emails = $manifest['email_accounts'] ?? [];
        if (empty($emails)) {
            return ['status' => 'skipped', 'reason' => 'No email accounts found'];
        }

        $mainDomain = $account->domains()->whereNull('parent_id')->first();
        if (!$mainDomain) {
            return ['status' => 'failed', 'error' => 'No main domain found'];
        }

        // Build the request. For each mailbox:
        //   - If the cPanel backup contained the original password hash, we
        //     pass it to the agent which re-uses it verbatim. The user's
        //     existing email password will keep working with no reset.
        //   - If the hash is missing (old backup, corrupted shadow file),
        //     we generate a fresh random password and the user is shown
        //     the credentials on the import result page.
        $accountList  = [];
        $generated    = []; // email => plaintext password (only for mailboxes without a hash)
        foreach ($emails as $email) {
            $hasHash = ! empty($email['password_hash']);
            $password = $hasHash ? null : bin2hex(random_bytes(12));

            $accountList[] = [
                'email'         => $email['email'],
                'password'      => $password ?? '',
                'password_hash' => $email['password_hash'] ?? '',
            ];

            if (! $hasHash) {
                $generated[$email['email']] = $password;
            }
        }

        $response = AgentService::for($server)->postLong('/migration/restore-email', [
            'backup_dir' => $backupDir,
            'domain'     => $mainDomain->domain,
            'username'   => $account->username,
            'accounts'   => $accountList,
        ], 300);

        if ($response && $response->successful()) {
            $imported = $response->json('accounts', []);
            $count = 0;
            $details = [];
            foreach ($imported as $em) {
                if (($em['status'] ?? '') === 'success') {
                    EmailAccount::create([
                        'domain_id'          => $mainDomain->id,
                        'email'              => $em['email'],
                        'quota'              => 1024,
                        'encrypted_password' => $generated[$em['email']] ?? null,
                        'password_preserved' => ! isset($generated[$em['email']]),
                        'status'             => 'active',
                    ]);
                    $count++;

                    // Surface on the import result page.
                    $em['preserved']  = ! isset($generated[$em['email']]);
                    if (isset($generated[$em['email']])) {
                        $em['new_password'] = $generated[$em['email']];
                    }
                }
                $details[] = $em;
            }
            return ['status' => 'success', 'count' => $count, 'details' => $details];
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Agent unreachable';
        return ['status' => 'failed', 'error' => $error];
    }

    private function restoreDns(Server $server, string $backupDir, string $domain): array
    {
        $response = AgentService::for($server)->postLong('/migration/restore-dns', [
            'backup_dir' => $backupDir,
            'domain'     => $domain,
            'server_ip'  => $server->ip_address,
        ]);

        if ($response && $response->successful()) {
            return ['status' => 'success', 'records' => $response->json('record_count', 0)];
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Agent unreachable';
        return ['status' => 'failed', 'error' => $error];
    }

    private function restoreSsl(Server $server, string $backupDir, Account $account, string $domain): array
    {
        $response = AgentService::for($server)->postLong('/migration/restore-ssl', [
            'backup_dir' => $backupDir,
            'domain'     => $domain,
            'username'   => $account->username,
        ]);

        if ($response && $response->successful()) {
            $data = $response->json();

            if ($data['installed'] ?? false) {
                $mainDomain = Domain::where('domain', $domain)->where('account_id', $account->id)->first();
                if ($mainDomain) {
                    SslCertificate::updateOrCreate(
                        ['domain_id' => $mainDomain->id],
                        [
                            'type'       => 'imported',
                            'status'     => 'active',
                            'expires_at' => $data['expires_at'] ?? null,
                        ]
                    );
                }
                return ['status' => 'success'];
            }

            return ['status' => 'skipped', 'reason' => $data['reason'] ?? 'No valid certificate found'];
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Agent unreachable';
        return ['status' => 'failed', 'error' => $error];
    }

    private function restoreCron(Server $server, string $backupDir, Account $account, array $manifest): array
    {
        $crons = $manifest['cron_jobs'] ?? [];
        if (empty($crons)) {
            return ['status' => 'skipped', 'reason' => 'No cron jobs found'];
        }

        $response = AgentService::for($server)->postLong('/migration/restore-cron', [
            'backup_dir'       => $backupDir,
            'username'         => $account->username,
            'original_username' => $manifest['username'] ?? $account->username,
        ]);

        if ($response && $response->successful()) {
            $imported = $response->json('cron_jobs', []);
            foreach ($imported as $cron) {
                if (($cron['status'] ?? '') === 'success') {
                    CronJob::create([
                        'account_id' => $account->id,
                        'schedule'   => $cron['schedule'],
                        'command'    => $cron['command'],
                        'enabled'    => true,
                    ]);
                }
            }
            return ['status' => 'success', 'count' => count($imported)];
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Agent unreachable';
        return ['status' => 'failed', 'error' => $error];
    }

    private function setPassword(Server $server, string $backupDir, string $username): void
    {
        AgentService::for($server)->postLong('/migration/set-password', [
            'backup_dir' => $backupDir,
            'username'   => $username,
        ]);
    }
}
