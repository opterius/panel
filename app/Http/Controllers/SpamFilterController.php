<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Services\AgentService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class SpamFilterController extends Controller
{
    public function index(Request $request)
    {
        $servers = Server::all();
        $selectedServer = null;
        $status = null;

        if ($request->has('server_id')) {
            $selectedServer = Server::findOrFail($request->server_id);
            $response = AgentService::for($selectedServer)->post('/rspamd/status', []);
            if ($response && $response->successful()) {
                $status = $response->json();
            }
        }

        return view('spam-filter.index', compact('servers', 'selectedServer', 'status'));
    }

    public function configure(Request $request)
    {
        $validated = $request->validate([
            'server_id'     => 'required|exists:servers,id',
            'action'        => 'required|in:enable,disable,configure',
            'reject_score'  => 'nullable|numeric|min:1|max:100',
            'add_header'    => 'nullable|numeric|min:1|max:100',
            'greylist_score' => 'nullable|numeric|min:1|max:100',
        ]);

        $server = Server::findOrFail($validated['server_id']);

        $response = AgentService::for($server)->post('/rspamd/configure', [
            'action'        => $validated['action'],
            'reject_score'  => (float) ($validated['reject_score'] ?? 15),
            'add_header'    => (float) ($validated['add_header'] ?? 6),
            'greylist_score' => (float) ($validated['greylist_score'] ?? 4),
        ]);

        if ($response && $response->successful()) {
            $action = $validated['action'];
            ActivityLogger::log("rspamd.{$action}", 'server', $server->id, $server->name,
                ucfirst($action) . "d Rspamd on {$server->name}");

            return back()->with('success', 'Spam filter ' . $action . 'd successfully.');
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Agent unreachable';
        return back()->with('error', 'Failed: ' . $error);
    }
}
