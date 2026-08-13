<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->latest();

        if ($request->filled('range') && $request->range !== 'all') {
            $days = match ($request->range) {
                'today' => 0,
                '7days' => 7,
                '30days' => 30,
                default => null,
            };
            if ($days !== null) {
                $query->where('created_at', '>=', now()->subDays($days));
            }
        }

        if ($request->filled('action') && $request->action !== 'all') {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_search')) {
            $search = $request->user_search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(15)->withQueryString();

        $todayCount = ActivityLog::whereDate('created_at', today())->count();
        $weekCount = ActivityLog::where('created_at', '>=', now()->subDays(7))->count();
        $totalCount = ActivityLog::count();
        $perUser = ActivityLog::whereNotNull('user_id')
            ->selectRaw('user_id, count(*) as count')
            ->groupBy('user_id')
            ->orderByDesc('count')
            ->with('user:id,first_name,last_name')
            ->get();

        $actionsPerDay = ActivityLog::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $actionBreakdown = ActivityLog::selectRaw('action, count(*) as count')
            ->groupBy('action')
            ->get();

        return Inertia::render('Admin/ActivityLog/IndexView', [
            'logs' => $logs,
            'filters' => [
                'range' => $request->range,
                'action' => $request->action,
                'user_search' => $request->user_search,
            ],
            'stats' => [
                'today_count' => $todayCount,
                'week_count' => $weekCount,
                'total_count' => $totalCount,
            ],
            'per_user' => $perUser,
            'chart_data' => [
                'actions_per_day' => $actionsPerDay,
                'action_breakdown' => $actionBreakdown,
            ],
        ]);
    }
}
