<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::withCount('accounts')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('packages.index', compact('packages'));
    }

    public function create()
    {
        return view('packages.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:100',
            'description'         => 'nullable|string|max:255',
            'php_versions'        => 'required|array|min:1',
            'php_versions.*'      => 'in:' . implode(',', config('opterius.php_versions')),
            'default_php_version' => 'required|in:' . implode(',', config('opterius.php_versions')),
            'disk_quota'          => 'required|integer|min:0',
            'bandwidth'           => 'required|integer|min:0',
            'max_subdomains'      => 'required|integer|min:0',
            'max_databases'       => 'required|integer|min:0',
            'max_email_accounts'  => 'required|integer|min:0',
            'ssl_enabled'         => 'boolean',
            'cron_jobs_enabled'   => 'boolean',
            'is_default'          => 'boolean',
        ]);

        if (!in_array($validated['default_php_version'], $validated['php_versions'])) {
            return back()->withErrors(['default_php_version' => 'The default PHP version must be one of the allowed versions.'])->withInput();
        }

        $validated['ssl_enabled'] = $request->boolean('ssl_enabled');
        $validated['cron_jobs_enabled'] = $request->boolean('cron_jobs_enabled');
        $validated['is_default'] = $request->boolean('is_default');

        if ($validated['is_default']) {
            Package::query()->update(['is_default' => false]);
        }

        Package::create($validated);

        return redirect()->route('packages.index')->with('success', 'Package created successfully.');
    }

    public function edit(Package $package)
    {
        return view('packages.edit', compact('package'));
    }

    public function update(Request $request, Package $package)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:100',
            'description'         => 'nullable|string|max:255',
            'php_versions'        => 'required|array|min:1',
            'php_versions.*'      => 'in:' . implode(',', config('opterius.php_versions')),
            'default_php_version' => 'required|in:' . implode(',', config('opterius.php_versions')),
            'disk_quota'          => 'required|integer|min:0',
            'bandwidth'           => 'required|integer|min:0',
            'max_subdomains'      => 'required|integer|min:0',
            'max_databases'       => 'required|integer|min:0',
            'max_email_accounts'  => 'required|integer|min:0',
            'ssl_enabled'         => 'boolean',
            'cron_jobs_enabled'   => 'boolean',
            'is_default'          => 'boolean',
        ]);

        if (!in_array($validated['default_php_version'], $validated['php_versions'])) {
            return back()->withErrors(['default_php_version' => 'The default PHP version must be one of the allowed versions.'])->withInput();
        }

        $validated['ssl_enabled'] = $request->boolean('ssl_enabled');
        $validated['cron_jobs_enabled'] = $request->boolean('cron_jobs_enabled');
        $validated['is_default'] = $request->boolean('is_default');

        if ($validated['is_default']) {
            Package::where('id', '!=', $package->id)->update(['is_default' => false]);
        }

        $package->update($validated);

        return redirect()->route('packages.index')->with('success', 'Package updated successfully.');
    }

    public function destroy(Package $package)
    {
        if ($package->accounts()->exists()) {
            return back()->with('error', 'Cannot delete a package that has accounts assigned to it.');
        }

        $package->delete();

        return redirect()->route('packages.index')->with('success', 'Package deleted.');
    }
}
