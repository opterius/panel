<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\ServerMetric;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MonitorController extends Controller
{
    /**
     * Time-range presets used by the historical charts. Each entry is:
     *   - hours back from now
     *   - bucket size in seconds (controls how many points the chart gets)
     */
    private const RANGES = [
        '1h'  => ['hours' => 1,    'bucket' => 60],       // 60  points (1/min)
        '6h'  => ['hours' => 6,    'bucket' => 300],      // 72  points (1/5 min)
        '24h' => ['hours' => 24,   'bucket' => 900],      // 96  points (1/15 min)
        '7d'  => ['hours' => 168,  'bucket' => 3600],     // 168 points (1/hour)
        '30d' => ['hours' => 720,  'bucket' => 14400],    // 180 points (1/4 hours)
    ];

    public function index(Request $request)
    {
        $servers = Server::all();
        $selectedServer = null;

        if ($request->has('server_id')) {
            $selectedServer = Server::findOrFail($request->server_id);
        } elseif ($servers->count() === 1) {
            $selectedServer = $servers->first();
        }

        return view('monitor.index', compact('servers', 'selectedServer'));
    }

    /**
     * API proxy — frontend polls this for real-time metrics directly from agent.
     * Used for the LIVE cards at the top of the page (CPU 2.4%, Memory, Load Avg).
     */
    public function realtime(Request $request)
    {
        $server = Server::findOrFail($request->server_id);

        $response = Http::withoutVerifying()
            ->timeout(5)
            ->get($server->agent_url . '/metrics/realtime');

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Agent unreachable'], 502);
    }

    /**
     * GET /admin/monitor/history?server_id=X&range=1h|6h|24h|7d|30d
     *
     * Returns aggregated metrics from the local database (populated by
     * monitor:collect every minute). Aggregation buckets keep the chart at
     * ~60–200 data points regardless of range.
     */
    public function history(Request $request)
    {
        $server = Server::findOrFail($request->server_id);
        $range  = $request->input('range', '1h');

        if (! isset(self::RANGES[$range])) {
            return response()->json(['error' => 'Invalid range'], 422);
        }

        $cfg    = self::RANGES[$range];
        $since  = Carbon::now()->subHours($cfg['hours']);
        $bucket = $cfg['bucket'];

        // Bucket the data using floor(unix_timestamp / bucket_size) so we get
        // stable aggregation windows. Compatible with both MySQL and SQLite.
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $bucketExpr = "CAST(strftime('%s', recorded_at) / {$bucket} AS INTEGER)";
        } else {
            $bucketExpr = "FLOOR(UNIX_TIMESTAMP(recorded_at) / {$bucket})";
        }

        $rows = ServerMetric::query()
            ->where('server_id', $server->id)
            ->where('recorded_at', '>=', $since)
            ->selectRaw("
                {$bucketExpr} AS bucket,
                MIN(recorded_at)        AS bucket_start,
                AVG(cpu_percent)        AS cpu_percent,
                AVG(mem_percent)        AS mem_percent,
                AVG(mem_used_mb)        AS mem_used_mb,
                MAX(mem_total_mb)       AS mem_total_mb,
                AVG(disk_percent)       AS disk_percent,
                AVG(load_avg_1)         AS load_avg_1,
                AVG(load_avg_5)         AS load_avg_5,
                AVG(load_avg_15)        AS load_avg_15,
                AVG(network_in_kb)      AS network_in_kb,
                AVG(network_out_kb)     AS network_out_kb
            ")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        // Format for the chart.
        $points = $rows->map(fn ($r) => [
            'timestamp'      => Carbon::parse($r->bucket_start)->toIso8601String(),
            'cpu_percent'    => round((float) $r->cpu_percent, 2),
            'mem_percent'    => round((float) $r->mem_percent, 2),
            'mem_used_mb'    => (int) $r->mem_used_mb,
            'mem_total_mb'   => (int) $r->mem_total_mb,
            'disk_percent'   => round((float) $r->disk_percent, 2),
            'load_avg_1'     => round((float) $r->load_avg_1, 2),
            'load_avg_5'     => round((float) $r->load_avg_5, 2),
            'load_avg_15'    => round((float) $r->load_avg_15, 2),
            'network_in_kb'  => (int) $r->network_in_kb,
            'network_out_kb' => (int) $r->network_out_kb,
        ])->values();

        // Quick statistics for the summary panel.
        $stats = [
            'cpu_avg'         => round((float) $rows->avg('cpu_percent'), 1),
            'cpu_peak'        => round((float) $rows->max('cpu_percent'), 1),
            'mem_avg'         => round((float) $rows->avg('mem_percent'), 1),
            'mem_peak'        => round((float) $rows->max('mem_percent'), 1),
            'load_peak'       => round((float) $rows->max('load_avg_1'), 2),
            'network_in_avg'  => round((float) $rows->avg('network_in_kb'), 1),
            'network_out_avg' => round((float) $rows->avg('network_out_kb'), 1),
            'sample_count'    => $rows->count(),
        ];

        return response()->json([
            'range'  => $range,
            'points' => $points,
            'stats'  => $stats,
        ]);
    }

    /**
     * API proxy for top processes.
     */
    public function topProcesses(Request $request)
    {
        $server = Server::findOrFail($request->server_id);

        $response = AgentService::for($server)->post('/metrics/top-processes', []);

        if ($response && $response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Agent unreachable'], 502);
    }
}
