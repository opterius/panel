<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Domain;
use App\Models\Package;
use App\Models\Server;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function index()
    {
        $accounts = Account::with('server', 'user', 'domains', 'databases')
            ->latest()
            ->get();

        return view('accounts.index', compact('accounts'));
    }

    public function create()
    {
        $servers = Server::all();
        $packages = Package::orderByDesc('is_default')->orderBy('name')->get();
        $defaultPackage = $packages->firstWhere('is_default', true);

        return view('accounts.create', compact('servers', 'packages', 'defaultPackage'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'server_id'  => 'required|exists:servers,id',
            'username'   => 'required|string|max:32|alpha_dash|unique:accounts,username',
            'domain'     => 'required|string|max:255|unique:domains,domain',
            'package_id' => 'required|exists:packages,id',
        ]);

        $package = Package::findOrFail($validated['package_id']);
        $phpVersion = $package->default_php_version;
        $diskQuota = $package->disk_quota;

        $account = DB::transaction(function () use ($validated, $phpVersion, $diskQuota) {
            $homeDir = '/home/' . $validated['username'];

            $account = Account::create([
                'user_id'      => auth()->id(),
                'server_id'    => $validated['server_id'],
                'package_id'   => $validated['package_id'] ?? null,
                'username'     => $validated['username'],
                'home_directory' => $homeDir,
                'php_version'  => $phpVersion,
                'disk_quota'   => $diskQuota,
            ]);

            Domain::create([
                'server_id'     => $validated['server_id'],
                'account_id'    => $account->id,
                'domain'        => $validated['domain'],
                'document_root' => $homeDir . '/' . $validated['domain'] . '/public_html',
                'php_version'   => $phpVersion,
                'status'        => 'pending',
            ]);

            return $account;
        });

        // Tell the agent to create the domain on the server
        $server = Server::findOrFail($validated['server_id']);
        $domain = $account->domains()->first();

        $response = AgentService::for($server)->post('/domains/create', [
            'domain'        => $domain->domain,
            'document_root' => $domain->document_root,
            'username'      => $account->username,
            'php_version'   => $domain->php_version,
        ]);

        if ($response && $response->successful()) {
            $domain->update(['status' => 'active']);

            // Auto-create DNS zone
            AgentService::for($server)->post('/dns/create-zone', [
                'domain'    => $domain->domain,
                'server_ip' => $server->ip_address,
                'ns1'       => config('opterius.ns1', 'ns1.' . $domain->domain),
                'ns2'       => config('opterius.ns2', 'ns2.' . $domain->domain),
            ]);

            return redirect()->route('accounts.show', $account)->with('success', 'Account created with domain ' . $validated['domain']);
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return redirect()->route('accounts.show', $account)->with('warning', 'Account saved but server setup failed: ' . $error);
    }

    public function show(Account $account)
    {
        $account->load('server', 'user', 'domains', 'databases', 'cronJobs');

        return view('accounts.show', compact('account'));
    }

    public function destroy(Request $request, Account $account)
    {
        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => 'The password is incorrect.']);
        }

        $account->delete();

        return redirect()->route('accounts.index')->with('success', 'Account deleted.');
    }
}
