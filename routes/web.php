<?php

use App\Http\Controllers\Api\MapDataController;
use App\Http\Controllers\Api\MapSearchDataController;
use App\Http\Controllers\Api\PathfinderController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Clerk\BurialRecordController;
use App\Http\Controllers\Clerk\DashboardController;
use App\Http\Controllers\Clerk\GenerateReportController;
use App\Http\Controllers\Clerk\ImportingController;
use App\Http\Controllers\Clerk\LotManagementController;
use App\Http\Controllers\Api\LotManagementSearchController;
use App\Http\Controllers\Api\LotManagementController as ApiLotManagement;
use App\Http\Controllers\VisitorInteractiveMapController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('WelcomeView');
})->name('visitor.index');

Route::controller(LoginController::class)->group(function () {
    Route::get('/login', 'create')->name('login');
    Route::post('/login', 'store')->name('login.store');
    Route::post('/logout', 'destroy')->name('logout');
});

Route::controller(RegistrationController::class)->group(function () {
    Route::get('/registration', 'create')->name('register');
    Route::post('/registration', 'store')->name('register.store');
});

Route::controller(VisitorInteractiveMapController::class)->group(function () {
    Route::get('/map', 'index')->name('visitor.map.index');
    Route::get('/map/search', 'search')->name('visitor.map.search');
});

Route::controller(MapDataController::class)->group(function () {
    Route::get('/data/burials', 'burialRecords')->name('api.map.burials');
    Route::get('/data/phases', 'phaseRecords')->name('api.map.phases');
    Route::get('/data/partial-burials', 'partialBurialRecords')->name('api.map.partial.burials');
    Route::get('/data/cluster/{clusterId}/burials', 'clusterBurialRecords')->name('api.map.cluster.burials');
});

Route::controller(MapSearchDataController::class)->group(function () {
    Route::get('/data-search/burials', 'search')->name('api.map.search');
});

Route::controller(LotManagementSearchController::class)->group(function () {
    Route::get('/data/phase', 'phase')->name('api.lot.management.phase');
    Route::get('/data/cluster', 'cluster')->name('api.lot.management.cluster');
    Route::get('/data/lot', 'lot')->name('api.lot.management.lot');
});

Route::controller(ApiLotManagement::class)->group(function () {
    Route::get('/data/phase/{phaseId}/clusters', 'getClusters')->name('api.lot.management.clusters');
    Route::get('/data/cluster/{clusterId}/lots', 'getLots')->name('api.lot.management.lots');
});

Route::controller(PathfinderController::class)->group(function () {
    Route::get('/api/junctions', 'junctions')->name('api.junctions');
    Route::get('/api/pathways', 'pathways')->name('api.pathways');
});


Route::prefix('clerk')
    ->name('clerk.')
    ->group(function () {

        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::controller(GenerateReportController::class)->group(function () {
            Route::get('/generate-report', 'index')->name('generate_report.index');
            Route::get('/generate-report/download', 'generate')->name('generate_report.generate');
        });

        Route::controller(ImportingController::class)->group(function () {
            Route::get('/import', 'index')->name('import.index');
            Route::post('/import', 'store')->name('import.store');
        });

        Route::get('/map', function () {
            return Inertia::render('Clerk/Map/IndexView');
        })->name('map.index');

        Route::controller(BurialRecordController::class)->group(function () {
            Route::get('/burial-records', 'index')->name('burial_records.index');
            Route::get('/burial-records/create', 'create')->name('burial_records.create');
            Route::post('/burial-records', 'store')->name('burial_records.store');
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
            Route::put('/lot-management/phase/{phase}', 'updatePhase')->name('lot_management.update.phase');
            Route::put('/lot-management/cluster/{cluster}', 'updateCluster')->name('lot_management.update.cluster');
            Route::put('/lot-management/lot/{lot}', 'updateLot')->name('lot_management.update.lot');
            Route::delete('/lot-management/phase/{phase}', 'deletePhase')->name('lot_management.delete.phase');
            Route::delete('/lot-management/cluster/{cluster}', 'deleteCluster')->name('lot_management.delete.cluster');
            Route::delete('/lot-management/lot/{lot}', 'deleteLot')->name('lot_management.delete.lot');
            Route::get('/lot-management/{lot}', 'show')->name('lot_management.show');
        });
    });
