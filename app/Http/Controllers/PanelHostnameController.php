<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Services\ActivityLogger;
use App\Services\AgentService;
use Illuminate\Http\Request;

class PanelHostnameController extends Controller
{
    public function index()
    {
        $currentUrl = config('app.url');
        $currentHost = parse_url($currentUrl, PHP_URL_HOST) ?: '';
        $currentPort = parse_url($currentUrl, PHP_URL_PORT) ?: 8443;
        $isIpBased = (bool) filter_var($currentHost, FILTER_VALIDATE_IP);

        return view('admin.panel-hostname', compact('currentUrl', 'currentHost', 'currentPort', 'isIpBased'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'hostname' => ['required', 'regex:/^([a-zA-Z0-9]([a-zA-Z0-9\-]*[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/'],
            'email'    => ['required', 'email'],
        ]);

        $server = Server::first();
        if (!$server) {
            return back()->with('error', __('panel_hostname.no_server'));
        }

        // Use long timeout — certbot can take 30-60 s and we don't want to time out mid-issue.
        $response = AgentService::for($server)->postLong('/panel/set-hostname', [
            'hostname' => strtolower($validated['hostname']),
            'email'    => $validated['email'],
        ], 180);

        if ($response && $response->successful()) {
            $newUrl = $response->json('url');

            ActivityLogger::log('panel.hostname_changed', 'panel', 0, $validated['hostname'],
                "Panel hostname set to {$validated['hostname']}", [
                    'hostname' => $validated['hostname'],
                    'url'      => $newUrl,
                ]);

            return redirect()->route('admin.panel-hostname.index')
                ->with('success', __('panel_hostname.success', ['url' => $newUrl]));
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
        return back()->withInput()->with('error', __('panel_hostname.failed', ['error' => $error]));
    }
}
