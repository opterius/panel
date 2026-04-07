<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\ActivityLogger;
use App\Services\AgentService;
use Illuminate\Http\Request;

class SshController extends Controller
{
    public function index(Request $request)
    {
        $accounts = auth()->user()->accessibleAccounts()
            ->with('server')
            ->get();

        $selectedAccount = null;
        $keys = [];
        $sshEnabled = false;

        if ($request->has('account')) {
            $selectedAccount = $accounts->firstWhere('username', $request->input('account'));
            if (!$selectedAccount) {
                abort(404);
            }
        } elseif ($request->has('account_id')) {
            $selectedAccount = $accounts->firstWhere('id', (int) $request->input('account_id'));
            if (!$selectedAccount) {
                abort(404);
            }
        }

        if ($selectedAccount) {

            // Fetch keys from agent
            $response = AgentService::for($selectedAccount->server)->post('/ssh/list-keys', [
                'username' => $selectedAccount->username,
            ]);

            if ($response && $response->successful()) {
                $keys = $response->json('keys', []);
            }

            // Check if SSH shell is enabled (stored locally for now)
            $sshEnabled = $selectedAccount->ssh_enabled ?? false;
        }

        return view('ssh.index', compact('accounts', 'selectedAccount', 'keys', 'sshEnabled'));
    }

    public function generateKey(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'key_type'   => 'required|in:rsa,ed25519',
        ]);

        $account = Account::with('server')->findOrFail($validated['account_id']);

        if (!$account->userCan(auth()->user(), 'ssh')) {
            return back()->with('error', __('ssh.no_permission'));
        }

        $response = AgentService::for($account->server)->post('/ssh/generate-key', [
            'username' => $account->username,
            'key_type' => $validated['key_type'],
            'comment'  => $account->username . '@' . $account->server->name,
        ]);

        if ($response && $response->successful()) {
            $data = $response->json();

            ActivityLogger::log('ssh.key_generated', 'account', $account->id, $account->username,
                "Generated {$validated['key_type']} SSH key for {$account->username}", ['key_type' => $validated['key_type'], 'server_id' => $account->server_id]);

            // Return private key as a downloadable file
            return response($data['private_key'])
                ->header('Content-Type', 'application/x-pem-file')
                ->header('Content-Disposition', 'attachment; filename="id_' . $validated['key_type'] . '_' . $account->username . '"');
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return back()->with('error', __('ssh.failed_to_generate_key', ['error' => $error]));
    }

    public function importKey(Request $request)
    {
        $validated = $request->validate([
            'account_id'  => 'required|exists:accounts,id',
            'public_key'  => 'required|string',
            'private_key' => 'nullable|string',
        ]);

        $account = Account::with('server')->findOrFail($validated['account_id']);

        if (!$account->userCan(auth()->user(), 'ssh')) {
            return back()->with('error', __('ssh.no_permission'));
        }

        $response = AgentService::for($account->server)->post('/ssh/import-key', [
            'username'    => $account->username,
            'public_key'  => $validated['public_key'],
            'private_key' => $validated['private_key'] ?? '',
        ]);

        if ($response && $response->successful()) {
            ActivityLogger::log('ssh.key_imported', 'account', $account->id, $account->username,
                "Imported SSH key for {$account->username}", ['server_id' => $account->server_id]);

            return redirect()
                ->route('user.ssh.index', ['account' => $account->username])
                ->with('success', __('ssh.key_imported'));
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return back()->with('error', __('ssh.failed_to_import_key', ['error' => $error]))->withInput();
    }

    public function deleteKey(Request $request)
    {
        $validated = $request->validate([
            'account_id'  => 'required|exists:accounts,id',
            'fingerprint' => 'required|string',
        ]);

        $account = Account::with('server')->findOrFail($validated['account_id']);

        if (!$account->userCan(auth()->user(), 'ssh')) {
            return back()->with('error', __('ssh.no_permission'));
        }

        $response = AgentService::for($account->server)->post('/ssh/delete-key', [
            'username'    => $account->username,
            'fingerprint' => $validated['fingerprint'],
        ]);

        if ($response && $response->successful()) {
            ActivityLogger::log('ssh.key_deleted', 'account', $account->id, $account->username,
                "Deleted SSH key for {$account->username}", ['server_id' => $account->server_id, 'fingerprint' => $validated['fingerprint']]);

            return redirect()
                ->route('user.ssh.index', ['account' => $account->username])
                ->with('success', __('ssh.key_removed'));
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return back()->with('error', __('ssh.failed_to_delete_key', ['error' => $error]));
    }

    public function toggleShell(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'enabled'    => 'required|boolean',
        ]);

        $account = Account::with('server')->findOrFail($validated['account_id']);

        if (!$account->userCan(auth()->user(), 'ssh')) {
            return back()->with('error', __('ssh.no_permission'));
        }

        $response = AgentService::for($account->server)->post('/ssh/toggle-shell', [
            'username' => $account->username,
            'enabled'  => (bool) $validated['enabled'],
        ]);

        if ($response && $response->successful()) {
            $account->update(['ssh_enabled' => (bool) $validated['enabled']]);
            $state = $validated['enabled'] ? 'enabled' : 'disabled';

            ActivityLogger::log('ssh.shell_toggled', 'account', $account->id, $account->username,
                "SSH shell {$state} for {$account->username}", ['enabled' => (bool) $validated['enabled'], 'server_id' => $account->server_id]);

            $successKey = $validated['enabled'] ? 'ssh.ssh_access_enabled' : 'ssh.ssh_access_disabled';
            return redirect()
                ->route('user.ssh.index', ['account' => $account->username])
                ->with('success', __($successKey, ['username' => $account->username]));
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return back()->with('error', __('ssh.failed_to_toggle_ssh', ['error' => $error]));
    }
}
