<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'php_versions',
        'default_php_version',
        'disk_quota',
        'bandwidth',
        'max_subdomains',
        'max_domains',
        'max_databases',
        'max_email_accounts',
        'max_php_workers',
        'memory_per_process',
        'ssl_enabled',
        'cron_jobs_enabled',
        'php_switch_enabled',
        'cdn_enabled',
        'is_default',
    ];

    protected $casts = [
        'php_versions' => 'array',
        'ssl_enabled' => 'boolean',
        'cron_jobs_enabled' => 'boolean',
        'php_switch_enabled' => 'boolean',
        'cdn_enabled' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function allowsPhpVersion(string $version): bool
    {
        return in_array($version, $this->php_versions ?? []);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function diskQuotaLabel(): string
    {
        return $this->formatMB($this->disk_quota);
    }

    public function bandwidthLabel(): string
    {
        return $this->formatMB($this->bandwidth) . ($this->bandwidth > 0 ? '/mo' : '');
    }

    private function formatMB(int $value): string
    {
        if ($value === 0) return 'Unlimited';
        if ($value >= 1024) return round($value / 1024, 1) . ' GB';
        return $value . ' MB';
    }

    public function limitLabel(int $value): string
    {
        return $value === 0 ? 'Unlimited' : (string) $value;
    }
}
