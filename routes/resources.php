<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Resource\AddResourceController;
use App\Http\Controllers\Resource\EditResourceController;
use App\Http\Controllers\Resource\PrintResourceController;
use App\Http\Controllers\Resource\NonPrintResourceController;
use App\Http\Controllers\Resource\ManageEstimatedResourceCountController;
use App\Http\Controllers\Resource\SearchPrintResourceController;
use App\Http\Controllers\Resource\AddPrintResourceController;
use App\Http\Controllers\Resource\AddNonPrintResourceController;

// ============================================================
// RESOURCE NAVIGATION
// ============================================================
Route::get('/print-resources', [PrintResourceController::class, 'index'])->name('print-resources');
Route::get('/nonprint-resources', [NonPrintResourceController::class, 'index'])->name('nonprint-resources');

// ============================================================
// ADD PRINT RESOURCE — School request flow (3-tab page)
// ============================================================
Route::get('/add-print-resource', [AddPrintResourceController::class, 'index'])
    ->name('print-resource.create');
Route::post('/add-print-resource', [AddPrintResourceController::class, 'store'])
    ->name('print-resource.store');
Route::get('/add-print-resource/{id}/edit', [AddPrintResourceController::class, 'edit'])
    ->name('print-resource.edit');
Route::put('/add-print-resource/{id}', [AddPrintResourceController::class, 'update'])
    ->name('print-resource.update');
Route::delete('/add-print-resource/{id}', [AddPrintResourceController::class, 'destroy'])
    ->name('print-resource.destroy');

// ============================================================
// ADD NON-PRINT RESOURCE (new dedicated page)
// ============================================================
Route::get('/add-nonprint-resource', [AddNonPrintResourceController::class, 'index'])
    ->name('nonprint-resource.create');
Route::post('/add-nonprint-resource', [AddNonPrintResourceController::class, 'store'])
    ->name('nonprint-resource.store');

// ============================================================
// SEARCH PRINT RESOURCE
// ============================================================


// 2. AJAX: search titles
Route::get('/search-print/query', [SearchPrintResourceController::class, 'search'])
    ->name('search-print-resource.search');

// 3. AJAX: get full details of a single PrintResource
Route::get('/search-print/{id}/details', [SearchPrintResourceController::class, 'show'])
    ->name('search-print-resource.show');

// 4. Add-acquisition form (pre-filled, read-only resource details — standalone page)
Route::get('/search-print/{id}/add', [SearchPrintResourceController::class, 'addForm'])
    ->name('search-print-resource.add-form');

// 5. Store acquisitions (standalone add-acquisition page)
Route::post('/search-print/{id}/add', [SearchPrintResourceController::class, 'store'])
    ->name('search-print-resource.store');

// 6. Store acquisitions (inline — from Search tab inside add-print-resource page)
Route::post('/search-print/{id}/add-inline', [SearchPrintResourceController::class, 'store'])
    ->name('search-print-resource.store-inline');

// ============================================================
// MANAGE RESOURCE
// ============================================================
Route::post('/add-print-resources', [AddPrintResourceController::class, 'addPrintResource'])->name('add-print-resource');
Route::post('/add-nonprint-resources', [AddNonPrintResourceController::class, 'addNonPrintResource'])->name('add-nonprint-resource');

Route::get('/edit-resource/{id}', [EditResourceController::class, 'index'])->name('edit-resource');
Route::put('/update-print-resource/{id}', [EditResourceController::class, 'updatePrintResource'])->name('update-print-resource');
Route::put('/update-nonprint-resource/{id}', [EditResourceController::class, 'updateNonPrintResource'])->name('update-nonprint-resource');

// ============================================================
// ESTIMATED COUNT
// ============================================================
Route::patch('/school-library/update-estimated-resource', [ManageEstimatedResourceCountController::class, 'updateEstimatedResource'])
    ->name('school-library.update-estimated-resource');

Route::patch('/school-library/update-estimated-resource-np', [ManageEstimatedResourceCountController::class, 'updateEstimatedResourceNP'])
    ->name('school-library.update-estimated-resource-np');
