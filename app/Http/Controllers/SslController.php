<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Setting;
use App\Models\SslCertificate;
use App\Services\ActivityLogger;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SslController extends Controller
{
    public function index()
    {
        $certificates = SslCertificate::with('domain.server', 'domain.account')
            ->whereHas('domain.account', fn ($q) => $q->where('user_id', Auth::id()))
            ->latest()
            ->get();

        $domains = Domain::with('server', 'account', 'sslCertificate')
            ->whereHas('account', fn ($q) => $q->where('user_id', Auth::id()))
            ->where('status', 'active')
            ->doesntHave('sslCertificate')
            ->get();

        return view('ssl.index', compact('certificates', 'domains'));
    }

    public function issue(Request $request)
    {
        $validated = $request->validate([
            'domain_id' => 'required|exists:domains,id',
            'email'     => 'required|email',
        ]);

        $domain = Domain::with('account.server')->findOrFail($validated['domain_id']);

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

            return redirect()->route('user.ssl.index')->with('success',
                'SSL certificate is being issued for ' . $domain->domain . '. This takes 1-2 minutes. Refresh the page to check the status.');
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return redirect()->route('user.ssl.index')->with('error', 'SSL issuance failed: ' . $error);
    }

    public function upload(Request $request)
    {
        $validated = $request->validate([
            'domain_id'   => 'required|exists:domains,id',
            'certificate' => 'required|string',
            'private_key' => 'required|string',
        ]);

        $domain = Domain::with('account.server')->findOrFail($validated['domain_id']);

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

            return redirect()->route('user.ssl.index')->with('success', 'Custom SSL certificate installed for ' . $domain->domain);
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return redirect()->route('user.ssl.index')->with('error', 'SSL upload failed: ' . $error);
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

            return redirect()->route('user.ssl.index')->with('success', 'SSL certificate renewed for ' . $certificate->domain->domain);
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return redirect()->route('user.ssl.index')->with('error', 'SSL renewal failed: ' . $error);
    }

    public function destroy(Request $request, SslCertificate $certificate)
    {
        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => 'The password is incorrect.']);
        }

        ActivityLogger::log('ssl.deleted', 'ssl_certificate', $certificate->id, null,
            "Deleted SSL certificate record", ['domain_id' => $certificate->domain_id]);

        $certificate->delete();

        return redirect()->route('user.ssl.index')->with('success', 'SSL certificate record removed.');
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
