<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Models\ServerMetric;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonitorCollect extends Command
{
    protected $signature   = 'monitor:collect {--retention=30 : days of metrics to keep}';
    protected $description = 'Poll every server agent for the latest metrics snapshot and store it. Old data is pruned based on --retention.';

    public function handle(): int
    {
        $retentionDays = (int) $this->option('retention');

        $servers = Server::all();
        $stored  = 0;
        $failed  = 0;

        foreach ($servers as $server) {
            try {
                $response = Http::withoutVerifying()
                    ->timeout(5)
                    ->get($server->agent_url . '/metrics/realtime');

                if (! $response->successful()) {
                    $failed++;
                    continue;
                }

                $data      = $response->json();
                $snapshots = $data['snapshots'] ?? [];

                if (empty($snapshots)) {
                    $failed++;
                    continue;
                }

                // Take only the most recent snapshot — we want one row per minute,
                // not the whole 60-snapshot buffer the agent returned.
                $latest = end($snapshots);

                ServerMetric::create([
                    'server_id'      => $server->id,
                    'recorded_at'    => now(),
                    'cpu_percent'    => $latest['cpu_percent'] ?? 0,
                    'mem_percent'    => $latest['mem_percent'] ?? 0,
                    'mem_used_mb'    => $latest['mem_used_mb'] ?? 0,
                    'mem_total_mb'   => $latest['mem_total_mb'] ?? 0,
                    'disk_percent'   => $latest['disk_percent'] ?? 0,
                    'load_avg_1'     => $latest['load_avg_1'] ?? 0,
                    'load_avg_5'     => $latest['load_avg_5'] ?? 0,
                    'load_avg_15'    => $latest['load_avg_15'] ?? 0,
                    'network_in_kb'  => $latest['network_in_kb'] ?? 0,
                    'network_out_kb' => $latest['network_out_kb'] ?? 0,
                ]);

                $stored++;
            } catch (\Throwable $e) {
                Log::warning("monitor:collect failed for server {$server->id}: " . $e->getMessage());
                $failed++;
            }
        }

        // Prune old metrics.
        $cutoff  = Carbon::now()->subDays($retentionDays);
        $deleted = ServerMetric::where('recorded_at', '<', $cutoff)->delete();

        $this->info("Stored: {$stored} · Failed: {$failed} · Pruned: {$deleted}");
        return self::SUCCESS;
    }
}
