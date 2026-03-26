<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BurialRecordResource;
use App\Http\Resources\ClusterResource;
use App\Models\BurialRecord;
use App\Models\Cluster;
use App\Models\Lot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MapDataController extends Controller
{
    // Fetch all burial records (may be heavy with 21k+ rows)
    public function burialRecords()
    {
        $burials = BurialRecord::with([
            'lot' => function ($q) {
                $q->select('id', 'type', 'phase_id', DB::raw('ST_AsGeoJSON(coordinates) as geometry'));
            },
            'deceasedRecord:id,first_name,last_name',
            'user:id,name,email',
        ])->get();

        return BurialRecordResource::collection($burials);
    }

    // Fetch burial records within a bounding box
    public function partialBurialRecords(Request $request)
    {
        $validated = $request->validate([
            'minLat' => 'required|numeric',
            'maxLat' => 'required|numeric',
            'minLng' => 'required|numeric',
            'maxLng' => 'required|numeric',
            'limit' => 'nullable|integer|min:1|max:5000',
        ]);

        $limit = $validated['limit'] ?? 5000;

        $clusterIds = DB::table('clusters')
            ->whereRaw(
                "MBRContains(
                ST_GeomFromText(CONCAT(
                    'POLYGON((',
                    ?, ' ', ?, ',',
                    ?, ' ', ?, ',',
                    ?, ' ', ?, ',',
                    ?, ' ', ?, ',',
                    ?, ' ', ?,
                    '))'
                )),
                coordinates
            )",
                [
                    $validated['minLng'],
                    $validated['minLat'],
                    $validated['maxLng'],
                    $validated['minLat'],
                    $validated['maxLng'],
                    $validated['maxLat'],
                    $validated['minLng'],
                    $validated['maxLat'],
                    $validated['minLng'],
                    $validated['minLat'],
                ]
            )
            ->limit($limit)
            ->pluck('id');

        $clusters = DB::table('clusters')
            ->whereIn('id', $clusterIds)
            ->select('id', 'cluster_name', 'cluster_type', DB::raw('ST_AsGeoJSON(coordinates) as coordinates'))
            ->get()
            ->map(function ($cluster) {
                $clusterModel = Cluster::find($cluster->id);
                $clusterModel->coordinates = $cluster->coordinates;
                $clusterModel->load([
                    'lots' => function ($query) {
                        $query->select('id', 'cluster_id', DB::raw('`column`'), DB::raw('`row`'), DB::raw('ST_AsGeoJSON(coordinates) as coordinates'));
                    },
                    'lots.burialRecords.deceasedRecord',
                    'lots.burialRecords.user'
                ]);
                return $clusterModel;
            });

        return ClusterResource::collection($clusters);
    }


    // TODO: Create a method for fetching the Phases
    // TODO: Create a method for fetching the Clusters 
}
