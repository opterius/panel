<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Reads System Settings with a config fallback.
 *
 * Settings saved in the admin UI take precedence over config/opanel.php, so a
 * value an admin changes on the System Settings page actually takes effect
 * instead of only being stored. Config remains the default for a fresh
 * install, and the per-domain argument is the last resort.
 */
class SystemSetting
{
    /**
     * @var array<string, array<string, string>> cache of group => settings
     */
    private static array $cache = [];

    /**
     * Fetch a setting from a System Settings category.
     */
    public static function get(string $category, string $key, mixed $default = null): mixed
    {
        if (! isset(self::$cache[$category])) {
            // Settings are read on nearly every provisioning call; cache per
            // request so one page render does not issue a query per lookup.
            self::$cache[$category] = Setting::getGroup('system_' . $category);
        }

        $value = self::$cache[$category][$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    public static function bool(string $category, string $key, bool $default = false): bool
    {
        $value = self::get($category, $key);

        return $value === null ? $default : in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
    }

    public static function int(string $category, string $key, int $default = 0): int
    {
        $value = self::get($category, $key);

        return $value === null ? $default : (int) $value;
    }

    /** Primary nameserver, falling back to config and then ns1.<domain>. */
    public static function ns1(string $domain = ''): string
    {
        return (string) self::get('system', 'ns1',
            config('opanel.ns1') ?: ($domain !== '' ? 'ns1.' . $domain : 'ns1.localhost'));
    }

    /** Secondary nameserver, falling back to config and then ns2.<domain>. */
    public static function ns2(string $domain = ''): string
    {
        return (string) self::get('system', 'ns2',
            config('opanel.ns2') ?: ($domain !== '' ? 'ns2.' . $domain : 'ns2.localhost'));
    }

    /** Whether new domains should get a certificate automatically. */
    public static function autoIssueSsl(): bool
    {
        return self::bool('ssl', 'auto_issue', true);
    }

    /** Contact address used when registering with Let's Encrypt. */
    public static function sslContactEmail(string $fallback = ''): string
    {
        return (string) self::get('ssl', 'le_contact_email', $fallback);
    }

    /**
     * Clear the per-request cache. Called after a save so the next read in the
     * same request sees the new value.
     */
    public static function flush(): void
    {
        self::$cache = [];
    }
}
