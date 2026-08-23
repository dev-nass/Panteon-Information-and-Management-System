<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BurialRecordIndexRequest;
use App\Http\Resources\BurialRecordResource;
use App\Models\BurialRecord;
use App\Services\BurialRecordService;
use Inertia\Inertia;

class BurialRecordController extends Controller
{
    public function __construct(protected BurialRecordService $service) {}

    public function index(BurialRecordIndexRequest $request)
    {
        $burialRecords = $this->service->index(
            $request->sortField(),
            $request->sortDirection(),
            $request->search,
            $request->filterValue(),
            $request->disposal,
        );

        return Inertia::render('Shared/BurialRecords/IndexView', [
            'burial_records' => BurialRecordResource::collection($burialRecords),
            'filters' => $request->only(['search', 'sort_field', 'sort_direction', 'filter', 'disposal']),
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
