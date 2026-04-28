<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\AgentService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class UserPhpController extends Controller
{
    /**
     * Common disable_functions presets — toggleable individually in the UI.
     */
    private const TOGGLEABLE_FUNCS = ['proc_open', 'popen', 'exec', 'shell_exec', 'system', 'passthru'];

    public function index()
    {
        $domains = Domain::with('account.server', 'account.package')
            ->whereIn('account_id', auth()->user()->currentAccountIds())
            ->where('status', 'active')
            ->orderByRaw('parent_id IS NOT NULL, domain')
            ->get();

        // Pull current php.ini overrides for each domain
        $configs = [];
        foreach ($domains as $domain) {
            $response = AgentService::for($domain->account->server)->get(
                '/php/config?domain=' . urlencode($domain->domain) . '&version=' . urlencode($domain->php_version)
            );
            $configs[$domain->id] = ($response && $response->successful())
                ? ($response->json('values', []) ?? [])
                : [];
        }

        // Get installed PHP versions from the first domain's server
        $versions = [];
        if ($domains->isNotEmpty()) {
            $server = $domains->first()->account->server;
            $response = AgentService::for($server)->post('/php/list-versions', []);
            if ($response && $response->successful()) {
                $allVersions = $response->json('versions', []);
                // Extract only installed version strings
                $versions = collect($allVersions)
                    ->filter(fn($v) => $v['installed'] ?? false)
                    ->pluck('version')
                    ->values()
                    ->toArray();
            }
        }

        // Fallback to config if agent returned nothing
        if (empty($versions)) {
            $versions = config('opterius.php_versions', []);
        }

        $toggleableFuncs = self::TOGGLEABLE_FUNCS;
        return view('php.user-index', compact('domains', 'versions', 'configs', 'toggleableFuncs'));
    }

    public function saveConfig(Request $request)
    {
        $validated = $request->validate([
            'domain_id'           => 'required|exists:domains,id',
            'memory_limit'        => 'nullable|string|max:10',
            'max_execution_time'  => 'nullable|integer|min:0|max:3600',
            'upload_max_filesize' => 'nullable|string|max:10',
            'post_max_size'       => 'nullable|string|max:10',
            'max_input_vars'      => 'nullable|integer|min:100|max:100000',
            'display_errors'      => 'nullable|in:On,Off',
            'allow_url_fopen'     => 'nullable|in:On,Off',
            'enabled_funcs'       => 'nullable|array',
            'enabled_funcs.*'     => 'string|in:' . implode(',', self::TOGGLEABLE_FUNCS),
        ]);

        $domain = Domain::with('account.server')
            ->whereIn('account_id', auth()->user()->currentAccountIds())
            ->findOrFail($validated['domain_id']);

        if (!$domain->account->userCan(auth()->user(), 'settings')) {
            return back()->with('error', __('php.no_permission'));
        }

        $values = [];
        foreach (['memory_limit', 'upload_max_filesize', 'post_max_size', 'max_execution_time', 'max_input_vars', 'display_errors', 'allow_url_fopen'] as $key) {
            if (!empty($validated[$key])) {
                $values[$key] = (string) $validated[$key];
            }
        }

        // Build disable_functions from "enabled" checkbox state — anything
        // NOT checked stays disabled. The form sends only the functions the
        // user wants to ALLOW; everything else from the toggleable list is
        // appended to disable_functions.
        $enabled = $validated['enabled_funcs'] ?? [];
        $disabled = array_values(array_diff(self::TOGGLEABLE_FUNCS, $enabled));
        $values['disable_functions'] = implode(',', $disabled);

        $response = AgentService::for($domain->account->server)->post('/php/config', [
            'domain'  => $domain->domain,
            'version' => $domain->php_version,
            'values'  => $values,
        ]);

        if ($response && $response->successful()) {
            ActivityLogger::log('php.config_updated', 'domain', $domain->id, $domain->domain,
                "Updated PHP settings for {$domain->domain}", $values);

            return redirect()->route('user.php.index')
                ->with('success', __('php.php_config_updated', ['domain' => $domain->domain]));
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return back()->with('error', __('php.failed_to_update_php_config', ['error' => $error]));
    }

    public function switchVersion(Request $request)
    {
        $validated = $request->validate([
            'domain_id'   => 'required|exists:domains,id',
            'new_version' => 'required|string|regex:/^\d+\.\d+$/',
        ]);

        $domain = Domain::with('account.server', 'account.package')
            ->whereIn('account_id', auth()->user()->currentAccountIds())
            ->findOrFail($validated['domain_id']);

        if (!$domain->account->userCan(auth()->user(), 'settings')) {
            return back()->with('error', __('php.no_permission'));
        }

        // Check if package allows PHP switching
        if ($domain->account->package && !$domain->account->package->php_switch_enabled) {
            return back()->with('error', __('php.php_switch_not_enabled'));
        }

        // Check version is allowed by package
        if ($domain->account->package) {
            $allowedVersions = $domain->account->package->php_versions ?? [];
            if (!empty($allowedVersions) && !in_array($validated['new_version'], $allowedVersions)) {
                return back()->with('error', __('php.php_version_not_in_package', ['version' => $validated['new_version']]));
            }
        }

        $oldVersion = $domain->php_version;

        $workingDir = dirname($domain->document_root);

        $response = AgentService::for($domain->account->server)->post('/php/switch-version', [
            'domain'           => $domain->domain,
            'username'         => $domain->account->username,
            'old_version'      => $oldVersion,
            'new_version'      => $validated['new_version'],
            'htaccess_enabled' => (bool) $domain->htaccess_enabled,
            'document_root'    => $domain->document_root,
            'logs_dir'         => $workingDir . '/logs',
        ]);

        if ($response && $response->successful()) {
            $domain->update(['php_version' => $validated['new_version']]);

            ActivityLogger::log('php.switched', 'domain', $domain->id, $domain->domain,
                "Switched PHP {$oldVersion} → {$validated['new_version']} for {$domain->domain}");

            return redirect()->route('user.php.index')
                ->with('success', __('php.php_switched', ['domain' => $domain->domain, 'old_version' => $oldVersion, 'new_version' => $validated['new_version']]));
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return back()->with('error', __('php.failed_to_switch_php', ['error' => $error]));
    }
}
