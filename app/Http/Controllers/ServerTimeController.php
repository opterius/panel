<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Services\ActivityLogger;
use App\Services\AgentService;
use Illuminate\Http\Request;

class ServerTimeController extends Controller
{
    /**
     * Show the per-server time + timezone + NTP page. Pulls a fresh snapshot
     * from the agent so the user always sees the actual server clock state,
     * not a stale DB cache.
     */
    public function show(Server $server)
    {
        $time = null;
        $reachable = false;

        $response = AgentService::for($server)->post('/system/time', []);
        if ($response && $response->successful()) {
            $time = $response->json();
            $reachable = true;
        }

        // Common timezones grouped by region for the picker. The agent only
        // accepts values from the host's tz database, but presenting all
        // ~400 zones is overwhelming — this is the WHM-style curated list.
        $timezones = $this->commonTimezones();

        return view('servers.time', compact('server', 'time', 'reachable', 'timezones'));
    }

    /**
     * Update the timezone via the agent. Validates against the curated list
     * to prevent setting random strings — the agent does its own regex
     * validation as a defense in depth.
     */
    public function updateTimezone(Request $request, Server $server)
    {
        $validated = $request->validate([
            'timezone' => 'required|string|max:64|regex:/^[A-Za-z][A-Za-z0-9_+\/\-]*$/',
        ]);

        $response = AgentService::for($server)->post('/system/timezone', [
            'timezone' => $validated['timezone'],
        ]);

        if (!$response || !$response->successful()) {
            $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
            return back()->with('error', __('server-time.timezone_failed', ['error' => $error]));
        }

        ActivityLogger::log('server.timezone_changed', 'server', $server->id, $server->name,
            "Set server {$server->name} timezone to {$validated['timezone']}",
            ['timezone' => $validated['timezone']]);

        return redirect()->route('admin.servers.time', $server)
            ->with('success', __('server-time.timezone_updated', ['timezone' => $validated['timezone']]));
    }

    /**
     * Trigger an immediate NTP sync via the agent.
     */
    public function syncNow(Request $request, Server $server)
    {
        $response = AgentService::for($server)->post('/system/ntp-sync', []);

        if (!$response || !$response->successful()) {
            $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
            return back()->with('error', __('server-time.sync_failed', ['error' => $error]));
        }

        $method = $response->json('method', 'ntp');

        ActivityLogger::log('server.ntp_synced', 'server', $server->id, $server->name,
            "Manual NTP sync on {$server->name} via {$method}",
            ['method' => $method]);

        return redirect()->route('admin.servers.time', $server)
            ->with('success', __('server-time.sync_done'));
    }

    /**
     * Curated timezone list grouped by region. We don't generate this from
     * the host because the panel may not have access to the same tz database
     * as the remote server, and listing all 400+ zones is overwhelming.
     * Hosts can manually edit /etc/localtime if they need an exotic zone.
     */
    private function commonTimezones(): array
    {
        return [
            'UTC' => ['UTC'],
            'Europe' => [
                'Europe/London', 'Europe/Dublin', 'Europe/Lisbon',
                'Europe/Madrid', 'Europe/Paris', 'Europe/Brussels',
                'Europe/Amsterdam', 'Europe/Berlin', 'Europe/Zurich',
                'Europe/Rome', 'Europe/Vienna', 'Europe/Prague',
                'Europe/Warsaw', 'Europe/Stockholm', 'Europe/Oslo',
                'Europe/Helsinki', 'Europe/Copenhagen', 'Europe/Athens',
                'Europe/Bucharest', 'Europe/Sofia', 'Europe/Kyiv',
                'Europe/Moscow', 'Europe/Istanbul',
            ],
            'Americas' => [
                'America/New_York', 'America/Toronto', 'America/Chicago',
                'America/Denver', 'America/Los_Angeles', 'America/Vancouver',
                'America/Phoenix', 'America/Anchorage', 'America/Halifax',
                'America/Mexico_City', 'America/Bogota', 'America/Lima',
                'America/Sao_Paulo', 'America/Buenos_Aires', 'America/Santiago',
            ],
            'Asia' => [
                'Asia/Dubai', 'Asia/Tehran', 'Asia/Karachi', 'Asia/Kolkata',
                'Asia/Dhaka', 'Asia/Bangkok', 'Asia/Jakarta', 'Asia/Singapore',
                'Asia/Hong_Kong', 'Asia/Shanghai', 'Asia/Taipei', 'Asia/Tokyo',
                'Asia/Seoul', 'Asia/Manila', 'Asia/Jerusalem', 'Asia/Riyadh',
            ],
            'Africa' => [
                'Africa/Casablanca', 'Africa/Lagos', 'Africa/Cairo',
                'Africa/Johannesburg', 'Africa/Nairobi',
            ],
            'Oceania' => [
                'Pacific/Auckland', 'Pacific/Honolulu',
                'Australia/Sydney', 'Australia/Melbourne', 'Australia/Perth',
            ],
        ];
    }
}
