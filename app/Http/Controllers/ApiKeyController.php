<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\Server;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiKeyController extends Controller
{
    public function index()
    {
        $apiKeys = ApiKey::with('user', 'server')->latest()->get();

        return view('api-keys.index', compact('apiKeys'));
    }

    public function create()
    {
        $servers = Server::all();

        $permissions = [
            'account.create'    => 'Create accounts',
            'account.suspend'   => 'Suspend accounts',
            'account.unsuspend' => 'Unsuspend accounts',
            'account.terminate' => 'Terminate (delete) accounts',
            'account.password'  => 'Change account passwords',
            'account.package'   => 'Change account packages',
        ];

        return view('api-keys.create', compact('servers', 'permissions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'server_id'     => 'nullable|exists:servers,id',
            'permissions'    => 'required|array|min:1',
            'permissions.*'  => 'string',
            'allowed_ips'   => 'nullable|string|max:1000',
        ]);

        // Parse IP whitelist
        $allowedIps = null;
        if (!empty($validated['allowed_ips'])) {
            $allowedIps = array_filter(
                array_map('trim', preg_split('/[\s,]+/', $validated['allowed_ips'])),
                fn ($ip) => filter_var($ip, FILTER_VALIDATE_IP)
            );
            $allowedIps = array_values($allowedIps) ?: null;
        }

        [$apiKey, $plaintext] = ApiKey::generate([
            'user_id'     => auth()->id(),
            'server_id'   => $validated['server_id'] ?: null,
            'name'        => $validated['name'],
            'permissions' => $validated['permissions'],
            'allowed_ips' => $allowedIps,
        ]);

        ActivityLogger::log('api_key.created', 'api_key', $apiKey->id, $apiKey->name,
            "Created API key '{$apiKey->name}'");

        return view('api-keys.show-key', [
            'apiKey'    => $apiKey,
            'plaintext' => $plaintext,
        ]);
    }

    public function destroy(Request $request, ApiKey $apiKey)
    {
        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => __('common.password_incorrect')]);
        }

        ActivityLogger::log('api_key.revoked', 'api_key', $apiKey->id, $apiKey->name,
            "Revoked API key '{$apiKey->name}'");

        $apiKey->delete();

        return redirect()->route('admin.api-keys.index')->with('success', __('api.api_key_revoked', ['name' => $apiKey->name]));
    }
}
