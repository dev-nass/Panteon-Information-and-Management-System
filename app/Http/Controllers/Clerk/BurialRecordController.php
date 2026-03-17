<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Http\Resources\BurialRecordResource;
use App\Http\Resources\DeceasedRecordResource;
use App\Models\BurialRecord;
use App\Models\DeceasedRecord;
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

    public function show(BurialRecord $burial_record)
    {
        return Inertia::render('Clerk/BurialRecords/ShowView', [
            'burial_record' => new BurialRecordResource(
                $burial_record->load(['deceasedRecord', 'lot', 'user'])
            ),
        ]);
    }
}
