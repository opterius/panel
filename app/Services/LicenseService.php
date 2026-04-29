<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class LicenseService
{
    private string $serverUrl;
    private string $licenseKey;

    public function __construct()
    {
        $this->serverUrl = rtrim(config('opterius.license_server_url'), '/');
        $this->licenseKey = config('opterius.license_key', '');
    }

    /**
     * Verify the license with the central server.
     * Caches the result for 24 hours so the panel works even if the license server is down.
     */
    public function verify(): array
    {
        if (empty($this->licenseKey)) {
            return $this->restricted('no_key', 'No license key configured.');
        }

        // Return cached response if available
        $cached = Cache::get('license_status');
        if ($cached !== null) {
            return $cached;
        }

        try {
            $response = Http::timeout(10)
                ->withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
                ->post($this->serverUrl . '/api/license/verify', [
                    'key'           => $this->licenseKey,
                    'server_ip'     => $this->getServerIp(),
                    'panel_version' => config('opterius.version', '1.0.0'),
                    'os'            => php_uname('s') . ' ' . php_uname('r'),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                Cache::put('license_status', $data, now()->addHours(24));
                return $data;
            }

            $data = $response->json();
            $result = [
                'valid'        => false,
                'reason'       => $data['reason'] ?? 'unknown',
                'message'      => $data['message'] ?? 'License check failed.',
                'max_domains'  => $data['max_domains']  ?? 1,
                'max_accounts' => $data['max_accounts'] ?? 1,
                'max_servers'  => $data['max_servers']  ?? 1,
                'plan'         => $data['plan'] ?? 'trial',
            ];
            Cache::put('license_status', $result, now()->addHours(24));
            return $result;

        } catch (\Exception $e) {
            // License server unreachable — use Laravel cache first
            $cached = Cache::get('license_status');
            if ($cached !== null) {
                return $cached;
            }

            // Try agent's cache file as fallback
            $agentCache = '/etc/opterius/license-cache.json';
            if (file_exists($agentCache)) {
                $data = json_decode(file_get_contents($agentCache), true);
                if ($data && ($data['valid'] ?? false)) {
                    Cache::put('license_status', $data, now()->addHours(24));
                    return $data;
                }
            }

            // No cache, no server — allow restricted mode
            return $this->restricted('unreachable', 'License server unreachable. Running in restricted mode.');
        }
    }

    /**
     * Register a trial license during installation.
     */
    public function registerTrial(): ?array
    {
        try {
            $response = Http::timeout(10)->post($this->serverUrl . '/api/license/register', [
                'server_ip'     => $this->getServerIp(),
                'hostname'      => gethostname(),
                'panel_version' => config('opterius.version', '1.0.0'),
                'os'            => php_uname('s') . ' ' . php_uname('r'),
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Check if the current license is valid.
     */
    public function isValid(): bool
    {
        $status = $this->verify();
        return $status['valid'] ?? false;
    }

    /**
     * Get the maximum number of domains allowed.
     */
    public function maxDomains(): int
    {
        $status = $this->verify();
        $max = $status['max_domains'] ?? 1;
        return $max === 0 ? PHP_INT_MAX : $max; // 0 = unlimited
    }

    /**
     * Get the maximum number of accounts allowed.
     */
    public function maxAccounts(): int
    {
        $status = $this->verify();
        $max = $status['max_accounts'] ?? $status['max_domains'] ?? 3;
        return $max === 0 ? PHP_INT_MAX : $max; // 0 = unlimited
    }

    /**
     * Get the maximum number of servers allowed.
     */
    public function maxServers(): int
    {
        $status = $this->verify();
        $max = $status['max_servers'] ?? 1;
        return $max === 0 ? PHP_INT_MAX : $max; // 0 = unlimited
    }

    /**
     * Get the current license plan.
     */
    public function plan(): string
    {
        $status = $this->verify();
        return $status['plan'] ?? 'trial';
    }

    /**
     * Clear the cached license status (force re-check).
     */
    public function clearCache(): void
    {
        Cache::forget('license_status');
    }

    private function restricted(string $reason, string $message): array
    {
        return [
            'valid'        => false,
            'reason'       => $reason,
            'message'      => $message,
            'max_domains'  => 1,
            'max_accounts' => 1,
            'max_servers'  => 1,
            'plan'         => 'restricted',
        ];
    }

    private function getServerIp(): string
    {
        // Force IPv4 — must match what the agent reports, otherwise the
        // panel and agent end up with separate activation entries (one per
        // address family) and exhaust the license's server slot.
        try {
            $ip = Http::timeout(5)
                ->withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
                ->get('https://api.ipify.org')
                ->body();
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $ip;
            }
        } catch (\Exception) {
        }

        return request()->server('SERVER_ADDR', '127.0.0.1');
    }
}
