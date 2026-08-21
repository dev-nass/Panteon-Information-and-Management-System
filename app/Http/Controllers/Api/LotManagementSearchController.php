<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClusterResource;
use App\Http\Resources\LotResource;
use App\Http\Resources\PhaseResource;
use App\Models\Cluster;
use App\Models\Lot;
use App\Models\Phase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LotManagementSearchController extends Controller
{
    /**
     * Description: Fetch phase by phase_id
     */
    public function phase(Request $request)
    {
        $validated = $request->validate([
            'phase_id' => 'required|integer',
        ]);

        $phase = DB::table('phases')
            ->where('id', $validated['phase_id'])
            ->select('id', 'phase_name', DB::raw('ST_AsGeoJSON(coordinates) as coordinates'))
            ->first();

        if (! $phase) {
            return PhaseResource::collection([]);
        }

        $phaseModel = Phase::find($phase->id);
        $phaseModel->coordinates = $phase->coordinates;

        return PhaseResource::collection([$phaseModel]);
    }

    /**
     * Description: Fetch cluster by cluster_id
     */
    public function cluster(Request $request)
    {
        $validated = $request->validate([
            'cluster_id' => 'required|integer',
        ]);

        $cluster = DB::table('clusters')
            ->where('id', $validated['cluster_id'])
            ->select('id', 'cluster_name', 'cluster_type', DB::raw('ST_AsGeoJSON(coordinates) as coordinates'))
            ->first();

        if (! $cluster) {
            return ClusterResource::collection([]);
        }

        $clusterModel = Cluster::find($cluster->id);
        $clusterModel->coordinates = $cluster->coordinates;
        $clusterModel->load([
            'lots' => function ($query) {
                $query->select('id', 'cluster_id', DB::raw('`column`'), DB::raw('`row`'), DB::raw('ST_AsGeoJSON(coordinates) as coordinates'));
            },
            'lots.burialRecords.deceasedRecord',
            'lots.burialRecords.user',
        ]);

        return ClusterResource::collection([$clusterModel]);
    }

    /**
     * Description: Fetch lot by lot_id
     */
    public function lot(Request $request)
    {
        $validated = $request->validate([
            'lot_id' => 'required|integer',
        ]);

        $lot = DB::table('lots')
            ->where('id', $validated['lot_id'])
            ->select('id', 'cluster_id', DB::raw('`column`'), DB::raw('`row`'), DB::raw('ST_AsGeoJSON(coordinates) as coordinates'))
            ->first();

        if (! $lot) {
            return LotResource::collection([]);
        }

        $lotModel = Lot::find($lot->id);
        $lotModel->coordinates = $lot->coordinates;
        $lotModel->load([
            'burialRecords.deceasedRecord',
            'burialRecords.user',
        ]);

        return LotResource::collection([$lotModel]);
    }
}
