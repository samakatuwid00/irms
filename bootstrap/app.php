<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureSchoolPopulationIsEntered;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/');
        $middleware->appendToGroup('web', EnsureSchoolPopulationIsEntered::class);
        //$middleware->append(\App\Http\Middleware\SecurityHeaders::class);
    })
    ->withCommands([
        App\Console\Commands\MvViewRefresher::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
