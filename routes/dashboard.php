<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

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
});