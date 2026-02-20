<?php

namespace App\Observers;

use App\Models\PrintTitle;
use Illuminate\Support\Facades\DB;

/**
 * PrintTitleObserver
 *
 * When a PrintTitle (or its authors) changes, rebuild search vectors on:
 *   - print_resources     (title + authors + isbn + publisher)
 *   - print_acquisitions  (same metadata + library info)
 *
 * Register in AppServiceProvider:
 *   PrintTitle::observe(PrintTitleObserver::class);
 */
class PrintTitleObserver
{
    public function saved(PrintTitle $printTitle): void
    {
        $this->rebuildVectors($printTitle->id);
    }

    public function deleted(PrintTitle $printTitle): void
    {
        $this->rebuildVectors($printTitle->id);
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function rebuildVectors(string $printTitleId): void
    {
        // 1. Rebuild resource vectors (title / authors changed)
        DB::statement('
            UPDATE print_resources
            SET search_vector = build_print_resource_search_vector(id)
            WHERE print_title_id = ?
        ', [$printTitleId]);

        // 2. Rebuild acquisition vectors — pass print_id and library_id directly
        //    to match the two-argument function signature and avoid the
        //    chicken-and-egg problem on INSERT.
        DB::statement('
            UPDATE print_acquisitions pa
            SET search_vector = build_print_acquisition_search_vector(pa.print_id, pa.library_id)
            FROM print_resources pr
            WHERE pa.print_id        = pr.id
              AND pr.print_title_id  = ?
        ', [$printTitleId]);
    }
}
