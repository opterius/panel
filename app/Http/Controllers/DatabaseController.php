<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Database;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DatabaseController extends Controller
{
    public function index()
    {
        $databases = Database::with('server', 'account')
            ->whereHas('account', fn ($q) => $q->where('user_id', Auth::id()))
            ->latest()
            ->get();

        return view('databases.index', compact('databases'));
    }

    public function create()
    {
        $accounts = Account::with('server')
            ->where('user_id', Auth::id())
            ->get();

        return view('databases.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_id'  => 'required|exists:accounts,id',
            'name'        => 'required|string|max:64|alpha_dash|unique:databases,name',
            'db_username' => 'required|string|max:32|alpha_dash',
            'db_password' => 'required|string|min:8',
            'remote'      => 'boolean',
        ]);

        $account = Account::with('server')->findOrFail($validated['account_id']);
        $host = $request->boolean('remote') ? '%' : 'localhost';

        // 1. Create database on server
        $response = AgentService::for($account->server)->post('/databases/create', [
            'name' => $validated['name'],
        ]);

        if (!$response || !$response->successful()) {
            $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
            return back()->with('error', 'Failed to create database: ' . $error)->withInput();
        }

        // 2. Create database user and grant privileges
        $response = AgentService::for($account->server)->post('/databases/user-create', [
            'username' => $validated['db_username'],
            'password' => $validated['db_password'],
            'database' => $validated['name'],
            'host'     => $host,
        ]);

        if (!$response || !$response->successful()) {
            // Rollback: delete the database we just created
            AgentService::for($account->server)->post('/databases/delete', [
                'name' => $validated['name'],
            ]);
            $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
            return back()->with('error', 'Failed to create database user: ' . $error)->withInput();
        }

        // 3. Save to panel database
        $database = Database::create([
            'server_id'   => $account->server_id,
            'account_id'  => $account->id,
            'name'        => $validated['name'],
            'db_username' => $validated['db_username'],
            'status'      => 'active',
        ]);

        return redirect()->route('user.databases.index')->with('success', 'Database ' . $database->name . ' created successfully.');
    }

    public function destroy(Database $database)
    {
        $database->load('account.server');

        // Delete database on server
        AgentService::for($database->account->server)->post('/databases/delete', [
            'name' => $database->name,
        ]);

        // Delete user on server
        if ($database->db_username) {
            AgentService::for($database->account->server)->post('/databases/user-delete', [
                'username' => $database->db_username,
                'host'     => 'localhost',
            ]);
            // Also try removing remote user
            AgentService::for($database->account->server)->post('/databases/user-delete', [
                'username' => $database->db_username,
                'host'     => '%',
            ]);
        }

        $database->delete();

        return redirect()->route('user.databases.index')->with('success', 'Database ' . $database->name . ' deleted.');
    }
}
