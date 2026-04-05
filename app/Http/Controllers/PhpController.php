<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Server;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PhpController extends Controller
{
    public function index(Request $request)
    {
        $servers = Server::all();
        $selectedServer = null;
        $versions = [];
        $domains = collect();

        if ($request->has('server_id')) {
            $selectedServer = Server::findOrFail($request->server_id);

            // Get installed PHP versions from agent
            $response = AgentService::for($selectedServer)->post('/php/list-versions', []);
            if ($response && $response->successful()) {
                $versions = $response->json('versions', []);
            }

            // Get domains on this server
            $domains = Domain::with('account')
                ->where('server_id', $selectedServer->id)
                ->where('status', 'active')
                ->get();
        }

        // Get extensions for the selected PHP version
        $extensions = [];
        $selectedVersion = $request->get('php_version', '');
        if ($selectedServer && $selectedVersion) {
            $response = AgentService::for($selectedServer)->post('/php/list-extensions', [
                'version' => $selectedVersion,
            ]);
            if ($response && $response->successful()) {
                $extensions = $response->json('extensions', []);
            }
        }

        return view('php.index', compact('servers', 'selectedServer', 'versions', 'domains', 'extensions', 'selectedVersion'));
    }

    public function toggleExtension(Request $request)
    {
        $validated = $request->validate([
            'server_id' => 'required|exists:servers,id',
            'version'   => 'required|string|regex:/^\d+\.\d+$/',
            'extension' => 'required|string|max:50',
            'enable'    => 'required|boolean',
        ]);

        $server = Server::findOrFail($validated['server_id']);

        $response = AgentService::for($server)->post('/php/extensions', [
            'version'   => $validated['version'],
            'extension' => $validated['extension'],
            'enable'    => (bool) $validated['enable'],
        ]);

        $action = $validated['enable'] ? 'enabled' : 'disabled';

        if ($response && $response->successful()) {
            return redirect()
                ->route('admin.php.index', ['server_id' => $server->id, 'php_version' => $validated['version']])
                ->with('success', ucfirst($validated['extension']) . ' ' . $action . ' for PHP ' . $validated['version']);
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return back()->with('error', 'Failed to ' . $action . ' extension: ' . $error);
    }

    public function install(Request $request)
    {
        $validated = $request->validate([
            'server_id' => 'required|exists:servers,id',
            'version'   => 'required|string|regex:/^\d+\.\d+$/',
        ]);

        $server = Server::findOrFail($validated['server_id']);

        $response = AgentService::for($server)->post('/php/install', [
            'version' => $validated['version'],
        ]);

        if ($response && $response->successful()) {
            return redirect()
                ->route('admin.php.index', ['server_id' => $server->id])
                ->with('success', 'PHP ' . $validated['version'] . ' installed successfully.');
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return back()->with('error', 'PHP installation failed: ' . $error);
    }

    public function switchVersion(Request $request)
    {
        $validated = $request->validate([
            'domain_id'   => 'required|exists:domains,id',
            'new_version' => 'required|string|regex:/^\d+\.\d+$/',
        ]);

        $domain = Domain::with('account.server')->findOrFail($validated['domain_id']);
        $oldVersion = $domain->php_version;

        $response = AgentService::for($domain->account->server)->post('/php/switch-version', [
            'domain'      => $domain->domain,
            'username'    => $domain->account->username,
            'old_version' => $oldVersion,
            'new_version' => $validated['new_version'],
        ]);

        if ($response && $response->successful()) {
            $domain->update(['php_version' => $validated['new_version']]);

            return redirect()
                ->route('admin.php.index', ['server_id' => $domain->server_id])
                ->with('success', $domain->domain . ' switched from PHP ' . $oldVersion . ' to PHP ' . $validated['new_version']);
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return back()->with('error', 'PHP switch failed: ' . $error);
    }

    public function config(Request $request)
    {
        $validated = $request->validate([
            'domain_id'            => 'required|exists:domains,id',
            'memory_limit'         => 'nullable|string|max:10',
            'upload_max_filesize'  => 'nullable|string|max:10',
            'post_max_size'        => 'nullable|string|max:10',
            'max_execution_time'   => 'nullable|integer|min:0|max:3600',
            'display_errors'       => 'nullable|in:On,Off',
        ]);

        $domain = Domain::with('account.server')->findOrFail($validated['domain_id']);

        $values = [];
        foreach (['memory_limit', 'upload_max_filesize', 'post_max_size', 'max_execution_time', 'display_errors'] as $key) {
            if (!empty($validated[$key])) {
                $values[$key] = (string) $validated[$key];
            }
        }

        if (empty($values)) {
            return back()->with('error', 'No configuration values provided.');
        }

        $response = AgentService::for($domain->account->server)->post('/php/config', [
            'domain'  => $domain->domain,
            'version' => $domain->php_version,
            'values'  => $values,
        ]);

        if ($response && $response->successful()) {
            return redirect()
                ->route('admin.php.index', ['server_id' => $domain->server_id])
                ->with('success', 'PHP configuration updated for ' . $domain->domain);
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return back()->with('error', 'PHP config update failed: ' . $error);
    }
}
