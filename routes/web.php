<?php

use Illuminate\Support\Facades\Route;
use RCI\MemberRewards\Http\Controllers\DashboardController;
use RCI\MemberRewards\Http\Controllers\DirectorController;

Route::middleware(['web', 'auth', 'verified'])
    ->prefix('member-rewards')
    ->name('member-rewards.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'member'])
            ->name('dashboard');

        Route::get('/director', [DirectorController::class, 'index'])
            ->name('director.index');
    });
