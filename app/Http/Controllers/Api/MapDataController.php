<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BurialRecordResource;
use App\Http\Resources\LotResource;
use App\Models\BurialRecord;
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
                $q->select('id', 'lot_type', 'phase_id', DB::raw('ST_AsGeoJSON(coordinates) as geometry'));
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
            'limit' => 'nullable|integer|min:1|max:1000',
        ]);

        $limit = $validated['limit'] ?? 100;

        $clusters = DB::table('clusters')
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
            ->pluck('id');

        $burialRecords = BurialRecord::whereHas('lot', function ($query) use ($clusters) {
            $query->whereIn('cluster_id', $clusters);
        })
            ->with([
                'lot' => function ($query) {
                    $query->select('id', 'cluster_id', DB::raw('`column`'), DB::raw('`row`'), DB::raw('ST_AsGeoJSON(coordinates) as coordinates'));
                },
                'lot.cluster' => function ($query) {
                    $query->select('id', 'cluster_name', 'cluster_type', DB::raw('ST_AsGeoJSON(coordinates) as coordinates'));
                },
                'deceasedRecord',
                'user'
            ])
            ->limit($limit)
            ->get();

        return BurialRecordResource::collection($burialRecords);
    }


    // TODO: Create a method for fetching the Phases
    // TODO: Create a method for fetching the Clusters 
}
