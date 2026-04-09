<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CdnZone;
use App\Models\Domain;
use App\Services\ActivityLogger;
use App\Services\AgentService;
use App\Services\BunnyCdnClient;
use Illuminate\Http\Request;
use RuntimeException;

class CdnController extends Controller
{
    /**
     * GET /user/cdn — list every domain with current CDN status.
     */
    public function index()
    {
        $domains = Domain::with(['account', 'cdnZone'])
            ->whereIn('account_id', auth()->user()->currentAccountIds())
            ->whereNull('parent_id')   // top-level only
            ->orderBy('domain')
            ->get();

        return view('user.cdn.index', [
            'domains'    => $domains,
            'configured' => BunnyCdnClient::isConfigured(),
        ]);
    }

    /**
     * POST /user/cdn/{domain}/enable — create a Pull Zone and switch on URL rewriting.
     */
    public function enable(Domain $domain)
    {
        $this->authorizeOwner($domain);

        if (! BunnyCdnClient::isConfigured()) {
            return back()->with('error', 'CDN integration is not configured by the administrator.');
        }

        // If the domain already has a zone, just re-enable it instead of recreating.
        $zone = $domain->cdnZone;

        try {
            $client = new BunnyCdnClient();

            if (! $zone || ! $zone->zone_id) {
                $created = $client->createPullZone($domain->domain);

                $hostname = $created['Hostnames'][0]['Value']
                    ?? ($created['Name'] ?? '') . '.b-cdn.net';

                $zone = CdnZone::updateOrCreate(
                    ['domain_id' => $domain->id],
                    [
                        'provider'       => 'bunnycdn',
                        'enabled'        => true,
                        'zone_id'        => $created['Id'] ?? null,
                        'zone_name'      => $created['Name'] ?? null,
                        'cdn_hostname'   => $hostname,
                        'rewrite_paths'  => CdnZone::DEFAULT_REWRITE_PATHS,
                        'last_synced_at' => now(),
                    ]
                );
            } else {
                $zone->update(['enabled' => true, 'last_synced_at' => now()]);
            }
        } catch (RuntimeException $e) {
            return back()->with('error', 'BunnyCDN error: ' . $e->getMessage());
        }

        // Push the Nginx sub_filter rewrite to the agent.
        $this->pushToAgent($domain, $zone);

        ActivityLogger::log('cdn.enabled', 'domain', $domain->id, $domain->domain,
            "Enabled CDN for {$domain->domain} (zone {$zone->cdn_hostname})");

        return back()->with('success', "CDN enabled. Asset URLs are now being rewritten to {$zone->cdnUrl()}.");
    }

    /**
     * POST /user/cdn/{domain}/disable
     */
    public function disable(Domain $domain)
    {
        $this->authorizeOwner($domain);

        $zone = $domain->cdnZone;
        if (! $zone) {
            return back()->with('error', 'CDN is not enabled for this domain.');
        }

        // Try to delete the BunnyCDN pull zone — best effort. If it fails (e.g.
        // the API key was revoked) we still want to disable the rewrite locally.
        try {
            if ($zone->zone_id) {
                (new BunnyCdnClient())->deletePullZone($zone->zone_id);
            }
        } catch (RuntimeException $e) {
            // Don't block the local disable on a remote failure.
        }

        $zone->update(['enabled' => false]);
        $this->pushToAgent($domain, $zone); // pushToAgent removes the include when disabled

        ActivityLogger::log('cdn.disabled', 'domain', $domain->id, $domain->domain,
            "Disabled CDN for {$domain->domain}");

        return back()->with('success', 'CDN disabled. URL rewriting has been removed from the Nginx vhost.');
    }

    /**
     * POST /user/cdn/{domain}/purge — flush the BunnyCDN cache for this zone.
     */
    public function purge(Domain $domain)
    {
        $this->authorizeOwner($domain);

        $zone = $domain->cdnZone;
        if (! $zone || ! $zone->zone_id) {
            return back()->with('error', 'CDN is not active for this domain.');
        }

        try {
            $ok = (new BunnyCdnClient())->purgeCache((int) $zone->zone_id);
            if (! $ok) {
                return back()->with('error', 'BunnyCDN refused the purge request.');
            }
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        ActivityLogger::log('cdn.purged', 'domain', $domain->id, $domain->domain,
            "Purged CDN cache for {$domain->domain}");

        return back()->with('success', 'CDN cache purged. New visitors will fetch fresh assets.');
    }

    /**
     * POST /user/cdn/{domain}/paths — update the rewrite path list.
     */
    public function updatePaths(Request $request, Domain $domain)
    {
        $this->authorizeOwner($domain);

        $data = $request->validate([
            'paths' => 'required|string|max:1000',
        ]);

        // Split on newlines or commas, normalise, dedupe.
        $paths = collect(preg_split('/[\r\n,]+/', $data['paths']))
            ->map(fn ($p) => '/' . trim($p, "/ \t"))
            ->filter(fn ($p) => $p !== '/' && preg_match('#^/[a-zA-Z0-9_\-./]+/$#', $p))
            ->unique()
            ->values()
            ->all();

        if (empty($paths)) {
            return back()->with('error', 'No valid paths provided. Each path must look like /folder/.');
        }

        $zone = $domain->cdnZone;
        if (! $zone) {
            return back()->with('error', 'CDN must be enabled before configuring paths.');
        }

        $zone->update(['rewrite_paths' => $paths]);
        $this->pushToAgent($domain, $zone);

        return back()->with('success', 'Rewrite paths updated.');
    }

    /**
     * Forward the current zone state to the agent — it writes / updates / removes
     * the per-domain Nginx include and reloads Nginx.
     */
    private function pushToAgent(Domain $domain, CdnZone $zone): void
    {
        AgentService::for($domain->account->server)->post('/cdn/configure', [
            'domain'        => $domain->domain,
            'enabled'       => $zone->enabled,
            'cdn_hostname'  => $zone->cdn_hostname,
            'rewrite_paths' => $zone->rewrite_paths ?? [],
        ]);
    }

    private function authorizeOwner(Domain $domain): void
    {
        abort_unless(in_array($domain->account_id, auth()->user()->currentAccountIds()), 403);
    }
}
