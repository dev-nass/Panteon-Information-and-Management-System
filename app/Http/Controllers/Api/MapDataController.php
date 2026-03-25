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
        $request->validate([
            'minLat' => 'required|numeric',
            'maxLat' => 'required|numeric',
            'minLng' => 'required|numeric',
            'maxLng' => 'required|numeric',
        ]);

        $limit = $validated['limit'] ?? 100;

        $minLng = $request->minLng;
        $minLat = $request->minLat;
        $maxLng = $request->maxLng;
        $maxLat = $request->maxLat;

        $lots = Lot::whereRaw(
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
                $minLng,
                $minLat,
                $maxLng,
                $minLat,
                $maxLng,
                $maxLat,
                $minLng,
                $maxLat,
                $minLng,
                $minLat,
            ]
        )
            ->with([
                'cluster.phase',  // Load cluster and phase
                'burialRecords.deceasedRecord:id,first_name,last_name,date_of_depository,deceased_date',
                'burialRecords.user:id,name',
            ])
            ->select(
                'id',
                'cluster_id',
                DB::raw('`column`'),
                DB::raw('`row`'),
                DB::raw('ST_AsGeoJSON(coordinates) as coordinates')  // ✅ This converts binary to JSON
            )
            ->limit($limit)
            ->get();

        return LotResource::collection($lots);
    }


    // TODO: Create a method for fetching the Phases
    // TODO: Create a method for fetching the Clusters 
}
