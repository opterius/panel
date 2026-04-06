<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\EmailAccount;
use App\Services\AgentService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class AutoresponderController extends Controller
{
    public function index(Request $request)
    {
        $domains = Domain::with('account.server')
            ->whereIn('account_id', auth()->user()->accessibleAccountIds())
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->get();

        $selectedDomain = null;
        $emailAccounts = [];

        if ($request->has('domain_id')) {
            $selectedDomain = Domain::with('account.server')
                ->whereIn('account_id', auth()->user()->accessibleAccountIds())
                ->findOrFail($request->domain_id);

            // Get email accounts for this domain
            $emails = EmailAccount::where('domain_id', $selectedDomain->id)->get();

            // Fetch autoresponder status for each email from agent
            foreach ($emails as $email) {
                $response = AgentService::for($selectedDomain->account->server)->post('/autoresponder/get', [
                    'email' => $email->email,
                ]);

                $emailAccounts[] = [
                    'email'   => $email->email,
                    'enabled' => ($response && $response->successful()) ? ($response->json('enabled') ?? false) : false,
                    'subject' => ($response && $response->successful()) ? ($response->json('subject') ?? '') : '',
                    'body'    => ($response && $response->successful()) ? ($response->json('body') ?? '') : '',
                ];
            }
        }

        return view('autoresponders.index', compact('domains', 'selectedDomain', 'emailAccounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'domain_id' => 'required|exists:domains,id',
            'email'     => 'required|email',
            'enabled'   => 'boolean',
            'subject'   => 'required_if:enabled,1|nullable|string|max:255',
            'body'      => 'required_if:enabled,1|nullable|string|max:5000',
        ]);

        $domain = Domain::with('account.server')
            ->whereIn('account_id', auth()->user()->accessibleAccountIds())
            ->findOrFail($validated['domain_id']);

        if (!$domain->account->userCan(auth()->user(), 'email')) {
            return back()->with('error', 'You do not have permission to manage autoresponders.');
        }

        $enabled = $request->boolean('enabled');

        $response = AgentService::for($domain->account->server)->post('/autoresponder/set', [
            'email'   => $validated['email'],
            'enabled' => $enabled,
            'subject' => $validated['subject'] ?? '',
            'body'    => $validated['body'] ?? '',
        ]);

        if ($response && $response->successful()) {
            $action = $enabled ? 'enabled' : 'disabled';
            ActivityLogger::log("autoresponder.{$action}", 'domain', $domain->id, $validated['email'],
                ucfirst($action) . " autoresponder for {$validated['email']}");

            return redirect()->route('user.autoresponders.index', ['domain_id' => $domain->id])
                ->with('success', "Autoresponder {$action} for {$validated['email']}.");
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Agent unreachable';
        return back()->with('error', $error)->withInput();
    }
}
