<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeceasedRecordResource;
use App\Models\DeceasedRecord;
use Inertia\Inertia;

class DeceasedRecordController extends Controller
{
    //
    public function index()
    {
        return Inertia::render('Clerk/DeceasedRecords/IndexView', [
            'deceased_records' => DeceasedRecordResource::collection(DeceasedRecord::latest()->paginate(10)),
        ]);
    }
}
