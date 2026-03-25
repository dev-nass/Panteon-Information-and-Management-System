<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LotResource;
use App\Models\Lot;
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

        $lots = Lot::with([
            'burialRecords.deceasedRecord:id,first_name,last_name,date_of_depository,date_of_death',
            'burialRecords.user:id,name',
        ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    // Search by lot number
                    $q->where('lot_number', 'like', "%{$search}%")
                        // Search by deceased name
                        ->orWhereHas('burialRecords.deceasedRecord', function ($deceased) use ($search) {
                        $deceased->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                        // Search by phase name
                        ->orWhereHas('phase', function ($phase) use ($search) {
                        $phase->where('phase_name', 'like', "%{$search}%");
                    });
                });
            })
            ->select(
                'id',
                'lot_number',
                'lot_type',
                'phase_id',
                'status',
                'total_capacity',
                DB::raw('ST_AsGeoJSON(coordinates) as coordinates')
            )
            ->limit(10)
            ->get();

        return LotResource::collection($lots);
    }
}
