<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Backup extends Model
{
    protected $fillable = [
        'server_id', 'account_id', 'username', 'filename',
        'type', 'size_mb', 'status',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
