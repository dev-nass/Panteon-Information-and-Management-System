<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BurialRecordResource;
use App\Models\BurialRecord;
use Illuminate\Http\Request;

class MapDataController extends Controller
{
    // throws 21k data instantly
    public function burialRecords()
    {
        $burials = BurialRecord::with(['lot', 'deceasedRecord', 'user'])->get();

        return BurialRecordResource::collection($burials);
    }


    public function partialBurialRecords(Request $request)
    {
        $request->validate([
            'minLat' => 'required|numeric',
            'maxLat' => 'required|numeric',
            'minLng' => 'required|numeric',
            'maxLng' => 'required|numeric',
        ]);

        $records = BurialRecord::whereHas('lot', function ($query) use ($request) {
            $query->whereRaw("
            ST_Within(
                ST_GeomFromGeoJSON(coordinates),
                ST_GeomFromText(CONCAT(
                    'POLYGON((',
                    ?, ' ', ?, ',',
                    ?, ' ', ?, ',',
                    ?, ' ', ?, ',',
                    ?, ' ', ?, ',',
                    ?, ' ', ?,
                    '))'
                ), 4326)
            )
        ", [
                $request->minLng, $request->minLat,
                $request->maxLng, $request->minLat,
                $request->maxLng, $request->maxLat,
                $request->minLng, $request->maxLat,
                $request->minLng, $request->minLat,
            ]);
        })
            ->with(['lot', 'deceasedRecord', 'user'])
            ->get();

        return BurialRecordResource::collection($records);
    }
}
