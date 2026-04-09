<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\CronJob;
use App\Models\CronRunHistory;
use App\Services\ActivityLogger;
use App\Services\AgentService;
use App\Support\CronSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CronJobController extends Controller
{
    public function index()
    {
        $cronJobs = CronJob::with('server', 'account')
            ->whereIn('account_id', auth()->user()->currentAccountIds())
            ->latest()
            ->get();

        return view('cronjobs.index', compact('cronJobs'));
    }

    public function create()
    {
        $accounts = auth()->user()->scopedToCurrent()
            ->with('server')
            ->get();

        return view('cronjobs.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_id'  => 'required|exists:accounts,id',
            'command'     => 'required|string|max:1000',
            'description' => 'nullable|string|max:200',
            // Either preset OR all 5 fields must be provided.
            'preset'      => 'nullable|string|max:50',
            'minute'      => 'nullable|string|max:20',
            'hour'        => 'nullable|string|max:20',
            'day'         => 'nullable|string|max:20',
            'month'       => 'nullable|string|max:20',
            'weekday'     => 'nullable|string|max:20',
        ]);

        $account = Account::with('server')->findOrFail($validated['account_id']);

        if (!$account->userCan(auth()->user(), 'cron')) {
            return back()->with('error', __('cron.no_permission'));
        }

        // Resolve schedule from preset or individual fields.
        if (! empty($validated['preset'])) {
            $schedule = CronSchedule::fromPreset($validated['preset']);
            if (! $schedule) {
                return back()->with('error', 'Invalid preset.');
            }
        } else {
            $schedule = CronSchedule::fromFields(
                $validated['minute']  ?? '*',
                $validated['hour']    ?? '*',
                $validated['day']     ?? '*',
                $validated['month']   ?? '*',
                $validated['weekday'] ?? '*'
            );
        }

        if (! CronSchedule::isValid($schedule)) {
            return back()->with('error', 'Invalid cron expression.');
        }

        // Insert the DB row first so we have a stable cron_job_id to send
        // to the agent. The agent uses this id to wrap the command in the
        // runner script that POSTs run results back to /api/cron/report.
        $cronJob = CronJob::create([
            'server_id'   => $account->server_id,
            'account_id'  => $account->id,
            'command'     => $validated['command'],
            'description' => $validated['description'] ?? null,
            'schedule'    => $schedule,
            'enabled'     => true,
        ]);

        $response = AgentService::for($account->server)->post('/cron/create', [
            'username'    => $account->username,
            'schedule'    => $schedule,
            'command'     => $validated['command'],
            'cron_job_id' => $cronJob->id,
        ]);

        if ($response && $response->successful()) {
            ActivityLogger::log('cron.created', 'cron_job', $cronJob->id, $cronJob->command,
                "Created cron job: {$schedule} {$validated['command']}", ['server_id' => $account->server_id, 'account_id' => $account->id]);

            return redirect()->route('user.cronjobs.index')->with('success', __('cron.cron_job_created'));
        }

        // Agent call failed — roll back the DB row so we don't show ghost cron jobs.
        $cronJob->delete();

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return back()->with('error', __('cron.failed_to_create_cron', ['error' => $error]))->withInput();
    }

    /**
     * GET /user/cronjobs/{cronJob} — show recent run history.
     */
    public function show(CronJob $cronJob)
    {
        $cronJob->load('account.server');

        if (! in_array($cronJob->account_id, auth()->user()->currentAccountIds())) {
            abort(403);
        }

        $history = $cronJob->history()->limit(20)->get();

        return view('cronjobs.show', compact('cronJob', 'history'));
    }

    /**
     * POST /api/cron/report — agent posts a run result here.
     * Authenticated via shared agent token + cron_job_id.
     */
    public function report(Request $request)
    {
        $data = $request->validate([
            'cron_job_id' => 'required|integer|exists:cron_jobs,id',
            'started_at'  => 'required|date',
            'finished_at' => 'required|date',
            'duration_ms' => 'required|integer|min:0',
            'exit_code'   => 'required|integer',
            'stdout'      => 'nullable|string',
            'stderr'      => 'nullable|string',
            'token'       => 'required|string',
        ]);

        $cronJob = CronJob::with('server')->find($data['cron_job_id']);
        if (! $cronJob || $data['token'] !== $cronJob->server->agent_token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Truncate huge outputs to keep the table sane.
        $maxBytes = 64 * 1024;
        $stdout   = mb_strcut((string) ($data['stdout'] ?? ''), 0, $maxBytes);
        $stderr   = mb_strcut((string) ($data['stderr'] ?? ''), 0, $maxBytes);

        CronRunHistory::create([
            'cron_job_id' => $cronJob->id,
            'started_at'  => $data['started_at'],
            'finished_at' => $data['finished_at'],
            'duration_ms' => $data['duration_ms'],
            'exit_code'   => $data['exit_code'],
            'stdout'      => $stdout,
            'stderr'      => $stderr,
        ]);

        $cronJob->update([
            'last_run_at' => $data['finished_at'],
            'last_output' => $stderr ?: $stdout,
        ]);

        // Prune to last 50 runs per cron job.
        $keepIds = $cronJob->history()->limit(50)->pluck('id');
        CronRunHistory::where('cron_job_id', $cronJob->id)
            ->whereNotIn('id', $keepIds)
            ->delete();

        return response()->json(['status' => 'ok']);
    }

    public function toggle(CronJob $cronJob)
    {
        $cronJob->load('account.server');

        if (!$cronJob->account->userCan(auth()->user(), 'cron')) {
            return back()->with('error', __('cron.no_permission'));
        }

        $newState = !$cronJob->enabled;

        $response = AgentService::for($cronJob->account->server)->post('/cron/toggle', [
            'username' => $cronJob->account->username,
            'command'  => $cronJob->command,
            'enabled'  => $newState,
        ]);

        if ($response && $response->successful()) {
            $cronJob->update(['enabled' => $newState]);
            $state = $newState ? 'enabled' : 'disabled';

            ActivityLogger::log('cron.toggled', 'cron_job', $cronJob->id, $cronJob->command,
                "Cron job {$state}: {$cronJob->command}", ['enabled' => $newState]);

            $successKey = $newState ? 'cron.cron_job_enabled' : 'cron.cron_job_disabled';
            return redirect()->route('user.cronjobs.index')->with('success', __($successKey));
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return back()->with('error', __('cron.failed_to_toggle_cron', ['error' => $error]));
    }

    public function destroy(Request $request, CronJob $cronJob)
    {
        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => __('common.password_incorrect')]);
        }

        $cronJob->load('account.server');

        if (!$cronJob->account->userCan(auth()->user(), 'cron')) {
            return back()->with('error', __('cron.no_permission'));
        }

        ActivityLogger::log('cron.deleted', 'cron_job', $cronJob->id, $cronJob->command,
            "Deleted cron job: {$cronJob->schedule} {$cronJob->command}", ['server_id' => $cronJob->server_id, 'account_id' => $cronJob->account_id]);

        AgentService::for($cronJob->account->server)->post('/cron/delete', [
            'username' => $cronJob->account->username,
            'command'  => $cronJob->command,
        ]);

        $cronJob->delete();

        return redirect()->route('user.cronjobs.index')->with('success', __('cron.cron_job_deleted'));
    }
}
