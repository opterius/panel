<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around the BunnyCDN HTTP API. Only the calls Opterius needs:
 * create / delete / get a Pull Zone, and purge cache.
 *
 * The API key comes from the panel's "integrations" settings group, written
 * by the admin once during setup. Each customer's domain shares the same
 * BunnyCDN account — the panel admin pays Bunny, customers consume.
 */
class BunnyCdnClient
{
    private const API_BASE = 'https://api.bunny.net';

    private string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? (string) Setting::get('integrations_bunnycdn_api_key', '');

        if ($this->apiKey === '') {
            throw new RuntimeException('BunnyCDN API key is not configured. Add it under Settings → Integrations.');
        }
    }

    /**
     * Static helper that returns null instead of throwing if the key is missing.
     * Useful for "is the integration configured?" checks in views.
     */
    public static function isConfigured(): bool
    {
        return ! empty(Setting::get('integrations_bunnycdn_api_key', ''));
    }

    /**
     * Create a Pull Zone for a domain.
     *
     * BunnyCDN zone names must be globally unique across all BunnyCDN accounts,
     * so we prefix with "opterius-" + the domain (sanitised).
     *
     * @return array<string,mixed> The created zone, including 'Id' and 'Hostnames'.
     */
    public function createPullZone(string $domain): array
    {
        $name = $this->buildZoneName($domain);

        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey,
            'Accept'    => 'application/json',
        ])
            ->acceptJson()
            ->asJson()
            ->post(self::API_BASE . '/pullzone', [
                'Name'      => $name,
                'OriginUrl' => "https://{$domain}",
                'Type'      => 0, // 0 = Standard, 1 = Volume
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('BunnyCDN createPullZone failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Delete a Pull Zone by id. Used when a customer disables CDN on a domain.
     */
    public function deletePullZone(int $zoneId): bool
    {
        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey,
        ])->delete(self::API_BASE . '/pullzone/' . $zoneId);

        return $response->successful();
    }

    /**
     * Purge the cache for an entire pull zone. Useful after a content update.
     */
    public function purgeCache(int $zoneId): bool
    {
        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey,
        ])->post(self::API_BASE . '/pullzone/' . $zoneId . '/purgeCache');

        return $response->successful();
    }

    /**
     * Verify the configured API key works by listing pull zones (read-only).
     * Returns true on success, false on auth failure or network error.
     */
    public function verifyCredentials(): bool
    {
        try {
            $response = Http::withHeaders([
                'AccessKey' => $this->apiKey,
                'Accept'    => 'application/json',
            ])->timeout(10)->get(self::API_BASE . '/pullzone', ['perPage' => 1]);

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Build a unique zone name for a domain. Zone names are globally unique
     * across all BunnyCDN accounts, so we use the panel admin's account ID
     * as a prefix to avoid collisions with other Opterius customers.
     */
    private function buildZoneName(string $domain): string
    {
        $accountId = Setting::get('integrations_bunnycdn_prefix', 'opterius');
        $clean     = preg_replace('/[^a-z0-9]/', '-', strtolower($domain));
        $clean     = trim((string) $clean, '-');
        return substr("{$accountId}-{$clean}", 0, 60);
    }
}
