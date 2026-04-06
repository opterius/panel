<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Services\AgentService;
use Illuminate\Http\Request;

class SecurityController extends Controller
{
    public function index(Request $request)
    {
        $servers = Server::all();
        $selectedServer = null;
        $firewallRules = [];
        $firewallStatus = 'unknown';
        $fail2ban = null;

        if ($request->has('server_id')) {
            $selectedServer = Server::findOrFail($request->server_id);

            // Firewall rules
            $response = AgentService::for($selectedServer)->post('/security/firewall-list', []);
            if ($response && $response->successful()) {
                $firewallRules = $response->json('rules') ?? [];
                $firewallStatus = $response->json('firewall_status', 'unknown');
            }

            // Fail2ban
            $response = AgentService::for($selectedServer)->post('/security/fail2ban-list', []);
            if ($response && $response->successful()) {
                $fail2ban = $response->json();
            }
        }

        return view('security.index', compact('servers', 'selectedServer', 'firewallRules', 'firewallStatus', 'fail2ban'));
    }

    public function scan(Request $request)
    {
        $validated = $request->validate([
            'server_id' => 'required|exists:servers,id',
            'username'  => 'nullable|string|max:32',
            'use_clamav' => 'boolean',
        ]);

        $server = Server::findOrFail($validated['server_id']);

        $response = AgentService::for($server)->post('/security/scan', [
            'username'  => $validated['username'] ?? '',
            'use_clamav' => $request->boolean('use_clamav'),
        ]);

        if ($response && $response->successful()) {
            $result = $response->json();
            return view('security.scan-results', compact('server', 'result'));
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
        return back()->with('error', 'Scan failed: ' . $error);
    }

    public function firewallAdd(Request $request)
    {
        $validated = $request->validate([
            'server_id' => 'required|exists:servers,id',
            'action'    => 'required|in:allow,deny',
            'port'      => 'required|string|max:20',
            'from'      => 'nullable|string|max:45',
        ]);

        $server = Server::findOrFail($validated['server_id']);

        $response = AgentService::for($server)->post('/security/firewall-add', [
            'action' => $validated['action'],
            'port'   => $validated['port'],
            'from'   => $validated['from'] ?? 'any',
        ]);

        if ($response && $response->successful()) {
            return redirect()->route('admin.security.index', ['server_id' => $server->id])->with('success', 'Firewall rule added.');
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Agent unreachable';
        return back()->with('error', $error);
    }

    public function firewallRemove(Request $request)
    {
        $validated = $request->validate([
            'server_id' => 'required|exists:servers,id',
            'number'    => 'required|integer',
        ]);

        $server = Server::findOrFail($validated['server_id']);

        AgentService::for($server)->post('/security/firewall-remove', [
            'number' => $validated['number'],
        ]);

        return redirect()->route('admin.security.index', ['server_id' => $server->id])->with('success', 'Firewall rule removed.');
    }

    public function ipBlock(Request $request)
    {
        $validated = $request->validate([
            'server_id' => 'required|exists:servers,id',
            'ip'        => 'required|ip',
            'action'    => 'required|in:block,unblock',
        ]);

        $server = Server::findOrFail($validated['server_id']);

        AgentService::for($server)->post('/security/ip-block', [
            'ip'     => $validated['ip'],
            'action' => $validated['action'],
        ]);

        return redirect()->route('admin.security.index', ['server_id' => $server->id])
            ->with('success', 'IP ' . $validated['ip'] . ' ' . $validated['action'] . 'ed.');
    }

    public function fail2banUnban(Request $request)
    {
        $validated = $request->validate([
            'server_id' => 'required|exists:servers,id',
            'ip'        => 'required|string',
            'jail'      => 'required|string',
        ]);

        $server = Server::findOrFail($validated['server_id']);

        AgentService::for($server)->post('/security/fail2ban-unban', [
            'ip'   => $validated['ip'],
            'jail' => $validated['jail'],
        ]);

        return redirect()->route('admin.security.index', ['server_id' => $server->id])
            ->with('success', $validated['ip'] . ' unbanned from ' . $validated['jail']);
    }
}
