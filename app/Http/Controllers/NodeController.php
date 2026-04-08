<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\NodeApp;
use App\Services\AgentService;
use Illuminate\Http\Request;

class NodeController extends Controller
{
    public function index()
    {
        $apps = NodeApp::with('account.server', 'domain')
            ->whereIn('account_id', auth()->user()->currentAccountIds())
            ->latest()
            ->get();

        // Fetch live PM2 status for each server that has apps
        $liveStatus = [];
        $serverGroups = $apps->groupBy('server_id');
        foreach ($serverGroups as $serverId => $serverApps) {
            $server = $serverApps->first()->account->server;
            $username = $serverApps->first()->account->username;

            $response = AgentService::for($server)->post('/node/list', [
                'username' => $username,
            ]);

            if ($response && $response->successful()) {
                foreach ($response->json('apps') ?? [] as $liveApp) {
                    $liveStatus[$liveApp['name']] = $liveApp;
                }
            }
        }

        // Sync status from PM2 into our records
        foreach ($apps as $app) {
            $pm2Status = $liveStatus[$app->pm2Name()]['status'] ?? null;
            if ($pm2Status && $pm2Status !== $app->status) {
                $app->update(['status' => in_array($pm2Status, ['online', 'running']) ? 'running' : 'stopped']);
            }
        }

        $domains = Domain::with('account.server')
            ->whereIn('account_id', auth()->user()->currentAccountIds())
            ->where('status', 'active')
            ->get();

        // Get Node.js version info from first available server
        $nodeInfo = ['node' => null, 'npm' => null, 'pm2' => null];
        if ($apps->isNotEmpty()) {
            $firstServer = $apps->first()->account->server;
            $versionResponse = AgentService::for($firstServer)->post('/node/version', []);
            if ($versionResponse && $versionResponse->successful()) {
                $nodeInfo = $versionResponse->json();
            }
        }

        return view('nodejs.index', compact('apps', 'domains', 'nodeInfo'));
    }

    public function create()
    {
        $domains = Domain::with('account.server')
            ->whereIn('account_id', auth()->user()->currentAccountIds())
            ->where('status', 'active')
            ->get();

        return view('nodejs.create', compact('domains'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'domain_id'       => 'required|exists:domains,id',
            'name'            => ['required', 'string', 'max:60', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'startup_command' => 'required|string|max:255',
            'port'            => 'required|integer|min:1024|max:65535',
        ], [
            'name.regex' => 'App name can only contain letters, numbers, hyphens, and underscores.',
        ]);

        $domain = Domain::with('account.server')->findOrFail($validated['domain_id']);
        $account = $domain->account;

        // Default working dir to the domain's document root parent
        $workingDir = dirname($domain->document_root);

        $response = AgentService::for($account->server)->post('/node/app-start', [
            'name'       => $validated['name'],
            'command'    => $validated['startup_command'],
            'working_dir'=> $workingDir,
            'port'       => (int) $validated['port'],
            'username'   => $account->username,
            'domain'     => $domain->domain,
        ]);

        if (!$response || !$response->successful()) {
            $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
            return back()->with('error', 'Failed to start app: ' . $error)->withInput();
        }

        NodeApp::create([
            'server_id'       => $account->server_id,
            'account_id'      => $account->id,
            'domain_id'       => $domain->id,
            'name'            => $validated['name'],
            'startup_command' => $validated['startup_command'],
            'working_dir'     => $workingDir,
            'port'            => $validated['port'],
            'status'          => 'running',
        ]);

        return redirect()->route('user.nodejs.index')->with('success', "App \"{$validated['name']}\" started on port {$validated['port']}.");
    }

    public function show(NodeApp $nodeApp)
    {
        $this->authorizeApp($nodeApp);

        // Fetch live logs
        $logs = '';
        $response = AgentService::for($nodeApp->account->server)->post('/node/app-logs', [
            'name'  => $nodeApp->pm2Name(),
            'lines' => 100,
        ]);
        if ($response && $response->successful()) {
            $logs = $response->json('logs', '');
        }

        return view('nodejs.show', compact('nodeApp', 'logs'));
    }

    public function restart(NodeApp $nodeApp)
    {
        $this->authorizeApp($nodeApp);

        $response = AgentService::for($nodeApp->account->server)->post('/node/app-restart', [
            'name'     => $nodeApp->name,
            'username' => $nodeApp->account->username,
        ]);

        if ($response && $response->successful()) {
            $nodeApp->update(['status' => 'running']);
            return back()->with('success', 'App restarted.');
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Agent unreachable';
        return back()->with('error', 'Restart failed: ' . $error);
    }

    public function stop(NodeApp $nodeApp)
    {
        $this->authorizeApp($nodeApp);

        $response = AgentService::for($nodeApp->account->server)->post('/node/app-stop', [
            'name'     => $nodeApp->name,
            'username' => $nodeApp->account->username,
        ]);

        if ($response && $response->successful()) {
            $nodeApp->update(['status' => 'stopped']);
            return back()->with('success', 'App stopped.');
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Agent unreachable';
        return back()->with('error', 'Stop failed: ' . $error);
    }

    public function destroy(NodeApp $nodeApp)
    {
        $this->authorizeApp($nodeApp);

        $response = AgentService::for($nodeApp->account->server)->post('/node/app-delete', [
            'name'     => $nodeApp->name,
            'username' => $nodeApp->account->username,
        ]);

        // Delete from panel even if agent fails (app may have been deleted manually)
        $nodeApp->delete();

        if (!$response || !$response->successful()) {
            return redirect()->route('user.nodejs.index')->with('error', 'App record removed from panel, but PM2 deletion may have failed.');
        }

        return redirect()->route('user.nodejs.index')->with('success', 'App deleted.');
    }

    private function authorizeApp(NodeApp $nodeApp): void
    {
        abort_unless(
            in_array($nodeApp->account_id, auth()->user()->currentAccountIds()),
            403
        );
    }
}
