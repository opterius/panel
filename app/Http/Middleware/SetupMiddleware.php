<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetupMiddleware
{
    /**
     * If no users exist in the database, redirect to the setup wizard.
     * Skip for the setup route itself and asset routes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Don't redirect if already on setup page
        if ($request->routeIs('setup.*')) {
            return $next($request);
        }

        // Don't redirect for asset/livewire routes
        if ($request->is('livewire/*', 'build/*', '_debugbar/*')) {
            return $next($request);
        }

        // If no users exist, redirect to setup
        if (User::count() === 0) {
            return redirect()->route('setup.index');
        }

        return $next($request);
    }
}
