<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\IpAddress;
use App\Models\Server;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class IpAddressController extends Controller
{
    public function index(Request $request)
    {
        $servers = Server::all();
        $selectedServer = null;
        $ipAddresses = collect();

        if ($request->has('server_id')) {
            $selectedServer = Server::findOrFail($request->server_id);
            $ipAddresses = IpAddress::with('account')
                ->where('server_id', $selectedServer->id)
                ->orderBy('ip_address')
                ->get();
        }

        return view('ip-addresses.index', compact('servers', 'selectedServer', 'ipAddresses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'server_id'  => 'required|exists:servers,id',
            'ip_address' => 'required|ip',
            'type'       => 'required|in:shared,dedicated',
            'note'       => 'nullable|string|max:255',
        ]);

        $exists = IpAddress::where('server_id', $validated['server_id'])
            ->where('ip_address', $validated['ip_address'])
            ->exists();

        if ($exists) {
            return back()->with('error', __('ip_addresses.ip_already_registered'))->withInput();
        }

        IpAddress::create($validated);

        ActivityLogger::log('ip.created', 'server', $validated['server_id'], $validated['ip_address'],
            "Added IP {$validated['ip_address']} ({$validated['type']})");

        return back()->with('success', __('ip_addresses.ip_added', ['ip' => $validated['ip_address']]));
    }

    public function assign(Request $request, IpAddress $ipAddress)
    {
        $validated = $request->validate([
            'account_id' => 'nullable|exists:accounts,id',
        ]);

        $ipAddress->update(['account_id' => $validated['account_id'] ?: null]);

        $label = $validated['account_id']
            ? 'Assigned to ' . Account::find($validated['account_id'])->username
            : 'Unassigned';

        ActivityLogger::log('ip.assigned', 'server', $ipAddress->server_id, $ipAddress->ip_address, $label);

        return back()->with('success', __('ip_addresses.ip_assigned', ['ip' => $ipAddress->ip_address, 'label' => $label]));
    }

    public function destroy(IpAddress $ipAddress)
    {
        ActivityLogger::log('ip.deleted', 'server', $ipAddress->server_id, $ipAddress->ip_address,
            "Removed IP {$ipAddress->ip_address}");

        $ipAddress->delete();

        return back()->with('success', __('ip_addresses.ip_removed', ['ip' => $ipAddress->ip_address]));
    }
}
