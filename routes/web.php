<?php

use App\Http\Controllers\Api\MapDataController;
use App\Http\Controllers\Clerk\BurialRecordController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('WelcomeView');
});
Route::get('/sample', function () {
    return Inertia::render('SampleView');
});

Route::controller(MapDataController::class)->group(function () {
    Route::get('/data/burials', 'burialRecords')->name('api.map.burials');
    Route::get('/data/partial-burials', 'partialBurialRecords')->name('api.map.partial.burials');
});

Route::prefix('clerk')
    ->name('clerk.')
    ->group(function () {

        Route::get('dashboard', function () {
            return Inertia::render('Clerk/DashboardView');
        })->name('dashboard');

        Route::get('/generate-report', function () {
            return Inertia::render('Clerk/GenerateReport/IndexView');
        })->name('generate_report.index');

        Route::get('/import', function () {
            return Inertia::render('Clerk/ImportRecord/IndexView');
        })->name('import.index');

        Route::get('/map', function () {
            return Inertia::render('Clerk/Map/IndexView');
        })->name('map.index');

        Route::controller(BurialRecordController::class)->group(function () {
            Route::get('/burial-records', 'index')->name('burial_records.index');
            Route::get('/burial-records/{burial_record}', 'show')->name('burial_records.show');
        });
    });
