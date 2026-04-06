<?php

namespace App\Http\Controllers;

use App\Models\AlertLog;
use App\Models\AlertRule;
use App\Models\Server;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $servers = Server::all();
        $selectedServer = null;
        $rules = collect();
        $recentLogs = collect();

        if ($request->has('server_id')) {
            $selectedServer = Server::findOrFail($request->server_id);
            $rules = AlertRule::where('server_id', $selectedServer->id)
                ->latest()
                ->get();
            $recentLogs = AlertLog::whereHas('rule', fn ($q) => $q->where('server_id', $selectedServer->id))
                ->latest()
                ->take(20)
                ->get();
        }

        return view('alerts.index', compact('servers', 'selectedServer', 'rules', 'recentLogs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'server_id'        => 'required|exists:servers,id',
            'metric'           => 'required|in:cpu,memory,disk,load',
            'operator'         => 'required|in:>,<',
            'threshold'        => 'required|numeric|min:0|max:100',
            'duration_minutes' => 'required|integer|min:1|max:60',
            'channel'          => 'required|in:email,telegram,slack,discord',
            'channel_value'    => 'required|string|max:500',
        ]);

        AlertRule::create([
            'server_id'        => $validated['server_id'],
            'metric'           => $validated['metric'],
            'operator'         => $validated['operator'],
            'threshold'        => $validated['threshold'],
            'duration_minutes' => $validated['duration_minutes'],
            'channel'          => $validated['channel'],
            'channel_config'   => ['value' => $validated['channel_value']],
            'enabled'          => true,
        ]);

        return redirect()
            ->route('admin.alerts.index', ['server_id' => $validated['server_id']])
            ->with('success', 'Alert rule created.');
    }

    public function toggle(AlertRule $alertRule)
    {
        $alertRule->update(['enabled' => !$alertRule->enabled]);

        return redirect()
            ->route('admin.alerts.index', ['server_id' => $alertRule->server_id])
            ->with('success', 'Alert ' . ($alertRule->enabled ? 'enabled' : 'disabled') . '.');
    }

    public function destroy(AlertRule $alertRule)
    {
        $serverId = $alertRule->server_id;
        $alertRule->delete();

        return redirect()
            ->route('admin.alerts.index', ['server_id' => $serverId])
            ->with('success', 'Alert rule deleted.');
    }
}
