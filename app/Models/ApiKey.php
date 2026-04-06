<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $fillable = [
        'user_id', 'server_id', 'name', 'key_hash', 'key_prefix',
        'permissions', 'allowed_ips', 'last_used_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'allowed_ips' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Generate a new API key. Returns [ApiKey, plaintext].
     */
    public static function generate(array $attributes): array
    {
        $plaintext = 'opt_' . Str::random(48);

        $apiKey = static::create(array_merge($attributes, [
            'key_hash' => hash('sha256', $plaintext),
            'key_prefix' => substr($plaintext, 0, 12),
        ]));

        return [$apiKey, $plaintext];
    }

    /**
     * Find an API key by its plaintext value.
     */
    public static function findByKey(string $plaintext): ?self
    {
        return static::where('key_hash', hash('sha256', $plaintext))->first();
    }

    public function hasPermission(string $scope): bool
    {
        $permissions = $this->permissions ?? [];

        // Wildcard = all permissions
        if (in_array('*', $permissions)) {
            return true;
        }

        return in_array($scope, $permissions);
    }

    public function isAllowedIp(string $ip): bool
    {
        if (empty($this->allowed_ips)) {
            return true;
        }

        return in_array($ip, $this->allowed_ips);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function touchLastUsed(): void
    {
        if (!$this->last_used_at || $this->last_used_at->diffInMinutes(now()) >= 1) {
            $this->update(['last_used_at' => now()]);
        }
    }
}
