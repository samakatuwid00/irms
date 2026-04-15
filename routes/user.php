<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\ManageUserController;
use App\Http\Controllers\User\ProfileController;

Route::middleware('auth')->group(function () {

    //MANAGE USER
    Route::get('/users', [ManageUserController::class, 'index'])->name('users');
    Route::patch('/users/{user}/status', [ManageUserController::class, 'updateStatus'])->name('users.updateStatus');

    //MANAGE PROFILE
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile/update', [ProfileController::class, 'updateInfo'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::put('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo');
    Route::put('/users/{user}/change-password', [ManageUserController::class, 'changePassword'])
    ->name('users.changePassword');

    Route::patch('/users/{user}/station', [ManageUserController::class, 'updateStation'])
     ->name('users.updateStation')
     ->middleware(['auth']);
    
    // Scoped districts for inline station edit
    Route::get('/divisions/{division}/districts', [ManageUserController::class, 'districtsByDivision'])
         ->name('divisions.districts');
});
