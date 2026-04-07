<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Redirect;
use App\Services\AgentService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    public function index()
    {
        $domains = Domain::with('account.server', 'redirects')
            ->whereIn('account_id', auth()->user()->currentAccountIds())
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->get();

        return view('redirects.index', compact('domains'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'domain_id'       => 'required|exists:domains,id',
            'source_path'     => 'required|string|max:500',
            'destination_url' => 'required|url|max:1000',
            'type'            => 'required|in:301,302',
        ]);

        $domain = Domain::with('account.server')
            ->whereIn('account_id', auth()->user()->currentAccountIds())
            ->findOrFail($validated['domain_id']);

        if (!$domain->account->userCan(auth()->user(), 'settings')) {
            return back()->with('error', __('redirects.no_permission'));
        }

        Redirect::create([
            'domain_id'       => $domain->id,
            'source_path'     => $validated['source_path'],
            'destination_url' => $validated['destination_url'],
            'type'            => $validated['type'],
        ]);

        // Sync all redirects for this domain to Nginx
        $this->syncRedirects($domain);

        ActivityLogger::log('redirect.created', 'domain', $domain->id, $validated['source_path'],
            "Created redirect {$validated['source_path']} → {$validated['destination_url']} ({$validated['type']})");

        return back()->with('success', __('redirects.redirect_created'));
    }

    public function destroy(Redirect $redirect)
    {
        $redirect->load('domain.account.server');

        if (!$redirect->domain->account->userCan(auth()->user(), 'settings')) {
            return back()->with('error', __('redirects.no_permission'));
        }

        $domain = $redirect->domain;

        ActivityLogger::log('redirect.deleted', 'domain', $domain->id, $redirect->source_path,
            "Deleted redirect {$redirect->source_path}");

        $redirect->delete();

        // Re-sync remaining redirects
        $this->syncRedirects($domain);

        return back()->with('success', __('redirects.redirect_removed'));
    }

    private function syncRedirects(Domain $domain): void
    {
        $domain->load('account.server', 'redirects');

        $redirects = $domain->redirects->where('enabled', true)->map(fn ($r) => [
            'source_path' => $r->source_path,
            'destination' => $r->destination_url,
            'type'        => $r->type,
        ])->values()->toArray();

        AgentService::for($domain->account->server)->post('/domains/redirect-sync', [
            'domain'    => $domain->domain,
            'redirects' => $redirects,
        ]);
    }
}
