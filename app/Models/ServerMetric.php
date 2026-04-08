<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'recorded_at',
        'cpu_percent',
        'mem_percent',
        'mem_used_mb',
        'mem_total_mb',
        'disk_percent',
        'load_avg_1',
        'load_avg_5',
        'load_avg_15',
        'network_in_kb',
        'network_out_kb',
    ];

    protected $casts = [
        'recorded_at'    => 'datetime',
        'cpu_percent'    => 'float',
        'mem_percent'    => 'float',
        'disk_percent'   => 'float',
        'load_avg_1'     => 'float',
        'load_avg_5'     => 'float',
        'load_avg_15'    => 'float',
        'mem_used_mb'    => 'integer',
        'mem_total_mb'   => 'integer',
        'network_in_kb'  => 'integer',
        'network_out_kb' => 'integer',
    ];

    public $timestamps = false;

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
