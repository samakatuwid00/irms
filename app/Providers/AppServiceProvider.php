<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\PrintTitle;
use App\Models\NonprintTitle;
use App\Models\SchoolLibrary;
use App\Models\DivisionLibrary;
use App\Models\RegionLibrary;
use App\Observers\PrintTitleObserver;
use App\Observers\NonPrintTitleObserver;
use App\Observers\SchoolLibraryObserver;
use App\Observers\DivisionLibraryObserver;
use App\Observers\RegionLibraryObserver;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register observers for Print Resources
        PrintTitle::observe(PrintTitleObserver::class);

        // Register observers for Non-Print Resources
        NonprintTitle::observe(NonPrintTitleObserver::class);

        // Register observers for Libraries (affects both Print and Non-Print)
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
