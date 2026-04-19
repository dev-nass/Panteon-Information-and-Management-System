<?php

use App\Http\Controllers\VisitorInteractiveMapController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('WelcomeView');
})->name('visitor.index');

Route::controller(VisitorInteractiveMapController::class)->group(function () {
    Route::get('/map', 'index')->name('visitor.map.index');
    Route::get('/map/search', 'search')->name('visitor.map.search');
});

require __DIR__.'/auth.php';
require __DIR__.'/api.php';
require __DIR__.'/clerk.php';
