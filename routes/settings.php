<?php

use App\Http\Controllers\Settings\ProfileController;

Route::prefix('settings')
    ->middleware(['auth', 'verified'])
    ->name('settings.')
    ->group(function () {

        Route::controller(ProfileController::class)->group(function () {
            Route::get('/profile', 'index')->name('profile.index');
            Route::post('/profile', 'update')->name('profile.update');
        });
    });
