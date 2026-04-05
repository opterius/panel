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
            'subdomain'     => ['required', 'string', 'max:63', 'regex:/^[a-zA-Z0-9]([a-zA-Z0-9_-]*[a-zA-Z0-9])?$/'],
            'document_root' => ['nullable', 'string', 'max:500', 'regex:/^[a-zA-Z0-9_\-\/\.]+$/'],
        ], [
            'subdomain.regex' => 'Subdomain can only contain letters, numbers, hyphens and underscores.',
            'document_root.regex' => 'Document root contains invalid characters.',
        ]);

        $fullDomain = $validated['subdomain'] . '.' . $domain->domain;

        // Check uniqueness
        if (Domain::where('domain', $fullDomain)->exists()) {
            return back()->with('error', 'Subdomain ' . $fullDomain . ' already exists.')->withInput();
        }

        // Default document root: inside parent domain's directory
        $parentDir = dirname($domain->document_root);
        $documentRoot = $validated['document_root'] ?: $parentDir . '/public_html/' . $validated['subdomain'];

        // Ensure document root is within the account's home directory
        $homeDir = $domain->account->home_directory;
        if (!str_starts_with($documentRoot, $homeDir)) {
            return back()->with('error', 'Document root must be within the account home directory.')->withInput();
        }

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
