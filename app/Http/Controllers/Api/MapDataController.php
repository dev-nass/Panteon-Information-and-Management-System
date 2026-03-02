<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BurialRecordResource;
use App\Models\BurialRecord;
use Illuminate\Http\Request;

class MapDataController extends Controller
{
    public function burialRrecords()
    {
        $burials = BurialRecord::with([
            'lot',
            'deceased',
            'user',
        ])->get();

        return BurialRecordResource::collection($burials);
    }
}
