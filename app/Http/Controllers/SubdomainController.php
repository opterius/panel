<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SubdomainController extends Controller
{
    public function create(Domain $domain)
    {
        $domain->load('account.server');

        return view('subdomains.create', compact('domain'));
    }

    public function store(Request $request, Domain $domain)
    {
        $domain->load('account.server');

        $validated = $request->validate([
            'subdomain'     => 'required|string|max:63|alpha_dash',
            'document_root' => 'nullable|string|max:500',
        ]);

        $fullDomain = $validated['subdomain'] . '.' . $domain->domain;

        // Check uniqueness
        if (Domain::where('domain', $fullDomain)->exists()) {
            return back()->with('error', 'Subdomain ' . $fullDomain . ' already exists.')->withInput();
        }

        // Default document root: inside parent domain's public_html
        $documentRoot = $validated['document_root'] ?: $domain->document_root . '/' . $validated['subdomain'];

        // Create subdomain record
        $subdomain = Domain::create([
            'server_id'     => $domain->server_id,
            'account_id'    => $domain->account_id,
            'domain'        => $fullDomain,
            'document_root' => $documentRoot,
            'php_version'   => $domain->php_version,
            'status'        => 'pending',
        ]);

        // Tell the agent to create the vhost (but NOT a new system user — reuse parent's)
        $response = AgentService::for($domain->account->server)->post('/domains/create', [
            'domain'        => $subdomain->domain,
            'document_root' => $subdomain->document_root,
            'username'      => $domain->account->username,
            'php_version'   => $subdomain->php_version,
        ]);

        if ($response && $response->successful()) {
            $subdomain->update(['status' => 'active']);

            // Auto-create DNS record for subdomain
            AgentService::for($domain->account->server)->post('/dns/add-record', [
                'domain'  => $domain->domain,
                'name'    => $fullDomain,
                'type'    => 'A',
                'content' => $domain->account->server->ip_address,
                'ttl'     => 3600,
            ]);

            return redirect()->route('user.domains.index')->with('success', 'Subdomain ' . $fullDomain . ' created.');
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        $subdomain->update(['status' => 'error']);

        return redirect()->route('user.domains.index')->with('warning', 'Subdomain saved but server setup failed: ' . $error);
    }
}
