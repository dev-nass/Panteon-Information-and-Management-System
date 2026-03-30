<?php

use App\Http\Controllers\Api\MapDataController;
use App\Http\Controllers\Api\MapSearchDataController;
use App\Http\Controllers\Clerk\BurialRecordController;
use App\Http\Controllers\Clerk\LotManagementController;
use App\Http\Controllers\Api\LotManagementController as ApiLotManagement;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('WelcomeView');
});

Route::controller(MapDataController::class)->group(function () {
    Route::get('/data/burials', 'burialRecords')->name('api.map.burials');
    Route::get('/data/phases', 'phaseRecords')->name('api.map.phases');
    Route::get('/data/partial-burials', 'partialBurialRecords')->name('api.map.partial.burials');
});

Route::controller(MapSearchDataController::class)->group(function () {
    Route::get('/data-search/burials', 'search')->name('api.map.search');
});

Route::controller(ApiLotManagement::class)->group(function () {
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
            Route::post('/burial-records/{burial_record}', 'update')->name('burial_records.update');
            Route::delete('/burial-records/{burial_record}', 'destroy')->name('burial_records.destroy');
        });


        Route::controller(LotManagementController::class)->group(function () {
            Route::get('/lot-management', 'index')->name('lot_management.index');
            Route::get('/lot-management/create', 'create')->name('lot_management.create');
            Route::post('/lot-management/phase', 'storePhase')->name('lot_management.store.phase');
            Route::post('/lot-management/cluster', 'storeCluster')->name('lot_management.store.cluster');
            Route::post('/lot-management/lot', 'storeLot')->name('lot_management.store.lot');
            Route::post('/lot-management/update', 'update')->name('lot_management.update');
            Route::delete('/lot-management/phase/{phase}', 'deletePhase')->name('lot_management.delete.phase');
            Route::delete('/lot-management/cluster/{cluster}', 'deleteCluster')->name('lot_management.delete.cluster');
            Route::delete('/lot-management/lot/{lot}', 'deleteLot')->name('lot_management.delete.lot');
            Route::get('/lot-management/{lot}', 'show')->name('lot_management.show');
        });
    });
