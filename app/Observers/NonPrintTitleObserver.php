<?php

namespace App\Observers;

use App\Models\NonprintTitle;
use Illuminate\Support\Facades\DB;

/**
 * NonPrintTitleObserver
 *
 * Updates search vectors when non-print titles change
 * Register in AppServiceProvider
 */
class NonPrintTitleObserver
{
    /**
     * Handle the NonprintTitle "saved" event.
     */
    public function saved(NonprintTitle $nonprintTitle): void
    {
        // Update search_vector for all non-print resources that use this title
        DB::statement('
            UPDATE nonprint_resources
            SET search_vector = build_nonprint_resource_search_vector(id)
            WHERE nonprint_title_id = ?
        ', [$nonprintTitle->id]);
    }

    /**
     * Handle the NonprintTitle "deleted" event.
     */
    public function deleted(NonprintTitle $nonprintTitle): void
    {
        // Update search_vector for affected resources
        DB::statement('
            UPDATE nonprint_resources
            SET search_vector = build_nonprint_resource_search_vector(id)
            WHERE nonprint_title_id = ?
        ', [$nonprintTitle->id]);
    }
}
