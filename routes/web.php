<?php

use Illuminate\Support\Facades\Route;
use RCI\MemberRewards\Http\Controllers\DashboardController;
use RCI\MemberRewards\Http\Controllers\DirectorController;
use RCI\MemberRewards\Http\Controllers\SettingsController;

Route::middleware(['web', 'auth', 'verified'])
    ->prefix('member-rewards')
    ->name('member-rewards.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'member'])
            ->name('dashboard');

        Route::get('/director', [DirectorController::class, 'index'])
            ->name('director.index');

        Route::get('/settings', [SettingsController::class, 'index'])
            ->name('settings');
        Route::post('/settings', [SettingsController::class, 'store'])
            ->name('settings.store');
    });
