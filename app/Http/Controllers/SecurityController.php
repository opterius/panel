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
        } elseif ($servers->count() === 1) {
            $selectedServer = $servers->first();
        }

        if ($selectedServer) {
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
        return back()->with('error', __('servers.scan_failed', ['error' => $error]));
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
            return redirect()->route('admin.security.index', ['server_id' => $server->id])->with('success', __('servers.firewall_rule_added'));
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

        return redirect()->route('admin.security.index', ['server_id' => $server->id])->with('success', __('servers.firewall_rule_removed'));
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
            ->with('success', __('servers.ip_actioned', ['ip' => $validated['ip'], 'actioned' => $validated['action'] . 'ed']));
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
            ->with('success', __('servers.ip_unbanned', ['ip' => $validated['ip'], 'jail' => $validated['jail']]));
    }

    /**
     * Lock down all hosting accounts on a server: chmod /home to 0711
     * (so logged-in users can't enumerate other customers' usernames),
     * apply 0750 perms + www-data ACL to each /home/{user}, and jail any
     * shell users with jailkit. Runs the agent's /security/lockdown
     * handler. Idempotent — safe to re-run.
     */
    public function lockdown(Request $request)
    {
        $validated = $request->validate([
            'server_id' => 'required|exists:servers,id',
        ]);

        $server   = Server::findOrFail($validated['server_id']);
        $response = AgentService::for($server)->post('/security/lockdown-accounts', []);

        if (! $response || ! $response->successful()) {
            return redirect()->route('admin.security.index', ['server_id' => $server->id])
                ->with('error', 'Lockdown failed: ' . ($response?->json('error') ?? 'agent unreachable'));
        }

        $locked = $response->json('locked', []);
        $jailed = $response->json('jailed', []);
        $msg    = sprintf(
            'Locked down %d account%s%s. /home now hides usernames from other customers.',
            count($locked),
            count($locked) === 1 ? '' : 's',
            count($jailed) > 0 ? ' (' . count($jailed) . ' shell user' . (count($jailed) === 1 ? '' : 's') . ' jailed)' : ''
        );

        return redirect()->route('admin.security.index', ['server_id' => $server->id])
            ->with('success', $msg);
    }
}
