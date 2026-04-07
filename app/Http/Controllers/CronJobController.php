<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\CronJob;
use App\Services\ActivityLogger;
use App\Services\AgentService;
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
            'account_id' => 'required|exists:accounts,id',
            'command'    => 'required|string|max:1000',
            'minute'     => 'required|string|max:20',
            'hour'       => 'required|string|max:20',
            'day'        => 'required|string|max:20',
            'month'      => 'required|string|max:20',
            'weekday'    => 'required|string|max:20',
        ]);

        $account = Account::with('server')->findOrFail($validated['account_id']);

        if (!$account->userCan(auth()->user(), 'cron')) {
            return back()->with('error', __('cron.no_permission'));
        }

        $schedule = implode(' ', [
            $validated['minute'],
            $validated['hour'],
            $validated['day'],
            $validated['month'],
            $validated['weekday'],
        ]);

        $response = AgentService::for($account->server)->post('/cron/create', [
            'username' => $account->username,
            'schedule' => $schedule,
            'command'  => $validated['command'],
        ]);

        if ($response && $response->successful()) {
            $cronJob = CronJob::create([
                'server_id'  => $account->server_id,
                'account_id' => $account->id,
                'command'    => $validated['command'],
                'schedule'   => $schedule,
                'enabled'    => true,
            ]);

            ActivityLogger::log('cron.created', 'cron_job', $cronJob->id, $cronJob->command,
                "Created cron job: {$schedule} {$validated['command']}", ['server_id' => $account->server_id, 'account_id' => $account->id]);

            return redirect()->route('user.cronjobs.index')->with('success', __('cron.cron_job_created'));
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return back()->with('error', __('cron.failed_to_create_cron', ['error' => $error]))->withInput();
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
