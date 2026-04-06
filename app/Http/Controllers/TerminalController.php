<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\AgentService;
use Illuminate\Http\Request;

class TerminalController extends Controller
{
    public function index()
    {
        $accounts = Account::with('server', 'domains')
            ->whereIn('id', auth()->user()->accessibleAccountIds())
            ->where('ssh_enabled', true)
            ->get();

        return view('terminal.index', compact('accounts'));
    }

    public function connect(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
        ]);

        $account = Account::with('server')
            ->whereIn('id', auth()->user()->accessibleAccountIds())
            ->where('ssh_enabled', true)
            ->findOrFail($validated['account_id']);

        if (!$account->userCan(auth()->user(), 'ssh')) {
            return back()->with('error', 'You do not have SSH permission for this account.');
        }

        // Request a one-time terminal token from the agent
        $response = AgentService::for($account->server)->post('/terminal/token', [
            'username' => $account->username,
        ]);

        if (!$response || !$response->successful()) {
            return back()->with('error', 'Could not connect to server agent.');
        }

        $token = $response->json('token');
        $agentUrl = rtrim($account->server->agent_url, '/');

        return view('terminal.session', [
            'account'  => $account,
            'token'    => $token,
            'agentUrl' => $agentUrl,
        ]);
    }
}
