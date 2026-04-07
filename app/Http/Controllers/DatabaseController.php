<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Database;
use App\Services\ActivityLogger;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseController extends Controller
{
    public function index()
    {
        $databases = Database::with('server', 'account')
            ->whereIn('account_id', auth()->user()->currentAccountIds())
            ->latest()
            ->get();

        return view('databases.index', compact('databases'));
    }

    public function create()
    {
        $accounts = auth()->user()->scopedToCurrent()
            ->with('server')
            ->get();

        return view('databases.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        // First-pass validation: shape of inputs. Length limits are tightened
        // below once we know the per-account prefix length.
        $validated = $request->validate([
            'account_id'  => 'required|exists:accounts,id',
            'name'        => 'required|string|alpha_dash',
            'db_username' => 'required|string|alpha_dash',
            'db_password' => 'required|string|min:8',
            'remote'      => 'boolean',
        ]);

        $account = Account::with('server')->findOrFail($validated['account_id']);

        if (!$account->userCan(auth()->user(), 'databases')) {
            return back()->with('error', __('databases.no_permission'));
        }

        // cPanel-style prefixing: every database/user gets the account username
        // as a prefix so accounts on the shared MySQL server can never collide.
        $prefix = $account->dbPrefix();
        $maxNameLen = 64 - strlen($prefix);  // MySQL db identifier limit
        $maxUserLen = 32 - strlen($prefix);  // MySQL user identifier limit

        $request->validate([
            'name'        => "max:{$maxNameLen}",
            'db_username' => "max:{$maxUserLen}",
        ], [
            'name.max'        => "Database name cannot exceed {$maxNameLen} characters (the '{$prefix}' prefix is added automatically).",
            'db_username.max' => "Database username cannot exceed {$maxUserLen} characters (the '{$prefix}' prefix is added automatically).",
        ]);

        $fullName = $account->prefixDbIdentifier($validated['name']);
        $fullUser = $account->prefixDbIdentifier($validated['db_username']);

        // Uniqueness check on the FINAL prefixed name (so two accounts named
        // "app" don't both get rejected — they'd be opterius_app vs other_app).
        if (Database::where('name', $fullName)->exists()) {
            return back()
                ->withErrors(['name' => "Database '{$fullName}' already exists."])
                ->withInput();
        }

        $host = $request->boolean('remote') ? '%' : 'localhost';

        $response = AgentService::for($account->server)->post('/databases/create', [
            'name' => $fullName,
        ]);

        if (!$response || !$response->successful()) {
            $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
            return back()->with('error', __('databases.failed_to_create_database', ['error' => $error]))->withInput();
        }

        $response = AgentService::for($account->server)->post('/databases/user-create', [
            'username' => $fullUser,
            'password' => $validated['db_password'],
            'database' => $fullName,
            'host'     => $host,
        ]);

        if (!$response || !$response->successful()) {
            AgentService::for($account->server)->post('/databases/delete', [
                'name' => $fullName,
            ]);
            $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
            return back()->with('error', __('databases.failed_to_create_user', ['error' => $error]))->withInput();
        }

        $database = Database::create([
            'server_id'   => $account->server_id,
            'account_id'  => $account->id,
            'name'        => $fullName,
            'db_username' => $fullUser,
            'status'      => 'active',
        ]);

        ActivityLogger::log('database.created', 'database', $database->id, $database->name,
            "Created database {$database->name}", ['server_id' => $account->server_id, 'account_id' => $account->id]);

        return redirect()->route('user.databases.show', $database)->with('success', __('databases.database_created', ['name' => $database->name]));
    }

    public function show(Database $database)
    {
        $database->load('account.server');

        $info = null;
        $response = AgentService::for($database->account->server)->post('/databases/info', [
            'name' => $database->name,
        ]);

        if ($response && $response->successful()) {
            $info = $response->json();
        }

        return view('databases.show', compact('database', 'info'));
    }

    public function changePassword(Request $request, Database $database)
    {
        $validated = $request->validate([
            'db_password' => 'required|string|min:8',
        ]);

        $database->load('account.server');

        if (!$database->account->userCan(auth()->user(), 'databases')) {
            return back()->with('error', __('databases.no_permission'));
        }

        $response = AgentService::for($database->account->server)->post('/databases/user-password', [
            'username' => $database->db_username,
            'password' => $validated['db_password'],
            'host'     => 'localhost',
        ]);

        if ($response && $response->successful()) {
            ActivityLogger::log('database.password_changed', 'database', $database->id, $database->name,
                "Changed password for database user {$database->db_username}", ['db_username' => $database->db_username]);

            return redirect()->route('user.databases.show', $database)->with('success', __('databases.password_changed', ['username' => $database->db_username]));
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return back()->with('error', __('databases.failed_to_change_password', ['error' => $error]));
    }

    public function repair(Request $request, Database $database)
    {
        $database->load('account.server');
        $action = $request->input('action', 'repair');

        $response = AgentService::for($database->account->server)->post('/databases/repair', [
            'name'   => $database->name,
            'action' => $action,
        ]);

        if ($response && $response->successful()) {
            return redirect()->route('user.databases.show', $database)->with('success', __('databases.repair_completed', ['action' => $action]));
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return back()->with('error', __('databases.action_failed', ['action' => ucfirst($action), 'error' => $error]));
    }

    public function destroy(Request $request, Database $database)
    {
        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => __('common.password_incorrect')]);
        }

        $database->load('account.server');

        if (!$database->account->userCan(auth()->user(), 'databases')) {
            return back()->with('error', __('databases.no_permission'));
        }

        ActivityLogger::log('database.deleted', 'database', $database->id, $database->name,
            "Deleted database {$database->name}", ['server_id' => $database->server_id, 'account_id' => $database->account_id]);

        AgentService::for($database->account->server)->post('/databases/delete', [
            'name' => $database->name,
        ]);

        if ($database->db_username) {
            AgentService::for($database->account->server)->post('/databases/user-delete', [
                'username' => $database->db_username,
                'host'     => 'localhost',
            ]);
            AgentService::for($database->account->server)->post('/databases/user-delete', [
                'username' => $database->db_username,
                'host'     => '%',
            ]);
        }

        $database->delete();

        return redirect()->route('user.databases.index')->with('success', __('databases.database_deleted', ['name' => $database->name]));
    }
}
