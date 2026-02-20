<?php

namespace App\Observers;

use App\Models\SchoolLibrary;
use Illuminate\Support\Facades\DB;

/**
 * SchoolLibraryObserver
 *
 * When a library's name changes, rebuild search vectors for:
 *   - print_acquisitions   (library_name is part of their search vector)
 *   - nonprint_resources   (still holds library_id / library_name directly)
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

        $this->rebuildVectors($library->id, $library->library_name);
    }

    private function rebuildVectors(string $libraryId, string $libraryName): void
    {
        // --- Print acquisitions -------------------------------------------
        // Pass print_id and library_id directly to match the two-argument
        // function signature — avoids querying the row being written.
        DB::statement('
            UPDATE print_acquisitions
            SET search_vector = build_print_acquisition_search_vector(print_id, library_id)
            WHERE library_id = ?
        ', [$libraryId]);

        // --- Non-print resources ------------------------------------------
        // library_name is still denormalised on nonprint_resources, so sync it
        // first, then rebuild the search vector.
        DB::statement('
            UPDATE nonprint_resources
            SET library_name = ?
            WHERE library_id = ?
        ', [$libraryName, $libraryId]);

        DB::statement('
            UPDATE nonprint_resources
            SET search_vector = build_nonprint_resource_search_vector(id)
            WHERE library_id = ?
        ', [$libraryId]);
    }
}
