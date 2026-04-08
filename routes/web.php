<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AliasController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\AutoresponderController;
use App\Http\Controllers\DnsTemplateController;
use App\Http\Controllers\IpAddressController;
use App\Http\Controllers\NginxDirectiveController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\SpamFilterController;
use App\Http\Controllers\SslOverviewController;
use App\Http\Controllers\TerminalController;
use App\Http\Controllers\MigrationController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CollaboratorController;
use App\Http\Controllers\CronJobController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\DnsController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\EmailSettingsController;
use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\ForwarderController;
use App\Http\Controllers\FtpController;
use App\Http\Controllers\CmsInstallerController;
use App\Http\Controllers\ComposerController;
use App\Http\Controllers\GitController;
use App\Http\Controllers\HtaccessController;
use App\Http\Controllers\LaravelInstallerController;
use App\Http\Controllers\NodeController;
use App\Http\Controllers\PostgresController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PhpController;
use App\Http\Controllers\ResellerController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SshController;
use App\Http\Controllers\SubdomainController;
use App\Http\Controllers\SslController;
use App\Http\Controllers\UserPhpController;
use App\Http\Controllers\UserLocaleController;
use App\Http\Controllers\WordPressController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Setup wizard (only works when no users exist)
Route::get('/setup', [App\Http\Controllers\SetupController::class, 'index'])->name('setup.index');
Route::post('/setup', [App\Http\Controllers\SetupController::class, 'store'])->name('setup.store');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    // Language preference
    Route::patch('/user/locale', [UserLocaleController::class, 'update'])->name('user.locale');

    // Default dashboard redirect based on role
    Route::get('/dashboard', function () {
        if (auth()->user()->isAdmin() || auth()->user()->isReseller()) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('user.dashboard');
    })->name('dashboard');

    // ================================================================
    // ADMIN PANEL — Server management (WHM equivalent)
    // ================================================================
    Route::prefix('admin')->middleware('admin')->name('admin.')->group(function () {

        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        Route::resource('servers', ServerController::class)->except(['edit', 'update']);
        Route::get('/servers/{server}/time', [\App\Http\Controllers\ServerTimeController::class, 'show'])->name('servers.time');
        Route::post('/servers/{server}/time/timezone', [\App\Http\Controllers\ServerTimeController::class, 'updateTimezone'])->name('servers.time.timezone');
        Route::post('/servers/{server}/time/sync', [\App\Http\Controllers\ServerTimeController::class, 'syncNow'])->name('servers.time.sync');

        // System Settings (admin-only, server-wide policies)
        Route::get('/system-settings/{category?}', [\App\Http\Controllers\SystemSettingsController::class, 'index'])
            ->where('category', '[a-z_-]+')
            ->name('system-settings.index');
        Route::post('/system-settings/{category}', [\App\Http\Controllers\SystemSettingsController::class, 'update'])
            ->where('category', '[a-z_-]+')
            ->name('system-settings.update');
        Route::resource('accounts', AccountController::class)->except(['edit', 'update']);
        Route::post('/accounts/{account}/suspend', [AccountController::class, 'suspend'])->name('accounts.suspend');
        Route::post('/accounts/{account}/update-owner', [AccountController::class, 'updateOwner'])->name('accounts.update-owner');
        Route::post('/accounts/{account}/change-password', [AccountController::class, 'changePassword'])->name('accounts.change-password');
        Route::post('/accounts/{account}/change-package', [AccountController::class, 'changePackage'])->name('accounts.change-package');
        Route::get('/accounts/{account}/collaborators', [CollaboratorController::class, 'index'])->name('collaborators.index');
        Route::post('/accounts/{account}/collaborators', [CollaboratorController::class, 'store'])->name('collaborators.store');
        Route::post('/accounts/{account}/collaborators/{user}/role', [CollaboratorController::class, 'updateRole'])->name('collaborators.update-role');
        Route::delete('/accounts/{account}/collaborators/{user}', [CollaboratorController::class, 'destroy'])->name('collaborators.destroy');
        Route::resource('packages', PackageController::class)->except(['show']);
        Route::resource('resellers', ResellerController::class);

        // Monitor
        Route::get('/monitor', [MonitorController::class, 'index'])->name('monitor.index');
        Route::get('/monitor/realtime', [MonitorController::class, 'realtime'])->name('monitor.realtime');
        Route::post('/monitor/processes', [MonitorController::class, 'topProcesses'])->name('monitor.processes');

        // Security
        Route::get('/security', [SecurityController::class, 'index'])->name('security.index');
        Route::post('/security/scan', [SecurityController::class, 'scan'])->name('security.scan');
        Route::post('/security/firewall-add', [SecurityController::class, 'firewallAdd'])->name('security.firewall-add');
        Route::post('/security/firewall-remove', [SecurityController::class, 'firewallRemove'])->name('security.firewall-remove');
        Route::post('/security/ip-block', [SecurityController::class, 'ipBlock'])->name('security.ip-block');
        Route::post('/security/fail2ban-unban', [SecurityController::class, 'fail2banUnban'])->name('security.fail2ban-unban');

        // Alerts
        Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
        Route::post('/alerts', [AlertController::class, 'store'])->name('alerts.store');
        Route::post('/alerts/{alertRule}/toggle', [AlertController::class, 'toggle'])->name('alerts.toggle');
        Route::delete('/alerts/{alertRule}', [AlertController::class, 'destroy'])->name('alerts.destroy');

        // Backups
        Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
        Route::post('/backups/create', [BackupController::class, 'create'])->name('backups.create');
        Route::post('/backups/{backup}/restore', [BackupController::class, 'restore'])->name('backups.restore');
        Route::get('/backups/{backup}/download', [BackupController::class, 'download'])->name('backups.download');
        Route::delete('/backups/{backup}', [BackupController::class, 'destroy'])->name('backups.destroy');

        // Services
        Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
        Route::post('/services/action', [ServiceController::class, 'action'])->name('services.action');

        // PHP Versions (server-level)
        Route::get('/php', [PhpController::class, 'index'])->name('php.index');
        Route::post('/php/install', [PhpController::class, 'install'])->name('php.install');
        Route::post('/php/switch', [PhpController::class, 'switchVersion'])->name('php.switch');
        Route::post('/php/config', [PhpController::class, 'config'])->name('php.config');
        Route::post('/php/extension', [PhpController::class, 'toggleExtension'])->name('php.extension');

        // Email Settings (global)
        Route::get('/email-settings', [EmailSettingsController::class, 'index'])->name('email-settings.index');
        Route::put('/email-settings', [EmailSettingsController::class, 'update'])->name('email-settings.update');

        // Activity Log
        Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
        Route::get('/activity-log/export', [ActivityLogController::class, 'export'])->name('activity-log.export');

        // Updates
        Route::get('/updates', [UpdateController::class, 'index'])->name('updates.index');
        Route::post('/updates/run', [UpdateController::class, 'run'])->name('updates.run');

        // Spam Filter
        Route::get('/spam-filter', [SpamFilterController::class, 'index'])->name('spam-filter.index');
        Route::post('/spam-filter', [SpamFilterController::class, 'configure'])->name('spam-filter.configure');

        // SSL Overview (server-wide)
        Route::get('/ssl-overview', [SslOverviewController::class, 'index'])->name('ssl-overview.index');
        Route::post('/ssl-overview/toggle-auto', [SslOverviewController::class, 'toggleAutoSsl'])->name('ssl-overview.toggle-auto');
        Route::post('/ssl-overview/recheck', [SslOverviewController::class, 'recheckMissing'])->name('ssl-overview.recheck');

        // DNS Templates
        Route::resource('dns-templates', DnsTemplateController::class)->except(['show']);

        // IP Address Management
        Route::get('/ip-addresses', [IpAddressController::class, 'index'])->name('ip-addresses.index');
        Route::post('/ip-addresses', [IpAddressController::class, 'store'])->name('ip-addresses.store');
        Route::put('/ip-addresses/{ipAddress}/assign', [IpAddressController::class, 'assign'])->name('ip-addresses.assign');
        Route::delete('/ip-addresses/{ipAddress}', [IpAddressController::class, 'destroy'])->name('ip-addresses.destroy');

        // cPanel Migrations
        Route::get('/migrations', [MigrationController::class, 'index'])->name('migrations.index');
        Route::get('/migrations/create', [MigrationController::class, 'create'])->name('migrations.create');
        Route::post('/migrations/parse', [MigrationController::class, 'parse'])->name('migrations.parse');
        Route::get('/migrations/{migration}/preview', [MigrationController::class, 'preview'])->name('migrations.preview');
        Route::post('/migrations/{migration}/execute', [MigrationController::class, 'execute'])->name('migrations.execute');
        Route::get('/migrations/{migration}', [MigrationController::class, 'show'])->name('migrations.show');
        Route::get('/migrations/{migration}/status', [MigrationController::class, 'status'])->name('migrations.status');
        Route::delete('/migrations/{migration}', [MigrationController::class, 'destroy'])->name('migrations.destroy');

        // API Keys
        Route::get('/api-keys', [ApiKeyController::class, 'index'])->name('api-keys.index');
        Route::get('/api-keys/create', [ApiKeyController::class, 'create'])->name('api-keys.create');
        Route::post('/api-keys', [ApiKeyController::class, 'store'])->name('api-keys.store');
        Route::delete('/api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->name('api-keys.destroy');

        // License
        Route::get('/license', [LicenseController::class, 'index'])->name('license.index');
        Route::put('/license', [LicenseController::class, 'update'])->name('license.update');
        Route::post('/license/refresh', [LicenseController::class, 'refresh'])->name('license.refresh');

        // Login as user
        Route::post('/login-as/{user}', function (\App\Models\User $user) {
            session()->put('admin_id', auth()->id());
            auth()->login($user);
            return redirect()->route('user.dashboard');
        })->name('login-as');
    });

    // ================================================================
    // USER PANEL — Domain management (cPanel equivalent)
    // ================================================================
    Route::name('user.')->group(function () {

        Route::get('/user/dashboard', function () {
            return view('user.dashboard');
        })->name('dashboard');

        // Switch active account in Hosting Mode (cPanel-style: one account at a time)
        Route::post('/switch-account', function (\Illuminate\Http\Request $request) {
            $accountId = (int) $request->input('account_id');
            $allowed = auth()->user()->accessibleAccountIds();
            if (in_array($accountId, $allowed)) {
                session(['current_account_id' => $accountId]);
            }
            return back();
        })->name('switch-account');

        // Return to admin (if impersonating)
        Route::post('/return-to-admin', function () {
            $adminId = session()->pull('admin_id');
            if ($adminId) {
                auth()->loginUsingId($adminId);
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('user.dashboard');
        })->name('return-to-admin');

        // WordPress
        Route::get('/wordpress', [WordPressController::class, 'index'])->name('wordpress.index');
        Route::get('/wordpress/install', [WordPressController::class, 'create'])->name('wordpress.create');
        Route::post('/wordpress/install', [WordPressController::class, 'store'])->name('wordpress.store');
        Route::post('/wordpress/update', [WordPressController::class, 'update'])->name('wordpress.update');

        // FTP
        Route::get('/ftp', [FtpController::class, 'index'])->name('ftp.index');
        Route::post('/ftp', [FtpController::class, 'store'])->name('ftp.store');
        Route::post('/ftp/delete', [FtpController::class, 'destroy'])->name('ftp.destroy');

        // Laravel
        Route::get('/laravel', [LaravelInstallerController::class, 'index'])->name('laravel.index');
        Route::get('/laravel/install', [LaravelInstallerController::class, 'create'])->name('laravel.create');
        Route::post('/laravel/install', [LaravelInstallerController::class, 'store'])->name('laravel.store');

        // CMS installers (Joomla, Drupal, Magento, PrestaShop)
        Route::get('/cms/{type}', [CmsInstallerController::class, 'index'])->name('cms.index');
        Route::get('/cms/{type}/install', [CmsInstallerController::class, 'create'])->name('cms.create');
        Route::post('/cms/{type}/install', [CmsInstallerController::class, 'store'])->name('cms.store');

        // Node.js / PM2
        Route::get('/nodejs', [NodeController::class, 'index'])->name('nodejs.index');
        Route::get('/nodejs/create', [NodeController::class, 'create'])->name('nodejs.create');
        Route::post('/nodejs', [NodeController::class, 'store'])->name('nodejs.store');
        Route::get('/nodejs/{nodeApp}', [NodeController::class, 'show'])->name('nodejs.show');
        Route::post('/nodejs/{nodeApp}/restart', [NodeController::class, 'restart'])->name('nodejs.restart');
        Route::post('/nodejs/{nodeApp}/stop', [NodeController::class, 'stop'])->name('nodejs.stop');
        Route::post('/nodejs/{nodeApp}/delete', [NodeController::class, 'destroy'])->name('nodejs.destroy');

        // PostgreSQL
        Route::get('/postgres', [PostgresController::class, 'index'])->name('postgres.index');
        Route::get('/postgres/create', [PostgresController::class, 'create'])->name('postgres.create');
        Route::post('/postgres', [PostgresController::class, 'store'])->name('postgres.store');
        Route::get('/postgres/{postgresDatabase}', [PostgresController::class, 'show'])->name('postgres.show');
        Route::post('/postgres/{postgresDatabase}/password', [PostgresController::class, 'changePassword'])->name('postgres.password');
        Route::post('/postgres/{postgresDatabase}/delete', [PostgresController::class, 'destroy'])->name('postgres.destroy');

        // Composer
        Route::get('/composer', [ComposerController::class, 'index'])->name('composer.index');
        Route::post('/composer/run', [ComposerController::class, 'run'])->name('composer.run');

        // Git
        Route::get('/git', [GitController::class, 'index'])->name('git.index');
        Route::post('/git/clone', [GitController::class, 'clone'])->name('git.clone');
        Route::post('/git/pull', [GitController::class, 'pull'])->name('git.pull');

        // Domains (create/store removed — domain is created with the account)
        Route::resource('domains', DomainController::class)->only(['index', 'destroy']);
        Route::post('/domains/{domain}/toggle-htaccess', [HtaccessController::class, 'toggle'])->name('domains.toggle-htaccess');

        // Directory password protection
        Route::get('/security/directory-protection',                  [\App\Http\Controllers\User\ProtectedDirectoryController::class, 'index'])->name('security.directories.index');
        Route::post('/security/directory-protection',                 [\App\Http\Controllers\User\ProtectedDirectoryController::class, 'store'])->name('security.directories.store');
        Route::delete('/security/directory-protection/{directory}',   [\App\Http\Controllers\User\ProtectedDirectoryController::class, 'destroy'])->name('security.directories.destroy');
        Route::post('/security/directory-protection/{directory}/users', [\App\Http\Controllers\User\ProtectedDirectoryController::class, 'addUser'])->name('security.directories.users.store');
        Route::delete('/security/directory-protection/users/{user}',  [\App\Http\Controllers\User\ProtectedDirectoryController::class, 'removeUser'])->name('security.directories.users.destroy');

        // Hotlink protection
        Route::get('/security/hotlink-protection',          [\App\Http\Controllers\User\HotlinkProtectionController::class, 'index'])->name('security.hotlink.index');
        Route::patch('/security/hotlink-protection/{domain}', [\App\Http\Controllers\User\HotlinkProtectionController::class, 'update'])->name('security.hotlink.update');

        // Email Forwarders
        Route::get('/forwarders', [ForwarderController::class, 'index'])->name('forwarders.index');
        Route::post('/forwarders', [ForwarderController::class, 'store'])->name('forwarders.store');
        Route::post('/forwarders/delete', [ForwarderController::class, 'destroy'])->name('forwarders.destroy');

        // Web Terminal
        Route::get('/terminal', [TerminalController::class, 'index'])->name('terminal.index');
        Route::post('/terminal/connect', [TerminalController::class, 'connect'])->name('terminal.connect');
        Route::post('/terminal/proxy', [TerminalController::class, 'proxy'])->name('terminal.proxy');

        // Custom Nginx Directives
        Route::get('/nginx-directives', [NginxDirectiveController::class, 'index'])->name('nginx-directives.index');
        Route::post('/nginx-directives', [NginxDirectiveController::class, 'store'])->name('nginx-directives.store');
        Route::delete('/nginx-directives/{domain}', [NginxDirectiveController::class, 'destroy'])->name('nginx-directives.destroy');

        // Autoresponders
        Route::get('/autoresponders', [AutoresponderController::class, 'index'])->name('autoresponders.index');
        Route::post('/autoresponders', [AutoresponderController::class, 'store'])->name('autoresponders.store');

        // Domain Aliases
        Route::get('/aliases', [AliasController::class, 'index'])->name('aliases.index');
        Route::post('/aliases', [AliasController::class, 'store'])->name('aliases.store');
        Route::delete('/aliases/{alias}', [AliasController::class, 'destroy'])->name('aliases.destroy');

        // URL Redirects
        Route::get('/redirects', [RedirectController::class, 'index'])->name('redirects.index');
        Route::post('/redirects', [RedirectController::class, 'store'])->name('redirects.store');
        Route::delete('/redirects/{redirect}', [RedirectController::class, 'destroy'])->name('redirects.destroy');

        // Subdomains
        Route::get('/subdomains', [SubdomainController::class, 'index'])->name('subdomains.index');
        Route::get('/subdomains/{domain}/create', [SubdomainController::class, 'create'])->name('subdomains.create');
        Route::post('/subdomains/{domain}', [SubdomainController::class, 'store'])->name('subdomains.store');
        Route::delete('/subdomains/{subdomain}', [SubdomainController::class, 'destroy'])->name('subdomains.destroy');

        // DNS Zone Editor
        Route::get('/dns/{domain}', [DnsController::class, 'index'])->name('dns.index');
        Route::post('/dns/{domain}/add-record', [DnsController::class, 'addRecord'])->name('dns.add-record');
        Route::post('/dns/{domain}/delete-record', [DnsController::class, 'deleteRecord'])->name('dns.delete-record');

        // Databases
        Route::resource('databases', DatabaseController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
        Route::post('/databases/{database}/password', [DatabaseController::class, 'changePassword'])->name('databases.password');
        Route::post('/databases/{database}/repair', [DatabaseController::class, 'repair'])->name('databases.repair');

        // SSL Certificates
        Route::get('/ssl', [SslController::class, 'index'])->name('ssl.index');
        Route::post('/ssl/issue', [SslController::class, 'issue'])->name('ssl.issue');
        Route::post('/ssl/upload', [SslController::class, 'upload'])->name('ssl.upload');
        Route::post('/ssl/{certificate}/renew', [SslController::class, 'renew'])->name('ssl.renew');
        Route::delete('/ssl/{certificate}', [SslController::class, 'destroy'])->name('ssl.destroy');

        // Email Accounts
        Route::get('/emails', [EmailController::class, 'index'])->name('emails.index');
        Route::post('/emails', [EmailController::class, 'store'])->name('emails.store');
        Route::post('/emails/{emailAccount}/password', [EmailController::class, 'changePassword'])->name('emails.password');
        Route::post('/emails/{emailAccount}/quota', [EmailController::class, 'updateQuota'])->name('emails.quota');
        Route::post('/emails/{emailAccount}/restrictions', [EmailController::class, 'updateRestrictions'])->name('emails.restrictions');
        Route::delete('/emails/{emailAccount}', [EmailController::class, 'destroy'])->name('emails.destroy');

        // SSH Access
        Route::get('/ssh', [SshController::class, 'index'])->name('ssh.index');
        Route::post('/ssh/generate-key', [SshController::class, 'generateKey'])->name('ssh.generate-key');
        Route::post('/ssh/import-key', [SshController::class, 'importKey'])->name('ssh.import-key');
        Route::post('/ssh/delete-key', [SshController::class, 'deleteKey'])->name('ssh.delete-key');
        Route::post('/ssh/toggle-shell', [SshController::class, 'toggleShell'])->name('ssh.toggle-shell');

        // PHP Version
        Route::get('/php', [UserPhpController::class, 'index'])->name('php.index');
        Route::post('/php/switch', [UserPhpController::class, 'switchVersion'])->name('php.switch');

        // Cron Jobs
        Route::get('/cron-jobs', [CronJobController::class, 'index'])->name('cronjobs.index');
        Route::get('/cron-jobs/create', [CronJobController::class, 'create'])->name('cronjobs.create');
        Route::post('/cron-jobs', [CronJobController::class, 'store'])->name('cronjobs.store');
        Route::post('/cron-jobs/{cronJob}/toggle', [CronJobController::class, 'toggle'])->name('cronjobs.toggle');
        Route::delete('/cron-jobs/{cronJob}', [CronJobController::class, 'destroy'])->name('cronjobs.destroy');

        // File Manager
        Route::get('/file-manager', [FileManagerController::class, 'index'])->name('filemanager.index');
        Route::get('/file-manager/edit', [FileManagerController::class, 'edit'])->name('filemanager.edit');
        Route::post('/file-manager/write', [FileManagerController::class, 'write'])->name('filemanager.write');
        Route::post('/file-manager/upload', [FileManagerController::class, 'upload'])->name('filemanager.upload');
        Route::post('/file-manager/delete', [FileManagerController::class, 'delete'])->name('filemanager.delete');
        Route::post('/file-manager/rename', [FileManagerController::class, 'rename'])->name('filemanager.rename');
        Route::post('/file-manager/mkdir', [FileManagerController::class, 'mkdir'])->name('filemanager.mkdir');
        Route::post('/file-manager/chmod', [FileManagerController::class, 'chmod'])->name('filemanager.chmod');
        Route::post('/file-manager/archive', [FileManagerController::class, 'archive'])->name('filemanager.archive');
    });
});
