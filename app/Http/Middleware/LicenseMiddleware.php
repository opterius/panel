<?php

namespace App\Http\Middleware;

use App\Services\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LicenseMiddleware
{
    /**
     * Check license validity. If expired/invalid, allow access but with a warning banner.
     * Domain creation is restricted in the controller (not here) so the panel stays usable.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $license = new LicenseService();
        $status = $license->verify();

        // Share license status with all views
        view()->share('licenseStatus', $status);

        return $next($request);
    }
}
