<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UpdateController extends Controller
{
    public function index()
    {
        $currentVersion = config('opterius.version', '1.0.0');

        // Check for latest version. Authoritative source is
        // get.opterius.com/agent/version.txt — it's bumped by every release.sh
        // run, so it always matches what's actually deployed. The old license
        // server API at /api/version/latest is kept as a fallback only.
        $latestVersion = null;
        try {
            $response = Http::timeout(5)->get('https://get.opterius.com/agent/version.txt');
            if ($response->successful()) {
                $latestVersion = trim($response->body());
            }
        } catch (\Exception $e) {
            // get.opterius.com unreachable — try the license server.
        }
        if (! $latestVersion) {
            try {
                $response = Http::timeout(5)->get(config('opterius.license_server_url') . '/api/version/latest');
                if ($response->successful()) {
                    $latestVersion = $response->json('version');
                }
            } catch (\Exception $e) {}
        }

        // Read release notes for the current version from CHANGELOG.md in the panel root.
        $changelog = $this->readChangelogFor($currentVersion);

        $updateAvailable = $latestVersion && version_compare($latestVersion, $currentVersion, '>');

        // Get agent version + update log from each server
        $servers = Server::all();
        $agentVersion = null;
        $mailVersion  = null;
        $updateLog    = null;

        foreach ($servers as $server) {
            try {
                $versionResp = AgentService::for($server)->get('/version');
                if ($versionResp && $versionResp->successful()) {
                    $agentVersion  = $versionResp->json('agent_version');
                    $mailInstalled = (bool) $versionResp->json('mail_installed', false);
                    $mailVersion   = $versionResp->json('mail_version') ?: null;
                }

                $logResp = AgentService::for($server)->get('/update/log');
                if ($logResp && $logResp->successful()) {
                    $updateLog = $logResp->json('log', '');
                }
            } catch (\Exception $e) {}

            // Only need the first server for now
            break;
        }

        $mailInstalled = $mailInstalled ?? false;
        return view('admin.updates', compact(
            'currentVersion', 'latestVersion', 'changelog',
            'updateAvailable', 'agentVersion', 'mailInstalled', 'mailVersion', 'updateLog'
        ));
    }

    /**
     * Run the panel update script via the agent.
     */
    public function run(Request $request)
    {
        $server = Server::first();
        if (!$server) {
            return back()->with('error', __('servers.no_server_configured'));
        }

        $response = AgentService::for($server)->postLong('/update/run', [], 300);

        if ($response && $response->successful()) {
            return redirect()->route('admin.updates.index')->with('success', __('servers.update_completed'));
        }

        $error  = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
        $output = $response ? $response->json('output', '') : '';
        return back()->with('error', __('servers.update_failed', ['error' => $error]))->with('output', $output);
    }

    /**
     * GET /admin/updates/log — returns the update log as JSON for the live log viewer.
     */
    public function log()
    {
        $server = Server::first();
        $log = '';

        if ($server) {
            try {
                $response = AgentService::for($server)->get('/update/log');
                if ($response && $response->successful()) {
                    $log = $response->json('log', '');
                }
            } catch (\Exception $e) {}
        }

        return response()->json(['log' => $log]);
    }

    /**
     * Extract the section for a given version from CHANGELOG.md.
     * Matches headings like "## [2.2.3] - 2026-04-21" and returns all content
     * until the next "## " heading. Returns null if the file or section is missing.
     */
    private function readChangelogFor(string $version): ?string
    {
        $path = base_path('CHANGELOG.md');
        if (!is_file($path)) {
            return null;
        }

        $content = @file_get_contents($path);
        if ($content === false) {
            return null;
        }

        $escaped = preg_quote($version, '/');
        if (!preg_match('/^##\s*\[' . $escaped . '\][^\n]*\n(.*?)(?=^##\s|\z)/ms', $content, $m)) {
            return null;
        }

        return trim($m[1]);
    }

    /**
     * Force an immediate webmail (Opterius Mail) git pull + migrate via the agent.
     */
    public function forceMailUpdate(Request $request)
    {
        $server = Server::first();
        if (!$server) {
            return back()->with('error', __('servers.no_server_configured'));
        }

        $response = AgentService::for($server)->post('/update/mail', []);

        if ($response && $response->successful()) {
            $status = $response->json('status');
            if ($status === 'skipped') {
                return redirect()->route('admin.updates.index')
                    ->with('success', 'Webmail is not installed on the server — nothing to update.');
            }
            return redirect()->route('admin.updates.index')
                ->with('success', 'Webmail updated successfully.');
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
        return back()->with('error', "Webmail update failed: {$error}");
    }

    /**
     * Tell the agent to check for a new binary immediately (force update).
     */
    public function forceAgentUpdate(Request $request)
    {
        $server = Server::first();
        if (!$server) {
            return back()->with('error', __('servers.no_server_configured'));
        }

        $response = AgentService::for($server)->post('/update/agent', []);

        if ($response && $response->successful()) {
            return redirect()->route('admin.updates.index')
                ->with('success', 'Agent update check triggered. The log below will update in a few seconds.');
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
        return back()->with('error', "Agent update failed: {$error}");
    }
}
