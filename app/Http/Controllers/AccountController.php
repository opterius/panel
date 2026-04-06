<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Domain;
use App\Models\Package;
use App\Models\Server;
use App\Http\Controllers\SslController;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function index()
    {
        $query = Account::with('server', 'user', 'domains', 'databases');

        // Resellers only see their own accounts
        if (auth()->user()->isReseller()) {
            $query->where('user_id', auth()->id());
        }

        $accounts = $query->latest()->get();

        $license = new \App\Services\LicenseService();
        $maxAccounts = $license->maxAccounts();
        $currentAccounts = Account::count();
        $atLimit = $currentAccounts >= $maxAccounts && $maxAccounts !== PHP_INT_MAX;

        return view('accounts.index', compact('accounts', 'maxAccounts', 'currentAccounts', 'atLimit'));
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
        // Check license account limit
        $license = new \App\Services\LicenseService();
        $maxAccounts = $license->maxAccounts();
        $currentAccounts = Account::count();

        if ($currentAccounts >= $maxAccounts) {
            return back()->with('error', __('accounts.account_limit_reached', ['current' => $currentAccounts, 'max' => $maxAccounts]))->withInput();
        }

        // Check reseller ACL and account limit
        if (auth()->user()->isReseller()) {
            if (!auth()->user()->resellerCan('account.create')) {
                return back()->with('error', __('accounts.permission_denied_create'))->withInput();
            }
            if (!auth()->user()->resellerCanCreate('accounts')) {
                $usage = auth()->user()->resellerUsage();
                return back()->with('error', __('accounts.reseller_account_limit_reached', ['used' => $usage['accounts']['used'], 'limit' => $usage['accounts']['limit']]))->withInput();
            }
        }

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

            // Auto SSL
            SslController::autoIssue($domain);

            ActivityLogger::log('account.created', 'account', $account->id, $account->username,
                "Created account {$account->username} with domain {$validated['domain']}", ['server_id' => $account->server_id]);

            return redirect()->route('admin.accounts.show', $account)->with('success', __('accounts.account_created', ['domain' => $validated['domain']]));
        }

        ActivityLogger::log('account.created', 'account', $account->id, $account->username,
            "Created account {$account->username} (server setup failed)", ['server_id' => $account->server_id]);

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return redirect()->route('admin.accounts.show', $account)->with('warning', __('accounts.account_created_warning', ['error' => $error]));
    }

    public function show(Account $account)
    {
        $account->load('server', 'user', 'domains', 'databases', 'cronJobs');

        // Fetch account stats from agent
        $stats = null;
        $response = AgentService::for($account->server)->post('/stats/account', [
            'username'  => $account->username,
            'domains'   => $account->domains->pluck('domain')->toArray(),
            'databases' => $account->databases->pluck('name')->toArray(),
        ]);

        if ($response && $response->successful()) {
            $stats = $response->json('stats');
        }

        return view('accounts.show', compact('account', 'stats'));
    }

    public function updateOwner(Request $request, Account $account)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $account->user_id,
            'password' => 'nullable|string|min:8',
        ]);

        $user = $account->user;
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        ActivityLogger::log('account.owner_updated', 'account', $account->id, $account->username,
            "Updated owner info for {$account->username}: {$validated['name']} ({$validated['email']})");

        return redirect()->route('admin.accounts.show', $account)->with('success', __('accounts.account_owner_updated'));
    }

    public function suspend(Request $request, Account $account)
    {
        if (auth()->user()->isReseller() && !auth()->user()->resellerCan('account.suspend')) {
            return back()->with('error', __('accounts.permission_denied_suspend'));
        }

        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => __('common.password_incorrect')]);
        }

        $account->load('server', 'domains');
        $action = $account->suspended ? 'unsuspend' : 'suspend';

        $response = AgentService::for($account->server)->post('/account/suspend', [
            'username' => $account->username,
            'domains'  => $account->domains->pluck('domain')->toArray(),
            'action'   => $action,
        ]);

        if ($response && $response->successful()) {
            $account->update([
                'suspended'      => !$account->suspended,
                'suspended_at'   => $action === 'suspend' ? now() : null,
                'suspend_reason' => $action === 'suspend' ? $request->input('reason', '') : null,
            ]);
            $account->domains()->update(['status' => $action === 'suspend' ? 'suspended' : 'active']);

            ActivityLogger::log("account.{$action}ed", 'account', $account->id, $account->username,
                ucfirst($action) . "ed account {$account->username}");

            $successKey = $action === 'suspend' ? 'accounts.account_suspended' : 'accounts.account_unsuspended';
            return redirect()->route('admin.accounts.show', $account)->with('success', __($successKey));
        }

        $failKey = $action === 'suspend' ? 'accounts.failed_to_suspend' : 'accounts.failed_to_unsuspend';
        return back()->with('error', __($failKey));
    }

    public function destroy(Request $request, Account $account)
    {
        if (auth()->user()->isReseller() && !auth()->user()->resellerCan('account.terminate')) {
            return back()->with('error', __('accounts.permission_denied_delete'));
        }

        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => __('common.password_incorrect')]);
        }

        $account->load('server', 'domains');

        // Delete everything on the server (vhosts, files, system user, email, cron)
        AgentService::for($account->server)->post('/account/delete', [
            'username' => $account->username,
            'domains'  => $account->domains->pluck('domain')->toArray(),
        ]);

        ActivityLogger::log('account.deleted', 'account', $account->id, $account->username,
            "Deleted account {$account->username}", ['server_id' => $account->server_id]);

        $account->delete();

        return redirect()->route('admin.accounts.index')->with('success', __('accounts.account_deleted'));
    }
}
