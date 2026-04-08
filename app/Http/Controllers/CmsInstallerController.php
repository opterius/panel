<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CmsInstallerController extends Controller
{
    private const VALID_TYPES = ['joomla', 'drupal', 'magento', 'prestashop'];

    public function index(string $type)
    {
        abort_unless(in_array($type, self::VALID_TYPES), 404);

        $domains = Domain::with('account.server')
            ->whereIn('account_id', auth()->user()->currentAccountIds())
            ->where('status', 'active')
            ->get();

        $sites = [];
        $servers = $domains->pluck('account.server')->unique('id');
        foreach ($servers as $server) {
            $serverDomains = $domains->where('account.server_id', $server->id);
            $username = $serverDomains->first()->account->username ?? '';
            if (!$username) continue;

            $response = AgentService::for($server)->post('/cms/scan', [
                'username' => $username,
                'domains'  => $serverDomains->pluck('domain')->toArray(),
                'type'     => $type,
            ]);

            if ($response && $response->successful()) {
                $sites = array_merge($sites, $response->json('sites') ?? []);
            }
        }

        return view('cms.index', compact('type', 'domains', 'sites'));
    }

    public function create(string $type)
    {
        abort_unless(in_array($type, self::VALID_TYPES), 404);

        $domains = Domain::with('account.server')
            ->whereIn('account_id', auth()->user()->currentAccountIds())
            ->where('status', 'active')
            ->get();

        return view('cms.create', compact('type', 'domains'));
    }

    public function store(Request $request, string $type)
    {
        abort_unless(in_array($type, self::VALID_TYPES), 404);

        $rules = [
            'domain_id'    => 'required|exists:domains,id',
            'install_path' => 'nullable|string|max:50|regex:/^[a-zA-Z0-9_-]*$/',
            'site_name'    => 'required|string|max:200',
            'admin_user'   => ['required', 'string', 'max:30', 'regex:/^[a-zA-Z0-9_]+$/'],
            'admin_pass'   => 'required|string|min:8',
            'admin_email'  => 'required|email',
        ];

        if ($type === 'magento') {
            $rules['magento_public_key']  = 'required|string';
            $rules['magento_private_key'] = 'required|string';
        }

        $validated = $request->validate($rules);

        $domain = Domain::with('account.server')->findOrFail($validated['domain_id']);

        $prefix = substr($type, 0, 2);
        $dbName = $domain->account->prefixDbIdentifier($prefix . '_' . Str::lower(Str::random(8)));
        $dbUser = $domain->account->prefixDbIdentifier($prefix . '_' . Str::lower(Str::random(8)));
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

        // 3. Install CMS
        $payload = [
            'type'          => $type,
            'domain'        => $domain->domain,
            'document_root' => $domain->document_root,
            'username'      => $domain->account->username,
            'install_path'  => $validated['install_path'] ?? '',
            'site_name'     => $validated['site_name'],
            'admin_user'    => $validated['admin_user'],
            'admin_pass'    => $validated['admin_pass'],
            'admin_email'   => $validated['admin_email'],
            'db_name'       => $dbName,
            'db_user'       => $dbUser,
            'db_password'   => $dbPass,
        ];

        if ($type === 'magento') {
            $payload['magento_public_key']  = $validated['magento_public_key'];
            $payload['magento_private_key'] = $validated['magento_private_key'];
        }

        $response = AgentService::for($domain->account->server)->post('/cms/install', $payload);

        if ($response && $response->successful()) {
            $data = $response->json();
            return redirect()->route('user.cms.index', $type)->with('success',
                __('servers.' . $type . '_installed', ['version' => $data['version'] ?? '', 'domain' => $domain->domain])
            );
        }

        // Rollback DB on failure
        AgentService::for($domain->account->server)->post('/databases/user-delete', ['username' => $dbUser]);
        AgentService::for($domain->account->server)->post('/databases/delete', ['name' => $dbName]);

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
        return back()->with('error', __('servers.' . $type . '_install_failed', ['error' => $error]))->withInput();
    }
}
