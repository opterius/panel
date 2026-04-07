<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MonitorController extends Controller
{
    public function index(Request $request)
    {
        $servers = Server::all();
        $selectedServer = null;

        if ($request->has('server_id')) {
            $selectedServer = Server::findOrFail($request->server_id);
        } elseif ($servers->count() === 1) {
            $selectedServer = $servers->first();
        }

        return view('monitor.index', compact('servers', 'selectedServer'));
    }

    /**
     * API proxy — frontend polls this to get real-time metrics from agent.
     */
    public function realtime(Request $request)
    {
        $server = Server::findOrFail($request->server_id);

        $response = Http::withoutVerifying()
            ->timeout(5)
            ->get($server->agent_url . '/metrics/realtime');

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Agent unreachable'], 502);
    }

    /**
     * API proxy for top processes.
     */
    public function topProcesses(Request $request)
    {
        $server = Server::findOrFail($request->server_id);

        $response = AgentService::for($server)->post('/metrics/top-processes', []);

        if ($response && $response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Agent unreachable'], 502);
    }
}
