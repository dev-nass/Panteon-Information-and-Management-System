<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Http\Requests\BurialRecordIndexRequest;
use App\Http\Requests\Clerk\BurialRecordStoreRequest;
use App\Http\Requests\Clerk\BurialRecordUpdateRequest;
use App\Http\Resources\BurialRecordResource;
use App\Models\BurialRecord;
use App\Services\BurialRecordService;
use App\Traits\LogsActivity;
use Inertia\Inertia;

class BurialRecordController extends Controller
{
    use LogsActivity;

    public function __construct(protected BurialRecordService $service) {}

    // handles tha diplay of table view, any form of filter is present or not
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
            'burial_records' => BurialRecordResource::collection(
                $burialRecords
            ),

            'filters' => $request->only(['search', 'sort_field', 'sort_direction', 'filter', 'disposal']),
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

        $this->logActivity(
            'created',
            $burialRecord,
            "Created burial record for {$burialRecord->deceasedRecord->first_name} {$burialRecord->deceasedRecord->last_name}",
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
        $deceased = $burial_record->deceasedRecord;
        $oldValues = $deceased->only([
            'first_name',
            'middle_name',
            'last_name',
            'address',
            'date_of_birth',
            'date_of_death',
            'date_of_depository',
        ]);

        $this->service->update($burial_record, $request->validated(), auth()->id());

        $deceased->refresh();
        $newValues = $deceased->only([
            'first_name',
            'middle_name',
            'last_name',
            'address',
            'date_of_birth',
            'date_of_death',
            'date_of_depository',
        ]);

        $this->logActivity(
            'updated',
            $burial_record,
            "Updated burial record for {$newValues['first_name']} {$newValues['last_name']}",
            $oldValues,
            $newValues,
        );

        return back()->with('success', 'Burial record updated successfully.');
    }

    public function destroy(BurialRecord $burial_record)
    {
        $this->logActivity(
            'deleted',
            $burial_record,
            "Deleted burial record for {$burial_record->deceasedRecord->first_name} {$burial_record->deceasedRecord->last_name}",
        );

        $burial_record->delete();

        return to_route('clerk.burial_records.index')
            ->with('success', 'Burial record deleted successfully.');
    }
}
