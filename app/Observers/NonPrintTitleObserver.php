<?php

namespace App\Observers;

use App\Models\NonprintTitle;
use Illuminate\Support\Facades\DB;

/**
 * NonPrintTitleObserver
 *
 * When a NonprintTitle (or its metadata) changes, rebuild search vectors on:
 *   - nonprint_resources     (title + subjects + grades + brand + code)
 *   - nonprint_acquisitions  (same metadata + library info)
 *
 * Register in AppServiceProvider:
 *   NonprintTitle::observe(NonPrintTitleObserver::class);
 */
class NonPrintTitleObserver
{
    public function saved(NonprintTitle $nonprintTitle): void
    {
        $this->rebuildVectors($nonprintTitle->id);
    }

    public function deleted(NonprintTitle $nonprintTitle): void
    {
        $this->rebuildVectors($nonprintTitle->id);
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function rebuildVectors(string $nonprintTitleId): void
    {
        // 1. Rebuild resource vectors (title / metadata changed)
        DB::statement('
            UPDATE nonprint_resources
            SET search_vector = build_nonprint_resource_search_vector(id)
            WHERE nonprint_title_id = ?
        ', [$nonprintTitleId]);

        // 2. Rebuild acquisition vectors — pass nonprint_id and library_id directly
        //    to match the two-argument function signature and avoid the
        //    chicken-and-egg problem on INSERT.
        DB::statement('
            UPDATE nonprint_acquisitions na
            SET search_vector = build_nonprint_acquisition_search_vector(na.nonprint_id, na.library_id)
            FROM nonprint_resources nr
            WHERE na.nonprint_id        = nr.id
              AND nr.nonprint_title_id  = ?
        ', [$nonprintTitleId]);
    }
}
