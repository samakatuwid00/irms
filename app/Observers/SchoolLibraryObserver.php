<?php

namespace App\Observers;

use App\Models\SchoolLibrary;
use Illuminate\Support\Facades\DB;

/**
 * SchoolLibraryObserver
 *
 * When a library's name changes, rebuild search vectors for:
 *   - print_acquisitions      (library_name is part of their search vector)
 *   - nonprint_acquisitions   (library_name is part of their search vector)
 *
 * Note: nonprint_resources no longer holds library info; that moved to
 * nonprint_acquisitions, so we don't update nonprint_resources here anymore.
 *
 * Register in AppServiceProvider:
 *   SchoolLibrary::observe(SchoolLibraryObserver::class);
 */
class SchoolLibraryObserver
{
    public function saved(SchoolLibrary $library): void
    {
        if (! $library->isDirty('library_name')) {
            return;
        }

        $this->rebuildVectors($library->id);
    }

    private function rebuildVectors(string $libraryId): void
    {
        // --- Print acquisitions -------------------------------------------
        // Pass print_id and library_id directly to match the two-argument
        // function signature — avoids querying the row being written.
        DB::statement('
            UPDATE print_acquisitions
            SET search_vector = build_print_acquisition_search_vector(print_id, library_id)
            WHERE library_id = ?
        ', [$libraryId]);

        // --- Non-print acquisitions ---------------------------------------
        // Pass nonprint_id and library_id directly to match the two-argument
        // function signature — avoids querying the row being written.
        DB::statement('
            UPDATE nonprint_acquisitions
            SET search_vector = build_nonprint_acquisition_search_vector(nonprint_id, library_id)
            WHERE library_id = ?
        ', [$libraryId]);
    }
}
