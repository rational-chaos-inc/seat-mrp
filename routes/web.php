<?php

use Illuminate\Support\Facades\Route;
use RCI\MemberRewards\Http\Controllers\DashboardController;
use RCI\MemberRewards\Http\Controllers\DirectorController;

Route::middleware(['web', 'auth'])
    ->prefix('member-rewards')
    ->name('member-rewards.')
    ->group(function () {
        // Member dashboard routes
        Route::get('/dashboard', [DashboardController::class, 'member'])
            ->name('dashboard')
            ->middleware('can:member-rewards.view_own_activities');

        Route::get('/character/{characterId}', [DashboardController::class, 'characterDetail'])
            ->name('character.detail')
            ->middleware('can:member-rewards.view_own_activities');

        // Director dashboard routes
        Route::middleware('can:member-rewards.view_all_activities')
            ->group(function () {
                Route::get('/director', [DirectorController::class, 'index'])
                    ->name('director.index');

                Route::get('/league-tables', [DirectorController::class, 'leagueTables'])
                    ->name('league-tables');

                Route::get('/member/{userId}', [DirectorController::class, 'memberDetail'])
                    ->name('member.detail');
            });
    });
