<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\EmailAccount;
use App\Services\ActivityLogger;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmailController extends Controller
{
    public function index()
    {
        $emailAccounts = EmailAccount::with('domain.server', 'domain.account')
            ->whereHas('domain', fn ($q) => $q->whereIn('account_id', auth()->user()->accessibleAccountIds()))
            ->latest()
            ->get();

        $domains = Domain::with('account.server')
            ->whereIn('account_id', auth()->user()->accessibleAccountIds())
            ->where('status', 'active')
            ->get();

        return view('emails.index', compact('emailAccounts', 'domains'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'domain_id' => 'required|exists:domains,id',
            'username'  => ['required', 'string', 'min:2', 'max:25', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9._-]*[a-zA-Z0-9]$/'],
            'password'  => ['required', 'string', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).+$/'],
            'quota'     => 'nullable|integer|min:0|max:51200',
        ], [
            'username.regex' => 'Username can only contain letters, numbers, dots, hyphens, underscores. Must start and end with a letter or number.',
            'password.regex' => 'Password must contain at least one uppercase, one lowercase, and one number.',
        ]);

        $domain = Domain::with('account.server')->findOrFail($validated['domain_id']);

        if (!$domain->account->userCan(auth()->user(), 'email')) {
            return back()->with('error', __('emails.no_permission'));
        }

        $email = $validated['username'] . '@' . $domain->domain;

        // Check uniqueness
        if (EmailAccount::where('email', $email)->exists()) {
            return back()->with('error', __('emails.email_already_exists', ['email' => $email]))->withInput();
        }

        $response = AgentService::for($domain->account->server)->post('/email/create', [
            'email'    => $email,
            'password' => $validated['password'],
            'quota'    => $validated['quota'] ?? 0,
            'domain'   => $domain->domain,
        ]);

        if ($response && $response->successful()) {
            $emailAccount = EmailAccount::create([
                'domain_id' => $domain->id,
                'email'     => $email,
                'quota'     => $validated['quota'] ?? 0,
                'status'    => 'active',
            ]);

            ActivityLogger::log('email.created', 'email', $emailAccount->id, $emailAccount->email,
                "Created email account {$email}", ['domain_id' => $domain->id]);

            return redirect()->route('user.emails.index')->with('success', __('emails.email_account_created', ['email' => $email]));
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return back()->with('error', __('emails.failed_to_create_email', ['error' => $error]))->withInput();
    }

    public function changePassword(Request $request, EmailAccount $emailAccount)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8',
        ]);

        $emailAccount->load('domain.account.server');

        if (!$emailAccount->domain->account->userCan(auth()->user(), 'email')) {
            return back()->with('error', __('emails.no_permission'));
        }

        $response = AgentService::for($emailAccount->domain->account->server)->post('/email/password', [
            'email'    => $emailAccount->email,
            'password' => $validated['password'],
        ]);

        if ($response && $response->successful()) {
            ActivityLogger::log('email.password_changed', 'email', $emailAccount->id, $emailAccount->email,
                "Changed password for email {$emailAccount->email}");

            return redirect()->route('user.emails.index')->with('success', __('emails.password_changed', ['email' => $emailAccount->email]));
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return back()->with('error', __('emails.failed_to_change_password', ['error' => $error]));
    }

    public function updateQuota(Request $request, EmailAccount $emailAccount)
    {
        $validated = $request->validate([
            'quota' => 'required|integer|min:0|max:51200',
        ]);

        $emailAccount->update(['quota' => $validated['quota']]);

        return redirect()->route('user.emails.index')->with('success', __('emails.quota_updated', ['email' => $emailAccount->email]));
    }

    public function updateRestrictions(Request $request, EmailAccount $emailAccount)
    {
        $emailAccount->load('domain.account');

        if (!$emailAccount->domain->account->userCan(auth()->user(), 'email')) {
            return back()->with('error', __('emails.no_permission'));
        }

        $validated = $request->validate([
            'can_send'           => 'boolean',
            'can_receive'        => 'boolean',
            'max_send_per_hour'  => 'nullable|integer|min:0|max:10000',
            'max_send_per_day'   => 'nullable|integer|min:0|max:100000',
            'max_send_per_week'  => 'nullable|integer|min:0|max:500000',
            'max_send_per_month' => 'nullable|integer|min:0|max:2000000',
        ]);

        $emailAccount->update([
            'can_send'           => $request->boolean('can_send'),
            'can_receive'        => $request->boolean('can_receive'),
            'max_send_per_hour'  => $validated['max_send_per_hour'] ?? 0,
            'max_send_per_day'   => $validated['max_send_per_day'] ?? 0,
            'max_send_per_week'  => $validated['max_send_per_week'] ?? 0,
            'max_send_per_month' => $validated['max_send_per_month'] ?? 0,
        ]);

        ActivityLogger::log('email.restrictions_changed', 'email', $emailAccount->id, $emailAccount->email,
            "Updated restrictions for email {$emailAccount->email}", [
                'can_send' => $request->boolean('can_send'),
                'can_receive' => $request->boolean('can_receive'),
            ]);

        return redirect()->route('user.emails.index')->with('success', __('emails.restrictions_updated', ['email' => $emailAccount->email]));
    }

    public function destroy(Request $request, EmailAccount $emailAccount)
    {
        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => __('common.password_incorrect')]);
        }

        $emailAccount->load('domain.account.server');

        if (!$emailAccount->domain->account->userCan(auth()->user(), 'email')) {
            return back()->with('error', __('emails.no_permission'));
        }

        ActivityLogger::log('email.deleted', 'email', $emailAccount->id, $emailAccount->email,
            "Deleted email account {$emailAccount->email}", ['domain_id' => $emailAccount->domain_id]);

        AgentService::for($emailAccount->domain->account->server)->post('/email/delete', [
            'email'  => $emailAccount->email,
            'domain' => $emailAccount->domain->domain,
        ]);

        $emailAccount->delete();

        return redirect()->route('user.emails.index')->with('success', __('emails.email_account_deleted', ['email' => $emailAccount->email]));
    }
}
