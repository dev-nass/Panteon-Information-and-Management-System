<?php

namespace App\Http\Controllers;

use App\Models\BurialRecord;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VisitorInteractiveMapController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');

        $burialRecords = BurialRecord::with('deceasedRecord')
            ->when($search, function ($query, $search) {
                $query->whereHas('deceasedRecord', function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->limit(10)
            ->get();

        return Inertia::render('VisitorInteractiveMapView', [
            'burial_records' => $burialRecords,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }
}
