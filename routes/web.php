<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;

use App\Http\Controllers\AddResourceController;
use App\Http\Controllers\EditResourceController;
use App\Http\Controllers\PrintResourceController;
use App\Http\Controllers\NonPrintResourceController;

use App\Http\Controllers\ManageUserController;
use App\Http\Controllers\ManageStationController;

use App\Http\Controllers\ProfileController;

use App\Http\Controllers\SchoolController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\RegionController;

use App\Http\Controllers\GenerateReportController;
use App\Http\Controllers\LookUpController;

use Faker\Guesser\Name;
use Pest\Plugin\Manager;

//Index
Route::get('/', [LoginController::class, 'showLoginForm'])->name('index');

Route::get('/index2', function () {
    return view('index2');
});

//Register
Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [RegisterController::class, 'submitRegistration'])->name('register.submit');

// Auth routes
Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Protected routes [NAVIGATIONS]
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/add-resources', [AddResourceController::class, 'index'])->name('add-resources');
Route::get('/print-resources', [PrintResourceController::class, 'index'])->name('print-resources');
Route::get('/nonprint-resources', [NonPrintResourceController::class, 'index'])->name('nonprint-resources');
Route::get('/stations', [ManageStationController::class, 'index'])->name('stations');
Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
Route::get('/generate-report', [GenerateReportController::class, 'index'])->name('generate-report');

//MANAGE REGION PROFILE
Route::get('/region-profile', [RegionController::class, 'index'])->name('region-profile');
Route::put('/region-profile', [RegionController::class, 'update']) ->name('region.profile.update');
Route::put('/region-profile/update-logo', [RegionController::class, 'updateLogo'])->name('region.logo.update');


//MANAGE DIVISION PROFILE
Route::get('/division-profile', [DivisionController::class, 'index'])->name('division-profile');
Route::put('/division-profile', [DivisionController::class, 'update'])->name('division.profile.update');
Route::put('/division-profile/update-logo', [DivisionController::class, 'updateLogo'])->name('division.logo.update');

//MANAGE DISTRICT PROFILE
Route::get('/district-profile', [DistrictController::class, 'index'])->name('district-profile');
Route::put('/district-profile', [DistrictController::class, 'update'])->name('district.profile.update');
Route::put('/district-profile/update-logo', [DistrictController::class, 'updateLogo'])->name('district.logo.update');

//MANAGE SCHOOL PROFILE
Route::get('/school-profile', [SchoolController::class, 'index'])->name('school-profile');
Route::put('/school-profile', [SchoolController::class, 'update'])->name('school.profile.update');
Route::put('/school-profile/update-logo', [SchoolController::class, 'updateLogo'])->name('school.logo.update');

//MANAGE DIVISION
Route::post('/divisions', [ManageStationController::class, 'addDivision'])->name('divisions.store');
Route::put('/divisions/{division}', [ManageStationController::class, 'updateDivision'])->name('divisions.update');
Route::delete('/divisions/{division}', [ManageStationController::class, 'destroyDivision'])->name('divisions.destroy');

//MANAGE DIVISION
Route::post('/districts', [ManageStationController::class, 'addDistrict'])->name('districts.store');
Route::put('/districts/{district}', [ManageStationController::class, 'updateDistrict'])->name('districts.update');
Route::delete('/districts/{district}', [ManageStationController::class, 'destroyDistrict'])->name('districts.destroy');

//MANAGE SCHOOL
Route::post('/schools', [ManageStationController::class, 'addSchool'])->name('schools.store');
Route::put('/schools/{school}', [ManageStationController::class, 'updateSchool'])->name('schools.update');
Route::delete('/schools/{school}', [ManageStationController::class, 'destroySchool'])->name('schools.destroy');

//MANAGE USER
Route::get('/users', [ManageUserController::class, 'index'])->name('users');
Route::patch('/users/{user}/status', [ManageUserController::class, 'updateStatus'])->name('users.updateStatus');

//MANAGE PROFILE
Route::put('/profile/update', [ProfileController::class, 'updateInfo'])->name('profile.update');
Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

//MANAGE PRINT RESOURCE
Route::post('/add-print-resources', [AddResourceController::class, 'addPrintResource'])->name('add-print-resource');
Route::post('/add-nonprint-resources', [AddResourceController::class, 'addNonPrintResource'])->name('add-nonprint-resource');

Route::get('/edit-resource/{id}', [EditResourceController::class, 'index'])->name('edit-resource');
Route::put('/update-print-resource/{id}', [EditResourceController::class, 'updatePrintResource'])->name('update-print-resource');
Route::put('/update-nonprint-resource/{id}', [EditResourceController::class, 'updateNonPrintResource'])->name('update-nonprint-resource');
