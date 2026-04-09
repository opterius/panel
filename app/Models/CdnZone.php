<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CdnZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'provider',
        'enabled',
        'zone_id',
        'zone_name',
        'cdn_hostname',
        'rewrite_paths',
        'last_synced_at',
    ];

    protected $casts = [
        'enabled'        => 'boolean',
        'rewrite_paths'  => 'array',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Sensible default rewrite paths used when the user enables CDN without
     * specifying their own. Covers the asset directories of every common CMS.
     */
    public const DEFAULT_REWRITE_PATHS = [
        '/wp-content/',
        '/wp-includes/',
        '/assets/',
        '/static/',
        '/storage/',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * The full HTTPS URL of the CDN edge for this zone.
     */
    public function cdnUrl(): ?string
    {
        return $this->cdn_hostname ? "https://{$this->cdn_hostname}" : null;
    }
}
