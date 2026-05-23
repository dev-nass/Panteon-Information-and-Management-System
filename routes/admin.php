<?php

use App\Http\Controllers\Admin\ClerkInvitationController;
use App\Http\Controllers\Admin\DashboardController;


// TODO: Create a separate middleware but for 'admin' similar on 'clerk' routes
Route::prefix('admin')
    ->middleware(['auth', 'verified'])
    ->name('admin.')
    ->group(function () {

        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::controller(ClerkInvitationController::class)->group(function () {
            Route::get('/clerk-invitations', 'index')->name('clerk_invitations.index');
            Route::post('/clerk-invitations', 'store')->name('clerk_invitations.store');
        });

    });