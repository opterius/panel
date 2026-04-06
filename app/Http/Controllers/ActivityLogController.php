<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->latest('created_at');

        // Filters
        if ($request->filled('action')) {
            $query->where('action', 'like', $request->action . '%');
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('entity_name', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(50);

        // Get unique action prefixes for filter dropdown
        $actionTypes = ActivityLog::selectRaw("SUBSTRING_INDEX(action, '.', 1) as type")
            ->distinct()
            ->pluck('type')
            ->sort();

        return view('admin.activity-log', compact('logs', 'actionTypes'));
    }

    public function export(Request $request)
    {
        $query = ActivityLog::with('user')->latest('created_at');

        if ($request->filled('action')) {
            $query->where('action', 'like', $request->action . '%');
        }

        $logs = $query->take(5000)->get();

        $csv = "Date,User,Action,Entity,Description,IP\n";
        foreach ($logs as $log) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s\n",
                $log->created_at->format('Y-m-d H:i:s'),
                str_replace(',', '', $log->user?->name ?? 'System'),
                $log->action,
                str_replace(',', '', $log->entity_name ?? ''),
                str_replace(',', '', $log->description ?? ''),
                $log->ip_address ?? ''
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="activity-log-' . now()->format('Y-m-d') . '.csv"');
    }
}
