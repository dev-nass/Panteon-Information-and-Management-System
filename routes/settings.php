<?php

use App\Http\Controllers\Settings\ProfileController;

Route::controller(ProfileController::class)->group(function () {
    Route::get('/settings', 'index')->name('settings.index');
});