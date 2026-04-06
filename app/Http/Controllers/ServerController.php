<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Services\ActivityLogger;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ServerController extends Controller
{
    public function index()
    {
        $servers = Server::with('domains', 'accounts', 'databases')
            ->latest()
            ->get();

        return view('servers.index', compact('servers'));
    }

    public function create()
    {
        return view('servers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'hostname' => 'nullable|string|max:255',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['agent_token'] = Str::random(64);
        $validated['agent_url'] = 'http://' . $validated['ip_address'] . ':' . config('opterius.agent_port');

        $server = Server::create($validated);

        ActivityLogger::log('server.added', 'server', $server->id, $server->name,
            "Added server {$server->name} ({$server->ip_address})", ['ip_address' => $server->ip_address]);

        return redirect()->route('admin.servers.show', $server)->with('success', 'Server added successfully.');
    }

    public function show(Server $server)
    {
        $server->load('domains', 'accounts', 'databases', 'cronJobs');

        $agentHealth = AgentService::for($server)->health();

        // Fetch server stats
        $serverStats = null;
        $response = AgentService::for($server)->post('/stats/server', []);
        if ($response && $response->successful()) {
            $serverStats = $response->json('stats');
        }

        return view('servers.show', compact('server', 'agentHealth', 'serverStats'));
    }

    public function destroy(Request $request, Server $server)
    {
        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => 'The password is incorrect.']);
        }

        ActivityLogger::log('server.removed', 'server', $server->id, $server->name,
            "Removed server {$server->name} ({$server->ip_address})", ['ip_address' => $server->ip_address]);

        $server->delete();

        return redirect()->route('admin.servers.index')->with('success', 'Server removed.');
    }
}
