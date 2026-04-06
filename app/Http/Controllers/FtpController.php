<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\ActivityLogger;
use App\Services\AgentService;
use Illuminate\Http\Request;

class FtpController extends Controller
{
    public function index(Request $request)
    {
        $accounts = auth()->user()->accessibleAccounts()
            ->with('server')
            ->get();

        $selectedAccount = null;
        $ftpAccounts = [];

        if ($request->has('account_id')) {
            $selectedAccount = auth()->user()->accessibleAccounts()
                ->with('server')
                ->findOrFail($request->account_id);

            $response = AgentService::for($selectedAccount->server)->post('/ftp/list', [
                'sys_user' => $selectedAccount->username,
            ]);

            if ($response && $response->successful()) {
                $ftpAccounts = $response->json('accounts') ?? [];
            }
        }

        return view('ftp.index', compact('accounts', 'selectedAccount', 'ftpAccounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'username'   => 'required|string|max:32|alpha_dash',
            'password'   => 'required|string|min:8',
            'directory'  => 'nullable|string|max:500',
        ]);

        $account = Account::with('server')->findOrFail($validated['account_id']);
        $directory = $validated['directory'] ?: $account->home_directory;

        $response = AgentService::for($account->server)->post('/ftp/create', [
            'username'  => $validated['username'],
            'password'  => $validated['password'],
            'directory' => $directory,
            'sys_user'  => $account->username,
        ]);

        if ($response && $response->successful()) {
            ActivityLogger::log('ftp.created', 'account', $account->id, $validated['username'],
                "Created FTP account {$validated['username']}");
            return redirect()->route('user.ftp.index', ['account_id' => $account->id])->with('success', 'FTP account created.');
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Agent unreachable';
        return back()->with('error', 'Failed: ' . $error)->withInput();
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'username'   => 'required|string',
        ]);

        $account = Account::with('server')->findOrFail($validated['account_id']);

        AgentService::for($account->server)->post('/ftp/delete', [
            'username' => $validated['username'],
        ]);

        ActivityLogger::log('ftp.deleted', 'account', $account->id, $validated['username'],
            "Deleted FTP account {$validated['username']}");

        return redirect()->route('user.ftp.index', ['account_id' => $account->id])->with('success', 'FTP account deleted.');
    }
}
