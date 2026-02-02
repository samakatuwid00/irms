<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\PrintTitle;
use App\Models\SchoolLibrary;
use App\Models\DivisionLibrary;
use App\Models\RegionLibrary;
use App\Observers\PrintTitleObserver;
use App\Observers\SchoolLibraryObserver;
use App\Observers\DivisionLibraryObserver;
use App\Observers\RegionLibraryObserver;

/**
 * ADD THIS TO YOUR AppServiceProvider
 *
 * Location: app/Providers/AppServiceProvider.php
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register observers to keep search vectors updated
        PrintTitle::observe(PrintTitleObserver::class);
        SchoolLibrary::observe(SchoolLibraryObserver::class);
        DivisionLibrary::observe(DivisionLibraryObserver::class);
        RegionLibrary::observe(RegionLibraryObserver::class);
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}
