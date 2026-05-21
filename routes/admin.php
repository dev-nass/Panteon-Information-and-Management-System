<?php

use App\Http\Controllers\Admin\ClerkInvitationController;


// TODO: Create a separate middleware but for 'admin' similar on 'clerk' routes
Route::prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::controller(ClerkInvitationController::class)->group(function () {
            Route::get('/clerk-invitations', 'index')->name('clerk_invitations.index');
            Route::post('/clerk-invitations', 'store')->name('clerk_invitations.store');
        });

    });