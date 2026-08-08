<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BurialRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BurialScheduleApiController extends Controller
{
    public function index(Request $request)
    {
        $start = $request->query('start');
        $end = $request->query('end');

        return BurialRecord::with([
            'deceasedRecord:id,first_name,middle_name,last_name,date_of_depository',
            'lot:id,column,row',
        ])
            ->whereHas('deceasedRecord', fn ($q) => $q->whereNotNull('date_of_depository'))
            ->whereHas('deceasedRecord', fn ($q) => $q->whereBetween('date_of_depository', [$start, $end]))
            ->get()
            ->map(fn ($record) => [
                'id' => $record->id,
                'title' => Str::limit(
                    $record->deceasedRecord->first_name.' '.$record->deceasedRecord->last_name,
                    20,
                ),
                'start' => $record->deceasedRecord->date_of_depository,
                'extendedProps' => [
                    'deceased_name' => trim(
                        $record->deceasedRecord->first_name.' '
                        .($record->deceasedRecord->middle_name ? $record->deceasedRecord->middle_name.' ' : '')
                        .$record->deceasedRecord->last_name,
                    ),
                    'lot_info' => $record->lot
                        ? $record->lot->column.'-'.$record->lot->row
                        : 'Unassigned',
                ],
            ]);
    }
}
