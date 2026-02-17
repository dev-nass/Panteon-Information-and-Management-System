<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('WelcomeView');
});
Route::get('/sample', function () {
    return Inertia::render('SampleView');
});
Route::get('/clerk-dashboard', function () {
    return Inertia::render('Clerks/DashboardView');
});
