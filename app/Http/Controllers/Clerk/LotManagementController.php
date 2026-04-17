<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Models\Cluster;
use App\Models\Lot;
use App\Models\Phase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LotManagementController extends Controller
{
    public function index()
    {
        $phases = Phase::select('id', 'phase_name', DB::raw('ST_AsGeoJSON(coordinates) as coordinates'))
            ->withCount('clusters')
            ->get()
            ->map(function ($phase) {
                return [
                    'id' => $phase->id,
                    'name' => $phase->phase_name,
                    'total_clusters' => $phase->clusters_count,
                    'coordinates' => $phase->coordinates,
                    'isPhase_mapped' => ! is_null($phase->coordinates),
                ];
            });

        return Inertia::render('Clerk/LotManagement/IndexView', [
            'phases' => $phases,
        ]);
    }

    public function create(Request $request)
    {
        $type = $request->query('type', 'phase');
        $phaseId = $request->query('phase_id');
        $clusterId = $request->query('cluster_id');

        $phases = Phase::select('id', 'phase_name', DB::raw('ST_AsGeoJSON(coordinates) as coordinates'))
            ->with([
                'clusters' => function ($query) {
                    $query->select('id', 'phase_id', 'cluster_name', 'cluster_type', 'total_capacity', DB::raw('ST_AsGeoJSON(coordinates) as coordinates'));
                },
                'clusters.lots.burialRecords',
            ])
            ->get()
            ->map(function ($phase) {
                return [
                    'id' => $phase->id,
                    'name' => $phase->phase_name,
                    'coordinates' => $phase->coordinates,
                    'clusters' => $phase->clusters->filter(function ($cluster) {
                        // Only include clusters that have capacity for more lots
                        $totalLots = $cluster->lots->count();

                        return $cluster->total_capacity === null || $totalLots < $cluster->total_capacity;
                    })->map(function ($cluster) {
                        return [
                            'id' => $cluster->id,
                            'name' => $cluster->cluster_name,
                            'type' => $cluster->cluster_type,
                            'total_capacity' => $cluster->total_capacity,
                            'coordinates' => $cluster->coordinates,
                        ];
                    })->values(),
                ];
            });

        return Inertia::render('Clerk/LotManagement/CreateView', [
            'phases' => $phases,
            'type' => $type,
            'phase_id' => $phaseId,
            'cluster_id' => $clusterId,
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
            'coordinates' => DB::raw("ST_GeomFromGeoJSON('".$validated['coordinates']."')"),
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
            'occupants' => 'required|integer|min:5',
            'coordinates' => 'required|json',
        ]);

        Cluster::create([
            'phase_id' => $validated['phase_id'],
            'cluster_name' => $validated['name'],
            'cluster_type' => $validated['type'],
            'total_capacity' => $validated['occupants'],
            'coordinates' => DB::raw("ST_GeomFromGeoJSON('".$validated['coordinates']."')"),
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
            'coordinates' => DB::raw("ST_GeomFromGeoJSON('".$validated['coordinates']."')"),
        ]);

        return to_route('clerk.lot_management.index')
            ->with('success', 'Lot created successfully.');
    }

    public function updatePhase(Request $request, Phase $phase)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'coordinates' => 'nullable|json',
        ]);

        DB::update(
            'UPDATE phases SET phase_name = ?, coordinates = ST_GeomFromGeoJSON(?) WHERE id = ?',
            [$validated['name'], $validated['coordinates'], $phase->id]
        );

        return to_route('clerk.lot_management.index')->with('success', 'Phase updated successfully.');
    }

    public function updateCluster(Request $request, Cluster $cluster)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:apartment,underground',
            'total_capacity' => 'nullable|integer|min:1',
            'coordinates' => 'nullable|json',
        ]);

        if (isset($validated['coordinates'])) {
            DB::update(
                'UPDATE clusters SET cluster_name = ?, cluster_type = ?, total_capacity = ?, coordinates = ST_GeomFromGeoJSON(?) WHERE id = ?',
                [$validated['name'], $validated['type'], $validated['total_capacity'] ?? null, $validated['coordinates'], $cluster->id]
            );
        } else {
            DB::update(
                'UPDATE clusters SET cluster_name = ?, cluster_type = ?, total_capacity = ? WHERE id = ?',
                [$validated['name'], $validated['type'], $validated['total_capacity'] ?? null, $cluster->id]
            );
        }

        return to_route('clerk.lot_management.index')->with('success', 'Cluster updated successfully.');
    }

    public function updateLot(Request $request, Lot $lot)
    {
        $validated = $request->validate([
            'column' => 'required|string|max:255',
            'row' => 'required|string|max:255',
            'coordinates' => 'nullable|json',
        ]);

        DB::update(
            'UPDATE lots SET `column` = ?, `row` = ?, coordinates = ST_GeomFromGeoJSON(?) WHERE id = ?',
            [$validated['column'], $validated['row'], $validated['coordinates'], $lot->id]
        );

        return to_route('clerk.lot_management.index')->with('success', 'Lot updated successfully.');
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

        if (! $burialRecord) {
            return to_route('clerk.lot_management.index')
                ->with('error', 'No burial record found for this lot.');
        }

        return to_route('clerk.burial_records.show', $burialRecord->id);
    }
}
