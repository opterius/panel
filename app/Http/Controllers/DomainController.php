<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\AgentService;
use Illuminate\Http\Request;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Hash;

class DomainController extends Controller
{
    public function index()
    {
        // Only show main domains (not subdomains) — each account has exactly one main domain
        $domains = Domain::with('server', 'account', 'sslCertificate', 'subdomains')
            ->whereIn('account_id', auth()->user()->currentAccountIds())
            ->whereNull('parent_id')
            ->latest()
            ->get();

        return view('domains.index', compact('domains'));
    }

    public function destroy(Request $request, Domain $domain)
    {
        $domain->load('account');

        if (!$domain->account->userCan(auth()->user(), 'settings')) {
            return back()->with('error', __('domains.no_permission_delete'));
        }

        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => __('common.password_incorrect')]);
        }

        // Only allow deleting subdomains, not the main domain (main domain is deleted with account)
        if (!$domain->isSubdomain()) {
            return back()->with('error', __('domains.main_domain_cannot_be_deleted'));
        }

        $domain->load('account.server');

        ActivityLogger::log('subdomain.deleted', 'domain', $domain->id, $domain->domain,
            "Deleted subdomain {$domain->domain}", ['server_id' => $domain->server_id, 'account_id' => $domain->account_id]);

        // Send delete request to the Go agent
        AgentService::for($domain->account->server)->post('/domains/delete', [
            'domain'      => $domain->domain,
            'username'    => $domain->account->username,
            'php_version' => $domain->php_version,
        ]);

        $domain->delete();

        return redirect()->route('user.domains.index')->with('success', __('domains.subdomain_deleted', ['domain' => $domain->domain]));
    }

    /**
     * POST /user/domains/{domain}/catchall
     *
     * Toggles multi-tenant catch-all mode: when enabled, every *.{domain}
     * subdomain serves the same nginx vhost without needing a per-subdomain
     * record. Useful for SaaS where customers get acmeN.fastbiz.ro on signup.
     * Pairs with a wildcard SSL cert on the same domain.
     */
    public function toggleCatchall(Request $request, Domain $domain)
    {
        $domain->load('account.server');

        if (!$domain->account->userCan(auth()->user(), 'settings')) {
            return back()->with('error', __('domains.no_permission'));
        }

        if ($domain->isSubdomain()) {
            return back()->with('error', 'Catch-all only applies to main domains.');
        }

        $enabled = (bool) $request->boolean('enabled');

        // Optional custom doc root — NULL means inherit from main domain vhost.
        $docRoot = trim((string) $request->input('document_root', ''));
        if ($enabled && $docRoot !== '' && !str_starts_with($docRoot, '/')) {
            return back()->with('error', 'Document root must be an absolute path (start with /).');
        }

        $response = AgentService::for($domain->account->server)->post('/domain/catchall', [
            'domain'        => $domain->domain,
            'username'      => $domain->account->username,
            'enabled'       => $enabled,
            'document_root' => $docRoot,
            'php_version'   => $domain->php_version,
        ]);

        if (!$response || !$response->successful()) {
            $error = $response ? $response->json('error', 'Unknown error') : 'Could not reach agent';
            return back()->with('error', "Catch-all toggle failed: {$error}");
        }

        $domain->update([
            'catchall_subdomains'     => $enabled,
            'catchall_document_root'  => $enabled && $docRoot !== '' ? $docRoot : null,
        ]);

        ActivityLogger::log('domain.catchall', 'domain', $domain->id, $domain->domain,
            ($enabled ? 'Enabled' : 'Disabled') . " catch-all subdomains for {$domain->domain}",
            ['enabled' => $enabled]);

        $msg = $enabled
            ? "Catch-all enabled — any *.{$domain->domain} subdomain now serves the same site."
            : "Catch-all disabled — subdomains again require their own record.";
        return back()->with('success', $msg);
    }
}
