<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * SSO — token-based auto-login from opterius.com client zone.
 *
 * Flow:
 *   1. opterius.com calls POST /sso/issue (server-to-server, HMAC-signed).
 *      Panel validates signature and returns a one-time token.
 *   2. opterius.com redirects the user's browser to GET /sso/login?token=...
 *      Panel validates the token, logs the user in, redirects to dashboard.
 *
 * Required .env:
 *   OPTERIUS_SSO_SECRET=<shared secret, min 32 chars, same on opterius.com>
 */
class SsoController extends Controller
{
    private const TOKEN_TTL = 120;

    /**
     * POST /sso/issue
     * Server-to-server — called by opterius.com.
     *
     * Body: { "email": "...", "timestamp": 1234567890, "signature": "hex-hmac-sha256" }
     */
    public function issue(Request $request): JsonResponse
    {
        $secret = config('opterius.sso_secret');

        if (empty($secret)) {
            return response()->json(['error' => 'SSO is not configured.'], 403);
        }

        $request->validate([
            'email'     => ['required', 'email'],
            'timestamp' => ['required', 'integer'],
            'signature' => ['required', 'string'],
        ]);

        $email     = strtolower($request->email);
        $timestamp = (int) $request->timestamp;

        if (abs(time() - $timestamp) > 60) {
            return response()->json(['error' => 'Timestamp expired.'], 422);
        }

        $expected = hash_hmac('sha256', "{$email}:{$timestamp}", $secret);
        if (!hash_equals($expected, strtolower($request->signature))) {
            return response()->json(['error' => 'Invalid signature.'], 403);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        $token = Str::random(48);
        Cache::put("sso:{$token}", $user->id, self::TOKEN_TTL);

        return response()->json([
            'token' => $token,
            'url'   => url('/sso/login') . '?token=' . $token,
        ]);
    }

    /**
     * GET /sso/login?token=...
     * Browser redirect — user arrives here from opterius.com.
     */
    public function login(Request $request): RedirectResponse
    {
        $secret = config('opterius.sso_secret');

        if (empty($secret)) {
            return redirect()->route('login')->withErrors(['email' => 'SSO is not configured.']);
        }

        $token = $request->query('token', '');
        if (!$token) {
            return redirect()->route('login');
        }

        $userId = Cache::pull("sso:{$token}");
        if (!$userId) {
            return redirect()->route('login')
                ->withErrors(['email' => 'SSO link is invalid or has expired. Please try again.']);
        }

        $user = User::find($userId);
        if (!$user) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Account not found.']);
        }

        Auth::login($user, remember: false);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
