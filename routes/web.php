<?php

use App\Http\Controllers\Api\MapDataController;
use App\Http\Controllers\Api\MapSearchDataController;
use App\Http\Controllers\Clerk\BurialRecordController;
use App\Http\Controllers\Clerk\LotManagementController;
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
    Route::get('/data/phases', 'phaseRecords')->name('api.map.phases');
    Route::get('/data/partial-burials', 'partialBurialRecords')->name('api.map.partial.burials');
});

Route::controller(MapSearchDataController::class)->group(function () {
    Route::get('/data-search/burials', 'search')->name('api.map.search');
});

Route::controller(LotManagementController::class)->group(function () {
    Route::get('/data/phase', 'phase')->name('api.lot.management.phase');
    Route::get('/data/cluster', 'cluster')->name('api.lot.management.cluster');
    Route::get('/data/lot', 'lot')->name('api.lot.management.lot');
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


        Route::controller(LotManagementController::class)->group(function () {
            Route::get('/lot-management', 'index')->name('lot_management.index');
        });
    });
