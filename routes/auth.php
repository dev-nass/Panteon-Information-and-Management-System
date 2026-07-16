<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ClerkRegistrationController;
use App\Http\Controllers\Auth\PasswordResetController;

Route::controller(LoginController::class)->group(function () {
    Route::get('/login', 'create')->name('login');
    Route::post('/login', 'store')
        ->middleware('throttle:login')
        ->name('login.store');
    Route::post('/logout', 'destroy')->name('logout');
});

/**
 * Description: Clerk Registration, after invitation
 */
Route::controller(ClerkRegistrationController::class)
    ->prefix('clerk')
    ->name('clerk.')
    ->group(function () {
        Route::get('/registration/{token}', 'create')->name('register');
        Route::post('/registration', 'store')
            ->middleware('throttle:register')
            ->name('register.store');
    });

Route::controller(PasswordResetController::class)->group(function () {
    Route::get('/forgot-password', 'create')->name('password.reset');
    Route::get('/verify-reset-code', 'checkVerifyResetCode')->name('password.verify.reset.code');
    Route::get('/reset-password', 'checkResetPassword')->name('password.reset.password');
});