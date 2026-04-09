<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CronJob extends Model
{
    protected $fillable = [
        'server_id', 'account_id', 'command', 'description', 'schedule', 'enabled', 'last_run_at', 'last_output',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'last_run_at' => 'datetime',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(CronRunHistory::class)->orderByDesc('started_at');
    }
}
