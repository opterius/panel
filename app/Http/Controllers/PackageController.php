<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Services\ActivityLogger;

// Note: Resellers need 'packages.manage' ACL to access this controller
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index()
    {
        $query = Package::withCount('accounts')
            ->orderByDesc('is_default')
            ->orderBy('name');

        // Resellers see global packages (owner_id null) + their own
        if (auth()->user()->isReseller()) {
            $query->where(function ($q) {
                $q->whereNull('owner_id')->orWhere('owner_id', auth()->id());
            });
        }

        $packages = $query->get();

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
            'max_domains'         => 'required|integer|min:0',
            'max_databases'       => 'required|integer|min:0',
            'max_email_accounts'  => 'required|integer|min:0',
            'max_php_workers'     => 'required|integer|min:1|max:50',
            'memory_per_process'  => 'required|integer|min:32|max:4096',
            'ssl_enabled'         => 'boolean',
            'cron_jobs_enabled'   => 'boolean',
            'php_switch_enabled'  => 'boolean',
            'is_default'          => 'boolean',
        ]);

        if (!in_array($validated['default_php_version'], $validated['php_versions'])) {
            return back()->withErrors(['default_php_version' => 'The default PHP version must be one of the allowed versions.'])->withInput();
        }

        $validated['ssl_enabled'] = $request->boolean('ssl_enabled');
        $validated['cron_jobs_enabled'] = $request->boolean('cron_jobs_enabled');
        $validated['php_switch_enabled'] = $request->boolean('php_switch_enabled');
        $validated['is_default'] = $request->boolean('is_default');

        if ($validated['is_default']) {
            Package::query()->update(['is_default' => false]);
        }

        $package = Package::create($validated);

        ActivityLogger::log('package.created', 'package', $package->id, $package->name,
            "Created package {$package->name}");

        return redirect()->route('admin.packages.index')->with('success', 'Package created successfully.');
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
            'max_domains'         => 'required|integer|min:0',
            'max_databases'       => 'required|integer|min:0',
            'max_email_accounts'  => 'required|integer|min:0',
            'max_php_workers'     => 'required|integer|min:1|max:50',
            'memory_per_process'  => 'required|integer|min:32|max:4096',
            'ssl_enabled'         => 'boolean',
            'cron_jobs_enabled'   => 'boolean',
            'php_switch_enabled'  => 'boolean',
            'is_default'          => 'boolean',
        ]);

        if (!in_array($validated['default_php_version'], $validated['php_versions'])) {
            return back()->withErrors(['default_php_version' => 'The default PHP version must be one of the allowed versions.'])->withInput();
        }

        $validated['ssl_enabled'] = $request->boolean('ssl_enabled');
        $validated['cron_jobs_enabled'] = $request->boolean('cron_jobs_enabled');
        $validated['php_switch_enabled'] = $request->boolean('php_switch_enabled');
        $validated['is_default'] = $request->boolean('is_default');

        if ($validated['is_default']) {
            Package::where('id', '!=', $package->id)->update(['is_default' => false]);
        }

        $package->update($validated);

        ActivityLogger::log('package.updated', 'package', $package->id, $package->name,
            "Updated package {$package->name}");

        return redirect()->route('admin.packages.index')->with('success', 'Package updated successfully.');
    }

    public function destroy(Package $package)
    {
        if ($package->accounts()->exists()) {
            return back()->with('error', 'Cannot delete a package that has accounts assigned to it.');
        }

        ActivityLogger::log('package.deleted', 'package', $package->id, $package->name,
            "Deleted package {$package->name}");

        $package->delete();

        return redirect()->route('admin.packages.index')->with('success', 'Package deleted.');
    }
}
