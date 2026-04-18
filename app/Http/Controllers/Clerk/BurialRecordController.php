<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clerk\BurialRecordStoreRequest;
use App\Http\Requests\Clerk\BurialRecordUpdateRequest;
use App\Http\Resources\BurialRecordResource;
use App\Models\BurialRecord;
use App\Models\Phase;
use App\Services\BurialRecordService;
use Inertia\Inertia;

class BurialRecordController extends Controller
{
    public function __construct(protected BurialRecordService $service) {}

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

        // TODO: understand this again
        $query = BurialRecord::query()
            ->with(['deceasedRecord', 'lot', 'user'])
            ->leftJoin('deceased_records', 'burial_records.deceased_record_id', '=', 'deceased_records.id')
            ->select('burial_records.*')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('deceased_records.first_name', 'like', "%{$search}%")
                        ->orWhere('deceased_records.middle_name', 'like', "%{$search}%")
                        ->orWhere('deceased_records.last_name', 'like', "%{$search}%");
                });
            })
            ->when($filter === 'buried', function ($q) {
                $q->whereNotNull('deceased_records.date_of_depository');
            })
            ->when($filter === 'pending', function ($q) {
                $q->whereNull('deceased_records.date_of_depository');
            })
            ->orderBy(
                str_starts_with($sortField, 'deceased_')
                ? 'deceased_records.'.str_replace('deceased_', '', $sortField)
                : "burial_records.$sortField",
                $sortDirection
            );

        return Inertia::render('Clerk/BurialRecords/IndexView', [
            'burial_records' => BurialRecordResource::collection(
                $query->paginate(25)->withQueryString()
            ),

            'filters' => request()->only(['search', 'sort_field', 'sort_direction', 'filter']),
        ]);
    }

    public function create()
    {
        $phases = Phase::with(['clusters.lots.burialRecords'])->get()->map(function ($phase) {
            return [
                'id' => $phase->id,
                'name' => $phase->phase_name,
                'clusters' => $phase->clusters->map(function ($cluster) {
                    return [
                        'id' => $cluster->id,
                        'name' => $cluster->cluster_name,
                        'cluster_type' => $cluster->cluster_type,
                        'lots' => $cluster->lots->map(function ($lot) {
                            $isOccupied = $lot->burialRecords->isNotEmpty();

                            return [
                                'id' => $lot->id,
                                'column' => $lot->column,
                                'row' => $lot->row,
                                'is_occupied' => $isOccupied,
                            ];
                        }),
                    ];
                }),
            ];
        });

        return Inertia::render('Clerk/BurialRecords/CreateView', [
            'phases' => $phases,
        ]);
    }

    public function store(BurialRecordStoreRequest $request)
    {
        $burialRecord = $this->service->store(
            deceasedData: $request->deceasedData(),
            applicantData: $request->applicantData(),
            createdBy: auth()->id(),
        );

        return to_route('clerk.burial_records.show', $burialRecord->id)
            ->with('success', 'Burial record created successfully.');
    }

    public function show(BurialRecord $burial_record)
    {
        $data = $this->service->getShowData($burial_record);

        return Inertia::render('Clerk/BurialRecords/ShowView', [
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
