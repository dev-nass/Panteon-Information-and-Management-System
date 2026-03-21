<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BurialRecordResource;
use App\Models\BurialRecord;
use App\Models\DeceasedRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapSearchDataController extends Controller
{
    public function search()
    {
        $search = request('search');

        $burial = BurialRecord::with([
            'lot:id,lot_number,lot_type,status,section_id',
            'lot.section:id,section_name',
            'deceasedRecord:id,first_name,last_name',
        ])
            ->when($search, function ($query) use ($search) {

                $query->where(function ($q) use ($search) {

                    // Search by deceased name
                    $q->whereHas('deceasedRecord', function ($deceased) use ($search) {
                        $deceased->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                        // Lot number
                        ->orWhereHas('lot', function ($lot) use ($search) {
                        $lot->where('lot_number', 'like', "%{$search}%");
                    })
                        // Section name
                        ->orWhereHas('lot.section', function ($section) use ($search) {
                        $section->where('section_name', 'like', "%{$search}%");
                    });
                });
            })->limit(10) // important for suggestions
            ->get();

        return BurialRecordResource::collection($burial);
    }
}
