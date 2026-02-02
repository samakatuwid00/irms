<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ManageUserController;
use App\Http\Controllers\ProfileController;

//MANAGE USER
Route::get('/users', [ManageUserController::class, 'index'])->name('users');
Route::patch('/users/{user}/status', [ManageUserController::class, 'updateStatus'])->name('users.updateStatus');

//MANAGE PROFILE
Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
Route::put('/profile/update', [ProfileController::class, 'updateInfo'])->name('profile.update');
Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
Route::put('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo');
