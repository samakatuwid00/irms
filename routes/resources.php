<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Resource\AddResourceController;
use App\Http\Controllers\Resource\EditResourceController;
use App\Http\Controllers\Resource\PrintResourceController;
use App\Http\Controllers\Resource\NonPrintResourceController;

// RESOURCE NAVIGATIONS
Route::get('/add-resources', [AddResourceController::class, 'index'])->name('add-resources');
Route::get('/print-resources', [PrintResourceController::class, 'index'])->name('print-resources');
Route::get('/nonprint-resources', [NonPrintResourceController::class, 'index'])->name('nonprint-resources');

//MANAGE RESOURCE
Route::post('/add-print-resources', [AddResourceController::class, 'addPrintResource'])->name('add-print-resource');
Route::post('/add-nonprint-resources', [AddResourceController::class, 'addNonPrintResource'])->name('add-nonprint-resource');
Route::get('/edit-resource/{id}', [EditResourceController::class, 'index'])->name('edit-resource');
Route::put('/update-print-resource/{id}', [EditResourceController::class, 'updatePrintResource'])->name('update-print-resource');
Route::put('/update-nonprint-resource/{id}', [EditResourceController::class, 'updateNonPrintResource'])->name('update-nonprint-resource');
