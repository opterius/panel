<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Services\AgentService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /**
     * GET /user/analytics — show the dashboard with the user's domains.
     */
    public function index()
    {
        $domains = Domain::with('account')
            ->whereIn('account_id', auth()->user()->currentAccountIds())
            ->orderBy('domain')
            ->get(['id', 'domain', 'account_id']);

        return view('user.analytics.index', compact('domains'));
    }

    /**
     * POST /user/analytics/query — proxy a query to the agent.
     * Front-end calls this whenever the customer changes domain or range.
     */
    public function query(Request $request)
    {
        $data = $request->validate([
            'domain_id' => 'required|integer',
            'range'     => 'required|in:24h,7d,30d,90d',
        ]);

        $domain = Domain::with('account.server')->findOrFail($data['domain_id']);

        if (! in_array($domain->account_id, auth()->user()->currentAccountIds())) {
            abort(403);
        }

        $response = AgentService::for($domain->account->server)->post('/analytics/query', [
            'domain' => $domain->domain,
            'range'  => $data['range'],
        ]);

        if (! $response || ! $response->successful()) {
            return response()->json([
                'error' => $response?->json('error') ?? 'Agent unreachable',
            ], 502);
        }

        return response()->json($response->json());
    }
}
