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
            ->whereIn('account_id', auth()->user()->accessibleAccountIds())
            ->whereNull('parent_id')
            ->latest()
            ->get();

        return view('domains.index', compact('domains'));
    }

    public function destroy(Request $request, Domain $domain)
    {
        $domain->load('account');

        if (!$domain->account->userCan(auth()->user(), 'settings')) {
            return back()->with('error', 'You do not have permission to delete domains on this account.');
        }

        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => 'The password is incorrect.']);
        }

        // Only allow deleting subdomains, not the main domain (main domain is deleted with account)
        if (!$domain->isSubdomain()) {
            return back()->with('error', 'The main domain cannot be deleted. Delete the account instead.');
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

        return redirect()->route('user.domains.index')->with('success', 'Subdomain ' . $domain->domain . ' removed.');
    }
}
