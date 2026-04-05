<?php

namespace App\Http\Controllers;

use App\Services\LicenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class LicenseController extends Controller
{
    public function index()
    {
        $license = new LicenseService();
        $status = $license->verify();

        return view('license.index', compact('status'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'license_key' => 'required|string|max:64',
        ]);

        // Update .env file
        $this->setEnvValue('OPTERIUS_LICENSE_KEY', $validated['license_key']);

        // Clear config and license cache
        Artisan::call('config:clear');
        $license = new LicenseService();
        $license->clearCache();

        return redirect()->route('admin.license.index')->with('success', 'License key updated. Verifying...');
    }

    public function refresh()
    {
        $license = new LicenseService();
        $license->clearCache();
        $status = $license->verify();

        if ($status['valid'] ?? false) {
            return redirect()->route('admin.license.index')->with('success', 'License verified successfully.');
        }

        return redirect()->route('admin.license.index')->with('error', $status['message'] ?? 'License verification failed.');
    }

    private function setEnvValue(string $key, string $value): void
    {
        $envPath = base_path('.env');
        $content = file_get_contents($envPath);

        if (str_contains($content, "$key=")) {
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
        } else {
            $content .= "\n{$key}={$value}\n";
        }

        file_put_contents($envPath, $content);
    }
}
