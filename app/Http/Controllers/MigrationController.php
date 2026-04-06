<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCpanelMigration;
use App\Models\CpanelMigration;
use App\Models\Package;
use App\Models\Server;
use App\Services\ActivityLogger;
use App\Services\MigrationService;
use Illuminate\Http\Request;

class MigrationController extends Controller
{
    public function __construct(
        private MigrationService $migrationService,
    ) {}

    public function index()
    {
        $migrations = CpanelMigration::with('server', 'account')
            ->latest()
            ->get();

        return view('migrations.index', compact('migrations'));
    }

    public function create()
    {
        $servers = Server::where('status', 'online')->get();

        return view('migrations.create', compact('servers'));
    }

    public function parse(Request $request)
    {
        $validated = $request->validate([
            'server_id'   => 'required|exists:servers,id',
            'source_path' => 'required|string|max:1000',
        ]);

        $server = Server::findOrFail($validated['server_id']);

        // Create the migration record
        $migration = CpanelMigration::create([
            'server_id'    => $server->id,
            'initiated_by' => auth()->id(),
            'source_type'  => 'cpanel_backup',
            'source_path'  => $validated['source_path'],
            'status'       => 'parsing',
        ]);

        // Parse the backup via the Go agent
        $result = $this->migrationService->parse($server, $validated['source_path']);

        if (!$result['success']) {
            $migration->update(['status' => 'failed', 'error' => $result['error']]);
            return redirect()->route('admin.migrations.index')
                ->with('error', __('migrations.failed_to_parse_backup', ['error' => $result['error']]));
        }

        $manifest = $result['manifest'];

        $migration->update([
            'status'            => 'previewing',
            'manifest'          => $manifest,
            'original_username' => $manifest['username'] ?? 'unknown',
            'target_username'   => $manifest['username'] ?? 'unknown',
            'main_domain'       => $manifest['main_domain'] ?? null,
        ]);

        return redirect()->route('admin.migrations.preview', $migration);
    }

    public function preview(CpanelMigration $migration)
    {
        if (!in_array($migration->status, ['previewing', 'failed'])) {
            return redirect()->route('admin.migrations.show', $migration);
        }

        $packages = Package::orderByDesc('is_default')->orderBy('name')->get();

        return view('migrations.preview', compact('migration', 'packages'));
    }

    public function execute(Request $request, CpanelMigration $migration)
    {
        $validated = $request->validate([
            'target_username' => 'required|string|max:32|alpha_dash|unique:accounts,username',
            'package_id'      => 'required|exists:packages,id',
            'import_files'    => 'boolean',
            'import_databases' => 'boolean',
            'import_email'    => 'boolean',
            'import_dns'      => 'boolean',
            'import_ssl'      => 'boolean',
            'import_cron'     => 'boolean',
        ]);

        $migration->update([
            'status'          => 'pending',
            'target_username' => $validated['target_username'],
            'options' => [
                'package'          => Package::find($validated['package_id'])->name,
                'import_files'     => $request->boolean('import_files', true),
                'import_databases' => $request->boolean('import_databases', true),
                'import_email'     => $request->boolean('import_email', true),
                'import_dns'       => $request->boolean('import_dns', true),
                'import_ssl'       => $request->boolean('import_ssl', true),
                'import_cron'      => $request->boolean('import_cron', true),
            ],
        ]);

        ActivityLogger::log('migration.started', 'migration', $migration->id, $migration->main_domain,
            "Started cPanel migration for {$migration->main_domain}",
            ['server_id' => $migration->server_id, 'source' => $migration->source_path]);

        ProcessCpanelMigration::dispatch($migration);

        return redirect()->route('admin.migrations.show', $migration)
            ->with('success', __('migrations.migration_started'));
    }

    public function show(CpanelMigration $migration)
    {
        $migration->load('server', 'account');

        return view('migrations.show', compact('migration'));
    }

    /**
     * AJAX endpoint for progress polling.
     */
    public function status(CpanelMigration $migration)
    {
        return response()->json([
            'status'       => $migration->status,
            'progress'     => $migration->progress,
            'current_step' => $migration->current_step,
            'result'       => $migration->result,
            'error'        => $migration->error,
        ]);
    }

    public function destroy(CpanelMigration $migration)
    {
        $migration->delete();

        return redirect()->route('admin.migrations.index')
            ->with('success', __('migrations.migration_record_deleted'));
    }
}
