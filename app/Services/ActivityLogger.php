<?php

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogger
{
    public static function log(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $entityName = null,
        ?string $description = null,
        array $metadata = []
    ): void {
        try {
            ActivityLog::create([
                'user_id'     => auth()->id(),
                'account_id'  => $metadata['account_id'] ?? null,
                'server_id'   => $metadata['server_id'] ?? null,
                'action'      => $action,
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'entity_name' => $entityName,
                'description' => $description,
                'metadata'    => $metadata,
                'ip_address'  => request()->ip(),
                'created_at'  => now(),
            ]);
        } catch (\Exception $e) {
            // Never let logging break the actual operation
        }
    }
}
