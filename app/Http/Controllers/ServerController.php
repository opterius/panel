<?php

namespace App\Http\Controllers;

use App\Models\Server;
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

        return redirect()->route('servers.show', $server)->with('success', 'Server added successfully.');
    }

    public function show(Server $server)
    {
        $server->load('domains', 'accounts', 'databases', 'cronJobs');

        $agentHealth = AgentService::for($server)->health();

        return view('servers.show', compact('server', 'agentHealth'));
    }

    public function destroy(Request $request, Server $server)
    {
        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => 'The password is incorrect.']);
        }

        $server->delete();

        return redirect()->route('servers.index')->with('success', 'Server removed.');
    }
}
