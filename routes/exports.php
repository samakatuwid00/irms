<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrintResourceExportController;
use App\Http\Controllers\NonPrintResourceExportController;

Route::get('/print-resources/export', [PrintResourceExportController::class, 'export'])
    ->name('print-resources.export');

Route::get('/nonprint-resources/export', [NonPrintResourceExportController::class, 'export'])
    ->name('nonprint-resources.export');
