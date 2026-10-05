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
            match ($request->range) {
                'today'  => $query->whereDate('created_at', today()),
                '7days'  => $query->where('created_at', '>=', now()->subDays(7)),
                '30days' => $query->where('created_at', '>=', now()->subDays(30)),
                default  => null,
            };
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

        $actionsPerDay = match ($request->range) {
            'today'  => ActivityLog::whereDate('created_at', today())
                ->selectRaw('HOUR(created_at) as date, count(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(fn ($r) => ['date' => sprintf('%02d:00', $r->date), 'count' => $r->count]),
            '7days'  => ActivityLog::where('created_at', '>=', now()->subDays(7))
                ->selectRaw('DATE(created_at) as date, count(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            default  => ActivityLog::where('created_at', '>=', now()->subDays(30))
                ->selectRaw('DATE(created_at) as date, count(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        };

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
