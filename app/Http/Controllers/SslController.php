<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Setting;
use App\Models\SslCertificate;
use App\Services\ActivityLogger;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SslController extends Controller
{
    public function index()
    {
        // Get all main domains for the current account, with their subdomains and SSL records
        $mainDomains = Domain::with('server', 'account', 'sslCertificate', 'subdomains.sslCertificate', 'subdomains.server')
            ->whereIn('account_id', auth()->user()->currentAccountIds())
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->orderBy('domain')
            ->get();

        // Sync DB status with actual cert files on disk (for any domain that has a cert record)
        foreach ($mainDomains as $main) {
            $this->syncDomainSslStatus($main);
            foreach ($main->subdomains as $sub) {
                $this->syncDomainSslStatus($sub);
            }
        }

        // Reload after sync
        $mainDomains = Domain::with('server', 'account', 'sslCertificate', 'subdomains.sslCertificate', 'subdomains.server')
            ->whereIn('account_id', auth()->user()->currentAccountIds())
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->orderBy('domain')
            ->get();

        return view('ssl.index', compact('mainDomains'));
    }

    /**
     * Check the actual cert file on the server and update the DB record.
     */
    private function syncDomainSslStatus(Domain $domain): void
    {
        if (!$domain->account || !$domain->account->server) return;

        $response = AgentService::for($domain->account->server)->post('/ssl/status', [
            'domain' => $domain->domain,
        ]);

        if (!$response || !$response->successful()) return;

        $exists = $response->json('exists', false);
        $cert = $domain->sslCertificate;

        if ($exists) {
            // Cert exists on disk → mark active
            if (!$cert || $cert->status !== 'active') {
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
        } else if ($cert && $cert->status !== 'pending') {
            // Cert was supposed to exist but doesn't → mark error
            $cert->update(['status' => 'error']);
        }
    }

    public function issue(Request $request)
    {
        $validated = $request->validate([
            'domain_id' => 'required|exists:domains,id',
            'email'     => 'required|email',
        ]);

        $domain = Domain::with('account.server')->findOrFail($validated['domain_id']);

        if (!$domain->account->userCan(auth()->user(), 'ssl')) {
            return back()->with('error', __('ssl.no_permission'));
        }

        // Use async endpoint — returns immediately
        $response = AgentService::for($domain->account->server)->post('/ssl/issue-async', [
            'domain'   => $domain->domain,
            'email'    => $validated['email'],
            'username' => $domain->account->username,
        ]);

        if ($response && ($response->successful() || $response->status() === 202)) {
            SslCertificate::updateOrCreate(
                ['domain_id' => $domain->id],
                [
                    'type'       => 'letsencrypt',
                    'status'     => 'pending',
                    'expires_at' => now()->addDays(90),
                    'auto_renew' => true,
                ]
            );

            ActivityLogger::log('ssl.issued', 'ssl_certificate', null, $domain->domain,
                "SSL issuance started for {$domain->domain}", ['domain_id' => $domain->id]);

            return redirect()->route('user.ssl.index')->with('success', __('ssl.ssl_certificate_issued', ['domain' => $domain->domain]));
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return redirect()->route('user.ssl.index')->with('error', __('ssl.ssl_issuance_failed', ['error' => $error]));
    }

    public function upload(Request $request)
    {
        $validated = $request->validate([
            'domain_id'   => 'required|exists:domains,id',
            'certificate' => 'required|string',
            'private_key' => 'required|string',
        ]);

        $domain = Domain::with('account.server')->findOrFail($validated['domain_id']);

        if (!$domain->account->userCan(auth()->user(), 'ssl')) {
            return back()->with('error', __('ssl.no_permission'));
        }

        $response = AgentService::for($domain->account->server)->post('/ssl/upload', [
            'domain'      => $domain->domain,
            'username'    => $domain->account->username,
            'certificate' => $validated['certificate'],
            'private_key' => $validated['private_key'],
        ]);

        if ($response && $response->successful()) {
            $ssl = SslCertificate::updateOrCreate(
                ['domain_id' => $domain->id],
                [
                    'type'       => 'custom',
                    'status'     => 'active',
                    'auto_renew' => false,
                ]
            );

            ActivityLogger::log('ssl.uploaded', 'ssl_certificate', $ssl->id, $domain->domain,
                "Uploaded custom SSL for {$domain->domain}", ['domain_id' => $domain->id]);

            return redirect()->route('user.ssl.index')->with('success', __('ssl.custom_ssl_installed', ['domain' => $domain->domain]));
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return redirect()->route('user.ssl.index')->with('error', __('ssl.ssl_upload_failed', ['error' => $error]));
    }

    public function renew(SslCertificate $certificate)
    {
        $certificate->load('domain.account.server');

        $response = AgentService::for($certificate->domain->account->server)->post('/ssl/renew', [
            'domain' => $certificate->domain->domain,
        ]);

        if ($response && $response->successful()) {
            $certificate->update([
                'status'     => 'active',
                'expires_at' => now()->addDays(90),
            ]);

            ActivityLogger::log('ssl.renewed', 'ssl_certificate', $certificate->id, $certificate->domain->domain,
                "Renewed SSL certificate for {$certificate->domain->domain}", ['domain_id' => $certificate->domain_id]);

            return redirect()->route('user.ssl.index')->with('success', __('ssl.ssl_renewed', ['domain' => $certificate->domain->domain]));
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return redirect()->route('user.ssl.index')->with('error', __('ssl.ssl_renewal_failed', ['error' => $error]));
    }

    public function destroy(Request $request, SslCertificate $certificate)
    {
        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => __('common.password_incorrect')]);
        }

        $certificate->load('domain.account.server');

        if (!$certificate->domain->account->userCan(auth()->user(), 'ssl')) {
            return back()->with('error', __('ssl.no_permission'));
        }

        // Tell the agent to delete the actual cert files from the server
        // (certbot delete + cleanup + rewrite Nginx vhost to HTTP-only).
        // Without this the panel only deleted its own DB record but the
        // stale cert stayed on disk and Nginx kept serving it.
        AgentService::for($certificate->domain->account->server)->post('/ssl/delete', [
            'domain'   => $certificate->domain->domain,
            'username' => $certificate->domain->account->username,
        ]);

        ActivityLogger::log('ssl.deleted', 'ssl_certificate', $certificate->id, null,
            "Deleted SSL certificate for " . $certificate->domain->domain,
            ['domain_id' => $certificate->domain_id]);

        $certificate->delete();

        return redirect()->route('user.ssl.index')->with('success', __('ssl.ssl_deleted'));
    }

    public function issueWildcard(Request $request)
    {
        $validated = $request->validate([
            'domain_id' => 'required|exists:domains,id',
        ]);

        $domain = Domain::with('account.server')->findOrFail($validated['domain_id']);

        if (!$domain->account->userCan(auth()->user(), 'ssl')) {
            return response()->json(['error' => __('ssl.no_permission')], 403);
        }

        $response = AgentService::for($domain->account->server)->post('/ssl/wildcard/start', [
            'domain'   => $domain->domain,
            'email'    => auth()->user()->email,
            'username' => $domain->account->username,
        ]);

        if (!$response || (!$response->successful() && $response->status() !== 202)) {
            $error = $response ? $response->json('error', 'Agent unreachable') : 'Could not connect to server agent';
            return response()->json(['error' => $error], 500);
        }

        $cert = SslCertificate::updateOrCreate(
            ['domain_id' => $domain->id],
            [
                'type'             => 'wildcard',
                'status'           => 'pending',
                'expires_at'       => now()->addDays(90),
                'auto_renew'       => true,
                'progress_step'    => 'starting',
                'progress_message' => 'Preparing wildcard certificate issuance...',
            ]
        );

        ActivityLogger::log('ssl.wildcard_started', 'ssl_certificate', $cert->id, $domain->domain,
            "Wildcard SSL issuance started for {$domain->domain}", ['domain_id' => $domain->id]);

        return response()->json(['success' => true, 'cert_id' => $cert->id]);
    }

    public function wildcardProgress(Request $request)
    {
        $validated = $request->validate(['domain_id' => 'required|exists:domains,id']);

        $domain = Domain::with('account.server')->findOrFail($validated['domain_id']);

        $response = AgentService::for($domain->account->server)->get(
            '/ssl/wildcard/progress?domain=' . urlencode($domain->domain)
        );

        if (!$response || !$response->successful()) {
            return response()->json(['step' => 'error', 'message' => 'Agent unreachable']);
        }

        $data = $response->json();
        $step = $data['step'] ?? 'error';

        // Sync final state back to DB
        $cert = $domain->sslCertificate;
        if ($cert) {
            if ($step === 'done') {
                $cert->update([
                    'status'           => 'active',
                    'type'             => 'wildcard',
                    'expires_at'       => now()->addDays(90),
                    'progress_step'    => 'done',
                    'progress_message' => $data['message'] ?? '',
                ]);

                // Remove Let's Encrypt subdomain cert records — they're now covered by wildcard.
                // Custom certs are kept as-is; the agent already skipped those vhosts.
                $domain->loadMissing('subdomains');
                $subdomainIds = $domain->subdomains->pluck('id');
                if ($subdomainIds->isNotEmpty()) {
                    SslCertificate::whereIn('domain_id', $subdomainIds)
                        ->where('type', '!=', 'custom')
                        ->delete();
                }
            } elseif ($step === 'error') {
                $cert->update([
                    'status'           => 'error',
                    'progress_step'    => 'error',
                    'progress_message' => $data['error'] ?? $data['message'] ?? '',
                ]);
            } else {
                $cert->update([
                    'progress_step'    => $step,
                    'progress_message' => $data['message'] ?? '',
                ]);
            }
        }

        return response()->json($data);
    }

    /**
     * Auto-issue SSL for a domain (called from domain/account creation).
     */
    public static function autoIssue(Domain $domain): void
    {
        if (Setting::get('auto_ssl_enabled', '1') !== '1') {
            return;
        }

        $domain->load('account.server');
        if (!$domain->account || !$domain->account->server) return;

        // Queue async SSL issuance
        $email = auth()->user()->email ?? 'admin@' . $domain->domain;

        AgentService::for($domain->account->server)->post('/ssl/issue-async', [
            'domain'   => $domain->domain,
            'email'    => $email,
            'username' => $domain->account->username,
        ]);

        SslCertificate::updateOrCreate(
            ['domain_id' => $domain->id],
            [
                'type'       => 'letsencrypt',
                'status'     => 'pending',
                'expires_at' => now()->addDays(90),
                'auto_renew' => true,
            ]
        );
    }
}
