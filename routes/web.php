<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
})->name('index');

Route::get('/register', function () {
    return view('register');
})->name('register');

Route::get('/dashboard', function () {
    return view('pages.dashboard');
})->name('dashboard');

Route::get('/add-resources', function () {
    return view('pages.add-resources');
})->name('add-resources');
Route::get('/print-resources', function () {
    return view('pages.print-resources');
})->name('print-resources');
Route::get('/nonprint-resources', function () {
    return view('pages.nonprint-resources');
})->name('nonprint-resources');
Route::get('/users', function () {
    return view('pages.users');
})->name('users');
Route::get('/stations', function () {
    return view('pages.stations');
})->name('stations');
Route::get('/profile', function () {
    return view('pages.profile');
})->name('profile');
Route::get('/school-profile', function () {
    return view('pages.school-profile');
})->name('school-profile');
Route::get('/district-profile', function () {
    return view('pages.district-profile');
})->name('district-profile');
Route::get('/division-profile', function () {
    return view('pages.division-profile');
})->name('division-profile');
Route::get('/region-profile', function () {
    return view('pages.region-profile');
})->name('region-profile');
