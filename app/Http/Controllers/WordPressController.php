<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WordPressController extends Controller
{
    public function index()
    {
        $domains = Domain::with('account.server')
            ->whereIn('account_id', auth()->user()->accessibleAccountIds())
            ->where('status', 'active')
            ->get();

        // Scan for existing WordPress installations
        $sites = [];
        $servers = $domains->pluck('account.server')->unique('id');
        foreach ($servers as $server) {
            $serverDomains = $domains->where('account.server_id', $server->id);
            $username = $serverDomains->first()->account->username ?? '';
            if (!$username) continue;

            $response = AgentService::for($server)->post('/wordpress/scan', [
                'username' => $username,
                'domains'  => $serverDomains->pluck('domain')->toArray(),
            ]);

            if ($response && $response->successful()) {
                $sites = array_merge($sites, $response->json('sites') ?? []);
            }
        }

        return view('wordpress.index', compact('domains', 'sites'));
    }

    public function create()
    {
        $domains = Domain::with('account.server')
            ->whereIn('account_id', auth()->user()->accessibleAccountIds())
            ->where('status', 'active')
            ->get();

        return view('wordpress.create', compact('domains'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'domain_id'      => 'required|exists:domains,id',
            'install_path'   => 'nullable|string|max:50|regex:/^[a-zA-Z0-9_-]*$/',
            'site_title'     => 'required|string|max:200',
            'admin_user'     => ['required', 'string', 'max:30', 'regex:/^[a-zA-Z0-9_]+$/', 'not_in:admin,administrator,root'],
            'admin_password' => ['required', 'string', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).+$/'],
            'admin_email'    => 'required|email',
            'language'       => 'nullable|string|max:10',
        ], [
            'admin_user.not_in' => 'Username "admin", "administrator", and "root" are not allowed for security.',
            'admin_password.regex' => 'Password must contain uppercase, lowercase, and a number.',
            'install_path.regex' => 'Install path can only contain letters, numbers, hyphens, and underscores.',
        ]);

        $domain = Domain::with('account.server')->findOrFail($validated['domain_id']);

        // Auto-generate database name and credentials
        $dbName = 'wp_' . Str::lower(Str::random(8));
        $dbUser = 'wp_' . Str::lower(Str::random(8));
        $dbPass = Str::random(24);

        // 1. Create database
        $response = AgentService::for($domain->account->server)->post('/databases/create', [
            'name' => $dbName,
        ]);

        if (!$response || !$response->successful()) {
            $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
            return back()->with('error', __('servers.failed_to_create_db', ['error' => $error]))->withInput();
        }

        // 2. Create database user
        $response = AgentService::for($domain->account->server)->post('/databases/user-create', [
            'username' => $dbUser,
            'password' => $dbPass,
            'database' => $dbName,
            'host'     => 'localhost',
        ]);

        if (!$response || !$response->successful()) {
            AgentService::for($domain->account->server)->post('/databases/delete', ['name' => $dbName]);
            $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
            return back()->with('error', __('servers.failed_to_create_db_user', ['error' => $error]))->withInput();
        }

        // 3. Install WordPress
        $response = AgentService::for($domain->account->server)->post('/wordpress/install', [
            'domain'         => $domain->domain,
            'document_root'  => $domain->document_root,
            'username'       => $domain->account->username,
            'install_path'   => $validated['install_path'] ?? '',
            'site_title'     => $validated['site_title'],
            'admin_user'     => $validated['admin_user'],
            'admin_password' => $validated['admin_password'],
            'admin_email'    => $validated['admin_email'],
            'db_name'        => $dbName,
            'db_user'        => $dbUser,
            'db_password'    => $dbPass,
            'language'       => $validated['language'] ?? 'en_US',
        ]);

        if ($response && $response->successful()) {
            $data = $response->json();
            return redirect()->route('user.wordpress.index')->with('success',
                __('servers.wordpress_installed', ['version' => $data['version'] ?? '', 'domain' => $domain->domain, 'admin_url' => $data['admin_url'] ?? ''])
            );
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
        return back()->with('error', __('servers.wordpress_install_failed', ['error' => $error]))->withInput();
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'domain_id' => 'required|exists:domains,id',
            'path'      => 'required|string',
            'type'      => 'required|in:core,plugins,themes,all',
        ]);

        $domain = Domain::with('account.server')->findOrFail($validated['domain_id']);

        $response = AgentService::for($domain->account->server)->post('/wordpress/update', [
            'path'     => $validated['path'],
            'username' => $domain->account->username,
            'type'     => $validated['type'],
        ]);

        if ($response && $response->successful()) {
            return redirect()->route('user.wordpress.index')->with('success', __('servers.wordpress_updated', ['domain' => $domain->domain]));
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
        return back()->with('error', __('servers.wordpress_update_failed', ['error' => $error]));
    }
}
