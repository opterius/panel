<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Backup;
use App\Models\Server;
use App\Services\ActivityLogger;
use App\Services\AgentService;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function index(Request $request)
    {
        $servers = Server::all();
        $selectedServer = null;
        $backups = collect();
        $accounts = collect();

        if ($request->has('server_id')) {
            $selectedServer = Server::findOrFail($request->server_id);
        } elseif ($servers->count() === 1) {
            $selectedServer = $servers->first();
        }

        if ($selectedServer) {
            $backups = Backup::where('server_id', $selectedServer->id)->latest()->get();
            $accounts = Account::where('server_id', $selectedServer->id)->get();

            // Sync with agent's backup list
            $response = AgentService::for($selectedServer)->post('/backup/list', []);
            if ($response && $response->successful()) {
                $agentBackups = $response->json('backups') ?? [];
                foreach ($agentBackups as $ab) {
                    if (!$backups->where('filename', $ab['filename'])->first()) {
                        Backup::create([
                            'server_id' => $selectedServer->id,
                            'username'  => $ab['username'] ?? '',
                            'filename'  => $ab['filename'],
                            'type'      => $ab['type'] ?? 'full',
                            'size_mb'   => $ab['size_mb'] ?? 0,
                            'status'    => 'completed',
                        ]);
                    }
                }
                $backups = Backup::where('server_id', $selectedServer->id)->latest()->get();
            }
        }

        return view('backups.index', compact('servers', 'selectedServer', 'backups', 'accounts'));
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'server_id'  => 'required|exists:servers,id',
            'account_id' => 'required|exists:accounts,id',
            'type'       => 'required|in:full,files,databases,email',
        ]);

        $server = Server::findOrFail($validated['server_id']);
        $account = Account::with('domains', 'databases')->findOrFail($validated['account_id']);

        $response = AgentService::for($server)->post('/backup/create', [
            'username'  => $account->username,
            'type'      => $validated['type'],
            'databases' => $account->databases->pluck('name')->toArray(),
            'domains'   => $account->domains->pluck('domain')->toArray(),
        ]);

        if ($response && $response->successful()) {
            $data = $response->json();

            Backup::create([
                'server_id'  => $server->id,
                'account_id' => $account->id,
                'username'   => $account->username,
                'filename'   => $data['filename'] ?? '',
                'type'       => $validated['type'],
                'size_mb'    => $data['size_mb'] ?? 0,
                'status'     => 'completed',
            ]);

            ActivityLogger::log('backup.created', 'account', $account->id, $account->username,
                "Created {$validated['type']} backup for {$account->username} ({$data['size_mb']} MB)");

            return redirect()->route('admin.backups.index', ['server_id' => $server->id])
                ->with('success', __('backups.backup_created', ['username' => $account->username, 'size' => $data['size_mb'], 'duration' => $data['duration']]));
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Agent unreachable';
        return back()->with('error', __('backups.backup_failed', ['error' => $error]));
    }

    public function restore(Request $request, Backup $backup)
    {
        $backup->load('server');

        $response = AgentService::for($backup->server)->post('/backup/restore', [
            'filename' => $backup->filename,
            'username' => $backup->username,
            'type'     => $request->input('type', 'full'),
        ]);

        if ($response && $response->successful()) {
            $restored = $response->json('restored', []);
            ActivityLogger::log('backup.restored', 'account', $backup->account_id, $backup->username,
                "Restored backup {$backup->filename}: " . implode(', ', $restored));

            return redirect()->route('admin.backups.index', ['server_id' => $backup->server_id])
                ->with('success', __('backups.restore_successful', ['items' => implode(', ', $restored)]));
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Agent unreachable';
        return back()->with('error', __('backups.restore_failed', ['error' => $error]));
    }

    public function download(Backup $backup)
    {
        $downloadUrl = $backup->server->agent_url . '/backup/download?filename=' . urlencode($backup->filename);
        return redirect($downloadUrl);
    }

    public function destroy(Backup $backup)
    {
        $backup->load('server');

        AgentService::for($backup->server)->post('/backup/delete', [
            'filename' => $backup->filename,
        ]);

        ActivityLogger::log('backup.deleted', 'account', $backup->account_id, $backup->username,
            "Deleted backup {$backup->filename}");

        $backup->delete();

        return redirect()->route('admin.backups.index', ['server_id' => $backup->server_id])
            ->with('success', __('backups.backup_deleted'));
    }

    /**
     * GET /admin/backups/{backup}/browse?path=...
     * Show the file browser for a single backup at the given path.
     */
    public function browse(Request $request, Backup $backup)
    {
        $backup->load('server');

        $path = $request->input('path', '');

        $response = AgentService::for($backup->server)->post('/backup/browse', [
            'filename' => $backup->filename,
            'path'     => $path,
        ]);

        $entries = [];
        $error   = null;

        if ($response && $response->successful()) {
            $entries = $response->json('entries') ?? [];
        } else {
            $error = $response?->json('error') ?? 'Agent unreachable';
        }

        // Build breadcrumbs
        $crumbs = [];
        if ($path !== '') {
            $accumulated = '';
            foreach (explode('/', trim($path, '/')) as $segment) {
                if ($segment === '') continue;
                $accumulated = ($accumulated === '' ? $segment : "$accumulated/$segment");
                $crumbs[] = ['name' => $segment, 'path' => $accumulated . '/'];
            }
        }

        return view('backups.browse', compact('backup', 'entries', 'path', 'crumbs', 'error'));
    }

    /**
     * POST /admin/backups/{backup}/restore-files
     * Restore individual files from a backup. Files come from a checkbox list
     * in the browse view.
     */
    public function restoreFiles(Request $request, Backup $backup)
    {
        $data = $request->validate([
            'files'         => 'required|array|min:1|max:200',
            'files.*'       => 'string|max:1000',
            'document_root' => 'required|string|max:255',
            'overwrite'     => 'nullable|boolean',
        ]);

        $backup->load('server');

        $response = AgentService::for($backup->server)->post('/backup/restore-files', [
            'filename'      => $backup->filename,
            'username'      => $backup->username,
            'document_root' => $data['document_root'],
            'files'         => $data['files'],
            'overwrite'     => (bool) ($data['overwrite'] ?? false),
        ]);

        if (! $response || ! $response->successful()) {
            $error = $response?->json('error') ?? 'Agent unreachable';
            return back()->with('error', "Restore failed: {$error}");
        }

        $result = $response->json();
        $restored = $result['restored'] ?? 0;
        $skipped  = $result['skipped'] ?? 0;

        ActivityLogger::log('backup.restored_files', 'account', $backup->account_id, $backup->username,
            "Restored {$restored} file(s) from backup {$backup->filename}", ['files' => $data['files']]);

        $msg = "Restored {$restored} file(s).";
        if ($skipped > 0) {
            $msg .= " Skipped {$skipped} (already existed — tick 'overwrite' to replace).";
        }

        return back()->with('success', $msg);
    }
}
