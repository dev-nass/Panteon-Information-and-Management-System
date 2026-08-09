<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Models\BurialRecord;
use App\Models\Phase;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboardService) {}

    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $stats = $this->dashboardService->getTotalStats();

        // Number of burials scheduled for today
        $todayBurialCount = BurialRecord::whereHas('deceasedRecord', function ($query) {
            $query->whereDate('date_of_depository', Carbon::today());
        })->count();

        // Today's burial schedules (upcoming burials for today)
        $todaySchedules = BurialRecord::with(['deceasedRecord.applicant', 'lot.cluster.phase'])
            ->whereHas('deceasedRecord', function ($query) {
                $query->whereDate('date_of_depository', Carbon::today());
            })
            ->orderBy('id')
            ->limit(5)
            ->get()
            ->map(fn ($record) => $this->scheduleSummary($record));

        // Upcoming burial schedules for the next 7 days (excluding today)
        $pendingTasks = BurialRecord::with(['deceasedRecord.applicant', 'lot.cluster.phase'])
            ->whereHas('deceasedRecord', function ($query) {
                $query->whereBetween('date_of_depository', [Carbon::tomorrow(), Carbon::today()->addDays(7)]);
            })
            ->get()
            ->sortBy(fn ($record) => $record->deceasedRecord->date_of_depository)
            ->take(10)
            ->map(fn ($record) => $this->scheduleSummary($record))
            ->values();

        // Recent activities (last 5 burial records created)
        $recentActivities = BurialRecord::with(['deceasedRecord', 'user'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($record) {
                return [
                    'action' => 'Registered profile: '.$record->deceasedRecord->first_name.' '.$record->deceasedRecord->last_name,
                    'time' => $record->created_at->diffForHumans(),
                    'type' => 'burial',
                ];
            });

        // Records encoded by the logged-in clerk (last 5)
        $recentBurialRecords = BurialRecord::with(['deceasedRecord', 'lot.cluster'])
            ->where('user_id', $userId)
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->id,
                    'deceased_name' => $record->deceasedRecord->first_name.' '.
                        ($record->deceasedRecord->middle_name ? substr($record->deceasedRecord->middle_name, 0, 1).'. ' : '').
                        $record->deceasedRecord->last_name,
                    'lot_number' => $record->lot
                        ? $record->lot->column.'-'.$record->lot->row
                        : 'N/A',
                    'created_at' => $record->created_at->format('M d, Y'),
                ];
            });

        // Phase statistics (availability at a glance)
        // Phase statistics: available lots per phase computed by
        // summing the available lots of every cluster under it
        $phaseStats = Phase::with(['clusters' => function ($query) {
            $query->withCount([
                'lots as total_lots',
                'lots as occupied_lots' => function ($q) {
                    $q->whereHas('burialRecords');
                },
            ])->orderBy('cluster_name');
        }])
            ->orderBy('phase_name')
            ->get()
            ->map(function ($phase) {
                $clusters = $phase->clusters->map(function ($cluster) {
                    $totalLots = $cluster->total_lots;
                    $occupiedLots = $cluster->occupied_lots;
                    $availableLots = $totalLots - $occupiedLots;
                    $occupancyRate = $totalLots > 0 ? ($occupiedLots / $totalLots) * 100 : 0;

                    return [
                        'name' => $cluster->cluster_name,
                        'type' => $cluster->cluster_type,
                        'available_lots' => $availableLots,
                        'total_lots' => $totalLots,
                        'occupancy_rate' => round($occupancyRate, 1),
                    ];
                });

                $totalLots = $clusters->sum('total_lots');
                $availableLots = $clusters->sum('available_lots');
                $occupiedLots = $totalLots - $availableLots;
                $occupancyRate = $totalLots > 0 ? ($occupiedLots / $totalLots) * 100 : 0;

                return [
                    'name' => $phase->phase_name,
                    'available_lots' => $availableLots,
                    'total_lots' => $totalLots,
                    'occupancy_rate' => round($occupancyRate, 1),
                ];
            });

        return Inertia::render('Clerk/DashboardView', [
            'stats' => $stats,
            'today_burial_count' => $todayBurialCount,
            'today_schedules' => $todaySchedules,
            'pending_tasks' => $pendingTasks,
            'recent_activities' => $recentActivities,
            'recent_burial_records' => $recentBurialRecords,
            'phase_stats' => $phaseStats,
        ]);
    }

    private function scheduleSummary($record): array
    {
        $time = $record->deceasedRecord->time_of_depository
            ? Carbon::parse($record->deceasedRecord->time_of_depository)->format('h:i A')
            : 'N/A';

        $lotNumber = $record->lot
            ? $record->lot->column.'-'.$record->lot->row
            : 'N/A';

        return [
            'id' => $record->id,
            'date' => Carbon::parse($record->deceasedRecord->date_of_depository)->format('M d'),
            'time' => $time,
            'deceased_name' => $record->deceasedRecord->first_name.' '.
                ($record->deceasedRecord->middle_name ? substr($record->deceasedRecord->middle_name, 0, 1).'. ' : '').
                $record->deceasedRecord->last_name,
            'lot_number' => $lotNumber,
            'contact_name' => ($record->deceasedRecord->applicant->first_name ?? '').' '.($record->deceasedRecord->applicant->last_name ?? 'N/A'),
            'contact_relationship' => $record->deceasedRecord->applicant->relationship ?? 'N/A',
            'contact_phone' => $record->deceasedRecord->applicant->contact_number ?? 'N/A',
            'status' => Carbon::parse($record->deceasedRecord->date_of_depository)->isPast() ? 'Completed' : 'Confirmed',
        ];
    }
}
