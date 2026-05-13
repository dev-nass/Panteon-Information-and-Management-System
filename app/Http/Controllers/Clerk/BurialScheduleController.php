<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use App\Models\BurialRecord;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BurialScheduleController extends Controller
{
    public function index()
    {
        $burialSchedules = BurialRecord::with([
            'deceasedRecord:id,first_name,middle_name,last_name,date_of_depository',
            'lot:id,column,row'
        ])
        ->whereHas('deceasedRecord', function ($query) {
            $query->whereNotNull('date_of_depository');
        })
        ->get()
        ->map(function ($record) {
            $fullName = $record->deceasedRecord->first_name . ' ' . $record->deceasedRecord->last_name;
            $truncatedName = strlen($fullName) > 20 ? substr($fullName, 0, 20) . '...' : $fullName;
            
            return [
                'id' => $record->id,
                'title' => $truncatedName,
                'start' => $record->deceasedRecord->date_of_depository,
                'lot' => $record->lot ? $record->lot->column . '-' . $record->lot->row : 'Unassigned',
                'extendedProps' => [
                    'deceased_name' => $record->deceasedRecord->first_name . ' ' . 
                                      ($record->deceasedRecord->middle_name ? $record->deceasedRecord->middle_name . ' ' : '') . 
                                      $record->deceasedRecord->last_name,
                    'lot_info' => $record->lot ? $record->lot->column . '-' . $record->lot->row : 'Unassigned'
                ]
            ];
        });

        return Inertia::render('Clerk/BurialSchedules/IndexView', [
            'burialSchedules' => $burialSchedules
        ]);
    }
}
