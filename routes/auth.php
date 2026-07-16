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

Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [PasswordResetController::class, 'create'])
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    Route::get('/verify-reset-code', [PasswordResetController::class, 'checkVerifyResetCode'])
        ->name('password.verify.show');
    Route::post('/verify-reset-code', [PasswordResetController::class, 'verifyCode'])
        ->middleware('throttle:10,1')
        ->name('password.verify');

    Route::get('/reset-password', [PasswordResetController::class, 'checkResetPassword'])
        ->name('password.reset.show');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])
        ->name('password.update');
});