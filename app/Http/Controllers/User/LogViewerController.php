<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Services\AgentService;
use Illuminate\Http\Request;

class LogViewerController extends Controller
{
    /**
     * GET /user/logs — pick a domain + log type to tail.
     */
    public function index()
    {
        $domains = Domain::with('account')
            ->whereIn('account_id', auth()->user()->currentAccountIds())
            ->orderBy('domain')
            ->get();

        return view('user.logs.index', compact('domains'));
    }

    /**
     * POST /user/logs/tail — JSON polling endpoint that proxies to the agent.
     * Frontend calls this every 2 seconds with the offset returned by the
     * previous response.
     */
    public function tail(Request $request)
    {
        $data = $request->validate([
            'domain_id' => 'required|integer',
            'log_type'  => 'required|in:access,error,php-error',
            'offset'    => 'nullable|integer|min:0',
        ]);

        $domain = Domain::with('account.server')->findOrFail($data['domain_id']);

        if (! in_array($domain->account_id, auth()->user()->currentAccountIds())) {
            abort(403);
        }

        $response = AgentService::for($domain->account->server)->post('/logs/tail', [
            'domain'    => $domain->domain,
            'username'  => $domain->account->username,
            'log_type'  => $data['log_type'],
            'offset'    => $data['offset'] ?? 0,
            'max_bytes' => 65536,
        ]);

        if (! $response || ! $response->successful()) {
            return response()->json([
                'error' => $response?->json('error') ?? 'Agent unreachable',
            ], 502);
        }

        return response()->json($response->json());
    }
}
