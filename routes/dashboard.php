<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Resource\ManageEstimatedResourceCountController;

// Protected routes [NAVIGATIONS]
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Existing LR Availability chart endpoint
    Route::get('/chart/lr-availability', [DashboardController::class, 'getLrAvailabilityData'])
        ->name('chart.lr-availability');

    // New LR Ratio chart endpoint
    Route::get('/chart/lr-ratio', [DashboardController::class, 'getLrRatioData'])
        ->name('chart.lr-ratio');

    // New Exdef chart
    Route::get('/chart/exdef', [DashboardController::class, 'getLrSufficiencyData'])
        ->name('chart.exdef');

    // New Heatmap chart
    Route::get('/chart/heatmap', [DashboardController::class, 'getLrHeatmapData'])
        ->name('chart.heatmap');

    // New Heatmap chart
    Route::get('/chart/heatmap', [DashboardController::class, 'getLrHeatmapData'])
        ->name('chart.heatmap');

    Route::get('/dashboard/bosy-status', [DashboardController::class, 'getBosyStatusData'])
        ->name('dashboard.bosy-status');

    Route::patch('/dashboard/school/{schoolId}/pre-inventory', [ManageEstimatedResourceCountController::class, 'updateSchoolPreInventory'])
        ->name('dashboard.school.pre-inventory.update');

    Route::get('/dashboard/bosy-settings', [DashboardController::class, 'getBosySettings'])
        ->name('dashboard.bosy-settings.get');
 
    // POST — only Regional Accounts (level ≥ 4); controller enforces this
    Route::post('/dashboard/bosy-settings', [DashboardController::class, 'updateBosySettings'])
        ->name('dashboard.bosy-settings.update');
 
});