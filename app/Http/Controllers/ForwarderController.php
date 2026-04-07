<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\ActivityLogger;
use App\Services\AgentService;
use Illuminate\Http\Request;

class ForwarderController extends Controller
{
    public function index(Request $request)
    {
        $domains = Domain::with('account.server')
            ->whereIn('account_id', auth()->user()->currentAccountIds())
            ->where('status', 'active')
            ->get();

        $selectedDomain = null;
        $forwarders = [];

        if ($request->has('domain_id')) {
            $selectedDomain = Domain::with('account.server')
                ->whereIn('account_id', auth()->user()->currentAccountIds())
                ->findOrFail($request->domain_id);

            $response = AgentService::for($selectedDomain->account->server)->post('/forwarder/list', [
                'domain' => $selectedDomain->domain,
            ]);

            if ($response && $response->successful()) {
                $forwarders = $response->json('forwarders') ?? [];
            }
        }

        return view('forwarders.index', compact('domains', 'selectedDomain', 'forwarders'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'domain_id'   => 'required|exists:domains,id',
            'source'      => 'required|string|max:255',
            'destination' => 'required|email|max:255',
        ]);

        $domain = Domain::with('account.server')->findOrFail($validated['domain_id']);

        if (!$domain->account->userCan(auth()->user(), 'email')) {
            return back()->with('error', __('emails.no_permission'));
        }

        // Build full source email
        $source = $validated['source'];
        if (!str_contains($source, '@')) {
            $source = $source . '@' . $domain->domain;
        }

        $response = AgentService::for($domain->account->server)->post('/forwarder/create', [
            'source'      => $source,
            'destination' => $validated['destination'],
            'domain'      => $domain->domain,
        ]);

        if ($response && $response->successful()) {
            ActivityLogger::log('forwarder.created', 'domain', $domain->id, $source,
                "Created forwarder {$source} → {$validated['destination']}");
            return redirect()->route('user.forwarders.index', ['domain_id' => $domain->id])->with('success', __('emails.forwarder_created', ['source' => $source, 'destination' => $validated['destination']]));
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Agent unreachable';
        return back()->with('error', $error)->withInput();
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'domain_id' => 'required|exists:domains,id',
            'source'    => 'required|string',
        ]);

        $domain = Domain::with('account.server')->findOrFail($validated['domain_id']);

        if (!$domain->account->userCan(auth()->user(), 'email')) {
            return back()->with('error', __('emails.no_permission'));
        }

        AgentService::for($domain->account->server)->post('/forwarder/delete', [
            'source' => $validated['source'],
        ]);

        ActivityLogger::log('forwarder.deleted', 'domain', $domain->id, $validated['source'],
            "Deleted forwarder {$validated['source']}");

        return redirect()->route('user.forwarders.index', ['domain_id' => $domain->id])->with('success', __('emails.forwarder_deleted'));
    }
}
