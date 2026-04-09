<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessCpanelMigration;
use App\Models\CpanelMigration;
use App\Services\ActivityLogger;
use App\Services\AgentService;
use App\Services\MigrationService;
use Illuminate\Http\Request;

/**
 * Customer-facing cPanel migration.
 *
 * Differences from the admin version:
 *  - The user can only import INTO their own existing account (no username
 *    selection, no package selection — those are fixed by the user's account).
 *  - The backup file is uploaded to a known temp directory inside the user's
 *    home, then parsed in place.
 *  - The user can only see migrations they initiated themselves.
 */
class MigrationController extends Controller
{
    public function __construct(
        private MigrationService $migrationService,
    ) {}

    /**
     * GET /user/import — list the user's previous import attempts.
     */
    public function index()
    {
        $account = auth()->user()->currentAccount();

        $migrations = CpanelMigration::with('server')
            ->where('initiated_by', auth()->id())
            ->latest()
            ->limit(20)
            ->get();

        return view('user.migrations.index', compact('migrations', 'account'));
    }

    /**
     * GET /user/import/upload — show the upload form.
     */
    public function create()
    {
        $account = auth()->user()->currentAccount();

        if (! $account) {
            return redirect()->route('user.dashboard')
                ->with('error', 'You need an active hosting account before importing.');
        }

        return view('user.migrations.create', compact('account'));
    }

    /**
     * POST /user/import — upload the backup file and start parsing.
     *
     * The file is uploaded directly to the agent which writes it under
     * /home/{user}/_imports/ — that path is then handed to the parse step.
     */
    public function store(Request $request)
    {
        $request->validate([
            'backup' => 'required|file|max:5242880|mimes:gz,tgz,tar,zip', // 5GB cap, common cPanel formats
        ]);

        $account = auth()->user()->currentAccount();
        if (! $account) {
            return back()->with('error', 'No active hosting account.');
        }

        $file = $request->file('backup');
        $importDir = "/home/{$account->username}/_imports";

        // Make sure the destination directory exists. The agent's mkdir
        // endpoint creates it under the user's home with safe permissions.
        AgentService::for($account->server)->post('/files/mkdir', [
            'username' => $account->username,
            'path'     => '_imports',
        ]);

        // Upload the file to the agent via multipart. The agent saves it as
        // {importDir}/{originalFilename}.
        $uploadResp = AgentService::for($account->server)->upload('/files/upload', [
            'username' => $account->username,
            'path'     => $importDir,
        ], $file);

        if (! $uploadResp || ! $uploadResp->successful()) {
            return back()->with('error', 'Failed to upload backup file: ' .
                ($uploadResp?->json('error') ?? 'agent unreachable'));
        }

        // The agent returns the full path of the saved file in the response.
        $remotePath = $uploadResp->json('path') ?? "{$importDir}/" . $file->getClientOriginalName();

        // Create the migration record + parse via agent.
        $migration = CpanelMigration::create([
            'server_id'    => $account->server_id,
            'account_id'   => $account->id,
            'initiated_by' => auth()->id(),
            'source_type'  => 'cpanel_backup',
            'source_path'  => $remotePath,
            'status'       => 'parsing',
        ]);

        $result = $this->migrationService->parse($account->server, $remotePath);

        if (! $result['success']) {
            $migration->update(['status' => 'failed', 'error' => $result['error']]);
            return redirect()->route('user.migrations.index')
                ->with('error', 'Failed to parse backup: ' . $result['error']);
        }

        $manifest = $result['manifest'];
        $migration->update([
            'status'            => 'previewing',
            'manifest'          => $manifest,
            'original_username' => $manifest['username'] ?? 'unknown',
            'target_username'   => $account->username,
            'main_domain'       => $manifest['main_domain'] ?? null,
        ]);

        return redirect()->route('user.migrations.preview', $migration);
    }

    /**
     * GET /user/import/{migration} — preview what will be imported and
     * let the user pick which sections to include.
     */
    public function preview(CpanelMigration $migration)
    {
        $this->authorizeOwner($migration);

        if (! in_array($migration->status, ['previewing', 'failed'])) {
            return redirect()->route('user.migrations.show', $migration);
        }

        return view('user.migrations.preview', compact('migration'));
    }

    /**
     * POST /user/import/{migration}/execute — actually run the import.
     */
    public function execute(Request $request, CpanelMigration $migration)
    {
        $this->authorizeOwner($migration);

        $request->validate([
            'import_files'     => 'nullable|boolean',
            'import_databases' => 'nullable|boolean',
            'import_email'     => 'nullable|boolean',
            'import_dns'       => 'nullable|boolean',
            'import_ssl'       => 'nullable|boolean',
            'import_cron'      => 'nullable|boolean',
        ]);

        $migration->update([
            'status'  => 'pending',
            'options' => [
                // For the user-mode import we always merge into the existing
                // account — the package and target_username are already set
                // from the user's own account, no selection needed.
                'package'          => $migration->account?->package?->name ?? 'existing',
                'merge_mode'       => true,
                'import_files'     => $request->boolean('import_files', true),
                'import_databases' => $request->boolean('import_databases', true),
                'import_email'     => $request->boolean('import_email', true),
                'import_dns'       => $request->boolean('import_dns', false), // off by default — DNS is disruptive
                'import_ssl'       => $request->boolean('import_ssl', true),
                'import_cron'      => $request->boolean('import_cron', true),
            ],
        ]);

        ActivityLogger::log('migration.started', 'migration', $migration->id, $migration->main_domain,
            "User initiated cPanel import for {$migration->main_domain}",
            ['server_id' => $migration->server_id, 'source' => $migration->source_path]);

        ProcessCpanelMigration::dispatch($migration);

        return redirect()->route('user.migrations.show', $migration)
            ->with('success', 'Import started. The page will refresh as it progresses.');
    }

    /**
     * GET /user/import/{migration}/show — show progress and result.
     */
    public function show(CpanelMigration $migration)
    {
        $this->authorizeOwner($migration);
        $migration->load('server');
        return view('user.migrations.show', compact('migration'));
    }

    /**
     * GET /user/import/{migration}/status — JSON for the progress poller.
     */
    public function status(CpanelMigration $migration)
    {
        $this->authorizeOwner($migration);

        return response()->json([
            'status'       => $migration->status,
            'progress'     => $migration->progress,
            'current_step' => $migration->current_step,
            'result'       => $migration->result,
            'error'        => $migration->error,
        ]);
    }

    /**
     * DELETE /user/import/{migration}
     */
    public function destroy(CpanelMigration $migration)
    {
        $this->authorizeOwner($migration);
        $migration->delete();

        return redirect()->route('user.migrations.index')
            ->with('success', 'Import record removed.');
    }

    private function authorizeOwner(CpanelMigration $migration): void
    {
        if ($migration->initiated_by !== auth()->id()) {
            abort(403);
        }
    }
}
