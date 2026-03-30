<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Http\Resources\PhaseResource;
use App\Models\Cluster;
use App\Models\Lot;
use App\Models\Phase;
use Illuminate\Http\Request;
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
                        ];
                    });

                    // cluster
                    return [
                        'id' => $cluster->id,
                        'name' => $cluster->cluster_name,
                        'lots' => $lots,
                        'occupants' => $clusterOccupants,
                        'type' => $cluster->cluster_type,
                    ];
                });

                // phase
                return [
                    'id' => $phase->id,
                    'name' => $phase->phase_name,
                    'total_capacity' => $totalCapacity,
                    'occupants' => $lotOccupants,
                    'clusters' => $clusters,
                ];
            });

        return Inertia::render('Clerk/LotManagement/IndexView', [
            'phases' => $phases,
        ]);
    }

    public function create()
    {
        $phases = Phase::with('clusters')->get()->map(function ($phase) {
            return [
                'id' => $phase->id,
                'name' => $phase->phase_name,
                'clusters' => $phase->clusters->map(function ($cluster) {
                    return [
                        'id' => $cluster->id,
                        'name' => $cluster->cluster_name,
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
        ]);

        Phase::create([
            'phase_name' => $validated['name'],
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
        ]);

        Cluster::create([
            'phase_id' => $validated['phase_id'],
            'cluster_name' => $validated['name'],
            'cluster_type' => $validated['type'],
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
        ]);

        Lot::create([
            'cluster_id' => $validated['cluster_id'],
            'column' => $validated['column'],
            'row' => $validated['row'],
        ]);

        return to_route('clerk.lot_management.index')
            ->with('success', 'Lot created successfully.');
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
