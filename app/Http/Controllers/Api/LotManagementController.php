<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClusterResource;
use App\Http\Resources\PhaseResource;
use App\Http\Resources\LotResource;
use App\Models\Cluster;
use App\Models\Phase;
use App\Models\Lot;
use Illuminate\Support\Facades\DB;

class LotManagementController extends Controller
{
    /**
     * Get clusters by phase ID
     */
    public function getClusters($phaseId)
    {
        $clusters = Cluster::select('id', 'phase_id', 'cluster_name', 'cluster_type', 'total_capacity', DB::raw('ST_AsGeoJSON(coordinates) as coordinates'))
            ->where('phase_id', $phaseId)
            ->withCount('lots')
            ->withCount([
                'lots as occupied_lots' => function ($query) {
                    $query->whereHas('burialRecords');
                }
            ])
            ->get()
            ->map(function ($cluster) {
                return [
                    'id' => $cluster->id,
                    'phase_id' => $cluster->phase_id,
                    'name' => $cluster->cluster_name,
                    'type' => $cluster->cluster_type,
                    'occupants' => $cluster->occupied_lots, // asscoaited burial recod
                    'total_lots' => $cluster->lots_count, // num of lots
                    'total_capacity' => $cluster->total_capacity, // max num of lots
                    'coordinates' => $cluster->coordinates,
                    'isCluster_mapped' => !is_null($cluster->coordinates),
                ];
            });

        return response()->json($clusters);
    }

    /**
     * Get lots by cluster ID
     */
    public function getLots($clusterId)
    {
        $lots = Lot::select('id', 'cluster_id', 'column', 'row', DB::raw('ST_AsGeoJSON(coordinates) as coordinates'))
            ->where('cluster_id', $clusterId)
            ->with('burialRecords:id,lot_id')
            ->get()
            ->map(function ($lot) {
                $isOccupied = $lot->burialRecords->isNotEmpty();

                return [
                    'id' => $lot->id,
                    'cluster_id' => $lot->cluster_id,
                    'column' => $lot->column,
                    'row' => $lot->row,
                    'status' => $isOccupied ? 'occupied' : 'available',
                    'coordinates' => $lot->coordinates,
                    'isLot_mapped' => !is_null($lot->coordinates),
                ];
            });

        return response()->json($lots);
    }
}
