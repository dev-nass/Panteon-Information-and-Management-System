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

        // TODO: understand this again
        $query = DeceasedRecord::query()->when($search, function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('middle_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%");
        })->latest();

        return Inertia::render('Clerk/DeceasedRecords/IndexView', [
            'deceased_records' => DeceasedRecordResource::collection(
                $query->paginate(10)->withQueryString()
            ),

            'filters' => request()->only(['search']),
        ]);
    }
}
