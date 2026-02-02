<?php

namespace App\Observers;

use App\Models\PrintTitle;
use Illuminate\Support\Facades\DB;

/**
 * PrintTitleObserver
 *
 * Updates search vectors when print titles change
 * Register in AppServiceProvider
 */
class PrintTitleObserver
{
    /**
     * Handle the PrintTitle "saved" event.
     */
    public function saved(PrintTitle $printTitle): void
    {
        // Update search_vector for all print resources that use this title
        DB::statement('
            UPDATE print_resources
            SET search_vector = build_print_resource_search_vector(id)
            WHERE print_title_id = ?
        ', [$printTitle->id]);
    }

    /**
     * Handle the PrintTitle "deleted" event.
     */
    public function deleted(PrintTitle $printTitle): void
    {
        // Update search_vector for affected resources
        DB::statement('
            UPDATE print_resources
            SET search_vector = build_print_resource_search_vector(id)
            WHERE print_title_id = ?
        ', [$printTitle->id]);
    }
}
