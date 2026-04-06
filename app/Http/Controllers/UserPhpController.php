<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\AgentService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class UserPhpController extends Controller
{
    public function index()
    {
        $domains = Domain::with('account.server', 'account.package')
            ->whereIn('account_id', auth()->user()->accessibleAccountIds())
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->get();

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

        return view('php.user-index', compact('domains', 'versions'));
    }

    public function switchVersion(Request $request)
    {
        $validated = $request->validate([
            'domain_id'   => 'required|exists:domains,id',
            'new_version' => 'required|string|regex:/^\d+\.\d+$/',
        ]);

        $domain = Domain::with('account.server', 'account.package')
            ->whereIn('account_id', auth()->user()->accessibleAccountIds())
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

        $response = AgentService::for($domain->account->server)->post('/php/switch-version', [
            'domain'      => $domain->domain,
            'username'    => $domain->account->username,
            'old_version' => $oldVersion,
            'new_version' => $validated['new_version'],
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
