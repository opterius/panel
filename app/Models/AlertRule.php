<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlertRule extends Model
{
    protected $fillable = [
        'server_id', 'metric', 'operator', 'threshold',
        'duration_minutes', 'channel', 'channel_config',
        'enabled', 'last_triggered_at',
    ];

    protected function casts(): array
    {
        return [
            'threshold' => 'decimal:2',
            'enabled' => 'boolean',
            'last_triggered_at' => 'datetime',
            'channel_config' => 'array',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AlertLog::class);
    }

    public function metricLabel(): string
    {
        return match ($this->metric) {
            'cpu'    => 'CPU Usage',
            'memory' => 'Memory Usage',
            'disk'   => 'Disk Usage',
            'load'   => 'Load Average',
            default  => $this->metric,
        };
    }
}
