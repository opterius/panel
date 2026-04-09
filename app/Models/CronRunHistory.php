<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CronRunHistory extends Model
{
    use HasFactory;

    protected $table = 'cron_run_history';

    protected $fillable = [
        'cron_job_id',
        'started_at',
        'finished_at',
        'duration_ms',
        'exit_code',
        'stdout',
        'stderr',
    ];

    protected $casts = [
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
        'exit_code'   => 'integer',
        'duration_ms' => 'integer',
    ];

    public function cronJob(): BelongsTo
    {
        return $this->belongsTo(CronJob::class);
    }

    public function isSuccess(): bool
    {
        return $this->exit_code === 0;
    }
}
