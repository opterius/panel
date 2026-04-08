<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\PostgresDatabase;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostgresController extends Controller
{
    public function index()
    {
        $databases = PostgresDatabase::with('account.server')
            ->whereIn('account_id', auth()->user()->currentAccountIds())
            ->latest()
            ->get();

        return view('postgres.index', compact('databases'));
    }

    public function create()
    {
        $accounts = Account::with('server')
            ->whereIn('id', auth()->user()->currentAccountIds())
            ->get();

        return view('postgres.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'db_name'    => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_]+$/'],
        ], [
            'db_name.regex' => 'Database name can only contain letters, numbers, and underscores.',
        ]);

        $account = Account::with('server')->findOrFail($validated['account_id']);
        abort_unless(in_array($account->id, auth()->user()->currentAccountIds()), 403);

        // Prefix with account username (cPanel-style uniqueness)
        $dbName   = $account->prefixDbIdentifier($validated['db_name']);
        $pgUser   = $account->prefixDbIdentifier($validated['db_name'] . '_u');
        $password = Str::random(24);

        // 1. Create PostgreSQL database
        $response = AgentService::for($account->server)->post('/postgres/create', [
            'name' => $dbName,
        ]);

        if (!$response || !$response->successful()) {
            $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
            return back()->with('error', 'Failed to create PostgreSQL database: ' . $error)->withInput();
        }

        // 2. Create PostgreSQL user and grant privileges
        $response = AgentService::for($account->server)->post('/postgres/user-create', [
            'pg_username' => $pgUser,
            'password'    => $password,
            'database'    => $dbName,
        ]);

        if (!$response || !$response->successful()) {
            // Roll back database
            AgentService::for($account->server)->post('/postgres/delete', ['name' => $dbName]);
            $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
            return back()->with('error', 'Failed to create PostgreSQL user: ' . $error)->withInput();
        }

        $db = PostgresDatabase::create([
            'server_id'   => $account->server_id,
            'account_id'  => $account->id,
            'name'        => $dbName,
            'pg_username' => $pgUser,
            'status'      => 'active',
        ]);

        return redirect()->route('user.postgres.show', $db)
            ->with('success', "PostgreSQL database \"{$dbName}\" created.")
            ->with('pg_password', $password); // show once
    }

    public function show(PostgresDatabase $postgresDatabase)
    {
        $this->authorizeDb($postgresDatabase);

        $info = [];
        $response = AgentService::for($postgresDatabase->account->server)->post('/postgres/info', [
            'name' => $postgresDatabase->name,
        ]);
        if ($response && $response->successful()) {
            $info = $response->json();
        }

        return view('postgres.show', ['db' => $postgresDatabase, 'info' => $info]);
    }

    public function changePassword(Request $request, PostgresDatabase $postgresDatabase)
    {
        $this->authorizeDb($postgresDatabase);

        $validated = $request->validate([
            'pg_password' => 'required|string|min:8',
        ]);

        $response = AgentService::for($postgresDatabase->account->server)->post('/postgres/user-password', [
            'pg_username' => $postgresDatabase->pg_username,
            'password'    => $validated['pg_password'],
        ]);

        if ($response && $response->successful()) {
            return back()->with('success', 'PostgreSQL user password updated.');
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Agent unreachable';
        return back()->with('error', 'Failed to change password: ' . $error);
    }

    public function destroy(Request $request, PostgresDatabase $postgresDatabase)
    {
        $this->authorizeDb($postgresDatabase);

        $request->validate(['password' => 'required']);
        if (!auth()->validate(['email' => auth()->user()->email, 'password' => $request->password])) {
            return back()->with('error', 'Incorrect password.');
        }

        // Delete user first, then database
        AgentService::for($postgresDatabase->account->server)->post('/postgres/user-delete', [
            'pg_username' => $postgresDatabase->pg_username,
        ]);

        $response = AgentService::for($postgresDatabase->account->server)->post('/postgres/delete', [
            'name' => $postgresDatabase->name,
        ]);

        if (!$response || !$response->successful()) {
            $error = $response ? $response->json('error', 'Unknown error') : 'Agent unreachable';
            return back()->with('error', 'Failed to delete PostgreSQL database: ' . $error);
        }

        $postgresDatabase->delete();

        return redirect()->route('user.postgres.index')->with('success', "PostgreSQL database \"{$postgresDatabase->name}\" deleted.");
    }

    private function authorizeDb(PostgresDatabase $db): void
    {
        abort_unless(in_array($db->account_id, auth()->user()->currentAccountIds()), 403);
    }
}
