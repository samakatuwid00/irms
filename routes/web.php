<?php

use Illuminate\Support\Facades\Route;
Route::get('/index2', function () {
    return view('index2');
});
// AUTHENTICATION ROUTES
require __DIR__.'/auth.php';

// DASHBOARD ROUTES
require __DIR__.'/dashboard.php';

// RESOURCE ROUTES
require __DIR__.'/resources.php';

//MANAGE EXPORT PRINT RESOURCE
require __DIR__.'/exports.php';

// STATION ROUTES
require __DIR__.'/stations.php';

// MANAGE USER ROUTES
require __DIR__.'/user.php';




