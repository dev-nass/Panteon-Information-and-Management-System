<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('WelcomeView');
});
Route::get('/sample', function () {
    return Inertia::render('SampleView');
});

Route::prefix('clerk')
    ->name('clerk.')
    ->group(function () {

        Route::get('dashboard', function () {
            return Inertia::render('Clerks/DashboardView');
        })->name('dashboard');

        Route::get('/map', function () {
            return Inertia::render('Clerks/Map/IndexView');
        })->name('map.index');
    });
