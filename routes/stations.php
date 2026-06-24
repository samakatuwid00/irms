<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Station\ManageStationController;
use App\Http\Controllers\Station\SchoolController;
use App\Http\Controllers\Station\DistrictController;
use App\Http\Controllers\Station\DivisionController;
use App\Http\Controllers\Station\LibraryHubController;
use App\Http\Controllers\Station\RegionController;
use App\Http\Controllers\Station\ImportSF6Controller;

Route::middleware('auth')->group(function () {

    //MANAGE STATIONS
    Route::get('/stations', [ManageStationController::class, 'index'])->name('stations');

    //MANAGE REGION PROFILE
    Route::get('/region-profile', [RegionController::class, 'index'])->name('region-profile');
    Route::put('/region-profile', [RegionController::class, 'update'])->name('region.profile.update');
    Route::put('/region-profile/update-logo', [RegionController::class, 'updateLogo'])->name('region.logo.update');

    //MANAGE DIVISION PROFILE
    Route::get('/division-profile', [DivisionController::class, 'index'])->name('division-profile');
    Route::put('/division-profile', [DivisionController::class, 'update'])->name('division.profile.update');
    Route::put('/division-profile/update-logo', [DivisionController::class, 'updateLogo'])->name('division.logo.update');
    Route::post('/division-profile/library-hubs', [LibraryHubController::class, 'store'])->name('division.library-hubs.store');
    Route::put('/division-profile/library-hubs/{libraryHub}', [LibraryHubController::class, 'update'])->name('division.library-hubs.update');

    //MANAGE DISTRICT PROFILE
    Route::get('/district-profile', [DistrictController::class, 'index'])->name('district-profile');
    Route::put('/district-profile', [DistrictController::class, 'update'])->name('district.profile.update');
    Route::put('/district-profile/update-logo', [DistrictController::class, 'updateLogo'])->name('district.logo.update');

    //MANAGE SCHOOL PROFILE
    Route::get('/school-profile', [SchoolController::class, 'index'])->name('school-profile');
    Route::put('/school-profile', [SchoolController::class, 'update'])->name('school.profile.update');
    Route::put('/school-profile/update-logo', [SchoolController::class, 'updateLogo'])->name('school.logo.update');
    Route::put('/school/grades', [SchoolController::class, 'updateGrades'])->name('school.grades.update');
    Route::put('/school/population', [SchoolController::class, 'updatePopulation'])->name('school.population.update');

    //MANAGE DIVISION
    Route::post('/divisions', [ManageStationController::class, 'addDivision'])->name('divisions.store');
    Route::put('/divisions/{division}', [ManageStationController::class, 'updateDivision'])->name('divisions.update');
    Route::delete('/divisions/{division}', [ManageStationController::class, 'destroyDivision'])->name('divisions.destroy');

    //MANAGE DISTRICT
    Route::post('/districts', [ManageStationController::class, 'addDistrict'])->name('districts.store');
    Route::put('/districts/{district}', [ManageStationController::class, 'updateDistrict'])->name('districts.update');
    Route::delete('/districts/{district}', [ManageStationController::class, 'destroyDistrict'])->name('districts.destroy');

    //MANAGE SCHOOL
    Route::post('/schools', [ManageStationController::class, 'addSchool'])->name('schools.store');
    Route::put('/schools/{school}', [ManageStationController::class, 'updateSchool'])->name('schools.update');
    Route::delete('/schools/{school}', [ManageStationController::class, 'destroySchool'])->name('schools.destroy');

    //AJAX endpoint for loading population data without page reload
    Route::get('/school/population/{sy_id}', [SchoolController::class, 'getPopulationData'])->name('school.population.get');

    Route::get('/import-sf6',            [ImportSF6Controller::class, 'index'])   ->name('import.sf6.index');
    Route::post('/import-sf6/preview',   [ImportSF6Controller::class, 'preview']) ->name('import.sf6.preview');
    Route::post('/import-sf6/store',     [ImportSF6Controller::class, 'store'])   ->name('import.sf6.store');

});
