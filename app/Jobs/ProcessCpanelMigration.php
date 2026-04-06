<?php

namespace App\Jobs;

use App\Models\CpanelMigration;
use App\Services\MigrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCpanelMigration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600; // 1 hour max
    public int $tries = 1;     // Don't retry automatically

    public function __construct(
        public CpanelMigration $migration,
    ) {}

    public function handle(MigrationService $service): void
    {
        try {
            $service->execute($this->migration);
        } catch (\Throwable $e) {
            $this->migration->markFailed($e->getMessage());
            throw $e;
        }
    }
}
