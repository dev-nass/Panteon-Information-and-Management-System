<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeceasedRecordResource;
use App\Models\DeceasedRecord;
use Inertia\Inertia;

class DeceasedRecordController extends Controller
{
    // handles tha diplay of table view, any form of filter is present or not
    public function index()
    {

        $search = request('search');
        $sortField = request('sort_field', 'id');
        $sortDirection = request('sort_direction', 'desc');

        // todo: understand this again
        $query = deceasedrecord::query()->when($search, function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orwhere('middle_name', 'like', "%{$search}%")
              ->orwhere('last_name', 'like', "%{$search}%");
        })->orderby($sortField, $sortDirection);

        return inertia::render('Clerk/DeceasedRecords/IndexView', [
            'deceased_records' => deceasedrecordresource::collection(
                $query->paginate(25)->withquerystring()
            ),

            'filters' => request()->only(['search', 'sort_field', 'sort_direction']),
        ]);
    }

    public function show(DeceasedRecord $deceased_record)
    {
        return Inertia::render('Clerk/DeceasedRecords/ShowView', [
            'deceased' => $deceased_record,
        ]);
    }
}
