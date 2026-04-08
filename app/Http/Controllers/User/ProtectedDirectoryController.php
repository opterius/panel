<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\ProtectedDirectory;
use App\Models\ProtectedDirectoryUser;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProtectedDirectoryController extends Controller
{
    /**
     * GET /user/security/directory-protection
     * List all protected directories across the user's domains.
     */
    public function index()
    {
        $domains = Domain::with(['account', 'protectedDirectories.users'])
            ->whereIn('account_id', auth()->user()->currentAccountIds())
            ->orderBy('domain')
            ->get();

        return view('user.security.protected-directories.index', compact('domains'));
    }

    /**
     * POST /user/security/directory-protection
     * Create a new protected directory + first user.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'domain_id' => 'required|integer',
            'path'      => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_\-\/.]+$/'],
            'label'     => 'nullable|string|max:100',
            'username'  => ['required', 'string', 'min:1', 'max:64', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'password'  => 'required|string|min:6|max:200',
        ]);

        $domain = Domain::findOrFail($data['domain_id']);
        $this->authorize($domain);

        // Reject "..", absolute paths, leading slash
        $clean = trim($data['path'], '/');
        if ($clean === '' || str_contains($clean, '..')) {
            return back()->with('error', 'Invalid directory path.');
        }

        // Hash with crypt() in apr1 format which both Apache and Nginx accept.
        $passwordHash = crypt($data['password'], '$apr1$' . substr(bin2hex(random_bytes(8)), 0, 8) . '$');

        DB::transaction(function () use ($domain, $clean, $data, $passwordHash) {
            $dir = $domain->protectedDirectories()->updateOrCreate(
                ['path' => $clean],
                ['label' => $data['label'] ?? null]
            );

            $dir->users()->updateOrCreate(
                ['username' => $data['username']],
                ['password_hash' => $passwordHash]
            );
        });

        $this->syncToAgent($domain, $clean);

        return back()->with('success', "Protected directory \"{$clean}\" created.");
    }

    /**
     * POST /user/security/directory-protection/{directory}/users
     * Add another user to an existing protected directory.
     */
    public function addUser(Request $request, ProtectedDirectory $directory)
    {
        $domain = $directory->domain;
        $this->authorize($domain);

        $data = $request->validate([
            'username' => ['required', 'string', 'min:1', 'max:64', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'password' => 'required|string|min:6|max:200',
        ]);

        $passwordHash = crypt($data['password'], '$apr1$' . substr(bin2hex(random_bytes(8)), 0, 8) . '$');

        $directory->users()->updateOrCreate(
            ['username' => $data['username']],
            ['password_hash' => $passwordHash]
        );

        $this->syncToAgent($domain, $directory->path);

        return back()->with('success', "User \"{$data['username']}\" added.");
    }

    /**
     * DELETE /user/security/directory-protection/users/{user}
     */
    public function removeUser(ProtectedDirectoryUser $user)
    {
        $directory = $user->directory;
        $domain    = $directory->domain;
        $this->authorize($domain);

        $username = $user->username;
        $user->delete();

        // If no users remain, remove the directory entirely.
        if ($directory->users()->count() === 0) {
            return $this->destroy($directory);
        }

        $this->syncToAgent($domain, $directory->path);

        return back()->with('success', "User \"{$username}\" removed.");
    }

    /**
     * DELETE /user/security/directory-protection/{directory}
     */
    public function destroy(ProtectedDirectory $directory)
    {
        $domain = $directory->domain;
        $this->authorize($domain);

        $path = $directory->path;

        AgentService::for($domain->account->server)->post('/domains/protected-dir/remove', [
            'domain'           => $domain->domain,
            'username'         => $domain->account->username,
            'document_root'    => $domain->document_root,
            'path'             => $path,
            'htaccess_enabled' => $domain->htaccess_enabled,
        ]);

        $directory->delete();

        return back()->with('success', "Directory protection removed for \"{$path}\".");
    }

    /**
     * Push the protected directory + all its users to the agent.
     */
    private function syncToAgent(Domain $domain, string $path): void
    {
        $directory = $domain->protectedDirectories()->where('path', $path)->with('users')->firstOrFail();

        AgentService::for($domain->account->server)->post('/domains/protected-dir/add', [
            'domain'           => $domain->domain,
            'username'         => $domain->account->username,
            'document_root'    => $domain->document_root,
            'path'             => $directory->path,
            'label'            => $directory->label ?? 'Restricted Area',
            'htaccess_enabled' => $domain->htaccess_enabled,
            'users'            => $directory->users->map(fn ($u) => [
                'username'      => $u->username,
                'password_hash' => $u->password_hash,
            ])->toArray(),
        ]);
    }

    private function authorize(Domain $domain): void
    {
        abort_unless(in_array($domain->account_id, auth()->user()->currentAccountIds()), 403);
    }
}
