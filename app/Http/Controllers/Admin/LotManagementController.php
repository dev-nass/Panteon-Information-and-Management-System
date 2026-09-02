<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cluster;
use App\Models\Lot;
use App\Models\Phase;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LotManagementController extends Controller
{
    use LogsActivity;

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
                    'isPhase_mapped' => !is_null($phase->coordinates),
                ];
            });

        return Inertia::render('Shared/LotManagement/IndexView', [
            'phases' => $phases,
        ]);
    }

    /**
     * Description: Redirect to Burial Record Show by finding burial from lot
     */
    public function show(Lot $lot)
    {
        $burialRecord = $lot->burialRecords()->first();

        if (!$burialRecord) {
            return to_route('admin.lot_management.index')
                ->with('error', 'No burial record found for this lot.');
        }

        return to_route('admin.burial_records.show', $burialRecord->id);
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

                        return $totalLots < $cluster->total_capacity;
                    })->map(function ($cluster) {
                        return [
                            'id' => $cluster->id,
                            'name' => $cluster->cluster_name,
                            'type' => $cluster->cluster_type,
                            'total_capacity' => $cluster->total_capacity,
                            'lot_count' => $cluster->lots->count(),
                            'remaining_capacity' => $cluster->total_capacity
                                ? $cluster->total_capacity - $cluster->lots->count()
                                : null,
                            'coordinates' => $cluster->coordinates,
                        ];
                    })->values(),
                ];
            });

        return Inertia::render('Shared/LotManagement/CreateView', [
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

        $phase = Phase::create([
            'phase_name' => $validated['name'],
            'coordinates' => DB::raw("ST_GeomFromGeoJSON('" . $validated['coordinates'] . "')"),
        ]);

        $this->logActivity(
            'created',
            $phase,
            "Created phase {$validated['name']}",
        );

        return to_route('admin.lot_management.index')
            ->with('success', 'Phase created successfully.');
    }

    public function storeCluster(Request $request)
    {
        $validated = $request->validate([
            'phase_id' => 'required|exists:phases,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:apartment,underground,columbarium',
            'total_capacity' => 'required|integer|min:5',
            'coordinates' => 'required|json',
        ]);

        $cluster = Cluster::create([
            'phase_id' => $validated['phase_id'],
            'cluster_name' => $validated['name'],
            'cluster_type' => $validated['type'],
            'total_capacity' => $validated['total_capacity'],
            'coordinates' => DB::raw("ST_GeomFromGeoJSON('" . $validated['coordinates'] . "')"),
        ]);

        $this->logActivity(
            'created',
            $cluster,
            "Created cluster {$validated['name']}",
        );

        return to_route('admin.lot_management.index')
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

        $lot = Lot::create([
            'cluster_id' => $validated['cluster_id'],
            'column' => $validated['column'],
            'row' => strtoupper($validated['row']),
            'coordinates' => DB::raw("ST_GeomFromGeoJSON('" . $validated['coordinates'] . "')"),
        ]);

        $this->logActivity(
            'created',
            $lot,
            "Created lot {$validated['row']}-{$validated['column']}",
        );

        return to_route('admin.lot_management.index')
            ->with('success', 'Lot created successfully.');
    }

    public function storeBulkLot(Request $request)
    {
        $validated = $request->validate([
            'cluster_id' => 'required|exists:clusters,id',
            'lots' => 'required|array|min:1',
            'lots.*.column' => 'required|string|max:255',
            'lots.*.row' => 'required|string|max:255',
            'lots.*.coordinates' => 'required|json',
        ]);

        $cluster = Cluster::findOrFail($validated['cluster_id']);

        // Check for duplicates within the submitted batch
        $seenKeys = [];

        foreach ($validated['lots'] as $lot) {
            $key = $lot['column'] . '|' . $lot['row'];

            if (isset($seenKeys[$key])) {
                return back()->withErrors([
                    'lots' => 'Duplicate lot (column ' . $lot['column'] . ', row ' . $lot['row'] . ') found in the batch.',
                ]);
            }

            $seenKeys[$key] = true;
        }

        // Check for duplicates against existing lots in the cluster
        $existingKeys = Lot::where('cluster_id', $cluster->id)
            ->get(['column', 'row'])
            ->map(fn($lot) => $lot->column . '|' . $lot->row)
            ->flip();

        foreach ($validated['lots'] as $lot) {
            $key = $lot['column'] . '|' . $lot['row'];

            if (isset($existingKeys[$key])) {
                return back()->withErrors([
                    'lots' => 'A lot with column ' . $lot['column'] . ' and row ' . $lot['row'] . ' already exists in this cluster.',
                ]);
            }
        }

        // Check that the cluster has enough remaining capacity
        if ($cluster->total_capacity) {
            $remainingCapacity = $cluster->total_capacity - $cluster->lots()->count();

            if (count($validated['lots']) > $remainingCapacity) {
                return back()->withErrors([
                    'lots' => 'Not enough capacity in this cluster. Only ' . $remainingCapacity . ' more lot(s) can be created.',
                ]);
            }
        }

        DB::transaction(function () use ($validated) {
            foreach ($validated['lots'] as $lot) {
                Lot::create([
                    'cluster_id' => $validated['cluster_id'],
                    'column' => $lot['column'],
                    'row' => $lot['row'],
                    'coordinates' => DB::raw("ST_GeomFromGeoJSON('" . $lot['coordinates'] . "')"),
                ]);
            }
        });

        $this->logActivity(
            'created',
            $cluster,
            'Bulk created ' . count($validated['lots']) . " lots in cluster {$cluster->cluster_name}",
            null,
            ['lot_count' => count($validated['lots'])],
        );

        return to_route('admin.lot_management.index')
            ->with('success', count($validated['lots']) . ' lots created successfully.');
    }

    public function updatePhase(Request $request, Phase $phase)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'coordinates' => 'nullable|json',
        ]);

        $oldName = $phase->phase_name;

        DB::update(
            'UPDATE phases SET phase_name = ?, coordinates = ST_GeomFromGeoJSON(?) WHERE id = ?',
            [$validated['name'], $validated['coordinates'], $phase->id]
        );

        $this->logActivity(
            'updated',
            $phase,
            "Updated phase {$validated['name']}",
            ['phase_name' => $oldName],
            ['phase_name' => $validated['name']],
        );

        return to_route('admin.lot_management.index')->with('success', 'Phase updated successfully.');
    }

    public function updateCluster(Request $request, Cluster $cluster)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:apartment,underground',
            'total_capacity' => 'nullable|integer|min:1',
            'coordinates' => 'nullable|json',
        ]);

        $oldValues = $cluster->only(['cluster_name', 'cluster_type', 'total_capacity']);

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

        $this->logActivity(
            'updated',
            $cluster,
            "Updated cluster {$validated['name']}",
            $oldValues,
            [
                'cluster_name' => $validated['name'],
                'cluster_type' => $validated['type'],
                'total_capacity' => $validated['total_capacity'] ?? null,
            ],
        );

        return to_route('admin.lot_management.index')->with('success', 'Cluster updated successfully.');
    }

    public function updateLot(Request $request, Lot $lot)
    {
        $validated = $request->validate([
            'column' => 'required|string|max:255',
            'row' => 'required|string|max:255',
            'coordinates' => 'nullable|json',
        ]);

        $oldValues = $lot->only(['column', 'row']);

        DB::update(
            'UPDATE lots SET `column` = ?, `row` = ?, coordinates = ST_GeomFromGeoJSON(?) WHERE id = ?',
            [$validated['column'], $validated['row'], $validated['coordinates'], $lot->id]
        );

        $this->logActivity(
            'updated',
            $lot,
            "Updated lot {$validated['row']}-{$validated['column']}",
            $oldValues,
            ['column' => $validated['column'], 'row' => $validated['row']],
        );

        return to_route('admin.lot_management.index')->with('success', 'Lot updated successfully.');
    }

    public function deletePhase(Phase $phase)
    {
        $this->logActivity(
            'deleted',
            $phase,
            "Deleted phase {$phase->phase_name}",
        );

        $phase->delete();

        return to_route('admin.lot_management.index')
            ->with('success', 'Phase deleted successfully.');
    }

    public function deleteCluster(Cluster $cluster)
    {
        $this->logActivity(
            'deleted',
            $cluster,
            "Deleted cluster {$cluster->cluster_name}",
        );

        $cluster->delete();

        return to_route('admin.lot_management.index')
            ->with('success', 'Cluster deleted successfully.');
    }

    public function deleteLot(Lot $lot)
    {
        $this->logActivity(
            'deleted',
            $lot,
            "Deleted lot {$lot->row}-{$lot->column}",
        );

        $lot->delete();

        return to_route('admin.lot_management.index')
            ->with('success', 'Lot deleted successfully.');
    }
}
