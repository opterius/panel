<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\DomainAlias;
use App\Services\AgentService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class AliasController extends Controller
{
    public function index()
    {
        $domains = Domain::with('account.server', 'aliases')
            ->whereIn('account_id', auth()->user()->accessibleAccountIds())
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->get();

        return view('aliases.index', compact('domains'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'domain_id'    => 'required|exists:domains,id',
            'alias_domain' => 'required|string|max:255|unique:domain_aliases,alias_domain|unique:domains,domain',
        ]);

        $domain = Domain::with('account.server')
            ->whereIn('account_id', auth()->user()->accessibleAccountIds())
            ->findOrFail($validated['domain_id']);

        if (!$domain->account->userCan(auth()->user(), 'dns')) {
            return back()->with('error', __('aliases.no_permission_domain_alias'));
        }

        $alias = DomainAlias::create([
            'domain_id'    => $domain->id,
            'alias_domain' => $validated['alias_domain'],
            'status'       => 'pending',
        ]);

        $response = AgentService::for($domain->account->server)->post('/domains/alias-add', [
            'domain'       => $domain->domain,
            'alias_domain' => $validated['alias_domain'],
        ]);

        if ($response && $response->successful()) {
            $alias->update(['status' => 'active']);

            // Auto-create DNS zone for the alias
            AgentService::for($domain->account->server)->post('/dns/create-zone', [
                'domain'    => $validated['alias_domain'],
                'server_ip' => $domain->account->server->ip_address,
                'ns1'       => config('opterius.ns1'),
                'ns2'       => config('opterius.ns2'),
            ]);

            ActivityLogger::log('alias.created', 'domain', $domain->id, $validated['alias_domain'],
                "Added domain alias {$validated['alias_domain']} → {$domain->domain}");

            return back()->with('success', __('aliases.domain_alias_added', ['domain' => $validated['alias_domain']]));
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Agent unreachable';
        $alias->update(['status' => 'error']);
        return back()->with('error', __('aliases.failed_to_add_domain_alias', ['error' => $error]))->withInput();
    }

    public function destroy(Request $request, DomainAlias $alias)
    {
        $alias->load('domain.account.server');

        if (!$alias->domain->account->userCan(auth()->user(), 'dns')) {
            return back()->with('error', __('aliases.no_permission_domain_alias'));
        }

        AgentService::for($alias->domain->account->server)->post('/domains/alias-remove', [
            'domain'       => $alias->domain->domain,
            'alias_domain' => $alias->alias_domain,
        ]);

        ActivityLogger::log('alias.deleted', 'domain', $alias->domain_id, $alias->alias_domain,
            "Removed domain alias {$alias->alias_domain}");

        $alias->delete();

        return back()->with('success', __('aliases.domain_alias_removed', ['domain' => $alias->alias_domain]));
    }
}
