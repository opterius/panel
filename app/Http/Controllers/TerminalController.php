<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TerminalController extends Controller
{
    public function index()
    {
        $accounts = Account::with('server', 'domains')
            ->whereIn('id', auth()->user()->currentAccountIds())
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
            ->whereIn('id', auth()->user()->currentAccountIds())
            ->where('ssh_enabled', true)
            ->findOrFail($validated['account_id']);

        if (!$account->userCan(auth()->user(), 'ssh')) {
            return back()->with('error', __('servers.no_ssh_permission'));
        }

        // Request a one-time terminal token from the agent
        $response = AgentService::for($account->server)->post('/terminal/token', [
            'username' => $account->username,
        ]);

        if (!$response || !$response->successful()) {
            return back()->with('error', __('servers.terminal_connect_failed'));
        }

        $token = $response->json('token');

        return view('terminal.session', [
            'account'   => $account,
            'token'     => $token,
            'proxyUrl'  => route('user.terminal.proxy'),
        ]);
    }

    /**
     * Proxy terminal data between browser and agent.
     * This avoids mixed-content issues (HTTPS panel -> HTTP agent).
     */
    public function proxy(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'token'      => 'required|string',
            'input'      => 'nullable|string',
        ]);

        $account = Account::with('server')
            ->whereIn('id', auth()->user()->currentAccountIds())
            ->findOrFail($validated['account_id']);

        $agentUrl = rtrim($account->server->agent_url, '/');
        $url = $agentUrl . '/terminal/connect?username=' . $account->username . '&token=' . $validated['token'];

        // Build HMAC signature for agent auth
        $timestamp = now()->toRfc3339String();
        $body = $validated['input'] ?? '';
        $path = '/terminal/connect';
        $payload = $timestamp . 'POST' . $path . $body;
        $signature = hash_hmac('sha256', $payload, $account->server->agent_token);

        try {
            $response = Http::withoutVerifying()
                ->timeout(30)
                ->withHeaders([
                    'X-Signature' => $signature,
                    'X-Timestamp' => $timestamp,
                    'Content-Type' => 'application/octet-stream',
                ])
                ->withBody($body, 'application/octet-stream')
                ->post($url);

            return response($response->body(), 200)
                ->header('Content-Type', 'application/octet-stream');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Connection failed: ' . $e->getMessage()], 500);
        }
    }
}
