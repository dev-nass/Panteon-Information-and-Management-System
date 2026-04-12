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
