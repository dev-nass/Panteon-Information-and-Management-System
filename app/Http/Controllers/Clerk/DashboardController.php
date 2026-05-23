<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Models\BurialRecord;
use App\Models\Cluster;
use App\Models\DeceasedRecord;
use App\Models\Lot;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'monthly');
        $year = $request->get('year', Carbon::now()->year);

        // Get date range based on filter
        $dateRange = $this->getDateRange($filter, $year);

        // Total statistics
        $totalBurialRecords = BurialRecord::count();
        $totalLots = Lot::count();
        $occupiedLots = Lot::has('burialRecords')->count();
        $availableLots = $totalLots - $occupiedLots;

        // Disposal type breakdown
        $disposalStats = DeceasedRecord::select('corpse_disposal', DB::raw('count(*) as count'))
            ->groupBy('corpse_disposal')
            ->get()
            ->pluck('count', 'corpse_disposal')
            ->toArray();

        // Monthly activity data
        $activityData = $this->getActivityData($filter, $year);

        // Today's burial schedules (upcoming burials for today)
        $todaySchedules = BurialRecord::with(['deceasedRecord.applicant', 'lot'])
            ->join('deceased_records', 'burial_records.deceased_record_id', '=', 'deceased_records.id')
            ->whereDate('deceased_records.date_of_depository', Carbon::today())
            ->orderBy('deceased_records.date_of_depository')
            ->limit(5)
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->id,
                    'time' => Carbon::parse($record->deceasedRecord->date_of_depository)->format('h:i A'),
                    'deceased_name' => $record->deceasedRecord->first_name . ' ' . 
                                      ($record->deceasedRecord->middle_name ? substr($record->deceasedRecord->middle_name, 0, 1) . '. ' : '') . 
                                      $record->deceasedRecord->last_name,
                    'lot_number' => $record->lot->lot_number ?? 'N/A',
                    'contact_name' => $record->deceasedRecord->applicant->first_name . ' ' . $record->deceasedRecord->applicant->last_name ?? 'N/A',
                    'contact_relationship' => $record->deceasedRecord->applicant->relationship ?? 'N/A',
                    'contact_phone' => $record->deceasedRecord->applicant->contact_number ?? 'N/A',
                    'status' => Carbon::parse($record->deceasedRecord->date_of_depository)->isPast() ? 'Completed' : 'Confirmed',
                ];
            });

        // Recent activities (last 5 burial records created)
        $recentActivities = BurialRecord::with(['deceasedRecord', 'user'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($record) {
                return [
                    'action' => 'Registered profile: ' . $record->deceasedRecord->first_name . ' ' . $record->deceasedRecord->last_name,
                    'time' => $record->created_at->diffForHumans(),
                    'type' => 'burial',
                ];
            });

        // Cluster statistics (section availability)
        $clusterStats = Cluster::with('lots')
            ->get()
            ->map(function ($cluster) {
                $totalLots = $cluster->lots->count();
                $occupiedLots = $cluster->lots()->has('burialRecords')->count();
                $availableLots = $totalLots - $occupiedLots;
                $occupancyRate = $totalLots > 0 ? ($occupiedLots / $totalLots) * 100 : 0;

                return [
                    'name' => $cluster->cluster_name,
                    'type' => $cluster->cluster_type,
                    'available_lots' => $availableLots,
                    'total_lots' => $totalLots,
                    'occupancy_rate' => round($occupancyRate, 1),
                ];
            })
            ->take(5);

        return Inertia::render('Clerk/DashboardView', [
            'stats' => [
                'total_burial_records' => $totalBurialRecords,
                'total_lots' => $totalLots,
                'occupied_lots' => $occupiedLots,
                'available_lots' => $availableLots,
            ],
            'disposal_stats' => [
                'burial' => $disposalStats['burial'] ?? 0,
                'cremation' => $disposalStats['cremation'] ?? 0,
            ],
            'activity_data' => $activityData,
            'current_filter' => $filter,
            'selected_year' => (int) $year,
            'today_schedules' => $todaySchedules,
            'recent_activities' => $recentActivities,
            'cluster_stats' => $clusterStats,
        ]);
    }

    private function getDateRange($filter, $year)
    {
        $now = Carbon::now();
        $targetYear = Carbon::create($year);

        return match ($filter) {
            'today' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
            'weekly' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
            ],
            'yearly' => [
                'start' => $targetYear->copy()->startOfYear(),
                'end' => $targetYear->copy()->endOfYear(),
            ],
            default => [ // monthly
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
        };
    }

    private function getActivityData($filter, $year)
    {
        $now = Carbon::now();

        if ($filter === 'today') {
            // Hourly data for today
            $data = BurialRecord::join('deceased_records', 'burial_records.deceased_record_id', '=', 'deceased_records.id')
                ->select(
                    DB::raw('HOUR(deceased_records.date_of_depository) as period'),
                    DB::raw('count(*) as count')
                )
                ->whereDate('deceased_records.date_of_depository', $now->toDateString())
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->pluck('count', 'period')
                ->toArray();

            $labels = range(0, 23);
            $values = array_map(fn($hour) => $data[$hour] ?? 0, $labels);
            $labels = array_map(fn($hour) => sprintf('%02d:00', $hour), $labels);

        } elseif ($filter === 'weekly') {
            // Daily data for this week
            $data = BurialRecord::join('deceased_records', 'burial_records.deceased_record_id', '=', 'deceased_records.id')
                ->select(
                    DB::raw('DATE(deceased_records.date_of_depository) as period'),
                    DB::raw('count(*) as count')
                )
                ->whereBetween('deceased_records.date_of_depository', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->pluck('count', 'period')
                ->toArray();

            $labels = [];
            $values = [];
            for ($i = 0; $i < 7; $i++) {
                $date = $now->copy()->startOfWeek()->addDays($i);
                $dateStr = $date->toDateString();
                $labels[] = $date->format('D');
                $values[] = $data[$dateStr] ?? 0;
            }

        } elseif ($filter === 'yearly') {
            // Monthly data for selected year
            $data = BurialRecord::join('deceased_records', 'burial_records.deceased_record_id', '=', 'deceased_records.id')
                ->select(
                    DB::raw('MONTH(deceased_records.date_of_depository) as period'),
                    DB::raw('count(*) as count')
                )
                ->whereYear('deceased_records.date_of_depository', $year)
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->pluck('count', 'period')
                ->toArray();

            $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $values = array_map(fn($month) => $data[$month] ?? 0, range(1, 12));

        } else { // monthly
            // Daily data for this month
            $daysInMonth = $now->daysInMonth;
            $data = BurialRecord::join('deceased_records', 'burial_records.deceased_record_id', '=', 'deceased_records.id')
                ->select(
                    DB::raw('DAY(deceased_records.date_of_depository) as period'),
                    DB::raw('count(*) as count')
                )
                ->whereYear('deceased_records.date_of_depository', $now->year)
                ->whereMonth('deceased_records.date_of_depository', $now->month)
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->pluck('count', 'period')
                ->toArray();

            $labels = range(1, $daysInMonth);
            $values = array_map(fn($day) => $data[$day] ?? 0, $labels);
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }
}
