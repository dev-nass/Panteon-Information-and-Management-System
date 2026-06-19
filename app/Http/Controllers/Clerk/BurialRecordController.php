<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clerk\BurialRecordStoreRequest;
use App\Http\Requests\Clerk\BurialRecordUpdateRequest;
use App\Http\Resources\BurialRecordResource;
use App\Models\BurialRecord;
use App\Services\BurialRecordService;
use Inertia\Inertia;

class BurialRecordController extends Controller
{
    public function __construct(protected BurialRecordService $service)
    {
    }

    // handles tha diplay of table view, any form of filter is present or not
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
            'burial_records' => BurialRecordResource::collection(
                $burialRecords
            ),

            'filters' => request()->only(['search', 'sort_field', 'sort_direction', 'filter']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Shared/BurialRecords/CreateView', [
            'phases' => $this->service->getCreateData(),
        ]);
    }

    public function store(BurialRecordStoreRequest $request)
    {
        $burialRecord = $this->service->store(
            deceasedData: $request->deceasedData(),
            applicantData: $request->applicantData(),
            lotData: $request->lotData(),
            createdBy: auth()->id(),
        );

        return to_route('clerk.burial_records.show', $burialRecord->id)
            ->with('success', 'Burial record created successfully.');
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

    public function update(BurialRecordUpdateRequest $request, BurialRecord $burial_record)
    {
        $this->service->update($burial_record, $request->validated(), auth()->id());

        return back()->with('success', 'Burial record updated successfully.');
    }

    public function destroy(BurialRecord $burial_record)
    {
        $burial_record->delete();

        return to_route('clerk.burial_records.index')
            ->with('success', 'Burial record deleted successfully.');
    }
}
