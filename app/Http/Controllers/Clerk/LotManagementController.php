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
                $occupants = 0;

                $clusters = $phase->clusters->map(function ($cluster) use (&$totalCapacity, &$occupants) {
                    $lots = $cluster->lots->map(function ($lot) use (&$totalCapacity, &$occupants) {
                        $totalCapacity++;
                        $isOccupied = $lot->burialRecords->isNotEmpty();
                        if ($isOccupied) {
                            $occupants++;
                        }

                        return [
                            'id' => $lot->id,
                            'number' => $lot->column . '-' . $lot->row,
                            'status' => $isOccupied ? 'occupied' : 'available',
                        ];
                    });

                    return [
                        'id' => $cluster->id,
                        'name' => $cluster->cluster_name,
                        'lots' => $lots,
                    ];
                });

                return [
                    'id' => $phase->id,
                    'name' => $phase->phase_name,
                    'total_capacity' => $totalCapacity,
                    'occupants' => $occupants,
                    'clusters' => $clusters,
                ];
            });

        return Inertia::render('Clerk/LotManagement/IndexView', [
            'phases' => $phases,
        ]);
    }
}
