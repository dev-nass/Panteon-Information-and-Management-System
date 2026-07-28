<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;

Route::prefix('settings')
    ->middleware(['auth', 'verified'])
    ->name('settings.')
    ->group(function () {

        Route::controller(ProfileController::class)->group(function () {
            Route::get('/profile', 'index')->name('profile.index');
            Route::post('/profile', 'update')->name('profile.update');
        });

        Route::controller(PasswordController::class)->group(function () {
            Route::get('/password', 'index')->name('password.index');
            Route::post('/password', 'update')->name('password.update');
        });
    });
