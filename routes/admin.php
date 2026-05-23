<?php

use App\Http\Controllers\Admin\ClerkInvitationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GenerateReportController;
use App\Http\Controllers\Admin\ImportingController;
use App\Http\Controllers\Admin\InteractiveMapController;
use App\Http\Controllers\Admin\LotManagementController;


Route::prefix('admin')
    ->middleware(['auth', 'verified', 'admin'])
    ->name('admin.')
    ->group(function () {

        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::controller(GenerateReportController::class)->group(function () {
            Route::get('/generate-report', 'index')->name('generate_report.index');
            Route::get('/generate-report/download', 'generate')->name('generate_report.generate');
        });

        Route::controller(ClerkInvitationController::class)->group(function () {
            Route::get('/clerk-invitations', 'index')->name('clerk_invitations.index');
            Route::post('/clerk-invitations', 'store')->name('clerk_invitations.store');
        });

        Route::get('/map', [InteractiveMapController::class, 'index'])->name('map.index');

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


        Route::controller(ImportingController::class)->group(function () {
            Route::get('/import', 'index')->name('import.index');
            Route::post('/import', 'store')->name('import.store');
        });
    });