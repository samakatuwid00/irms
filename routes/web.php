<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    RegisterController,
    LoginController,
    DashboardController,
    ManageResourceController,
    PrintResourceController,
    NonPrintResourceController,
    ManageUserController,
    ManageStationController,
    ProfileController,
    SchoolController,
    DistrictController,
    DivisionController,
    RegionController,
    GenerateReportController
};
//Index
Route::get('/', [LoginController::class, 'showLoginForm'])->name('index');

//Register
Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [RegisterController::class, 'submitRegistration'])->name('register.submit');

// Auth routes
Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Protected routes [NAVIGATIONS]
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/add-resources', [ManageResourceController::class, 'index'])->name('add-resources');
Route::get('/print-resources', [PrintResourceController::class, 'index'])->name('print-resources');
Route::get('/nonprint-resources', [NonPrintResourceController::class, 'index'])->name('nonprint-resources');
Route::get('/users', [ManageUserController::class, 'index'])->name('users');
Route::get('/stations', [ManageStationController::class, 'index'])->name('stations');
Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
Route::get('/generate-report', [GenerateReportController::class, 'index'])->name('generate-report');

//MANAGE REGION PROFILE
Route::get('/region-profile', [RegionController::class, 'index'])->name('region-profile');
Route::put('/region-profile', [RegionController::class, 'update']) ->name('region.profile.update');

//MANAGE DIVISION PROFILE
Route::get('/division-profile', [DivisionController::class, 'index'])->name('division-profile');
Route::put('/division-profile', [DivisionController::class, 'update'])->name('division.profile.update');

//MANAGE DISTRICT PROFILE
Route::get('/district-profile', [DistrictController::class, 'index'])->name('district-profile');
Route::put('/district-profile', [DistrictController::class, 'update'])->name('district.profile.update');

//MANAGE SCHOOL PROFILE
Route::get('/school-profile', [SchoolController::class, 'index'])->name('school-profile');
Route::put('/school-profile', [SchoolController::class, 'update'])->name('school.profile.update');

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
Route::get('/{user}', [ManageUserController::class, 'show'])->name('show');
Route::patch('/{user}/status', [ManageUserController::class, 'updateStatus'])->name('users.updateStatus');

//MANAGE PROFILE
Route::put('/profile/update', [ProfileController::class, 'updateInfo'])->name('profile.update');
Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

