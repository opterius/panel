<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\AgentService;
use Illuminate\Http\Request;

class GitController extends Controller
{
    /**
     * Show the Git manager for a selected domain.
     */
    public function index(Request $request)
    {
        $domains = Domain::with('account.server')
            ->whereIn('account_id', auth()->user()->currentAccountIds())
            ->where('status', 'active')
            ->get();

        $selectedDomain = null;
        $status = null;
        $log    = null;

        $domainId = $request->query('domain_id') ?? $domains->first()?->id;

        if ($domainId) {
            $selectedDomain = $domains->firstWhere('id', $domainId);

            if ($selectedDomain) {
                $workingDir = dirname($selectedDomain->document_root);

                $statusResp = AgentService::for($selectedDomain->account->server)->post('/git/status', [
                    'working_dir' => $workingDir,
                    'username'    => $selectedDomain->account->username,
                ]);

                if ($statusResp && $statusResp->successful()) {
                    $status = $statusResp->json();

                    if ($status['initialized'] ?? false) {
                        $logResp = AgentService::for($selectedDomain->account->server)->post('/git/log', [
                            'working_dir' => $workingDir,
                            'username'    => $selectedDomain->account->username,
                            'limit'       => 10,
                        ]);
                        if ($logResp && $logResp->successful()) {
                            $log = $logResp->json()['commits'] ?? [];
                        }
                    }
                }
            }
        }

        return view('git.index', compact('domains', 'selectedDomain', 'status', 'log'));
    }

    /**
     * Clone a repository into the domain's working directory.
     */
    public function clone(Request $request)
    {
        $validated = $request->validate([
            'domain_id'    => 'required|exists:domains,id',
            'repo_url'     => 'required|string|max:500',
            'branch'       => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z0-9\/_.-]+$/'],
            'access_token' => 'nullable|string|max:500',
        ]);

        $domain = Domain::with('account.server')->findOrFail($validated['domain_id']);
        abort_unless(in_array($domain->account_id, auth()->user()->currentAccountIds()), 403);

        $workingDir = dirname($domain->document_root);

        $response = AgentService::for($domain->account->server)->post('/git/clone', [
            'working_dir'  => $workingDir,
            'username'     => $domain->account->username,
            'repo_url'     => $validated['repo_url'],
            'branch'       => $validated['branch'] ?? '',
            'access_token' => $validated['access_token'] ?? '',
        ]);

        $output  = null;
        $success = false;

        if ($response) {
            $data    = $response->json();
            $output  = $data['output'] ?? '';
            $success = $response->successful() && ($data['success'] ?? false);
        }

        return redirect()->route('user.git.index', ['domain_id' => $domain->id])
            ->with('git_output', $output)
            ->with('git_command', 'clone')
            ->with($success ? 'success' : 'error',
                $success ? 'Repository cloned successfully.' : 'Clone failed. See output below.'
            );
    }

    /**
     * Pull latest changes from remote.
     */
    public function pull(Request $request)
    {
        $validated = $request->validate([
            'domain_id'    => 'required|exists:domains,id',
            'access_token' => 'nullable|string|max:500',
        ]);

        $domain = Domain::with('account.server')->findOrFail($validated['domain_id']);
        abort_unless(in_array($domain->account_id, auth()->user()->currentAccountIds()), 403);

        $workingDir = dirname($domain->document_root);

        $response = AgentService::for($domain->account->server)->post('/git/pull', [
            'working_dir'  => $workingDir,
            'username'     => $domain->account->username,
            'access_token' => $validated['access_token'] ?? '',
        ]);

        $output  = null;
        $success = false;

        if ($response) {
            $data    = $response->json();
            $output  = $data['output'] ?? '';
            $success = $response->successful() && ($data['success'] ?? false);
        }

        return redirect()->route('user.git.index', ['domain_id' => $domain->id])
            ->with('git_output', $output)
            ->with('git_command', 'pull')
            ->with($success ? 'success' : 'error',
                $success ? 'git pull completed successfully.' : 'git pull failed. See output below.'
            );
    }
}
