<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\CronJob;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CronJobController extends Controller
{
    public function index()
    {
        $cronJobs = CronJob::with('server', 'account')
            ->whereHas('account', fn ($q) => $q->where('user_id', Auth::id()))
            ->latest()
            ->get();

        return view('cronjobs.index', compact('cronJobs'));
    }

    public function create()
    {
        $accounts = Account::with('server')
            ->where('user_id', Auth::id())
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
            CronJob::create([
                'server_id'  => $account->server_id,
                'account_id' => $account->id,
                'command'    => $validated['command'],
                'schedule'   => $schedule,
                'enabled'    => true,
            ]);

            return redirect()->route('user.cronjobs.index')->with('success', 'Cron job created successfully.');
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return back()->with('error', 'Failed to create cron job: ' . $error)->withInput();
    }

    public function toggle(CronJob $cronJob)
    {
        $cronJob->load('account.server');
        $newState = !$cronJob->enabled;

        $response = AgentService::for($cronJob->account->server)->post('/cron/toggle', [
            'username' => $cronJob->account->username,
            'command'  => $cronJob->command,
            'enabled'  => $newState,
        ]);

        if ($response && $response->successful()) {
            $cronJob->update(['enabled' => $newState]);
            $state = $newState ? 'enabled' : 'disabled';
            return redirect()->route('user.cronjobs.index')->with('success', "Cron job $state.");
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return back()->with('error', 'Failed to toggle cron job: ' . $error);
    }

    public function destroy(CronJob $cronJob)
    {
        $cronJob->load('account.server');

        AgentService::for($cronJob->account->server)->post('/cron/delete', [
            'username' => $cronJob->account->username,
            'command'  => $cronJob->command,
        ]);

        $cronJob->delete();

        return redirect()->route('user.cronjobs.index')->with('success', 'Cron job deleted.');
    }
}
