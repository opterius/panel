<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\Setting;
use App\Services\ActivityLogger;
use App\Services\AgentService;
use Illuminate\Http\Request;

class SystemSettingsController extends Controller
{
    /**
     * The structure of the System Settings page. Each entry maps a category
     * slug → display label key + icon. Adding a new category is just adding
     * a row here and creating the matching `system-settings/{slug}.blade.php`
     * partial. Categories with no settings yet still appear in the sidebar
     * as "Coming soon" so the page already feels complete.
     */
    private array $categories = [
        'display'       => ['icon' => 'm9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z', 'ready' => true],
        'domains'       => ['icon' => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064', 'ready' => true],
        'mail'          => ['icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'ready' => true],
        'security'      => ['icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'ready' => true],
        'ssl'           => ['icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', 'ready' => true],
        'php'           => ['icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4', 'ready' => true],
        'notifications' => ['icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9', 'ready' => true],
        'integrations'  => ['icon' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1', 'ready' => true],
        'system'        => ['icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', 'ready' => true],
    ];

    /**
     * Show the System Settings page for the requested category. Defaults to
     * 'domains' (the only populated one right now).
     */
    public function index(string $category = 'domains')
    {
        if (!array_key_exists($category, $this->categories)) {
            abort(404);
        }

        // Pull every setting in the category at once. Subviews can read what
        // they need from this map.
        $settings = Setting::getGroup('system_' . $category);

        // Pre-compute the data each category-specific subview needs. Keep
        // this controller-side rather than in Blade so subviews stay clean.
        $extra = [];
        if ($category === 'domains') {
            $extra['php_versions'] = config('opanel.php_versions', ['8.1', '8.2', '8.3', '8.4']);
            $extra['default_php_version'] = $settings['default_php_version']
                ?? config('opanel.default_php_version', '8.3');
        }

        // Defaults for every category, so a field always renders with a sane
        // value on a fresh install instead of an empty box.
        $settings += match ($category) {
            'display' => [
                'panel_name'     => config('app.name', 'OPanel'),
                'primary_color'  => '#4f46e5',
                'logo_url'       => '',
                'default_locale' => config('app.locale', 'en'),
                'items_per_page' => 25,
            ],
            'mail' => [
                'from_address'           => config('mail.from.address', ''),
                'from_name'              => config('mail.from.name', config('app.name')),
                'smtp_host'              => '',
                'smtp_port'              => 587,
                'smtp_encryption'        => 'tls',
                'smtp_username'          => '',
                'smtp_password'          => '',
                'max_hourly_per_account' => 200,
            ],
            'security' => [
                'password_min_length' => 8,
                'require_2fa_admins'  => '0',
                'session_lifetime'    => config('session.lifetime', 120),
                'max_login_attempts'  => 5,
                'lockout_minutes'     => 15,
                'admin_ip_allowlist'  => '',
            ],
            'ssl' => [
                'auto_issue'        => '1',
                'force_https'       => '1',
                'le_contact_email'  => '',
                'renew_days_before' => 30,
            ],
            'php' => [
                'memory_limit'        => '256M',
                'max_execution_time'  => 30,
                'upload_max_filesize' => '64M',
                'post_max_size'       => '64M',
                'disable_functions'   => 'exec,passthru,shell_exec,system,proc_open,popen',
            ],
            'notifications' => [
                'admin_email'              => auth()->user()?->email ?? '',
                'notify_account_created'   => '1',
                'notify_account_suspended' => '1',
                'disk_threshold'           => 90,
                'load_threshold'           => 8,
            ],
            'system' => [
                'ns1'                    => config('opanel.ns1'),
                'ns2'                    => config('opanel.ns2'),
                'panel_hostname'         => request()->getHost(),
                'timezone'               => config('app.timezone', 'UTC'),
                'backup_retention_days'  => 30,
                'metrics_retention_days' => 30,
            ],
            default => [],
        };

        if ($category === 'system') {
            $extra['timezones'] = \DateTimeZone::listIdentifiers();
        }
        if ($category === 'display') {
            $extra['locales'] = $this->availableLocales();
        }

        return view('system-settings.index', [
            'categories' => $this->categories,
            'category'   => $category,
            'settings'   => $settings,
            'extra'      => $extra,
        ]);
    }

    /**
     * Save the settings for a category. Each category has its own validation
     * + key list to keep the form schema scoped — Domains can't accidentally
     * overwrite a Security setting, etc.
     */
    public function update(Request $request, string $category)
    {
        if (!array_key_exists($category, $this->categories)) {
            abort(404);
        }

        switch ($category) {
            case 'domains':
                $validated = $request->validate([
                    'default_php_version' => 'required|in:' . implode(',', config('opanel.php_versions', ['8.1', '8.2', '8.3', '8.4'])),
                ]);
                Setting::set('default_php_version', $validated['default_php_version'], 'system_domains');
                break;

            case 'integrations':
                $validated = $request->validate([
                    'bunnycdn_api_key'    => 'nullable|string|max:200',
                    'bunnycdn_prefix'     => 'nullable|string|max:32|regex:/^[a-z0-9-]*$/',
                    'maxmind_account_id'  => 'nullable|string|max:32',
                    'maxmind_license_key' => 'nullable|string|max:80',
                ]);
                // Empty string clears the key — useful for revoking the integration.
                Setting::set('integrations_bunnycdn_api_key',    $validated['bunnycdn_api_key']    ?? '', 'system_integrations');
                Setting::set('integrations_bunnycdn_prefix',     $validated['bunnycdn_prefix']     ?? 'opanel', 'system_integrations');
                Setting::set('integrations_maxmind_account_id',  $validated['maxmind_account_id']  ?? '', 'system_integrations');
                Setting::set('integrations_maxmind_license_key', $validated['maxmind_license_key'] ?? '', 'system_integrations');
                break;

            case 'display':
                $validated = $request->validate([
                    'panel_name'      => 'required|string|max:60',
                    'primary_color'   => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
                    'logo_url'        => 'nullable|url|max:255',
                    'default_locale'  => 'required|string|max:5',
                    'items_per_page'  => 'required|integer|min:10|max:200',
                ]);
                $this->put('system_display', $validated, [
                    'panel_name', 'primary_color', 'logo_url', 'default_locale', 'items_per_page',
                ]);
                break;

            case 'mail':
                $validated = $request->validate([
                    'from_address'      => 'required|email|max:255',
                    'from_name'         => 'required|string|max:60',
                    'smtp_host'         => 'nullable|string|max:255',
                    'smtp_port'         => 'nullable|integer|min:1|max:65535',
                    'smtp_encryption'   => 'nullable|in:none,tls,ssl',
                    'smtp_username'     => 'nullable|string|max:255',
                    'smtp_password'     => 'nullable|string|max:255',
                    'max_hourly_per_account' => 'required|integer|min:0|max:100000',
                ]);
                // An empty password submission keeps the stored one, so the
                // admin can edit the host without re-typing the secret.
                if (($validated['smtp_password'] ?? '') === '') {
                    unset($validated['smtp_password']);
                }
                $this->put('system_mail', $validated, [
                    'from_address', 'from_name', 'smtp_host', 'smtp_port',
                    'smtp_encryption', 'smtp_username', 'smtp_password',
                    'max_hourly_per_account',
                ]);
                break;

            case 'security':
                $validated = $request->validate([
                    'password_min_length' => 'required|integer|min:8|max:128',
                    'require_2fa_admins'  => 'nullable|boolean',
                    'session_lifetime'    => 'required|integer|min:5|max:43200',
                    'max_login_attempts'  => 'required|integer|min:1|max:100',
                    'lockout_minutes'     => 'required|integer|min:1|max:1440',
                    'admin_ip_allowlist'  => 'nullable|string|max:2000',
                ]);
                $validated['require_2fa_admins'] = $request->boolean('require_2fa_admins') ? '1' : '0';
                $this->put('system_security', $validated, [
                    'password_min_length', 'require_2fa_admins', 'session_lifetime',
                    'max_login_attempts', 'lockout_minutes', 'admin_ip_allowlist',
                ]);
                break;

            case 'ssl':
                $validated = $request->validate([
                    'auto_issue'        => 'nullable|boolean',
                    'force_https'       => 'nullable|boolean',
                    'le_contact_email'  => 'nullable|email|max:255',
                    'renew_days_before' => 'required|integer|min:1|max:89',
                ]);
                $validated['auto_issue']  = $request->boolean('auto_issue') ? '1' : '0';
                $validated['force_https'] = $request->boolean('force_https') ? '1' : '0';
                $this->put('system_ssl', $validated, [
                    'auto_issue', 'force_https', 'le_contact_email', 'renew_days_before',
                ]);
                break;

            case 'php':
                $validated = $request->validate([
                    'memory_limit'        => 'required|string|max:16|regex:/^\d+[MG]$/',
                    'max_execution_time'  => 'required|integer|min:5|max:3600',
                    'upload_max_filesize' => 'required|string|max:16|regex:/^\d+[MG]$/',
                    'post_max_size'       => 'required|string|max:16|regex:/^\d+[MG]$/',
                    'disable_functions'   => 'nullable|string|max:2000',
                ]);
                $this->put('system_php', $validated, [
                    'memory_limit', 'max_execution_time', 'upload_max_filesize',
                    'post_max_size', 'disable_functions',
                ]);
                break;

            case 'notifications':
                $validated = $request->validate([
                    'admin_email'              => 'nullable|email|max:255',
                    'notify_account_created'   => 'nullable|boolean',
                    'notify_account_suspended' => 'nullable|boolean',
                    'disk_threshold'           => 'required|integer|min:50|max:99',
                    'load_threshold'           => 'required|numeric|min:0.5|max:100',
                ]);
                $validated['notify_account_created']   = $request->boolean('notify_account_created') ? '1' : '0';
                $validated['notify_account_suspended'] = $request->boolean('notify_account_suspended') ? '1' : '0';
                $this->put('system_notifications', $validated, [
                    'admin_email', 'notify_account_created', 'notify_account_suspended',
                    'disk_threshold', 'load_threshold',
                ]);
                break;

            case 'system':
                $validated = $request->validate([
                    // Hostnames, not URLs — these become NS records in every zone.
                    'ns1'                     => ['required', 'string', 'max:253', 'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/i'],
                    'ns2'                     => ['required', 'string', 'max:253', 'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/i'],
                    'panel_hostname'          => 'nullable|string|max:253',
                    'timezone'                => 'required|timezone',
                    'backup_retention_days'   => 'required|integer|min:1|max:365',
                    'metrics_retention_days'  => 'required|integer|min:1|max:365',
                ]);
                $this->put('system_system', $validated, [
                    'ns1', 'ns2', 'panel_hostname', 'timezone',
                    'backup_retention_days', 'metrics_retention_days',
                ]);
                break;

            default:
                return back();
        }

        // Drop the per-request cache so anything reading a setting later in
        // this same request sees what we just saved.
        \App\Support\SystemSetting::flush();

        ActivityLogger::log('settings.system_updated', null, null, $category,
            "Updated system settings: {$category}", ['category' => $category]);

        return redirect()->route('admin.system-settings.index', ['category' => $category])
            ->with('success', __('system-settings.saved'));
    }

    /**
     * Locale codes that actually have a translation directory, so the picker
     * never offers a language the panel cannot render.
     */
    private function availableLocales(): array
    {
        $dirs = glob(lang_path('*'), GLOB_ONLYDIR) ?: [];
        $locales = array_map('basename', $dirs);

        return $locales ?: ['en'];
    }

    /**
     * Persist a validated payload into a settings group.
     *
     * Only keys in $allowed are written, so a crafted extra form field can
     * never create an arbitrary setting row.
     */
    private function put(string $group, array $validated, array $allowed): void
    {
        foreach ($allowed as $key) {
            if (array_key_exists($key, $validated)) {
                Setting::set($key, (string) $validated[$key], $group);
            }
        }
    }

    /**
     * Read a system setting, falling back to config.
     *
     * Used by the rest of the app (DNS zone creation, SSL auto-issue) so an
     * admin's choice in System Settings actually takes effect rather than
     * only being stored.
     */
    public static function value(string $group, string $key, $default = null)
    {
        $settings = Setting::getGroup('system_' . $group);
        $value = $settings[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    /**
     * POST /admin/system-settings/maxmind-download
     * Trigger every server's agent to download the GeoLite2 file using the
     * stored MaxMind license key. Used by the "Download" button in the
     * Integrations panel after the admin saves their credentials.
     */
    public function downloadMaxMind()
    {
        $accountId  = (string) Setting::get('integrations_maxmind_account_id', '');
        $licenseKey = (string) Setting::get('integrations_maxmind_license_key', '');

        if ($accountId === '' || $licenseKey === '') {
            return back()->with('error', 'Save your MaxMind Account ID and License Key first, then click Download.');
        }

        $servers   = Server::all();
        $succeeded = 0;
        $failed    = 0;
        $errors    = [];

        foreach ($servers as $server) {
            $response = AgentService::for($server)->postLong('/analytics/geoip-update', [
                'account_id'  => $accountId,
                'license_key' => $licenseKey,
            ]);

            if ($response && $response->successful()) {
                $succeeded++;
            } else {
                $failed++;
                $errors[] = $server->name . ': ' . ($response?->json('error') ?? 'unreachable');
            }
        }

        ActivityLogger::log('settings.maxmind_downloaded', null, null, 'maxmind',
            "Downloaded GeoLite2 to {$succeeded} server(s)", ['succeeded' => $succeeded, 'failed' => $failed]);

        if ($failed === 0) {
            return back()->with('success', "GeoLite2 database downloaded to {$succeeded} server(s).");
        }
        return back()->with('error',
            "Downloaded to {$succeeded} server(s), failed on {$failed}: " . implode('; ', $errors));
    }
}
