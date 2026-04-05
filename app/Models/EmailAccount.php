<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailAccount extends Model
{
    protected $fillable = [
        'domain_id', 'email', 'quota', 'status',
        'can_send', 'can_receive', 'max_send_per_hour', 'max_send_per_day',
    ];

    protected function casts(): array
    {
        return [
            'can_send' => 'boolean',
            'can_receive' => 'boolean',
        ];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
