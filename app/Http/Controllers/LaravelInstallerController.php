<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LaravelInstallerController extends Controller
{
    public function index()
    {
        $domains = Domain::with('account.server')
            ->whereHas('account', fn ($q) => $q->where('user_id', Auth::id()))
            ->where('status', 'active')
            ->get();

        // Scan for existing Laravel installations
        $sites = [];
        $servers = $domains->pluck('account.server')->unique('id');
        foreach ($servers as $server) {
            $serverDomains = $domains->where('account.server_id', $server->id);
            $username = $serverDomains->first()->account->username ?? '';
            if (!$username) continue;

            $response = AgentService::for($server)->post('/laravel/scan', [
                'username' => $username,
                'domains'  => $serverDomains->pluck('domain')->toArray(),
            ]);

            if ($response && $response->successful()) {
                $sites = array_merge($sites, $response->json('sites') ?? []);
            }
        }

        return view('laravel-installer.index', compact('domains', 'sites'));
    }

    public function create()
    {
        $domains = Domain::with('account.server')
            ->whereHas('account', fn ($q) => $q->where('user_id', Auth::id()))
            ->where('status', 'active')
            ->get();

        return view('laravel-installer.create', compact('domains'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'domain_id'     => 'required|exists:domains,id',
            'install_path'  => 'nullable|string|max:50|regex:/^[a-zA-Z0-9_-]*$/',
            'version'       => 'required|in:latest,13,12,11,10',
            'app_name'      => 'required|string|max:100',
            'app_env'       => 'required|in:production,local,staging',
            'db_mode'       => 'required|in:auto,manual',
            'db_name'       => 'nullable|required_if:db_mode,manual|string|max:64',
            'db_user'       => 'nullable|required_if:db_mode,manual|string|max:32',
            'db_password'   => 'nullable|required_if:db_mode,manual|string',
        ]);

        $domain = Domain::with('account.server')->findOrFail($validated['domain_id']);

        // Auto or manual database
        if ($validated['db_mode'] === 'auto') {
            $dbName = 'laravel_' . Str::lower(Str::random(8));
            $dbUser = 'laravel_' . Str::lower(Str::random(8));
            $dbPass = Str::random(24);

            // Create database
            $response = AgentService::for($domain->account->server)->post('/databases/create', [
                'name' => $dbName,
            ]);
            if (!$response || !$response->successful()) {
                return back()->with('error', 'Failed to create database.')->withInput();
            }

            // Create user
            $response = AgentService::for($domain->account->server)->post('/databases/user-create', [
                'username' => $dbUser,
                'password' => $dbPass,
                'database' => $dbName,
                'host'     => 'localhost',
            ]);
            if (!$response || !$response->successful()) {
                AgentService::for($domain->account->server)->post('/databases/delete', ['name' => $dbName]);
                return back()->with('error', 'Failed to create database user.')->withInput();
            }
        } else {
            $dbName = $validated['db_name'];
            $dbUser = $validated['db_user'];
            $dbPass = $validated['db_password'];
        }

        // Build app URL
        $appURL = 'http://' . $domain->domain;
        if (!empty($validated['install_path'])) {
            $appURL .= '/' . $validated['install_path'];
        }

        // Install Laravel
        $response = AgentService::for($domain->account->server)->post('/laravel/install', [
            'domain'        => $domain->domain,
            'document_root' => $domain->document_root,
            'username'      => $domain->account->username,
            'install_path'  => $validated['install_path'] ?? '',
            'version'       => $validated['version'],
            'db_name'       => $dbName,
            'db_user'       => $dbUser,
            'db_password'   => $dbPass,
            'app_name'      => $validated['app_name'],
            'app_url'       => $appURL,
            'app_env'       => $validated['app_env'],
        ]);

        if ($response && $response->successful()) {
            $data = $response->json();
            return redirect()->route('user.laravel.index')->with('success',
                'Laravel ' . ($data['version'] ?? '') . ' installed on ' . $domain->domain
            );
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
        return back()->with('error', 'Laravel installation failed: ' . $error)->withInput();
    }
}
