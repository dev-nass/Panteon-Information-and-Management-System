<?php

use App\Http\Controllers\Clerk\BurialRecordController;
use App\Http\Controllers\Clerk\BurialScheduleController;
use App\Http\Controllers\Clerk\DashboardController;
use App\Http\Controllers\Clerk\LotManagementController;
use Inertia\Inertia;

Route::prefix('clerk')
    ->middleware(['auth', 'verified', 'clerk'])
    ->name('clerk.')
    ->group(function () {

        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/map', function () {
            return Inertia::render('Shared/Map/IndexView');
        })->name('map.index');

        Route::controller(BurialScheduleController::class)->group(function () {
            Route::get('/burial-schedules', 'index')->name('burial_schedules.index');
        });

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
            Route::get('/lot-management/{lot}', 'show')->name('lot_management.show');
        });
    });
