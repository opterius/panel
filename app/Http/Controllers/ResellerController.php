<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ResellerController extends Controller
{
    public function index()
    {
        $resellers = User::where('role', 'reseller')
            ->withCount('accounts')
            ->latest()
            ->get();

        return view('resellers.index', compact('resellers'));
    }

    public function create()
    {
        return view('resellers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                   => 'required|string|max:255',
            'email'                  => 'required|email|unique:users,email',
            'password'               => 'required|string|min:8',
            'reseller_max_accounts'  => 'required|integer|min:0',
            'reseller_max_disk'      => 'required|integer|min:0',
            'reseller_max_bandwidth' => 'required|integer|min:0',
            'reseller_max_domains'   => 'required|integer|min:0',
            'reseller_max_databases' => 'required|integer|min:0',
            'reseller_max_email'     => 'required|integer|min:0',
            'reseller_acl'           => 'nullable|array',
            'reseller_acl.*'         => 'string',
        ]);

        $validated['role'] = 'reseller';
        $validated['password'] = Hash::make($validated['password']);
        $validated['reseller_acl'] = $validated['reseller_acl'] ?? [];

        $reseller = User::create($validated);

        ActivityLogger::log('reseller.created', 'user', $reseller->id, $reseller->name,
            "Created reseller {$reseller->name} ({$reseller->email})", ['email' => $reseller->email]);

        return redirect()->route('admin.resellers.index')->with('success', __('servers.reseller_created', ['name' => $validated['name']]));
    }

    public function show(User $reseller)
    {
        abort_unless($reseller->isReseller(), 404);

        $reseller->loadCount('accounts');
        $usage = $reseller->resellerUsage();
        $accounts = $reseller->accounts()->with('server', 'domains')->latest()->get();

        return view('resellers.show', compact('reseller', 'usage', 'accounts'));
    }

    public function edit(User $reseller)
    {
        abort_unless($reseller->isReseller(), 404);

        return view('resellers.edit', compact('reseller'));
    }

    public function update(Request $request, User $reseller)
    {
        abort_unless($reseller->isReseller(), 404);

        $validated = $request->validate([
            'name'                   => 'required|string|max:255',
            'email'                  => 'required|email|unique:users,email,' . $reseller->id,
            'password'               => 'nullable|string|min:8',
            'reseller_max_accounts'  => 'required|integer|min:0',
            'reseller_max_disk'      => 'required|integer|min:0',
            'reseller_max_bandwidth' => 'required|integer|min:0',
            'reseller_max_domains'   => 'required|integer|min:0',
            'reseller_max_databases' => 'required|integer|min:0',
            'reseller_max_email'     => 'required|integer|min:0',
            'reseller_acl'           => 'nullable|array',
            'reseller_acl.*'         => 'string',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['reseller_acl'] = $validated['reseller_acl'] ?? [];

        $reseller->update($validated);

        ActivityLogger::log('reseller.updated', 'user', $reseller->id, $reseller->name,
            "Updated reseller {$reseller->name}");

        return redirect()->route('admin.resellers.show', $reseller)->with('success', __('servers.reseller_updated'));
    }

    public function destroy(Request $request, User $reseller)
    {
        abort_unless($reseller->isReseller(), 404);

        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => __('common.password_incorrect')]);
        }

        ActivityLogger::log('reseller.deleted', 'user', $reseller->id, $reseller->name,
            "Deleted reseller {$reseller->name} ({$reseller->email})", ['email' => $reseller->email]);

        $reseller->delete();

        return redirect()->route('admin.resellers.index')->with('success', __('servers.reseller_deleted'));
    }
}
