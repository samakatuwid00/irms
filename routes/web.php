<?php

use Illuminate\Support\Facades\Route;

// AUTHENTICATION ROUTES
require __DIR__.'/auth.php';

// DASHBOARD ROUTES
require __DIR__.'/dashboard.php';

// RESOURCE ROUTES
require __DIR__.'/resources.php';

//MANAGE EXPORT PRINT RESOURCE
require __DIR__.'/exports.php';

// STATION ROUTES
require __DIR__.'/stations.php';

// MANAGE USER ROUTES
require __DIR__.'/user.php';


// use App\Http\Controllers\ImportPrintResourceController;

/*
|--------------------------------------------------------------------------
| Import Print Resource Routes
|--------------------------------------------------------------------------
|
| GET  /import-print-resource           → show the CSV upload form
| POST /import-print-resource           → process and import the CSV
| GET  /import-print-resource/template  → download the blank CSV template
|
*/

    // Route::get(
    //     '/import-print-resource',
    //     [ImportPrintResourceController::class, 'showImportForm']
    // )->name('print-resource.import.form');

    // Route::post(
    //     '/import-print-resource',
    //     [ImportPrintResourceController::class, 'import']
    // )->name('print-resource.import');

    // Route::get(
    //     '/import-print-resource/template',
    //     [ImportPrintResourceController::class, 'template']
    // )->name('print-resource.import.template');

