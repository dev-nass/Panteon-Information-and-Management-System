<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cluster;
use App\Models\Phase;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboardService) {}

    public function index(Request $request)
    {
        $tab = $request->get('tab', 'summary');
        $filter = $request->get('filter', 'monthly');
        $year = $request->get('year', now()->year);
        $phaseId = $request->get('phase_id');
        $clusterPage = $request->get('cluster_page', 1);
        $clusterType = $request->get('cluster_type');

        $data = [
            'stats' => $this->dashboardService->getTotalStats(),
            'disposal_stats' => $this->dashboardService->getDisposalStats(),
            'current_tab' => $tab,
            'current_filter' => $filter,
            'selected_year' => (int) $year,
        ];

        if ($tab === 'summary') {
            $data['activity_data'] = $this->dashboardService->getActivityData($filter, (int) $year);
        } elseif ($tab === 'phases') {
            $data['phase_data'] = $this->getPhaseOccupancyData();
        } elseif ($tab === 'clusters') {
            $data['phases'] = Phase::select('id', 'phase_name')->orderBy('phase_name')->get();
            $data['selected_phase_id'] = (int) ($phaseId ?? Phase::where('phase_name', '1a')->value('id') ?? Phase::first()?->id);
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
                        ->selectRaw('count(lots.id)');
                },
                'clusters as occupied_lots' => function ($query) {
                    $query->join('lots', 'clusters.id', '=', 'lots.cluster_id')
                        ->whereExists(function ($subQuery) {
                            $subQuery->selectRaw(1)
                                ->from('burial_records')
                                ->whereColumn('burial_records.lot_id', 'lots.id');
                        })
                        ->selectRaw('count(lots.id)');
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
}
