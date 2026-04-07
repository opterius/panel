<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Domain;
use App\Models\Server;
use App\Models\Setting;
use App\Models\SslCertificate;
use App\Services\ActivityLogger;
use App\Services\AgentService;
use Illuminate\Http\Request;

class SslOverviewController extends Controller
{
    public function index(Request $request)
    {
        $servers = Server::all();
        $selectedServer = null;

        if ($request->has('server_id')) {
            $selectedServer = Server::findOrFail($request->server_id);
        } elseif ($servers->count() === 1) {
            $selectedServer = $servers->first();
        }

        $accounts = collect();
        $stats = [
            'total'        => 0,
            'active'       => 0,
            'pending'      => 0,
            'failed'       => 0,
            'missing'      => 0,
            'expiring'     => 0,
        ];

        if ($selectedServer) {
            // Get all accounts on this server with their domains and SSL status
            $accounts = Account::with([
                'user',
                'domains' => fn ($q) => $q->whereNull('parent_id')->orderBy('domain'),
                'domains.sslCertificate',
                'domains.subdomains.sslCertificate',
            ])
                ->where('server_id', $selectedServer->id)
                ->orderBy('username')
                ->get();

            // Sync any non-active certs against the live agent so the page reflects
            // reality (e.g. a "pending" cert that finished issuing in the background).
            // We only check non-active ones to keep the page snappy.
            foreach ($accounts as $account) {
                foreach ($account->domains as $domain) {
                    $this->syncIfStale($domain, $selectedServer);
                    foreach ($domain->subdomains as $sub) {
                        $this->syncIfStale($sub, $selectedServer);
                    }
                }
            }

            // Reload after sync so the view sees the updated cert records.
            $accounts = Account::with([
                'user',
                'domains' => fn ($q) => $q->whereNull('parent_id')->orderBy('domain'),
                'domains.sslCertificate',
                'domains.subdomains.sslCertificate',
            ])
                ->where('server_id', $selectedServer->id)
                ->orderBy('username')
                ->get();

            // Calculate stats across all domains
            foreach ($accounts as $account) {
                foreach ($account->domains as $domain) {
                    $this->countStat($domain, $stats);
                    foreach ($domain->subdomains as $sub) {
                        $this->countStat($sub, $stats);
                    }
                }
            }
        }

        $autoSslEnabled = Setting::get('auto_ssl_enabled', '1') === '1';

        return view('ssl-overview.index', compact('servers', 'selectedServer', 'accounts', 'stats', 'autoSslEnabled'));
    }

    /**
     * For any domain whose cert is NOT marked active, ask the agent whether the
     * cert file actually exists on disk and update the DB record accordingly.
     * Active certs are skipped to keep page loads fast.
     */
    private function syncIfStale(Domain $domain, Server $server): void
    {
        $cert = $domain->sslCertificate;
        if ($cert && $cert->status === 'active') {
            return;
        }

        $resp = AgentService::for($server)->post('/ssl/status', [
            'domain' => $domain->domain,
        ]);

        if (!$resp || !$resp->successful()) return;

        if ($resp->json('exists', false)) {
            SslCertificate::updateOrCreate(
                ['domain_id' => $domain->id],
                [
                    'type'       => $cert->type ?? 'letsencrypt',
                    'status'     => 'active',
                    'expires_at' => now()->addDays(90),
                    'auto_renew' => true,
                ]
            );
        }
    }

    private function countStat(Domain $domain, array &$stats): void
    {
        $stats['total']++;
        $cert = $domain->sslCertificate;

        if (!$cert) {
            $stats['missing']++;
            return;
        }

        switch ($cert->status) {
            case 'active':
                $stats['active']++;
                if ($cert->expires_at && $cert->expires_at->diffInDays(now(), false) > -30) {
                    $stats['expiring']++;
                }
                break;
            case 'pending':
                $stats['pending']++;
                break;
            case 'error':
            case 'failed':
                $stats['failed']++;
                break;
            default:
                $stats['missing']++;
        }
    }

    /**
     * Toggle the auto-SSL global setting.
     */
    public function toggleAutoSsl(Request $request)
    {
        $enabled = $request->boolean('enabled');
        Setting::set('auto_ssl_enabled', $enabled ? '1' : '0', 'ssl');

        ActivityLogger::log('settings.auto_ssl', null, null, null,
            'Auto-SSL ' . ($enabled ? 'enabled' : 'disabled'));

        return back()->with('success', 'Auto-SSL ' . ($enabled ? 'enabled' : 'disabled') . '. New domains will ' . ($enabled ? '' : 'NOT ') . 'auto-issue SSL.');
    }

    /**
     * Bulk re-issue SSL for all domains/subdomains WITHOUT a valid cert.
     * Skips domains that already have an active cert (saves Let's Encrypt rate limit).
     */
    public function recheckMissing(Request $request)
    {
        $request->validate(['server_id' => 'required|exists:servers,id']);
        $server = Server::findOrFail($request->server_id);

        $domains = Domain::with('account.server', 'sslCertificate')
            ->where('server_id', $server->id)
            ->where('status', 'active')
            ->get();

        $issued = 0;
        $skipped = 0;

        foreach ($domains as $domain) {
            $cert = $domain->sslCertificate;

            // Check actual cert status from agent (don't trust DB alone)
            $statusResp = AgentService::for($server)->post('/ssl/status', [
                'domain' => $domain->domain,
            ]);

            $exists = $statusResp && $statusResp->successful() && $statusResp->json('exists');

            if ($exists) {
                // Cert really exists - mark active in DB and skip
                if (!$cert || $cert->status !== 'active') {
                    SslCertificate::updateOrCreate(
                        ['domain_id' => $domain->id],
                        ['type' => 'letsencrypt', 'status' => 'active', 'expires_at' => now()->addDays(90), 'auto_renew' => true]
                    );
                }
                $skipped++;
                continue;
            }

            // Cert missing - trigger async issuance
            $email = auth()->user()->email ?? 'admin@' . $domain->domain;
            AgentService::for($server)->post('/ssl/issue-async', [
                'domain'   => $domain->domain,
                'email'    => $email,
                'username' => $domain->account->username ?? '',
            ]);

            SslCertificate::updateOrCreate(
                ['domain_id' => $domain->id],
                ['type' => 'letsencrypt', 'status' => 'pending', 'expires_at' => now()->addDays(90), 'auto_renew' => true]
            );

            $issued++;
        }

        ActivityLogger::log('ssl.bulk_recheck', 'server', $server->id, $server->name,
            "Bulk SSL recheck on {$server->name}: {$issued} issued, {$skipped} already valid");

        return back()->with('success', "Re-check complete: {$issued} certificates queued for issuance, {$skipped} already valid (skipped to save rate limits).");
    }
}
