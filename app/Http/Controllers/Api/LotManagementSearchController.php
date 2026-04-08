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

class LotManagementSearchController extends Controller
{

    /**
     * Description: Fetch phase by phase_id
     */
    public function phase()
    {
        $phaseId = request('phase_id');

        if (!$phaseId) {
            return PhaseResource::collection([]);
        }

        $phase = DB::table('phases')
            ->where('id', $phaseId)
            ->select('id', 'phase_name', DB::raw('ST_AsGeoJSON(coordinates) as coordinates'))
            ->first();

        if (!$phase) {
            return PhaseResource::collection([]);
        }

        $phaseModel = Phase::find($phase->id);
        $phaseModel->coordinates = $phase->coordinates;

        return PhaseResource::collection([$phaseModel]);
    }

    /**
     * Description: Fetch cluster by cluster_id
     */
    public function cluster()
    {
        $clusterId = request('cluster_id');

        if (!$clusterId) {
            return ClusterResource::collection([]);
        }

        $cluster = DB::table('clusters')
            ->where('id', $clusterId)
            ->select('id', 'cluster_name', 'cluster_type', DB::raw('ST_AsGeoJSON(coordinates) as coordinates'))
            ->first();

        if (!$cluster) {
            return ClusterResource::collection([]);
        }

        $clusterModel = Cluster::find($cluster->id);
        $clusterModel->coordinates = $cluster->coordinates;
        $clusterModel->load([
            'lots' => function ($query) {
                $query->select('id', 'cluster_id', DB::raw('`column`'), DB::raw('`row`'), DB::raw('ST_AsGeoJSON(coordinates) as coordinates'));
            },
            'lots.burialRecords.deceasedRecord',
            'lots.burialRecords.user'
        ]);

        return ClusterResource::collection([$clusterModel]);
    }

    /**
     * Description: Fetch lot by lot_id
     */
    public function lot()
    {
        $lotId = request('lot_id');

        if (!$lotId) {
            return LotResource::collection([]);
        }

        $lot = DB::table('lots')
            ->where('id', $lotId)
            ->select('id', 'cluster_id', DB::raw('`column`'), DB::raw('`row`'), DB::raw('ST_AsGeoJSON(coordinates) as coordinates'))
            ->first();

        if (!$lot) {
            return LotResource::collection([]);
        }

        $lotModel = Lot::find($lot->id);
        $lotModel->coordinates = $lot->coordinates;
        $lotModel->load([
            'burialRecords.deceasedRecord',
            'burialRecords.user'
        ]);

        return LotResource::collection([$lotModel]);
    }
}
