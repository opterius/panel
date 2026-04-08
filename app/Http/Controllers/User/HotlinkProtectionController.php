<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\HotlinkProtection;
use App\Services\AgentService;
use Illuminate\Http\Request;

class HotlinkProtectionController extends Controller
{
    /**
     * GET /user/security/hotlink-protection
     * List all domains with their hotlink protection state.
     */
    public function index()
    {
        $domains = Domain::with(['account', 'hotlinkProtection'])
            ->whereIn('account_id', auth()->user()->currentAccountIds())
            ->orderBy('domain')
            ->get();

        return view('user.security.hotlink-protection.index', compact('domains'));
    }

    /**
     * POST /user/security/hotlink-protection/{domain}
     * Enable / update / disable hotlink protection for one domain.
     */
    public function update(Request $request, Domain $domain)
    {
        $this->authorize($domain);

        $data = $request->validate([
            'enabled'            => 'required|boolean',
            'allowed_domains'    => 'nullable|string|max:1000',  // textarea, one per line
            'allowed_extensions' => 'nullable|string|max:200',   // comma-separated
            'allow_direct'       => 'nullable|boolean',
            'redirect_url'       => 'nullable|url|max:255',
        ]);

        $allowedDomains = collect(preg_split('/[\s,]+/', $data['allowed_domains'] ?? ''))
            ->map(fn ($d) => trim($d))
            ->filter()
            ->values()
            ->all();

        $allowedExtensions = collect(explode(',', $data['allowed_extensions'] ?? ''))
            ->map(fn ($e) => trim(strtolower($e), '. '))
            ->filter()
            ->values()
            ->all();

        if (empty($allowedExtensions)) {
            $allowedExtensions = HotlinkProtection::DEFAULT_EXTENSIONS;
        }

        // Persist to DB
        $protection = HotlinkProtection::updateOrCreate(
            ['domain_id' => $domain->id],
            [
                'enabled'            => (bool) $data['enabled'],
                'allowed_domains'    => $allowedDomains,
                'allowed_extensions' => $allowedExtensions,
                'allow_direct'       => (bool) ($data['allow_direct'] ?? true),
                'redirect_url'       => $data['redirect_url'] ?? null,
            ]
        );

        // Push to agent
        $response = AgentService::for($domain->account->server)->post('/domains/hotlink/set', [
            'domain'             => $domain->domain,
            'username'           => $domain->account->username,
            'document_root'      => $domain->document_root,
            'htaccess_enabled'   => $domain->htaccess_enabled,
            'enabled'            => $protection->enabled,
            'allowed_domains'    => $protection->allowed_domains ?? [],
            'allowed_extensions' => $protection->allowed_extensions ?? [],
            'allow_direct'       => $protection->allow_direct,
            'redirect_url'       => $protection->redirect_url,
        ]);

        if (! $response || ! $response->successful()) {
            $error = $response?->json('error') ?? 'Agent unreachable';
            return back()->with('error', 'Failed to update hotlink protection: ' . $error);
        }

        $msg = $protection->enabled
            ? "Hotlink protection enabled for {$domain->domain}."
            : "Hotlink protection disabled for {$domain->domain}.";

        return back()->with('success', $msg);
    }

    private function authorize(Domain $domain): void
    {
        abort_unless(in_array($domain->account_id, auth()->user()->currentAccountIds()), 403);
    }
}
