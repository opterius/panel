<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Server;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

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

        // Auto-register this server so the admin doesn't have to add it manually.
        // The agent is already running on 127.0.0.1:7443 (installed by the installer).
        if (Server::count() === 0) {
            // Detect the server's public IP
            $publicIp = null;
            try {
                $publicIp = trim(Http::timeout(5)->get('https://ifconfig.me')->body());
            } catch (\Exception $e) {
                try {
                    $publicIp = trim(Http::timeout(5)->get('https://api.ipify.org')->body());
                } catch (\Exception $e) {}
            }

            $agentToken = config('opterius.agent_secret') ?: env('OPTERIUS_AGENT_SECRET', '');

            Server::create([
                'user_id'     => $admin->id,
                'name'        => 'Opterius Server',
                'ip_address'  => $publicIp ?: '127.0.0.1',
                'hostname'    => gethostname() ?: 'localhost',
                'agent_url'   => 'https://127.0.0.1:7443',
                'agent_token' => $agentToken,
                'status'      => 'online',
            ]);
        }

        // Log in the admin
        Auth::login($admin);

        return redirect()->route('admin.dashboard')->with('success', __('servers.setup_welcome'));
    }
}
