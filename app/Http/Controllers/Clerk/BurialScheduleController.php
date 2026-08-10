<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Models\BurialRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BurialScheduleController extends Controller
{
    public function index()
    {
        return Inertia::render('Clerk/BurialSchedules/IndexView');
    }

    public function showByDate(string $date, Request $request)
    {
        if (! Carbon::hasFormat($date, 'Y-m-d')) {
            abort(404);
        }

        $search = trim((string) $request->query('search'));

        $burials = BurialRecord::with([
            'deceasedRecord:id,first_name,middle_name,last_name,time_of_depository',
            'lot:id,column,row',
        ])
            ->whereHas('deceasedRecord', fn ($query) => $query->where('date_of_depository', $date))
            ->when($search, fn ($query, $search) => $query->whereHas(
                'deceasedRecord',
                fn ($query) => $query
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%"),
            ))
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($record) => [
                'id' => $record->id,
                'full_name' => trim(
                    $record->deceasedRecord->first_name.' '
                    .($record->deceasedRecord->middle_name ? $record->deceasedRecord->middle_name.' ' : '')
                    .$record->deceasedRecord->last_name,
                ),
                'lot' => $record->lot
                    ? $record->lot->column.'-'.$record->lot->row
                    : 'Unassigned',
                'time' => $record->deceasedRecord->time_of_depository
                    ? Carbon::parse($record->deceasedRecord->time_of_depository)->format('g:i A')
                    : 'Not set',
            ]);

        return Inertia::render('Clerk/BurialSchedules/ShowView', [
            'burials' => $burials,
            'date' => $date,
            'filters' => ['search' => $search],
        ]);
    }
}
