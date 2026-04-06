<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CpanelMigration extends Model
{
    protected $fillable = [
        'server_id', 'account_id', 'initiated_by', 'source_type', 'source_path',
        'original_username', 'target_username', 'main_domain',
        'manifest', 'options', 'result',
        'status', 'progress', 'current_step',
        'started_at', 'completed_at', 'error',
    ];

    protected function casts(): array
    {
        return [
            'manifest' => 'array',
            'options' => 'array',
            'result' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
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

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function updateProgress(int $percent, string $step): void
    {
        $this->update([
            'progress' => $percent,
            'current_step' => $step,
        ]);
    }

    public function markRunning(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function markCompleted(array $result): void
    {
        $hasFailures = collect($result)->contains(fn ($r) => ($r['status'] ?? '') === 'failed');

        $this->update([
            'status' => $hasFailures ? 'partial' : 'completed',
            'progress' => 100,
            'current_step' => null,
            'result' => $result,
            'completed_at' => now(),
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'current_step' => null,
            'error' => $error,
            'completed_at' => now(),
        ]);
    }
}
