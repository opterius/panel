<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\ActivityLogger;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SubdomainController extends Controller
{
    public function index()
    {
        $domains = Domain::with('account.server', 'subdomains')
            ->whereIn('account_id', auth()->user()->currentAccountIds())
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->get();

        return view('subdomains.index', compact('domains'));
    }

    public function create(Domain $domain)
    {
        // Authorization
        if (!in_array($domain->account_id, auth()->user()->currentAccountIds())) {
            abort(404);
        }

        $domain->load('account.server');

        return view('subdomains.create', compact('domain'));
    }

    public function destroy(Request $request, Domain $subdomain)
    {
        $subdomain->load('account.server', 'parent');

        if (!in_array($subdomain->account_id, auth()->user()->currentAccountIds())) {
            abort(404);
        }

        if (!$subdomain->isSubdomain()) {
            return back()->with('error', __('domains.main_domain_cannot_be_deleted'));
        }

        if (!$subdomain->account->userCan(auth()->user(), 'settings')) {
            return back()->with('error', __('domains.no_permission'));
        }

        $parent = $subdomain->parent;

        // 1. Remove Nginx vhost + FPM pool + SSL cert (file content NOT touched)
        \App\Services\AgentService::for($subdomain->account->server)->post('/domains/delete', [
            'domain'      => $subdomain->domain,
            'username'    => $subdomain->account->username,
            'php_version' => $subdomain->php_version,
        ]);

        // 2. Remove DNS A record from parent zone
        if ($parent) {
            \App\Services\AgentService::for($subdomain->account->server)->post('/dns/delete-record', [
                'domain' => $parent->domain,
                'name'   => $subdomain->domain,
                'type'   => 'A',
            ]);
        }

        \App\Services\ActivityLogger::log('subdomain.deleted', 'domain', $subdomain->id, $subdomain->domain,
            "Deleted subdomain {$subdomain->domain} (files preserved at {$subdomain->document_root})");

        $domain = $subdomain->domain;
        $subdomain->delete();

        return redirect()->route('user.subdomains.index')
            ->with('success', "Subdomain {$domain} removed. Files at the document root were not deleted.");
    }

    public function store(Request $request, Domain $domain)
    {
        $domain->load('account.server');

        if (!$domain->account->userCan(auth()->user(), 'settings')) {
            return back()->with('error', __('domains.no_permission'));
        }

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
            return back()->with('error', __('domains.subdomain_already_exists', ['domain' => $fullDomain]))->withInput();
        }

        // Default document root: inside parent domain's public_html, named with the
        // FULL subdomain (e.g. /home/user/opterius.com/public_html/get.opterius.com)
        // — not just the leaf label — so it's visually obvious which folder belongs
        // to which subdomain when listing public_html.
        $parentDir = dirname($domain->document_root);
        $documentRoot = $validated['document_root'] ?: $parentDir . '/public_html/' . $fullDomain;

        // Ensure document root is within the account's home directory
        $homeDir = $domain->account->home_directory;
        if (!str_starts_with($documentRoot, $homeDir)) {
            return back()->with('error', __('domains.document_root_outside_home'))->withInput();
        }

        // Create subdomain record
        $subdomain = Domain::create([
            'server_id'     => $domain->server_id,
            'account_id'    => $domain->account_id,
            'parent_id'     => $domain->id,
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

            // Auto-issue SSL for the subdomain
            \App\Http\Controllers\SslController::autoIssue($subdomain);

            ActivityLogger::log('subdomain.created', 'domain', $subdomain->id, $subdomain->domain,
                "Created subdomain {$fullDomain}", ['server_id' => $domain->server_id, 'parent_domain_id' => $domain->id]);

            return redirect()->route('user.subdomains.index')->with('success', __('domains.subdomain_created', ['domain' => $fullDomain]));
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        $subdomain->update(['status' => 'error']);

        return redirect()->route('user.domains.index')->with('warning', __('domains.subdomain_setup_failed', ['error' => $error]));
    }
}
