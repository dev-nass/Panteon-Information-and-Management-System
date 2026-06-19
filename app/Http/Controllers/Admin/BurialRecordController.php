<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BurialRecordResource;
use App\Models\BurialRecord;
use App\Services\BurialRecordService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BurialRecordController extends Controller
{

    public function __construct(protected BurialRecordService $service)
    {
    }


    public function index()
    {
        $search = request('search');
        $filter = request('filter', 'all');

        $allowedSorts = [
            'id',
            'deceased_first_name',
            'deceased_date_of_birth',
            'deceased_date_of_death',
            'deceased_date_of_depository',
        ];
        $sortField = in_array(request('sort_field'), $allowedSorts)
            ? request('sort_field')
            : 'id';

        $sortDirection = request('sort_direction', 'desc');

        $burialRecords = $this->service->index($sortField, $sortDirection, $search, $filter);
        return Inertia::render('Shared/BurialRecords/IndexView', [
            'burial_records' => BurialRecordResource::collection($burialRecords),
            'filters' => request()->only(['search', 'sort_field', 'sort_direction', 'filter']),
        ]);
    }


    public function show(BurialRecord $burial_record)
    {
        $data = $this->service->getShowData($burial_record);

        return Inertia::render('Shared/BurialRecords/ShowView', [
            'burial_record' => new BurialRecordResource($data['burial_record']),
            'current_selection' => $data['current_selection'],
            'phases' => $data['phases'],
        ]);
    }
}
