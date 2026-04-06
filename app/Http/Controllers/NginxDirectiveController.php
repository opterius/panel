<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\NginxDirective;
use App\Services\AgentService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class NginxDirectiveController extends Controller
{
    public function index()
    {
        $domains = Domain::with('account.server', 'nginxDirective')
            ->whereIn('account_id', auth()->user()->accessibleAccountIds())
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->get();

        return view('nginx-directives.index', compact('domains'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'domain_id'  => 'required|exists:domains,id',
            'directives' => 'required|string|max:10000',
        ]);

        $domain = Domain::with('account.server')
            ->whereIn('account_id', auth()->user()->accessibleAccountIds())
            ->findOrFail($validated['domain_id']);

        if (!$domain->account->userCan(auth()->user(), 'settings')) {
            return back()->with('error', 'You do not have permission to manage Nginx directives.');
        }

        $directive = NginxDirective::updateOrCreate(
            ['domain_id' => $domain->id],
            ['directives' => $validated['directives'], 'enabled' => true],
        );

        // Sync to server
        $this->syncDirectives($domain);

        ActivityLogger::log('nginx.updated', 'domain', $domain->id, $domain->domain,
            "Updated custom Nginx directives for {$domain->domain}");

        return back()->with('success', 'Nginx directives updated. Config reloaded.');
    }

    public function destroy(Domain $domain)
    {
        $domain->load('account.server');

        if (!$domain->account->userCan(auth()->user(), 'settings')) {
            return back()->with('error', 'Permission denied.');
        }

        NginxDirective::where('domain_id', $domain->id)->delete();
        $this->syncDirectives($domain);

        return back()->with('success', 'Custom directives removed.');
    }

    private function syncDirectives(Domain $domain): void
    {
        $directive = NginxDirective::where('domain_id', $domain->id)->where('enabled', true)->first();

        AgentService::for($domain->account->server)->post('/domains/custom-nginx', [
            'domain'     => $domain->domain,
            'directives' => $directive ? $directive->directives : '',
        ]);
    }
}
