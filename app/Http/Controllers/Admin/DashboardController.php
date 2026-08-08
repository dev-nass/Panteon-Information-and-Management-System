<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BurialRecord;
use App\Models\Cluster;
use App\Models\DeceasedRecord;
use App\Models\Lot;
use App\Models\Phase;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'summary');
        $filter = $request->get('filter', 'monthly');
        $year = $request->get('year', Carbon::now()->year);
        $phaseId = $request->get('phase_id');
        $clusterPage = $request->get('cluster_page', 1);
        $clusterType = $request->get('cluster_type');

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

        $data = [
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
            'current_tab' => $tab,
            'current_filter' => $filter,
            'selected_year' => (int) $year,
        ];

        if ($tab === 'summary') {
            $data['activity_data'] = $this->getActivityData($filter, $year);
        } elseif ($tab === 'phases') {
            $data['phase_data'] = $this->getPhaseOccupancyData();
        } elseif ($tab === 'clusters') {
            $data['phases'] = Phase::select('id', 'phase_name')->orderBy('phase_name')->get();
            $data['selected_phase_id'] = $phaseId ?? Phase::where('phase_name', '1a')->value('id') ?? Phase::first()?->id;
            $data['cluster_data'] = $this->getClusterOccupancyData($data['selected_phase_id'], (int) $clusterPage, $clusterType);
            $data['selected_type'] = in_array($clusterType, ['underground', 'apartment', 'columbarium']) ? $clusterType : '';
        }

        return Inertia::render('Admin/DashboardView', $data);
    }

    private function getPhaseOccupancyData()
    {
        $phases = Phase::select('id', 'phase_name')
            ->withCount([
                'clusters as total_lots' => function ($query) {
                    $query->join('lots', 'clusters.id', '=', 'lots.cluster_id')
                        ->select(DB::raw('count(lots.id)'));
                },
                'clusters as occupied_lots' => function ($query) {
                    $query->join('lots', 'clusters.id', '=', 'lots.cluster_id')
                        ->whereExists(function ($subQuery) {
                            $subQuery->select(DB::raw(1))
                                ->from('burial_records')
                                ->whereColumn('burial_records.lot_id', 'lots.id');
                        })
                        ->select(DB::raw('count(lots.id)'));
                },
            ])
            ->get();

        $labels = [];
        $occupied = [];
        $available = [];

        foreach ($phases as $phase) {
            $labels[] = $phase->phase_name;
            $occupied[] = $phase->occupied_lots;
            $available[] = $phase->total_lots - $phase->occupied_lots;
        }

        return [
            'labels' => $labels,
            'occupied' => $occupied,
            'available' => $available,
        ];
    }

    private function getClusterOccupancyData($phaseId = null, $page = 1, $type = null)
    {
        $query = Cluster::withCount([
            'lots as total_lots',
            'lots as occupied_lots' => function ($query) {
                $query->whereHas('burialRecords');
            },
        ]);

        if ($phaseId) {
            $query->where('phase_id', $phaseId);
        }

        if ($type && in_array($type, ['underground', 'apartment', 'columbarium'])) {
            $query->where('cluster_type', $type);
        }

        $clusters = $query->paginate(10, ['*'], 'page', $page);

        $labels = [];
        $types = [
            'underground' => ['occupied' => [], 'available' => []],
            'apartment' => ['occupied' => [], 'available' => []],
            'columbarium' => ['occupied' => [], 'available' => []],
        ];

        foreach ($clusters->items() as $cluster) {
            $type = $cluster->cluster_type;

            if (! isset($types[$type])) {
                $type = 'underground';
            }

            $labels[] = $cluster->cluster_name;
            $types[$type]['occupied'][] = $cluster->occupied_lots;
            $types[$type]['available'][] = $cluster->total_lots - $cluster->occupied_lots;
        }

        return [
            'labels' => $labels,
            'types' => $types,
            'current_page' => $clusters->currentPage(),
            'last_page' => $clusters->lastPage(),
            'total' => $clusters->total(),
        ];
    }

    private function getActivityData($filter, $year)
    {
        $now = Carbon::now();

        if ($filter === 'today') {
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
            $values = array_map(fn ($hour) => $data[$hour] ?? 0, $labels);
            $labels = array_map(fn ($hour) => sprintf('%02d:00', $hour), $labels);

        } elseif ($filter === 'weekly') {
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
            $values = array_map(fn ($month) => $data[$month] ?? 0, range(1, 12));

        } else {
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
            $values = array_map(fn ($day) => $data[$day] ?? 0, $labels);
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }
}
