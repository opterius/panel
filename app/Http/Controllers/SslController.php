<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\SslCertificate;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $response = AgentService::for($domain->account->server)->post('/ssl/issue', [
            'domain'   => $domain->domain,
            'email'    => $validated['email'],
            'username' => $domain->account->username,
        ]);

        if ($response && $response->successful()) {
            SslCertificate::updateOrCreate(
                ['domain_id' => $domain->id],
                [
                    'type'       => 'letsencrypt',
                    'status'     => 'active',
                    'expires_at' => now()->addDays(90),
                    'auto_renew' => true,
                ]
            );

            return redirect()->route('user.ssl.index')->with('success', 'SSL certificate issued for ' . $domain->domain);
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
            SslCertificate::updateOrCreate(
                ['domain_id' => $domain->id],
                [
                    'type'       => 'custom',
                    'status'     => 'active',
                    'auto_renew' => false,
                ]
            );

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

            return redirect()->route('user.ssl.index')->with('success', 'SSL certificate renewed for ' . $certificate->domain->domain);
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to server agent';
        return redirect()->route('user.ssl.index')->with('error', 'SSL renewal failed: ' . $error);
    }

    public function destroy(SslCertificate $certificate)
    {
        $certificate->delete();

        return redirect()->route('user.ssl.index')->with('success', 'SSL certificate record removed.');
    }
}
