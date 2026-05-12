<?php

namespace App\Services;

use App\Models\Server;
use Illuminate\Support\Facades\Cache;

class PhpVersionsService
{
    private const CACHE_KEY = 'opterius:php_versions:union';
    private const CACHE_TTL = 3600; // 1 hour — re-poll the agents at most once an hour

    /**
     * Versions installed on at least one managed server, sorted ascending.
     * Empty result falls back to the config list so the package form never
     * blanks out if every agent is unreachable.
     */
    public static function available(): array
    {
        $versions = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $all = [];
            foreach (Server::all() as $server) {
                $resp = AgentService::for($server)->post('/php/list-versions', []);
                if (! $resp || ! $resp->successful()) {
                    continue;
                }
                foreach ($resp->json('versions', []) as $v) {
                    if (! empty($v['installed']) && ! empty($v['version'])) {
                        $all[$v['version']] = true;
                    }
                }
            }
            $keys = array_keys($all);
            usort($keys, 'version_compare');
            return $keys;
        });

        return $versions ?: config('opterius.php_versions', []);
    }

    /**
     * Drop the cached list. Called after a PHP version is installed or
     * uninstalled so the package form picks up the change on the next render.
     */
    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
