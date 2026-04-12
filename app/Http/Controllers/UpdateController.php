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

        // Check for latest version from license server
        $latestVersion = null;
        $changelog = null;
        try {
            $response = Http::timeout(5)->get(config('opterius.license_server_url') . '/api/version/latest');
            if ($response->successful()) {
                $data = $response->json();
                $latestVersion = $data['version'] ?? null;
                $changelog = $data['changelog'] ?? null;
            }
        } catch (\Exception $e) {
            // License server unreachable
        }

        $updateAvailable = $latestVersion && version_compare($latestVersion, $currentVersion, '>');

        // Get agent version + update log from each server
        $servers = Server::all();
        $agentVersion = null;
        $updateLog = null;

        foreach ($servers as $server) {
            try {
                $versionResp = AgentService::for($server)->get('/version');
                if ($versionResp && $versionResp->successful()) {
                    $agentVersion = $versionResp->json('agent_version');
                }

                $logResp = AgentService::for($server)->get('/update/log');
                if ($logResp && $logResp->successful()) {
                    $updateLog = $logResp->json('log', '');
                }
            } catch (\Exception $e) {}

            // Only need the first server for now
            break;
        }

        return view('admin.updates', compact(
            'currentVersion', 'latestVersion', 'changelog',
            'updateAvailable', 'agentVersion', 'updateLog'
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

        $response = AgentService::for($server)->post('/update/run', []);

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
