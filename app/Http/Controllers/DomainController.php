<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Domain;
use App\Models\Server;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class DomainController extends Controller
{
    public function index()
    {
        $domains = Domain::with('server', 'account', 'sslCertificate')
            ->whereHas('account', fn ($q) => $q->where('user_id', Auth::id()))
            ->latest()
            ->get();

        return view('domains.index', compact('domains'));
    }

    public function create()
    {
        $accounts = Account::with('server')
            ->where('user_id', Auth::id())
            ->get();

        return view('domains.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_id'  => 'required|exists:accounts,id',
            'domain'      => 'required|string|max:255|unique:domains,domain',
            'php_version' => 'required|string|in:' . implode(',', config('opterius.php_versions')),
        ]);

        $account = Account::with('server')->findOrFail($validated['account_id']);
        $documentRoot = $account->home_directory . '/' . $validated['domain'] . '/public_html';

        $domain = Domain::create([
            'server_id'     => $account->server_id,
            'account_id'    => $account->id,
            'domain'        => $validated['domain'],
            'document_root' => $documentRoot,
            'php_version'   => $validated['php_version'],
            'status'        => 'pending',
        ]);

        // Send create request to the Go agent
        $response = AgentService::for($account->server)->post('/domains/create', [
            'domain'        => $domain->domain,
            'document_root' => $domain->document_root,
            'username'      => $account->username,
            'php_version'   => $domain->php_version,
        ]);

        if ($response && $response->successful()) {
            $domain->update(['status' => 'active']);

            // Auto-create DNS zone
            AgentService::for($account->server)->post('/dns/create-zone', [
                'domain'    => $domain->domain,
                'server_ip' => $account->server->ip_address,
                'ns1'       => config('opterius.ns1', 'ns1.' . $domain->domain),
                'ns2'       => config('opterius.ns2', 'ns2.' . $domain->domain),
            ]);

            return redirect()->route('user.domains.index')->with('success', 'Domain ' . $domain->domain . ' created successfully.');
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        $domain->update(['status' => 'error']);

        return redirect()->route('user.domains.index')->with('warning', 'Domain saved but agent setup failed: ' . $error);
    }

    public function destroy(Request $request, Domain $domain)
    {
        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => 'The password is incorrect.']);
        }

        $domain->load('account.server');

        // Send delete request to the Go agent
        AgentService::for($domain->account->server)->post('/domains/delete', [
            'domain'      => $domain->domain,
            'username'    => $domain->account->username,
            'php_version' => $domain->php_version,
        ]);

        $domain->delete();

        return redirect()->route('user.domains.index')->with('success', 'Domain ' . $domain->domain . ' removed.');
    }
}
