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

        return view('admin.updates', compact('currentVersion', 'latestVersion', 'changelog', 'updateAvailable'));
    }

    public function run(Request $request)
    {
        $server = Server::first();
        if (!$server) {
            return back()->with('error', 'No server configured.');
        }

        $response = AgentService::for($server)->post('/update/run', []);

        if ($response && $response->successful()) {
            $output = $response->json('output', '');
            return redirect()->route('admin.updates.index')->with('success', 'Update completed successfully.');
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
        $output = $response ? $response->json('output', '') : '';
        return back()->with('error', 'Update failed: ' . $error)->with('output', $output);
    }
}
