<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Services\AgentService;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $servers = Server::all();
        $selectedServer = null;
        $services = [];

        if ($request->has('server_id')) {
            $selectedServer = Server::findOrFail($request->server_id);

            $response = AgentService::for($selectedServer)->post('/services/list', []);
            if ($response && $response->successful()) {
                $services = $response->json('services', []);
            }
        }

        return view('services.index', compact('servers', 'selectedServer', 'services'));
    }

    public function action(Request $request)
    {
        $validated = $request->validate([
            'server_id' => 'required|exists:servers,id',
            'service'   => 'required|string|max:50',
            'action'    => 'required|in:start,stop,restart,reload',
        ]);

        $server = Server::findOrFail($validated['server_id']);

        $response = AgentService::for($server)->post('/services/action', [
            'service' => $validated['service'],
            'action'  => $validated['action'],
        ]);

        if ($response && $response->successful()) {
            return redirect()
                ->route('admin.services.index', ['server_id' => $server->id])
                ->with('success', ucfirst($validated['action']) . ' ' . $validated['service'] . ' successful.');
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
        return back()->with('error', 'Failed to ' . $validated['action'] . ' ' . $validated['service'] . ': ' . $error);
    }
}
