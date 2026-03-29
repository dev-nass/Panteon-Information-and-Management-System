<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Http\Resources\PhaseResource;
use App\Models\Phase;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LotManagementController extends Controller
{
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
}
