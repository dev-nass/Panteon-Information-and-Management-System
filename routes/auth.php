<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ClerkRegistrationController;

Route::controller(LoginController::class)->group(function () {
    Route::get('/login', 'create')->name('login');
    Route::post('/login', 'store')
        ->middleware('throttle:login')
        ->name('login.store');
    Route::post('/logout', 'destroy')->name('logout');
});

Route::controller(ClerkRegistrationController::class)->group(function () {
    Route::get('/registration/{token}', 'create')->name('register');
    Route::post('/registration', 'store')
        ->middleware('throttle:register')
        ->name('register.store');
});