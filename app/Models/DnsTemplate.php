<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DnsTemplate extends Model
{
    protected $fillable = ['name', 'is_default', 'records'];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'records' => 'array',
        ];
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    /**
     * Replace template variables in records.
     * Variables: {domain}, {ip}, {ns1}, {ns2}
     */
    public function resolveRecords(string $domain, string $serverIp): array
    {
        $ns1 = config('opterius.ns1', 'ns1.' . $domain);
        $ns2 = config('opterius.ns2', 'ns2.' . $domain);

        return collect($this->records)->map(function ($record) use ($domain, $serverIp, $ns1, $ns2) {
            $replace = fn ($str) => str_replace(
                ['{domain}', '{ip}', '{ns1}', '{ns2}'],
                [$domain, $serverIp, $ns1, $ns2],
                $str
            );

            return [
                'name'     => $replace($record['name'] ?? $domain),
                'type'     => $record['type'] ?? 'A',
                'content'  => $replace($record['content'] ?? $serverIp),
                'ttl'      => $record['ttl'] ?? 3600,
                'priority' => $record['priority'] ?? 0,
            ];
        })->toArray();
    }
}
