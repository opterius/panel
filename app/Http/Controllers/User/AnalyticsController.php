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
            'domain_id'    => 'required|integer',
            'range'        => 'required|in:24h,7d,30d,90d',
            'offset_hours' => 'nullable|integer|min:0|max:8640',
        ]);

        $domain = $this->authorizedDomain($data['domain_id']);

        $response = AgentService::for($domain->account->server)->post('/analytics/query', [
            'domain'       => $domain->domain,
            'range'        => $data['range'],
            'offset_hours' => (int) ($data['offset_hours'] ?? 0),
        ]);

        if (! $response || ! $response->successful()) {
            return response()->json([
                'error' => $response?->json('error') ?? 'Agent unreachable',
            ], 502);
        }

        return response()->json($response->json());
    }

    /**
     * POST /user/analytics/live — real-time visitor stats (last 5–30 min).
     */
    public function live(Request $request)
    {
        $data = $request->validate([
            'domain_id' => 'required|integer',
        ]);

        $domain = $this->authorizedDomain($data['domain_id']);

        $response = AgentService::for($domain->account->server)->post('/analytics/live', [
            'domain' => $domain->domain,
        ]);

        if (! $response || ! $response->successful()) {
            return response()->json([
                'error' => $response?->json('error') ?? 'Agent unreachable',
            ], 502);
        }

        return response()->json($response->json());
    }

    private function authorizedDomain(int $id): Domain
    {
        $domain = Domain::with('account.server')->findOrFail($id);

        if (! in_array($domain->account_id, auth()->user()->currentAccountIds())) {
            abort(403);
        }

        return $domain;
    }
}
