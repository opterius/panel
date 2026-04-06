<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Domain;
use App\Models\Package;
use App\Models\Server;
use App\Services\AgentService;
use App\Services\ProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhmcsApiController extends Controller
{
    public function __construct(
        private ProvisioningService $provisioning,
    ) {}

    /**
     * Test connection - used by WHMCS "Test Connection" button.
     */
    public function testConnection(Request $request): JsonResponse
    {
        return response()->json([
            'result'  => 'success',
            'message' => 'Connection successful.',
            'data'    => [
                'panel'   => 'Opterius Panel',
                'version' => config('opterius.version', '1.0.0'),
            ],
        ]);
    }

    /**
     * Create a hosting account.
     */
    public function createAccount(Request $request): JsonResponse
    {
        $apiKey = $request->attributes->get('api_key');

        if (!$apiKey->hasPermission('account.create')) {
            return $this->error('Insufficient permissions.', 403);
        }

        $validated = $request->validate([
            'domain'           => 'required|string|max:255',
            'username'         => 'required|string|max:32|alpha_dash',
            'password'         => 'required|string|min:8',
            'package'          => 'required|string|max:255',
            'server_id'        => 'nullable|integer|exists:servers,id',
            'client_email'     => 'required|email|max:255',
            'client_name'      => 'required|string|max:255',
            'whmcs_service_id' => 'nullable|integer',
            'whmcs_client_id'  => 'nullable|integer',
        ]);

        // Resolve package by name
        $package = Package::where('name', $validated['package'])->first();
        if (!$package) {
            $available = Package::pluck('name')->implode(', ');
            return $this->error("Package '{$validated['package']}' not found. Available: {$available}", 404);
        }

        // Resolve server: API key scope > request param > auto-assign
        $serverId = $apiKey->server_id ?? $validated['server_id'] ?? null;
        if (!$serverId) {
            // Auto-assign: pick server with fewest accounts
            $server = Server::where('status', 'online')
                ->withCount('accounts')
                ->orderBy('accounts_count')
                ->first();

            if (!$server) {
                return $this->error('No available servers.', 422);
            }
            $serverId = $server->id;
        }

        // Find or create the panel user
        $user = $this->provisioning->findOrCreateUser(
            $validated['client_email'],
            $validated['client_name'],
            $validated['password'],
        );

        $result = $this->provisioning->createAccount([
            'server_id'        => $serverId,
            'username'         => $validated['username'],
            'domain'           => $validated['domain'],
            'package_id'       => $package->id,
            'owner_user_id'    => $user->id,
            'whmcs_service_id' => $validated['whmcs_service_id'] ?? null,
            'whmcs_client_id'  => $validated['whmcs_client_id'] ?? null,
            'created_via'      => 'whmcs',
        ]);

        if ($result['success']) {
            return response()->json([
                'result'  => 'success',
                'message' => "Account {$validated['username']} created with domain {$validated['domain']}.",
                'data'    => [
                    'account_id' => $result['account']->id,
                    'username'   => $result['account']->username,
                    'domain'     => $validated['domain'],
                    'server_ip'  => $result['account']->server->ip_address ?? null,
                ],
            ]);
        }

        // Account was created in DB but server setup failed
        if ($result['account']) {
            return response()->json([
                'result'  => 'warning',
                'message' => $result['error'],
                'data'    => ['account_id' => $result['account']->id],
            ], 200);
        }

        $status = str_contains($result['error'], 'already exists') ? 409 : 422;
        return $this->error($result['error'], $status);
    }

    /**
     * Suspend an account.
     */
    public function suspendAccount(Request $request): JsonResponse
    {
        $apiKey = $request->attributes->get('api_key');

        if (!$apiKey->hasPermission('account.suspend')) {
            return $this->error('Insufficient permissions.', 403);
        }

        $account = $this->findAccount($request);
        if (!$account) {
            return $this->error('Account not found.', 404);
        }

        if ($account->suspended) {
            return response()->json(['result' => 'success', 'message' => 'Account is already suspended.']);
        }

        $result = $this->provisioning->suspendAccount($account, $request->input('reason', 'Suspended by billing system'));

        return $result['success']
            ? response()->json(['result' => 'success', 'message' => "Account {$account->username} suspended."])
            : $this->error($result['error'], 500);
    }

    /**
     * Unsuspend an account.
     */
    public function unsuspendAccount(Request $request): JsonResponse
    {
        $apiKey = $request->attributes->get('api_key');

        if (!$apiKey->hasPermission('account.unsuspend')) {
            return $this->error('Insufficient permissions.', 403);
        }

        $account = $this->findAccount($request);
        if (!$account) {
            return $this->error('Account not found.', 404);
        }

        if (!$account->suspended) {
            return response()->json(['result' => 'success', 'message' => 'Account is already active.']);
        }

        $result = $this->provisioning->unsuspendAccount($account);

        return $result['success']
            ? response()->json(['result' => 'success', 'message' => "Account {$account->username} unsuspended."])
            : $this->error($result['error'], 500);
    }

    /**
     * Terminate (delete) an account.
     */
    public function terminateAccount(Request $request): JsonResponse
    {
        $apiKey = $request->attributes->get('api_key');

        if (!$apiKey->hasPermission('account.terminate')) {
            return $this->error('Insufficient permissions.', 403);
        }

        $account = $this->findAccount($request);
        if (!$account) {
            return $this->error('Account not found.', 404);
        }

        $username = $account->username;
        $result = $this->provisioning->terminateAccount($account);

        return $result['success']
            ? response()->json(['result' => 'success', 'message' => "Account {$username} terminated."])
            : $this->error($result['error'], 500);
    }

    /**
     * Change the system password for an account.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $apiKey = $request->attributes->get('api_key');

        if (!$apiKey->hasPermission('account.password')) {
            return $this->error('Insufficient permissions.', 403);
        }

        $request->validate(['password' => 'required|string|min:8']);

        $account = $this->findAccount($request);
        if (!$account) {
            return $this->error('Account not found.', 404);
        }

        $result = $this->provisioning->changePassword($account, $request->input('password'));

        return $result['success']
            ? response()->json(['result' => 'success', 'message' => "Password changed for {$account->username}."])
            : $this->error($result['error'], 500);
    }

    /**
     * Change the package for an account.
     */
    public function changePackage(Request $request): JsonResponse
    {
        $apiKey = $request->attributes->get('api_key');

        if (!$apiKey->hasPermission('account.package')) {
            return $this->error('Insufficient permissions.', 403);
        }

        $request->validate(['package' => 'required|string|max:255']);

        $account = $this->findAccount($request);
        if (!$account) {
            return $this->error('Account not found.', 404);
        }

        $package = Package::where('name', $request->input('package'))->first();
        if (!$package) {
            return $this->error("Package '{$request->input('package')}' not found.", 404);
        }

        $result = $this->provisioning->changePackage($account, $package);

        return $result['success']
            ? response()->json(['result' => 'success', 'message' => "Package changed to {$package->name} for {$account->username}."])
            : $this->error($result['error'], 500);
    }

    /**
     * List available packages.
     */
    public function listPackages(): JsonResponse
    {
        $packages = Package::all()->map(fn ($p) => [
            'name'        => $p->name,
            'disk_quota'  => $p->diskQuotaLabel(),
            'bandwidth'   => $p->bandwidthLabel(),
            'php_default' => $p->default_php_version,
        ]);

        return response()->json(['result' => 'success', 'data' => $packages]);
    }

    /**
     * Get account usage stats.
     */
    public function getUsage(Request $request): JsonResponse
    {
        $apiKey = $request->attributes->get('api_key');

        $account = $this->findAccount($request);
        if (!$account) {
            return $this->error('Account not found.', 404);
        }

        $account->load('server', 'domains', 'databases');

        $stats = null;
        $response = AgentService::for($account->server)->post('/stats/account', [
            'username'  => $account->username,
            'domains'   => $account->domains->pluck('domain')->toArray(),
            'databases' => $account->databases->pluck('name')->toArray(),
        ]);

        if ($response && $response->successful()) {
            $stats = $response->json('stats');
        }

        return response()->json([
            'result' => 'success',
            'data'   => [
                'username'     => $account->username,
                'domain'       => $account->domains->first()?->domain,
                'suspended'    => $account->suspended,
                'disk_used_mb' => $stats['disk_usage']['total_mb'] ?? 0,
                'disk_limit'   => $account->disk_quota,
                'bw_used_mb'   => $stats['bandwidth']['total_mb'] ?? 0,
            ],
        ]);
    }

    /**
     * Lookup an account by username or domain.
     */
    private function findAccount(Request $request): ?Account
    {
        if ($request->filled('username')) {
            return Account::where('username', $request->input('username'))->first();
        }

        if ($request->filled('domain')) {
            $domain = Domain::where('domain', $request->input('domain'))->whereNull('parent_id')->first();
            return $domain?->account;
        }

        return null;
    }

    private function error(string $message, int $status = 400): JsonResponse
    {
        return response()->json(['result' => 'error', 'message' => $message], $status);
    }
}
