<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeceasedRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapSearchDataController extends Controller
{
    public function search(): JsonResponse
    {
        $search = request('search');

        $deceased = DeceasedRecord::with('burialRecords.lot.section')

            ->when($search, function ($query) use ($search) {

                $query->where(function ($q) use ($search) {

                    // Search by full name (first + last)
                    $q->where('first_name', 'like', "%{$search}%")

                        // Lot number
                        ->orWhereHas('burialRecords.lot', function ($lot) use ($search) {
                            $lot->where('lot_number', 'like', "%{$search}%");
                        })
                        // Section name
                        ->orWhereHas('burialRecords.lot.section', function ($section) use ($search) {
                            $section->where('section_name', 'like', "%{$search}%");
                        });
                });
            })->limit(10) // important for suggestions
            ->get();

        return response()->json([
            'results' => $deceased,
        ]);
    }
}
