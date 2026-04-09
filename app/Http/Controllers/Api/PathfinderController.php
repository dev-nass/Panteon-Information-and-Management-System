<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Junction;
use App\Models\Pathway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PathfinderController extends Controller
{
    public function junctions()
    {
        $junctions = Junction::select(
            'id',
            'junction_number',
            'type',
            'label',
            DB::raw('ST_AsGeoJSON(coordinates) as coordinates')
        )->get();

        return response()->json([
            'data' => $junctions->map(function ($junction) {
                return [
                    'id' => $junction->id,
                    'junction_number' => $junction->junction_number,
                    'type' => $junction->type,
                    'label' => $junction->label,
                    'coordinates' => $junction->coordinates,
                ];
            })
        ]);
    }

    public function pathways()
    {
        $pathways = Pathway::select(
            'id',
            'from_junction_id',
            'to_junction_id',
            'distance_meters',
            DB::raw('ST_AsGeoJSON(coordinates) as coordinates')
        )->get();

        return response()->json([
            'data' => $pathways->map(function ($pathway) {
                return [
                    'id' => $pathway->id,
                    'from_junction_id' => $pathway->from_junction_id,
                    'to_junction_id' => $pathway->to_junction_id,
                    'distance_meters' => $pathway->distance_meters,
                    'coordinates' => $pathway->coordinates,
                ];
            })
        ]);
    }
}
