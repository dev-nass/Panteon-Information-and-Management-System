<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Http\Resources\PhaseResource;
use App\Models\Cluster;
use App\Models\Lot;
use App\Models\Phase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LotManagementController extends Controller
{

    /**
     * Description: For the table view of the Lot Management page
     */
    public function index()
    {
        $phases = Phase::with(['clusters.lots.burialRecords'])->get()
            ->map(function ($phase) {
                $totalCapacity = 0;
                $lotOccupants = 0;

                $clusters = $phase->clusters->map(function ($cluster) use (&$totalCapacity, &$lotOccupants) {
                    $clusterOccupants = 0;
                    $lots = $cluster->lots->map(function ($lot) use (&$totalCapacity, &$lotOccupants, &$clusterOccupants) {
                        $totalCapacity++;
                        $isOccupied = $lot->burialRecords->isNotEmpty();
                        if ($isOccupied) {
                            $lotOccupants++;
                            $clusterOccupants++;
                        }

                        // lot
                        return [
                            'id' => $lot->id,
                            'column' => $lot->column,
                            'row' => $lot->row,
                            'status' => $isOccupied ? 'occupied' : 'available',
                            'isLot_mapped' => !is_null($lot->coordinates),
                        ];
                    });

                    // cluster
                    return [
                        'id' => $cluster->id,
                        'name' => $cluster->cluster_name,
                        'lots' => $lots,
                        'occupants' => $clusterOccupants,
                        'type' => $cluster->cluster_type,
                        'isCluster_mapped' => !is_null($cluster->coordinates),
                    ];
                });

                // phase
                return [
                    'id' => $phase->id,
                    'name' => $phase->phase_name,
                    'total_capacity' => $totalCapacity,
                    'occupants' => $lotOccupants,
                    'clusters' => $clusters,
                    'isPhase_mapped' => !is_null($phase->coordinates),
                ];
            });

        return Inertia::render('Clerk/LotManagement/IndexView', [
            'phases' => $phases,
        ]);
    }

    public function create()
    {
        $phases = Phase::select('id', 'phase_name', DB::raw('ST_AsGeoJSON(coordinates) as coordinates'))
            ->with(['clusters' => function ($query) {
                $query->select('id', 'phase_id', 'cluster_name', 'cluster_type', DB::raw('ST_AsGeoJSON(coordinates) as coordinates'));
            }])
            ->get()
            ->map(function ($phase) {
                return [
                    'id' => $phase->id,
                    'name' => $phase->phase_name,
                    'coordinates' => $phase->coordinates,
                    'clusters' => $phase->clusters->map(function ($cluster) {
                        return [
                            'id' => $cluster->id,
                            'name' => $cluster->cluster_name,
                            'type' => $cluster->cluster_type,
                            'coordinates' => $cluster->coordinates,
                        ];
                    }),
                ];
            });

        return Inertia::render('Clerk/LotManagement/CreateView', [
            'phases' => $phases,
        ]);
    }

    public function storePhase(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'coordinates' => 'required|json',
        ]);

        Phase::create([
            'phase_name' => $validated['name'],
            'coordinates' => DB::raw("ST_GeomFromGeoJSON('" . $validated['coordinates'] . "')"),
        ]);

        return to_route('clerk.lot_management.index')
            ->with('success', 'Phase created successfully.');
    }

    public function storeCluster(Request $request)
    {
        $validated = $request->validate([
            'phase_id' => 'required|exists:phases,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:apartment,underground',
            'occupants' => 'required|integer|min:0',
            'coordinates' => 'required|json',
        ]);

        Cluster::create([
            'phase_id' => $validated['phase_id'],
            'cluster_name' => $validated['name'],
            'cluster_type' => $validated['type'],
            'coordinates' => DB::raw("ST_GeomFromGeoJSON('" . $validated['coordinates'] . "')"),
        ]);

        return to_route('clerk.lot_management.index')
            ->with('success', 'Cluster created successfully.');
    }

    public function storeLot(Request $request)
    {
        $validated = $request->validate([
            'cluster_id' => 'required|exists:clusters,id',
            'column' => 'required|string|max:255',
            'row' => 'required|string|max:255',
            'status' => 'required|in:available,occupied',
            'coordinates' => 'required|json',
        ]);

        $existingLot = Lot::where('cluster_id', $validated['cluster_id'])
            ->where('row', $validated['row'])
            ->where('column', $validated['column'])
            ->first();

        if ($existingLot) {
            return back()->withErrors([
                'row' => 'A lot with this row and column already exists in this cluster.',
                'column' => 'A lot with this row and column already exists in this cluster.',
            ]);
        }

        Lot::create([
            'cluster_id' => $validated['cluster_id'],
            'column' => $validated['column'],
            'row' => $validated['row'],
            'coordinates' => DB::raw("ST_GeomFromGeoJSON('" . $validated['coordinates'] . "')"),
        ]);

        return to_route('clerk.lot_management.index')
            ->with('success', 'Lot created successfully.');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'phases' => 'required|array',
            'phases.*.id' => 'required|exists:phases,id',
            'phases.*.name' => 'required|string|max:255',
            'phases.*.clusters' => 'array',
            'phases.*.clusters.*.id' => 'required|exists:clusters,id',
            'phases.*.clusters.*.name' => 'required|string|max:255',
            'phases.*.clusters.*.type' => 'required|string|max:255',
            'phases.*.clusters.*.lots' => 'array',
            'phases.*.clusters.*.lots.*.id' => 'required|exists:lots,id',
            'phases.*.clusters.*.lots.*.column' => 'required|string|max:255',
            'phases.*.clusters.*.lots.*.row' => 'required|string|max:255',
        ]);

        foreach ($validated['phases'] as $phaseData) {
            $phase = Phase::find($phaseData['id']);
            $phase->update(['phase_name' => $phaseData['name']]);

            if (isset($phaseData['clusters'])) {
                foreach ($phaseData['clusters'] as $clusterData) {
                    $cluster = Cluster::find($clusterData['id']);
                    $cluster->update([
                        'cluster_name' => $clusterData['name'],
                        'cluster_type' => $clusterData['type'],
                    ]);

                    if (isset($clusterData['lots'])) {
                        foreach ($clusterData['lots'] as $lotData) {
                            $lot = Lot::find($lotData['id']);
                            $lot->update([
                                'column' => $lotData['column'],
                                'row' => $lotData['row'],
                            ]);
                        }
                    }
                }
            }
        }

        return to_route('clerk.lot_management.index')
            ->with('success', 'Changes saved successfully.');
    }

    public function deletePhase(Phase $phase)
    {
        $phase->delete();

        return to_route('clerk.lot_management.index')
            ->with('success', 'Phase deleted successfully.');
    }

    public function deleteCluster(Cluster $cluster)
    {
        $cluster->delete();

        return to_route('clerk.lot_management.index')
            ->with('success', 'Cluster deleted successfully.');
    }

    public function deleteLot(Lot $lot)
    {
        $lot->delete();

        return to_route('clerk.lot_management.index')
            ->with('success', 'Lot deleted successfully.');
    }

    /**
     * Description: Redirect to Burial Record Show by finding burial from lot
     */
    public function show(Lot $lot)
    {
        $burialRecord = $lot->burialRecords()->first();

        if (!$burialRecord) {
            return to_route('clerk.lot_management.index')
                ->with('error', 'No burial record found for this lot.');
        }

        return to_route('clerk.burial_records.show', $burialRecord->id);
    }
}
