<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BurialRecordResource;
use App\Http\Resources\ClusterResource;
use App\Http\Resources\PhaseResource;
use App\Models\BurialRecord;
use App\Models\Cluster;
use App\Models\Lot;
use App\Models\Phase;
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

    /**
     * Description: Fetch all Phase Records within the database
     */
    public function phaseRecords()
    {
        $phases = Phase::select(
            'id',
            // 'phase_code',
            'phase_name',
            // 'description',
            // 'status',
            // 'total_capacity',
            DB::raw('ST_AsGeoJSON(coordinates) as coordinates')
        )->get();

        return PhaseResource::collection($phases);
    }

    /**
     *  Description: Fetch clusters within the specific bound
     *               The ClusterResource then retrieve the
     *               cluster -> lot -> burial_record -> deceased record
     */
    // TODO: How did the Ai manage to fix the n+1 issue here 
    public function partialBurialRecords(Request $request)
    {
        $validated = $request->validate([
            'minLat' => 'required|numeric',
            'maxLat' => 'required|numeric',
            'minLng' => 'required|numeric',
            'maxLng' => 'required|numeric',
            'limit' => 'nullable|integer|min:1|max:5000',
        ]);

        $limit = $validated['limit'] ?? 1000;

        // Only fetch clusters without lots/burial records but with counts
        $clusters = Cluster::select('id', 'phase_id', 'cluster_name', 'cluster_type', DB::raw('ST_AsGeoJSON(coordinates) as coordinates'))
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
            ->with('phase:id,phase_name')
            ->withCount([
                'lots as total_lots',
                'lots as occupied_lots' => function ($query) {
                    $query->whereExists(function ($subQuery) {
                        $subQuery->select(DB::raw(1))
                            ->from('burial_records')
                            ->whereColumn('burial_records.lot_id', 'lots.id');
                    });
                }
            ])
            ->limit($limit)
            ->get();

        return ClusterResource::collection($clusters);
    }

    /**
     * Fetch lots and burial records for a specific cluster
     */
    public function clusterBurialRecords(Request $request, $clusterId)
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:5000',
        ]);

        $limit = $validated['limit'] ?? 1000;

        // Get lot IDs with burial records within the limit
        $lotIds = DB::table('lots')
            ->where('cluster_id', $clusterId)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('burial_records')
                    ->whereColumn('burial_records.lot_id', 'lots.id');
            })
            ->limit($limit)
            ->pluck('id');

        $cluster = Cluster::where('id', $clusterId)
            ->select('id', 'phase_id', 'cluster_name', 'cluster_type', DB::raw('ST_AsGeoJSON(coordinates) as coordinates'))
            ->with([
                'phase:id,phase_name',
                'lots' => function ($query) use ($lotIds) {
                    $query->select('id', 'cluster_id', 'column', 'row', DB::raw('ST_AsGeoJSON(coordinates) as coordinates'))
                        ->whereIn('id', $lotIds);
                },
                'lots.burialRecords' => function ($query) {
                    $query->select('id', 'deceased_record_id', 'lot_id', 'user_id');
                },
                'lots.burialRecords.deceasedRecord:id,first_name,middle_name,last_name,date_of_birth,date_of_death',
                'lots.burialRecords.user:id,first_name,last_name',
            ])
            ->first();

        if (!$cluster) {
            return response()->json(['message' => 'Cluster not found'], 404);
        }

        return new ClusterResource($cluster);
    }


    // TODO: Create a method for fetching the Phases
    // TODO: Create a method for fetching the Clusters
}
