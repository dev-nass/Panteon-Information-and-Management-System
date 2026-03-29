<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Http\Resources\PhaseResource;
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
                            'number' => $lot->column . '-' . $lot->row,
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
