<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\EmailAccount;
use App\Services\ActivityLogger;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class EmailController extends Controller
{
    public function index()
    {
        $emailAccounts = EmailAccount::with('domain.server', 'domain.account')
            ->whereHas('domain.account', fn ($q) => $q->where('user_id', Auth::id()))
            ->latest()
            ->get();

        $domains = Domain::with('account.server')
            ->whereHas('account', fn ($q) => $q->where('user_id', Auth::id()))
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
        $email = $validated['username'] . '@' . $domain->domain;

        // Check uniqueness
        if (EmailAccount::where('email', $email)->exists()) {
            return back()->with('error', 'Email account ' . $email . ' already exists.')->withInput();
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

            return redirect()->route('user.emails.index')->with('success', 'Email account ' . $email . ' created.');
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return back()->with('error', 'Failed to create email: ' . $error)->withInput();
    }

    public function changePassword(Request $request, EmailAccount $emailAccount)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8',
        ]);

        $emailAccount->load('domain.account.server');

        $response = AgentService::for($emailAccount->domain->account->server)->post('/email/password', [
            'email'    => $emailAccount->email,
            'password' => $validated['password'],
        ]);

        if ($response && $response->successful()) {
            ActivityLogger::log('email.password_changed', 'email', $emailAccount->id, $emailAccount->email,
                "Changed password for email {$emailAccount->email}");

            return redirect()->route('user.emails.index')->with('success', 'Password changed for ' . $emailAccount->email);
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return back()->with('error', 'Failed to change password: ' . $error);
    }

    public function updateQuota(Request $request, EmailAccount $emailAccount)
    {
        $validated = $request->validate([
            'quota' => 'required|integer|min:0|max:51200',
        ]);

        $emailAccount->update(['quota' => $validated['quota']]);

        return redirect()->route('user.emails.index')->with('success', 'Quota updated for ' . $emailAccount->email);
    }

    public function updateRestrictions(Request $request, EmailAccount $emailAccount)
    {
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

        return redirect()->route('user.emails.index')->with('success', 'Restrictions updated for ' . $emailAccount->email);
    }

    public function destroy(Request $request, EmailAccount $emailAccount)
    {
        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => 'The password is incorrect.']);
        }

        $emailAccount->load('domain.account.server');

        ActivityLogger::log('email.deleted', 'email', $emailAccount->id, $emailAccount->email,
            "Deleted email account {$emailAccount->email}", ['domain_id' => $emailAccount->domain_id]);

        AgentService::for($emailAccount->domain->account->server)->post('/email/delete', [
            'email'  => $emailAccount->email,
            'domain' => $emailAccount->domain->domain,
        ]);

        $emailAccount->delete();

        return redirect()->route('user.emails.index')->with('success', 'Email account ' . $emailAccount->email . ' deleted.');
    }
}
