<?php

namespace App\Http\Controllers;

use App\Models\DnsTemplate;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class DnsTemplateController extends Controller
{
    public function index()
    {
        $templates = DnsTemplate::withCount('packages')->get();

        return view('dns-templates.index', compact('templates'));
    }

    public function create()
    {
        return view('dns-templates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'is_default' => 'boolean',
            'records'    => 'required|array|min:1',
            'records.*.name'     => 'required|string|max:255',
            'records.*.type'     => 'required|in:A,AAAA,CNAME,MX,TXT,NS,SRV,CAA',
            'records.*.content'  => 'required|string|max:1000',
            'records.*.ttl'      => 'required|integer|min:60',
            'records.*.priority' => 'nullable|integer|min:0',
        ]);

        if ($request->boolean('is_default')) {
            DnsTemplate::query()->update(['is_default' => false]);
        }

        $template = DnsTemplate::create([
            'name'       => $validated['name'],
            'is_default' => $request->boolean('is_default'),
            'records'    => $validated['records'],
        ]);

        ActivityLogger::log('dns_template.created', 'dns_template', $template->id, $template->name,
            "Created DNS template '{$template->name}'");

        return redirect()->route('admin.dns-templates.index')->with('success', "DNS template '{$template->name}' created.");
    }

    public function edit(DnsTemplate $dnsTemplate)
    {
        return view('dns-templates.edit', ['template' => $dnsTemplate]);
    }

    public function update(Request $request, DnsTemplate $dnsTemplate)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'is_default' => 'boolean',
            'records'    => 'required|array|min:1',
            'records.*.name'     => 'required|string|max:255',
            'records.*.type'     => 'required|in:A,AAAA,CNAME,MX,TXT,NS,SRV,CAA',
            'records.*.content'  => 'required|string|max:1000',
            'records.*.ttl'      => 'required|integer|min:60',
            'records.*.priority' => 'nullable|integer|min:0',
        ]);

        if ($request->boolean('is_default')) {
            DnsTemplate::where('id', '!=', $dnsTemplate->id)->update(['is_default' => false]);
        }

        $dnsTemplate->update([
            'name'       => $validated['name'],
            'is_default' => $request->boolean('is_default'),
            'records'    => $validated['records'],
        ]);

        return redirect()->route('admin.dns-templates.index')->with('success', 'DNS template updated.');
    }

    public function destroy(DnsTemplate $dnsTemplate)
    {
        $dnsTemplate->delete();
        return redirect()->route('admin.dns-templates.index')->with('success', 'DNS template deleted.');
    }
}
