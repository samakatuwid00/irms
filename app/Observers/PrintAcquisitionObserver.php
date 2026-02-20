<?php

namespace App\Observers;

use App\Models\PrintAcquisition;
use Illuminate\Support\Facades\DB;

/**
 * PrintAcquisitionObserver
 *
 * The BEFORE trigger on print_acquisitions handles search_vector rebuilds
 * automatically on every INSERT/UPDATE by passing NEW.print_id and NEW.library_id
 * directly to the builder function (avoiding the chicken-and-egg problem where
 * querying by acquisition_id finds nothing during an INSERT).
 *
 * This observer adds:
 *   - dirty-checking so we only act when relevant fields actually changed
 *   - auto-sync of library_name when library_id changes
 *
 * Register in AppServiceProvider:
 *   PrintAcquisition::observe(PrintAcquisitionObserver::class);
 */
class PrintAcquisitionObserver
{
    /**
     * Columns whose change should trigger a search vector rebuild.
     */
    private const VECTOR_FIELDS = [
        'print_id',
        'library_id',
        'library_name',
    ];

    public function saving(PrintAcquisition $acquisition): void
    {
        // On a new record the trigger always builds the vector — nothing to do.
        if (! $acquisition->exists) {
            return;
        }

        // On an existing record, skip if nothing that affects the vector changed.
        if (! $acquisition->isDirty(self::VECTOR_FIELDS)) {
            return;
        }

        // If library_id changed, refresh the denormalised library_name so the
        // DB trigger picks up the correct value when it fires.
        if ($acquisition->isDirty('library_id')) {
            $acquisition->library_name = $this->resolveLibraryName($acquisition->library_id);
        }
    }

    public function saved(PrintAcquisition $acquisition): void
    {
        // The BEFORE trigger already rebuilt the vector during the save.
        // This hook is kept for future extension.
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Resolves the latest library_name from whichever library table owns the ID.
     * Mirrors the COALESCE logic inside build_print_acquisition_search_vector().
     */
    private function resolveLibraryName(string $libraryId): string
    {
        $result = DB::selectOne("
            SELECT COALESCE(
                (SELECT library_name FROM school_libraries   WHERE id = ?),
                (SELECT library_name FROM division_libraries WHERE id = ?),
                (SELECT library_name FROM region_libraries   WHERE id = ?),
                ''
            ) AS library_name
        ", [$libraryId, $libraryId, $libraryId]);

        return $result?->library_name ?? '';
    }
}
