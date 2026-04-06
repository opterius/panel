<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CollaboratorController extends Controller
{
    public function index(Account $account)
    {
        $account->load('collaborators', 'user');

        $roles = [
            'owner'         => 'Owner — Full control',
            'admin'         => 'Admin — Everything except account deletion',
            'developer'     => 'Developer — Files, Databases, SSH, Cron',
            'designer'      => 'Designer — Files only',
            'email_manager' => 'Email Manager — Email accounts only',
            'viewer'        => 'Viewer — Read-only access',
        ];

        return view('collaborators.index', compact('account', 'roles'));
    }

    public function store(Request $request, Account $account)
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'nullable|string|min:8',
            'name'     => 'nullable|string|max:255',
            'role'     => 'required|in:owner,admin,developer,designer,email_manager,viewer',
        ]);

        // Find or create the user
        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            if (empty($validated['password'])) {
                return back()->with('error', __('collaborators.user_not_found_provide_password'))->withInput();
            }

            $user = User::create([
                'name'     => $validated['name'] ?? explode('@', $validated['email'])[0],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role'     => 'user',
            ]);
        }

        // Check if already a collaborator
        if ($account->collaborators()->where('user_id', $user->id)->exists()) {
            return back()->with('error', __('collaborators.already_a_collaborator', ['email' => $user->email]))->withInput();
        }

        $account->collaborators()->attach($user->id, ['role' => $validated['role']]);

        ActivityLogger::log('collaborator.added', 'account', $account->id, $user->email,
            "Added {$user->email} as {$validated['role']} to {$account->username}");

        return redirect()->route('admin.collaborators.index', $account)
            ->with('success', __('collaborators.collaborator_added_as_role', ['email' => $user->email, 'role' => $validated['role']]));
    }

    public function updateRole(Request $request, Account $account, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|in:owner,admin,developer,designer,email_manager,viewer',
        ]);

        $account->collaborators()->updateExistingPivot($user->id, ['role' => $validated['role']]);

        ActivityLogger::log('collaborator.role_changed', 'account', $account->id, $user->email,
            "Changed {$user->email} role to {$validated['role']} on {$account->username}");

        return redirect()->route('admin.collaborators.index', $account)
            ->with('success', __('collaborators.role_updated_for', ['email' => $user->email]));
    }

    public function destroy(Account $account, User $user)
    {
        $account->collaborators()->detach($user->id);

        ActivityLogger::log('collaborator.removed', 'account', $account->id, $user->email,
            "Removed {$user->email} from {$account->username}");

        return redirect()->route('admin.collaborators.index', $account)
            ->with('success', __('collaborators.collaborator_removed_from', ['email' => $user->email]));
    }
}
