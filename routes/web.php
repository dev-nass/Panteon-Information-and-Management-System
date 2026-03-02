<?php

use App\Http\Controllers\Api\MapDataController;
use App\Http\Controllers\Clerk\DeceasedRecordController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('WelcomeView');
});
Route::get('/sample', function () {
    return Inertia::render('SampleView');
});

Route::controller(MapDataController::class)->group(function () {
    Route::get('/data/burials')->name('api.map.burials');
});

Route::prefix('clerk')
    ->name('clerk.')
    ->group(function () {

        Route::get('dashboard', function () {
            return Inertia::render('Clerk/DashboardView');
        })->name('dashboard');

        Route::get('/map', function () {
            return Inertia::render('Clerk/Map/IndexView');
        })->name('map.index');

        Route::controller(DeceasedRecordController::class)->group(function () {
            Route::get('/deceased-records', 'index')->name('deceased_records.index');
            Route::get('/deceased-records/{deceased_record}', 'show')->name('deceased_records.show');
        });
    });
