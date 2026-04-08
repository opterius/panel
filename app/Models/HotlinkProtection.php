<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotlinkProtection extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'enabled',
        'allowed_domains',
        'allowed_extensions',
        'allow_direct',
        'redirect_url',
    ];

    protected $casts = [
        'enabled'            => 'boolean',
        'allow_direct'       => 'boolean',
        'allowed_domains'    => 'array',
        'allowed_extensions' => 'array',
    ];

    public const DEFAULT_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'mp4', 'webm'];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
