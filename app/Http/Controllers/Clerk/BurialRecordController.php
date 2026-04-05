<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Http\Resources\BurialRecordResource;
use App\Http\Resources\DeceasedRecordResource;
use App\Models\BurialRecord;
use App\Models\DeceasedRecord;
use App\Models\Phase;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BurialRecordController extends Controller
{
    // handles tha diplay of table view, any form of filter is present or not
    public function index()
    {

        $search = request('search');

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

        // FIXME: Asdasd
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
            ->orderBy(
                str_starts_with($sortField, 'deceased_')
                ? "deceased_records." . str_replace('deceased_', '', $sortField)
                : "burial_records.$sortField",
                $sortDirection
            );

        return Inertia::render('Clerk/BurialRecords/IndexView', [
            'burial_records' => BurialRecordResource::collection(
                $query->paginate(25)->withQueryString()
            ),

            'filters' => request()->only(['search', 'sort_field', 'sort_direction']),
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'age' => 'nullable|integer',
            'birth_date' => 'nullable|date',
            'civil_status' => 'nullable|string|max:255',
            'religion' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:255',
            'occupation_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'lgbtq' => 'nullable|string|max:255',
            'precinct_num' => 'required|integer',
            'death_date' => 'required|date',
            'death_cause' => 'nullable|string|max:255',
            'death_place' => 'nullable|string|max:255',
            'corpse_disposal' => 'nullable|string|max:255',
            'cremation_place' => 'nullable|string|max:255',
            'cremation_date' => 'nullable|date',
            'burial_place' => 'nullable|string|max:255',
            'burial_date' => 'nullable|date',
            'father_name' => 'nullable|string|max:255',
            'mother_maiden_name' => 'nullable|string|max:255',
            'company_address' => 'nullable|string|max:255',
            'company_supervisor' => 'nullable|string|max:255',
            'lot_id' => 'required|exists:lots,id',
        ]);

        $deceasedRecord = DeceasedRecord::create([
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'age' => $validated['age'] ?? null,
            'date_of_birth' => $validated['birth_date'] ?? null,
            'civil_status' => $validated['civil_status'] ?? null,
            'religion' => $validated['religion'] ?? null,
            'nationality' => $validated['nationality'] ?? null,
            'occupation' => $validated['occupation_name'] ?? null,
            'address' => $validated['address'] ?? null,
            'part_of_LGBTQ' => $validated['lgbtq'] ?? null,
            'precinct_num' => $validated['precinct_num'],
            'date_of_death' => $validated['death_date'],
            'cause_of_death' => $validated['death_cause'] ?? null,
            'place_of_death' => $validated['death_place'] ?? null,
            'corpse_disposal' => $validated['corpse_disposal'] ?? null,
            'cremation_place' => $validated['cremation_place'] ?? null,
            'cremation_date' => $validated['cremation_date'] ?? null,
            'burial_place' => $validated['burial_place'] ?? null,
            'date_of_depository' => $validated['burial_date'] ?? null,
            'father_name' => $validated['father_name'] ?? null,
            'mother_maiden_name' => $validated['mother_maiden_name'] ?? null,
            'company_address' => $validated['company_address'] ?? null,
            'company_supervisor_name' => $validated['company_supervisor'] ?? null,
        ]);

        $burialRecord = BurialRecord::create([
            'deceased_record_id' => $deceasedRecord->id,
            'lot_id' => $validated['lot_id'],
        ]);

        return to_route('clerk.burial_records.show', $burialRecord->id)
            ->with('success', 'Burial record created successfully.');
    }

    public function show(BurialRecord $burial_record)
    {
        $currentBurialRecordId = $burial_record->id;

        $phases = Phase::with(['clusters.lots.burialRecords'])->get()->map(function ($phase) use ($currentBurialRecordId) {
            return [
                'id' => $phase->id,
                'name' => $phase->phase_name,
                'clusters' => $phase->clusters->map(function ($cluster) use ($currentBurialRecordId) {
                    return [
                        'id' => $cluster->id,
                        'name' => $cluster->cluster_name,
                        'cluster_type' => $cluster->cluster_type,
                        'lots' => $cluster->lots->map(function ($lot) use ($currentBurialRecordId) {
                            $isOccupied = $lot->burialRecords->where('id', '!=', $currentBurialRecordId)->isNotEmpty();
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

        return Inertia::render('Clerk/BurialRecords/ShowView', [
            'burial_record' => new BurialRecordResource(
                $burial_record->load(['deceasedRecord', 'lot', 'user', 'lot.cluster'])
            ),
            'phases' => $phases,
        ]);
    }

    public function update(Request $request, BurialRecord $burial_record)
    {
        $validated = $request->validate([
            'deceased.first_name' => 'required|string|max:255',
            'deceased.middle_name' => 'nullable|string|max:255',
            'deceased.last_name' => 'required|string|max:255',
            'deceased.age' => 'nullable|integer',
            'deceased.birth.date' => 'nullable|date',
            'deceased.civil_status' => 'nullable|string|max:255',
            'deceased.religion' => 'nullable|string|max:255',
            'deceased.nationality' => 'nullable|string|max:255',
            'deceased.occupation.name' => 'nullable|string|max:255',
            'deceased.address' => 'nullable|string|max:255',
            'deceased.lgbtq' => 'nullable|string|max:255',
            'deceased.precinct_num' => 'nullable|integer',
            'deceased.death.date' => 'nullable|date',
            'deceased.death.cause' => 'nullable|string|max:255',
            'deceased.death.place' => 'nullable|string|max:255',
            'deceased.corpse_disposal' => 'nullable|string|max:255',
            'deceased.cremation.place' => 'nullable|string|max:255',
            'deceased.cremation.date' => 'nullable|date',
            'deceased.burial_place' => 'nullable|string|max:255',
            'deceased.burial.date' => 'nullable|date',
            'deceased.family.father' => 'nullable|string|max:255',
            'deceased.family.mother_maiden' => 'nullable|string|max:255',
            'deceased.occupation.address' => 'nullable|string|max:255',
            'deceased.occupation.supervisor' => 'nullable|string|max:255',
            'lot_id' => 'nullable|exists:lots,id',
        ]);

        $deceasedRecord = $burial_record->deceasedRecord;
        $deceasedRecord->update([
            'first_name' => $validated['deceased']['first_name'],
            'middle_name' => $validated['deceased']['middle_name'] ?? null,
            'last_name' => $validated['deceased']['last_name'],
            'age' => $validated['deceased']['age'] ?? null,
            'date_of_birth' => $validated['deceased']['birth']['date'] ?? null,
            'civil_status' => $validated['deceased']['civil_status'] ?? null,
            'religion' => $validated['deceased']['religion'] ?? null,
            'nationality' => $validated['deceased']['nationality'] ?? null,
            'occupation' => $validated['deceased']['occupation']['name'] ?? null,
            'address' => $validated['deceased']['address'] ?? null,
            'part_of_LGBTQ' => $validated['deceased']['lgbtq'] ?? null,
            'precinct_num' => $validated['deceased']['precinct_num'] ?? null,
            'date_of_death' => $validated['deceased']['death']['date'] ?? null,
            'cause_of_death' => $validated['deceased']['death']['cause'] ?? null,
            'place_of_death' => $validated['deceased']['death']['place'] ?? null,
            'corpse_disposal' => $validated['deceased']['corpse_disposal'] ?? null,
            'cremation_place' => $validated['deceased']['cremation']['place'] ?? null,
            'cremation_date' => $validated['deceased']['cremation']['date'] ?? null,
            'burial_place' => $validated['deceased']['burial_place'] ?? null,
            'date_of_depository' => $validated['deceased']['burial']['date'] ?? null,
            'father_name' => $validated['deceased']['family']['father'] ?? null,
            'mother_maiden_name' => $validated['deceased']['family']['mother_maiden'] ?? null,
            'company_address' => $validated['deceased']['occupation']['address'] ?? null,
            'company_supervisor_name' => $validated['deceased']['occupation']['supervisor'] ?? null,
        ]);

        if (isset($validated['lot_id'])) {
            $burial_record->update(['lot_id' => $validated['lot_id']]);
        }

        return back()->with('success', 'Burial record updated successfully.');
    }

    public function destroy(BurialRecord $burial_record)
    {
        $burial_record->delete();

        return to_route('clerk.burial_records.index')
            ->with('success', 'Burial record deleted successfully.');
    }
}
