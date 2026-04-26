<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Services\ActivityLogger;
use App\Services\AgentService;
use Illuminate\Http\Request;

class PanelHostnameController extends Controller
{
    public function index(Request $request)
    {
        // Use the URL the user is actually visiting, not the configured APP_URL
        // (which can be stale or wrong on fresh installs).
        $currentUrl  = $request->getSchemeAndHttpHost();
        $currentHost = $request->getHost();
        $currentPort = $request->getPort() ?: 8443;
        $isIpBased   = (bool) filter_var($currentHost, FILTER_VALIDATE_IP);

        // Show a hint when the configured APP_URL drifts from the live URL.
        $configuredUrl = rtrim((string) config('app.url'), '/');
        $configMismatch = $configuredUrl !== '' && $configuredUrl !== rtrim($currentUrl, '/');

        return view('admin.panel-hostname', compact(
            'currentUrl', 'currentHost', 'currentPort', 'isIpBased', 'configuredUrl', 'configMismatch'
        ));
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

            // Redirect to a standalone success page on the OLD URL — the browser
            // is still on the old hostname/IP, and the new vhost no longer
            // answers there. Sending the user back to the admin route would
            // produce ERR_EMPTY_RESPONSE. The success page has a big button +
            // auto-redirect to the new URL.
            return view('admin.panel-hostname-success', [
                'newUrl'   => $newUrl,
                'hostname' => $validated['hostname'],
            ]);
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
        return back()->withInput()->with('error', __('panel_hostname.failed', ['error' => $error]));
    }
}
