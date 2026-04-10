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

        // Get agent version from the first server
        $agentVersion = null;
        $server = Server::first();
        if ($server) {
            try {
                $agentResponse = AgentService::for($server)->get('/version');
                if ($agentResponse && $agentResponse->successful()) {
                    $agentVersion = $agentResponse->json('agent_version');
                }
            } catch (\Exception $e) {}
        }

        return view('admin.updates', compact('currentVersion', 'latestVersion', 'changelog', 'updateAvailable', 'agentVersion'));
    }

    public function run(Request $request)
    {
        $server = Server::first();
        if (!$server) {
            return back()->with('error', __('servers.no_server_configured'));
        }

        $response = AgentService::for($server)->post('/update/run', []);

        if ($response && $response->successful()) {
            $output = $response->json('output', '');
            return redirect()->route('admin.updates.index')->with('success', __('servers.update_completed'));
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
        $output = $response ? $response->json('output', '') : '';
        return back()->with('error', __('servers.update_failed', ['error' => $error]))->with('output', $output);
    }
}
