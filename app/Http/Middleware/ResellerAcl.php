<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResellerAcl
{
    /**
     * Check if the current reseller has a specific ACL permission.
     * Usage in routes: ->middleware('reseller_acl:account.create')
     * Admins always pass. Non-resellers always pass (they're regular users with different permission model).
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = auth()->user();

        // Admins bypass all ACL checks
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Resellers must have the permission
        if ($user->isReseller() && !$user->resellerCan($permission)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Permission denied.'], 403);
            }
            return redirect()->back()->with('error', 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
