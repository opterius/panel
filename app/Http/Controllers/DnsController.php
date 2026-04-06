<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DnsController extends Controller
{
    public function index(Request $request, Domain $domain)
    {
        $domain->load('account.server');

        $records = [];
        $response = AgentService::for($domain->account->server)->post('/dns/list-records', [
            'domain' => $domain->domain,
        ]);

        if ($response && $response->successful()) {
            $records = $response->json('records', []);
        }

        return view('dns.index', compact('domain', 'records'));
    }

    public function addRecord(Request $request, Domain $domain)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'type'     => 'required|string|in:A,AAAA,CNAME,MX,TXT,NS,SRV,CAA',
            'content'  => 'required|string|max:65535',
            'ttl'      => 'required|integer|min:60|max:86400',
            'priority' => 'nullable|integer|min:0|max:65535',
        ]);

        $domain->load('account.server');

        if (!$domain->account->userCan(auth()->user(), 'dns')) {
            return back()->with('error', __('dns.no_permission'));
        }

        $response = AgentService::for($domain->account->server)->post('/dns/add-record', [
            'domain'   => $domain->domain,
            'name'     => $validated['name'],
            'type'     => $validated['type'],
            'content'  => $validated['content'],
            'ttl'      => $validated['ttl'],
            'priority' => $validated['priority'] ?? 0,
        ]);

        if ($response && $response->successful()) {
            return redirect()->route('user.dns.index', $domain)->with('success', __('dns.record_added', ['type' => $validated['type']]));
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
        return back()->with('error', __('dns.failed_to_add_record', ['error' => $error]))->withInput();
    }

    public function deleteRecord(Request $request, Domain $domain)
    {
        $validated = $request->validate([
            'record_id' => 'required|integer',
        ]);

        $domain->load('account.server');

        if (!$domain->account->userCan(auth()->user(), 'dns')) {
            return back()->with('error', __('dns.no_permission'));
        }

        $response = AgentService::for($domain->account->server)->post('/dns/delete-record', [
            'domain' => $domain->domain,
            'id'     => $validated['record_id'],
        ]);

        if ($response && $response->successful()) {
            return redirect()->route('user.dns.index', $domain)->with('success', __('dns.record_deleted'));
        }

        $error = $response ? $response->json('error', 'Unknown error') : 'Could not connect to agent';
        return back()->with('error', __('dns.failed_to_delete_record', ['error' => $error]));
    }
}
