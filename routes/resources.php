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
use App\Http\Controllers\Resource\MasterlistController;

// ============================================================
// RESOURCE TABLE
// ============================================================
Route::get('/print-resources', [PrintResourceController::class, 'index'])->name('print-resources');
Route::get('/nonprint-resources', [NonPrintResourceController::class, 'index'])->name('nonprint-resources');

// ============================================================
// ADD PRINT RESOURCE
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
// PRINT RESOURCE MASTERLIST (division level 3, region level 4)
// ============================================================
Route::get('/masterlist', [MasterlistController::class, 'index'])
    ->name('masterlist.index');

// Edit an approved resource (division + region)
Route::get('/masterlist/{id}/edit', [MasterlistController::class, 'editForm'])
    ->name('masterlist.edit');
Route::put('/masterlist/{id}', [MasterlistController::class, 'update'])
    ->name('masterlist.update');

// Approve a school request (division only)
Route::patch('/masterlist/{id}/approve', [MasterlistController::class, 'approve'])
    ->name('masterlist.approve');

// Reject a school request (division only) — deletes the resource only
Route::delete('/masterlist/{id}/reject', [MasterlistController::class, 'reject'])
    ->name('masterlist.reject');

// AJAX search endpoints (optional — for future async use)
Route::get('/masterlist/search', [MasterlistController::class, 'search'])
    ->name('masterlist.search');
Route::get('/masterlist/requests/search', [MasterlistController::class, 'requestSearch'])
    ->name('masterlist.requests.search');

// ============================================================
// SEARCH PRINT RESOURCE
// ============================================================
Route::get('/search-print/query', [SearchPrintResourceController::class, 'search'])
    ->name('search-print-resource.search');

// Get full details of a single print resource
Route::get('/search-print/{id}/details', [SearchPrintResourceController::class, 'show'])
    ->name('search-print-resource.show');

// Add-acquisition form pre-filled with read-only resource details
Route::get('/search-print/{id}/add', [SearchPrintResourceController::class, 'addForm'])
    ->name('search-print-resource.add-form');

// Add the resource to acquisitions with pre-filled details
Route::post('/search-print/{id}/add', [SearchPrintResourceController::class, 'store'])
    ->name('search-print-resource.store');

// ============================================================
// ADD NON-PRINT RESOURCE
// ============================================================
Route::get('/add-nonprint-resource', [AddNonPrintResourceController::class, 'index'])
    ->name('nonprint-resource.create');
Route::post('/add-nonprint-resource', [AddNonPrintResourceController::class, 'store'])
    ->name('nonprint-resource.store');


// ============================================================
// EDIT RESOURCE
// ============================================================

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
