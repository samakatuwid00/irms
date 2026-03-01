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
use App\Http\Controllers\Resource\SearchNonPrintResourceController;
use App\Http\Controllers\Resource\MasterlistController;
use App\Http\Controllers\Resource\NonPrintMasterlistController;

Route::middleware('auth')->group(function () {

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
    Route::get('/print-masterlist', [MasterlistController::class, 'index'])
        ->name('masterlist.index');

    // Edit an approved resource (division + region)
    Route::get('/print-masterlist/{id}/edit', [MasterlistController::class, 'editForm'])
        ->name('masterlist.edit');
    Route::put('/print-masterlist/{id}', [MasterlistController::class, 'update'])
        ->name('masterlist.update');

    // Approve a school request (division only)
    Route::patch('/print-masterlist/{id}/approve', [MasterlistController::class, 'approve'])
        ->name('masterlist.approve');

    // Reject a school request (division only) — deletes the resource only
    Route::delete('/print-masterlist/{id}/reject', [MasterlistController::class, 'reject'])
        ->name('masterlist.reject');

    // AJAX search endpoints
    Route::get('/print-masterlist/search', [MasterlistController::class, 'search'])
        ->name('masterlist.search');
    Route::get('/print-masterlist/requests/search', [MasterlistController::class, 'requestSearch'])
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
    Route::get('/add-nonprint-resource/{id}/edit', [AddNonPrintResourceController::class, 'edit'])
        ->name('nonprint-resource.edit');
    Route::put('/add-nonprint-resource/{id}', [AddNonPrintResourceController::class, 'update'])
        ->name('nonprint-resource.update');
    Route::delete('/add-nonprint-resource/{id}', [AddNonPrintResourceController::class, 'destroy'])
        ->name('nonprint-resource.destroy');

    // ── SEARCH NON-PRINT RESOURCE (AJAX keyword search + detail modal) ─
    Route::get('/search-nonprint/query', [SearchNonPrintResourceController::class, 'search'])
        ->name('search-nonprint-resource.search');

    Route::get('/search-nonprint/{id}/details', [SearchNonPrintResourceController::class, 'show'])
        ->name('search-nonprint-resource.show');

    // ── ADD ACQUISITION to an existing approved non-print resource ──
    Route::get('/search-nonprint/{id}/add', [SearchNonPrintResourceController::class, 'addForm'])
        ->name('search-nonprint-resource.add-form');

    Route::post('/search-nonprint/{id}/add', [SearchNonPrintResourceController::class, 'store'])
        ->name('search-nonprint-resource.store');

    // ============================================================
    // NON-PRINT RESOURCE MASTERLIST (division level 3, region level 4)
    // ============================================================
    Route::get('/nonprint-masterlist', [NonPrintMasterlistController::class, 'index'])
        ->name('nonprint-masterlist.index');

    // Edit an approved resource (division + region)
    Route::get('/nonprint-masterlist/{id}/edit', [NonPrintMasterlistController::class, 'editForm'])
        ->name('nonprint-masterlist.edit');
    Route::put('/nonprint-masterlist/{id}', [NonPrintMasterlistController::class, 'update'])
        ->name('nonprint-masterlist.update');

    // Approve a school request (division only)
    Route::patch('/nonprint-masterlist/{id}/approve', [NonPrintMasterlistController::class, 'approve'])
        ->name('nonprint-masterlist.approve');

    // Reject a school request (division only) — deletes the resource only
    Route::delete('/nonprint-masterlist/{id}/reject', [NonPrintMasterlistController::class, 'reject'])
        ->name('nonprint-masterlist.reject');

    // AJAX search endpoints
    Route::get('/nonprint-masterlist/search', [NonPrintMasterlistController::class, 'search'])
        ->name('nonprint-masterlist.search');
    Route::get('/nonprint-masterlist/requests/search', [NonPrintMasterlistController::class, 'requestSearch'])
        ->name('nonprint-masterlist.requests.search');

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

});
