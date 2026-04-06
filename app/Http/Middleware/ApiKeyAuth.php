<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Services\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if (!$bearer) {
            return response()->json([
                'result' => 'error',
                'message' => 'API key required. Use Authorization: Bearer <key>',
            ], 401);
        }

        $apiKey = ApiKey::findByKey($bearer);

        if (!$apiKey) {
            ActivityLogger::log('api.auth_failed', null, null, null,
                'Invalid API key attempt', ['ip' => $request->ip()]);

            return response()->json([
                'result' => 'error',
                'message' => 'Invalid API key.',
            ], 401);
        }

        if ($apiKey->isExpired()) {
            return response()->json([
                'result' => 'error',
                'message' => 'API key has expired.',
            ], 401);
        }

        if (!$apiKey->isAllowedIp($request->ip())) {
            return response()->json([
                'result' => 'error',
                'message' => 'IP address not allowed.',
            ], 403);
        }

        $apiKey->touchLastUsed();

        // Store the API key on the request for permission checks
        $request->attributes->set('api_key', $apiKey);

        // Authenticate the associated user for activity logging
        if ($apiKey->user_id) {
            Auth::onceUsingId($apiKey->user_id);
        }

        return $next($request);
    }
}
