<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SetupController extends Controller
{
    public function index()
    {
        // If users already exist, redirect to login
        if (User::count() > 0) {
            return redirect()->route('login');
        }

        return view('setup.index');
    }

    public function store(Request $request)
    {
        // Prevent running setup if users already exist
        if (User::count() > 0) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255',
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).+$/'],
        ], [
            'password.regex' => 'Password must contain at least one uppercase, one lowercase, and one number.',
        ]);

        // Create admin user
        $admin = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => 'admin',
        ]);

        // Create default package if none exists
        if (Package::count() === 0) {
            Package::create([
                'name'                => 'Default',
                'description'         => 'Default hosting package',
                'php_versions'        => config('opterius.php_versions'),
                'default_php_version' => config('opterius.default_php_version'),
                'disk_quota'          => 0,
                'bandwidth'           => 0,
                'max_subdomains'      => 0,
                'max_domains'         => 0,
                'max_databases'       => 0,
                'max_email_accounts'  => 0,
                'max_php_workers'     => 5,
                'memory_per_process'  => 256,
                'ssl_enabled'         => true,
                'cron_jobs_enabled'   => true,
                'is_default'          => true,
            ]);
        }

        // Log in the admin
        Auth::login($admin);

        return redirect()->route('admin.dashboard')->with('success', __('servers.setup_welcome'));
    }
}
