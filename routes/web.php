<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ImportedLrhubController;

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

//     Route::get('/import-resources', [ImportController::class, 'index'])->name('import.index');
//     Route::post('/import-print-resources', [ImportController::class, 'importPrintResources'])->name('import.print-resources');

// Route::get('/import-old', function () {
//     return view('indexImport');
// });
// Route::post('/imported-schools/import', [ImportedLrhubController::class, 'importCsv'])->name('imported_schools.import');
