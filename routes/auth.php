<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegistrationController;

Route::controller(LoginController::class)->group(function () {
    Route::get('/login', 'create')->name('login');
    Route::post('/login', 'store')
        ->middleware('throttle:login')
        ->name('login.store');
    Route::post('/logout', 'destroy')->name('logout');
});

Route::controller(RegistrationController::class)->group(function () {
    Route::get('/registration', 'create')->name('register');
    Route::post('/registration', 'store')
        ->middleware('throttle:register')
        ->name('register.store');
});