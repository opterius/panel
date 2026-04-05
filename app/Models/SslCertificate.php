<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SslCertificate extends Model
{
    protected $fillable = [
        'domain_id', 'type', 'status', 'expires_at', 'auto_renew',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'auto_renew' => 'boolean',
        ];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
