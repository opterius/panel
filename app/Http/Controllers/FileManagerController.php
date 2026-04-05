<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FileManagerController extends Controller
{
    public function index(Request $request)
    {
        $accounts = Account::with('server')
            ->where('user_id', Auth::id())
            ->get();

        $selectedAccount = null;
        $entries = [];
        $currentPath = '';

        if ($request->has('account_id')) {
            $selectedAccount = Account::with('server')
                ->where('user_id', Auth::id())
                ->findOrFail($request->account_id);

            $currentPath = $request->get('path', '/home/' . $selectedAccount->username);

            $response = AgentService::for($selectedAccount->server)->post('/files/list', [
                'username' => $selectedAccount->username,
                'path'     => $currentPath,
            ]);

            if ($response && $response->successful()) {
                $entries = $response->json('entries', []);
                $currentPath = $response->json('path', $currentPath);
            }
        }

        return view('filemanager.index', compact('accounts', 'selectedAccount', 'entries', 'currentPath'));
    }

    public function edit(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'path'       => 'required|string',
        ]);

        $account = Account::with('server')
            ->where('user_id', Auth::id())
            ->findOrFail($validated['account_id']);

        $response = AgentService::for($account->server)->post('/files/read', [
            'username' => $account->username,
            'path'     => $validated['path'],
        ]);

        if ($response && $response->successful()) {
            $content = $response->json('content', '');
            $path = $response->json('path');
            return view('filemanager.edit', compact('account', 'path', 'content'));
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
        return back()->with('error', 'Failed to read file: ' . $error);
    }

    public function read(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'path'       => 'required|string',
        ]);

        $account = Account::with('server')->findOrFail($validated['account_id']);

        $response = AgentService::for($account->server)->post('/files/read', [
            'username' => $account->username,
            'path'     => $validated['path'],
        ]);

        if ($response && $response->successful()) {
            return response()->json($response->json());
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
        return response()->json(['error' => $error], 500);
    }

    public function write(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'path'       => 'required|string',
            'content'    => 'required|string',
        ]);

        $account = Account::with('server')->findOrFail($validated['account_id']);

        $response = AgentService::for($account->server)->post('/files/write', [
            'username' => $account->username,
            'path'     => $validated['path'],
            'content'  => $validated['content'],
        ]);

        if ($response && $response->successful()) {
            return back()->with('success', 'File saved.');
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
        return back()->with('error', 'Failed to save: ' . $error);
    }

    public function upload(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'path'       => 'required|string',
            'file'       => 'required|file|max:102400', // 100MB
        ]);

        $account = Account::with('server')->findOrFail($validated['account_id']);
        $file = $request->file('file');

        $response = AgentService::for($account->server)->post('/files/upload', [
            'username' => $account->username,
            'path'     => $validated['path'],
        ]);

        // For file upload we need multipart — use Http facade directly
        $response = \Illuminate\Support\Facades\Http::withoutVerifying()
            ->timeout(120)
            ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
            ->post($account->server->agent_url . '/files/upload', [
                'username' => $account->username,
                'path'     => $validated['path'],
            ]);

        if ($response->successful()) {
            return back()->with('success', 'File uploaded: ' . $file->getClientOriginalName());
        }

        $error = $response->json('error', 'Upload failed');
        return back()->with('error', $error);
    }

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'path'       => 'required|string',
        ]);

        $account = Account::with('server')->findOrFail($validated['account_id']);

        $response = AgentService::for($account->server)->post('/files/delete', [
            'username' => $account->username,
            'path'     => $validated['path'],
        ]);

        if ($response && $response->successful()) {
            return back()->with('success', 'Deleted successfully.');
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
        return back()->with('error', 'Failed to delete: ' . $error);
    }

    public function rename(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'path'       => 'required|string',
            'new_name'   => 'required|string|max:255',
        ]);

        $account = Account::with('server')->findOrFail($validated['account_id']);

        $response = AgentService::for($account->server)->post('/files/rename', [
            'username' => $account->username,
            'path'     => $validated['path'],
            'new_name' => $validated['new_name'],
        ]);

        if ($response && $response->successful()) {
            return back()->with('success', 'Renamed successfully.');
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
        return back()->with('error', 'Failed to rename: ' . $error);
    }

    public function mkdir(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'path'       => 'required|string',
        ]);

        $account = Account::with('server')->findOrFail($validated['account_id']);

        $response = AgentService::for($account->server)->post('/files/mkdir', [
            'username' => $account->username,
            'path'     => $validated['path'],
        ]);

        if ($response && $response->successful()) {
            return back()->with('success', 'Folder created.');
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
        return back()->with('error', 'Failed to create folder: ' . $error);
    }

    public function chmod(Request $request)
    {
        $validated = $request->validate([
            'account_id'  => 'required|exists:accounts,id',
            'path'        => 'required|string',
            'permissions' => 'required|string|regex:/^[0-7]{3,4}$/',
        ]);

        $account = Account::with('server')->findOrFail($validated['account_id']);

        $response = AgentService::for($account->server)->post('/files/chmod', [
            'username'    => $account->username,
            'path'        => $validated['path'],
            'permissions' => $validated['permissions'],
        ]);

        if ($response && $response->successful()) {
            return back()->with('success', 'Permissions updated.');
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
        return back()->with('error', 'Failed to change permissions: ' . $error);
    }

    public function archive(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'path'       => 'required|string',
        ]);

        $account = Account::with('server')->findOrFail($validated['account_id']);

        $response = AgentService::for($account->server)->post('/files/archive', [
            'username' => $account->username,
            'path'     => $validated['path'],
        ]);

        if ($response && $response->successful()) {
            $action = $response->json('action', 'done');
            return back()->with('success', ucfirst($action) . ' successfully.');
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
        return back()->with('error', 'Archive operation failed: ' . $error);
    }
}
