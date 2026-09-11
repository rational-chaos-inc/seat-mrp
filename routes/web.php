<?php

use Illuminate\Support\Facades\Route;
use RCI\MemberRewards\Http\Controllers\DashboardController;
use RCI\MemberRewards\Http\Controllers\DirectorController;

Route::middleware(['web', 'auth', 'verified'])
    ->prefix('member-rewards')
    ->name('member-rewards.')
    ->group(function () {
        // Member dashboard routes
        Route::get('/dashboard', [DashboardController::class, 'member'])
            ->name('dashboard');

        Route::get('/character/{characterId}', [DashboardController::class, 'characterDetail'])
            ->name('character.detail');

        // Director dashboard routes
        Route::get('/director', [DirectorController::class, 'index'])
            ->name('director.index');

        Route::get('/league-tables', [DirectorController::class, 'leagueTables'])
            ->name('league-tables');

        Route::get('/member/{userId}', [DirectorController::class, 'memberDetail'])
            ->name('member.detail');
    });
