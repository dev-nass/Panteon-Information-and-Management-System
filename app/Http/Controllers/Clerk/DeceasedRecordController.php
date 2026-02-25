<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeceasedRecordResource;
use App\Models\DeceasedRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeceasedRecordController extends Controller
{
    //
    public function index()
    {
        return Inertia::render('Clerk/Map/IndexView', [
            DeceasedRecordResource::collection(DeceasedRecord::latest()->paginate(10)),
        ]);
    }
}
