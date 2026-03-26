<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClusterResource;
use App\Models\Cluster;
use Illuminate\Support\Facades\DB;

class MapSearchDataController extends Controller
{

    /**
     * TODO: Create a two function for search, one for user side and the this existing is for clerk
     * TODO: Remember to also fetch the pathways and junctions later on
     */
    public function search()
    {
        $search = request('search');
        $burialId = request('burial_id');

        // If burial_id is provided, fetch specific cluster for that burial
        if ($burialId) {
            $lotId = DB::table('burial_records')
                ->where('id', $burialId)
                ->value('lot_id');

            if (!$lotId) {
                return ClusterResource::collection([]);
            }

            $clusterId = DB::table('lots')
                ->where('id', $lotId)
                ->value('cluster_id');

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
                'lots' => function ($query) use ($lotId) {
                    $query->select('id', 'cluster_id', DB::raw('`column`'), DB::raw('`row`'), DB::raw('ST_AsGeoJSON(coordinates) as coordinates'))
                        ->where('id', $lotId);
                },
                'lots.burialRecords.deceasedRecord',
                'lots.burialRecords.user'
            ]);

            return ClusterResource::collection([$clusterModel]);
        }

        // Otherwise, search by deceased name
        $lotIds = DB::table('burial_records')
            ->join('deceased_records', 'burial_records.deceased_record_id', '=', 'deceased_records.id')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('deceased_records.first_name', 'like', "%{$search}%")
                        ->orWhere('deceased_records.last_name', 'like', "%{$search}%");
                });
            })
            ->distinct()
            ->limit(10)
            ->pluck('burial_records.lot_id');

        $clusterIds = DB::table('lots')
            ->whereIn('id', $lotIds)
            ->distinct()
            ->pluck('cluster_id');

        $clusters = DB::table('clusters')
            ->whereIn('id', $clusterIds)
            ->select('id', 'cluster_name', 'cluster_type', DB::raw('ST_AsGeoJSON(coordinates) as coordinates'))
            ->get()
            ->map(function ($cluster) use ($lotIds) {
                $clusterModel = Cluster::find($cluster->id);
                $clusterModel->coordinates = $cluster->coordinates;
                $clusterModel->load([
                    'lots' => function ($query) use ($lotIds) {
                        $query->select('id', 'cluster_id', DB::raw('`column`'), DB::raw('`row`'), DB::raw('ST_AsGeoJSON(coordinates) as coordinates'))
                            ->whereIn('id', $lotIds);
                    },
                    'lots.burialRecords.deceasedRecord',
                    'lots.burialRecords.user'
                ]);
                return $clusterModel;
            });

        return ClusterResource::collection($clusters);
    }
}
