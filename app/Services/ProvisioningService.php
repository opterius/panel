<?php

namespace App\Services;

use App\Http\Controllers\SslController;
use App\Models\Account;
use App\Models\Domain;
use App\Models\Package;
use App\Models\Server;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProvisioningService
{
    /**
     * Create a hosting account with its primary domain.
     *
     * @param array $params Keys: server_id, username, domain, package_id, owner_user_id,
     *                       whmcs_service_id?, whmcs_client_id?, created_via?
     * @return array{success: bool, account: ?Account, error: ?string}
     */
    public function createAccount(array $params): array
    {
        // License check
        $license = new LicenseService();
        $maxAccounts = $license->maxAccounts();
        $currentAccounts = Account::count();

        if ($currentAccounts >= $maxAccounts) {
            return ['success' => false, 'account' => null, 'error' => "Account limit reached ({$currentAccounts}/{$maxAccounts})."];
        }

        // Check uniqueness
        if (Account::where('username', $params['username'])->exists()) {
            return ['success' => false, 'account' => null, 'error' => "Username '{$params['username']}' already exists."];
        }

        if (Domain::where('domain', $params['domain'])->exists()) {
            return ['success' => false, 'account' => null, 'error' => "Domain '{$params['domain']}' already exists."];
        }

        $package = Package::findOrFail($params['package_id']);
        $server = Server::findOrFail($params['server_id']);
        $phpVersion = $package->default_php_version;
        $diskQuota = $package->disk_quota;

        $account = DB::transaction(function () use ($params, $phpVersion, $diskQuota) {
            $homeDir = '/home/' . $params['username'];

            $account = Account::create([
                'user_id'          => $params['owner_user_id'],
                'server_id'        => $params['server_id'],
                'package_id'       => $params['package_id'],
                'username'         => $params['username'],
                'home_directory'   => $homeDir,
                'php_version'      => $phpVersion,
                'disk_quota'       => $diskQuota,
                'whmcs_service_id' => $params['whmcs_service_id'] ?? null,
                'whmcs_client_id'  => $params['whmcs_client_id'] ?? null,
                'created_via'      => $params['created_via'] ?? 'panel',
            ]);

            Domain::create([
                'server_id'     => $params['server_id'],
                'account_id'    => $account->id,
                'domain'        => $params['domain'],
                'document_root' => $homeDir . '/' . $params['domain'] . '/public_html',
                'php_version'   => $phpVersion,
                'status'        => 'pending',
            ]);

            return $account;
        });

        // Tell the agent to create the domain on the server
        $domain = $account->domains()->first();

        $response = AgentService::for($server)->post('/domains/create', [
            'domain'        => $domain->domain,
            'document_root' => $domain->document_root,
            'username'      => $account->username,
            'php_version'   => $domain->php_version,
        ]);

        if ($response && $response->successful()) {
            $domain->update(['status' => 'active']);

            // Skip DNS/SSL if this is a migration (they'll be imported separately)
            if (!($params['skip_auto_setup'] ?? false)) {
                // Auto-create DNS zone
                AgentService::for($server)->post('/dns/create-zone', [
                    'domain'    => $domain->domain,
                    'server_ip' => $server->ip_address,
                    'ns1'       => config('opterius.ns1', 'ns1.' . $domain->domain),
                    'ns2'       => config('opterius.ns2', 'ns2.' . $domain->domain),
                ]);

                // Auto SSL
                SslController::autoIssue($domain);
            }

            ActivityLogger::log('account.created', 'account', $account->id, $account->username,
                "Created account {$account->username} with domain {$params['domain']}",
                ['server_id' => $account->server_id, 'created_via' => $params['created_via'] ?? 'panel']);

            return ['success' => true, 'account' => $account, 'error' => null];
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';

        ActivityLogger::log('account.created', 'account', $account->id, $account->username,
            "Created account {$account->username} (server setup failed: {$error})",
            ['server_id' => $account->server_id]);

        return ['success' => false, 'account' => $account, 'error' => "Account saved but server setup failed: {$error}"];
    }

    /**
     * Suspend an account.
     */
    public function suspendAccount(Account $account, string $reason = ''): array
    {
        $account->load('server', 'domains');

        $response = AgentService::for($account->server)->post('/account/suspend', [
            'username' => $account->username,
            'domains'  => $account->domains->pluck('domain')->toArray(),
            'action'   => 'suspend',
        ]);

        if ($response && $response->successful()) {
            $account->update([
                'suspended'      => true,
                'suspended_at'   => now(),
                'suspend_reason' => $reason,
            ]);
            $account->domains()->update(['status' => 'suspended']);

            ActivityLogger::log('account.suspended', 'account', $account->id, $account->username,
                "Suspended account {$account->username}" . ($reason ? ": {$reason}" : ''));

            return ['success' => true, 'error' => null];
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return ['success' => false, 'error' => "Failed to suspend: {$error}"];
    }

    /**
     * Unsuspend an account.
     */
    public function unsuspendAccount(Account $account): array
    {
        $account->load('server', 'domains');

        $response = AgentService::for($account->server)->post('/account/suspend', [
            'username' => $account->username,
            'domains'  => $account->domains->pluck('domain')->toArray(),
            'action'   => 'unsuspend',
        ]);

        if ($response && $response->successful()) {
            $account->update([
                'suspended'      => false,
                'suspended_at'   => null,
                'suspend_reason' => null,
            ]);
            $account->domains()->update(['status' => 'active']);

            ActivityLogger::log('account.unsuspended', 'account', $account->id, $account->username,
                "Unsuspended account {$account->username}");

            return ['success' => true, 'error' => null];
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return ['success' => false, 'error' => "Failed to unsuspend: {$error}"];
    }

    /**
     * Terminate (delete) an account.
     */
    public function terminateAccount(Account $account): array
    {
        $account->load('server', 'domains');

        // Tell agent to fully delete the account (vhosts, FPM, files, user, email, cron)
        AgentService::for($account->server)->post('/account/delete', [
            'username' => $account->username,
            'domains'  => $account->domains->pluck('domain')->toArray(),
        ]);

        ActivityLogger::log('account.terminated', 'account', $account->id, $account->username,
            "Terminated account {$account->username}", ['server_id' => $account->server_id]);

        $account->delete();

        return ['success' => true, 'error' => null];
    }

    /**
     * Change the system password for an account.
     */
    public function changePassword(Account $account, string $newPassword): array
    {
        $account->load('server');

        $response = AgentService::for($account->server)->post('/account/password', [
            'username' => $account->username,
            'password' => $newPassword,
        ]);

        if ($response && $response->successful()) {
            ActivityLogger::log('account.password_changed', 'account', $account->id, $account->username,
                "Changed system password for {$account->username}");

            return ['success' => true, 'error' => null];
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return ['success' => false, 'error' => "Password change failed: {$error}"];
    }

    /**
     * Change the package assigned to an account.
     */
    public function changePackage(Account $account, Package $newPackage): array
    {
        $oldPackage = $account->package;

        $account->update([
            'package_id' => $newPackage->id,
            'disk_quota' => $newPackage->disk_quota,
        ]);

        ActivityLogger::log('account.package_changed', 'account', $account->id, $account->username,
            "Changed package from {$oldPackage?->name} to {$newPackage->name}");

        return ['success' => true, 'error' => null];
    }

    /**
     * Find or create a User for API-based provisioning.
     */
    public function findOrCreateUser(string $email, string $name, string $password): User
    {
        $user = User::where('email', $email)->first();

        if ($user) {
            return $user;
        }

        return User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
            'role'     => 'user',
        ]);
    }
}
