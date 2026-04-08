<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\AgentService;
use Illuminate\Http\Request;

class ComposerController extends Controller
{
    /**
     * Show the Composer manager for a selected domain.
     * Domain is passed as a query param: /composer?domain_id=X
     */
    public function index(Request $request)
    {
        $domains = Domain::with('account.server')
            ->whereIn('account_id', auth()->user()->currentAccountIds())
            ->where('status', 'active')
            ->get();

        $selectedDomain = null;
        $info = null;

        $domainId = $request->query('domain_id') ?? $domains->first()?->id;

        if ($domainId) {
            $selectedDomain = $domains->firstWhere('id', $domainId);

            if ($selectedDomain) {
                $workingDir = dirname($selectedDomain->document_root);

                $response = AgentService::for($selectedDomain->account->server)->post('/composer/info', [
                    'working_dir' => $workingDir,
                ]);

                if ($response && $response->successful()) {
                    $info = $response->json();
                } elseif ($response && $response->status() === 404) {
                    $info = ['error' => 'No composer.json found in ' . $workingDir];
                }
            }
        }

        return view('composer.index', compact('domains', 'selectedDomain', 'info'));
    }

    /**
     * Run a Composer command (install, update, require, remove, dump-autoload).
     */
    public function run(Request $request)
    {
        $validated = $request->validate([
            'domain_id' => 'required|exists:domains,id',
            'command'   => 'required|in:install,update,require,remove,dump-autoload',
            'packages'  => 'nullable|string|max:500',
            'flags'     => 'nullable|array',
            'flags.*'   => 'in:--no-dev,--optimize-autoloader,--no-scripts,--prefer-dist,--prefer-source,--with-all-dependencies',
        ]);

        $domain = Domain::with('account.server')->findOrFail($validated['domain_id']);
        abort_unless(in_array($domain->account_id, auth()->user()->currentAccountIds()), 403);

        $workingDir = dirname($domain->document_root);

        // Parse space-separated package list for require / remove
        $packages = [];
        if (!empty($validated['packages'])) {
            $packages = array_filter(array_map('trim', explode(' ', $validated['packages'])));
        }

        $payload = [
            'working_dir' => $workingDir,
            'username'    => $domain->account->username,
            'command'     => $validated['command'],
            'packages'    => $packages,
            'flags'       => $validated['flags'] ?? [],
        ];

        $response = AgentService::for($domain->account->server)->post('/composer/run', $payload);

        $output = null;
        $success = false;

        if ($response) {
            $data    = $response->json();
            $output  = $data['output'] ?? '';
            $success = $response->successful() && ($data['success'] ?? false);
        }

        return redirect()->route('user.composer.index', ['domain_id' => $domain->id])
            ->with('composer_output', $output)
            ->with('composer_command', $validated['command'])
            ->with($success ? 'success' : 'error',
                $success
                    ? 'composer ' . $validated['command'] . ' completed successfully.'
                    : 'composer ' . $validated['command'] . ' failed. See output below.'
            );
    }
}
