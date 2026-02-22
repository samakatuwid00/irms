<?php

namespace App\Services\Resource\Actions;

use App\Models\PrintAcquisition;
use App\Models\PrintMasterlist;
use App\Models\PrintResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EditPrintResourceService
{
    private const STATUS_MAP = [
        'usable'            => 'USABLE',
        'partially_damaged' => 'PARTIALLY DAMAGED',
        'damaged'           => 'DAMAGED',
        'lost'              => 'LOST',
        'condemnable'       => 'CONDEMNABLE',
    ];

    /**
     * Update acquisitions for an existing PrintResource.
     *
     * Resource metadata (title, authors, cover, type, publisher, etc.) is
     * intentionally NOT touched here — only PrintAcquisition and
     * PrintMasterlist rows are written.
     *
     * Returns ['resource' => PrintResource].
     */
    public function updatePrintResource(string $id, array $data): array
    {
        $printResource = null;

        DB::transaction(function () use ($id, $data, &$printResource) {
            $printResource = PrintResource::with('printAcquisitions')->findOrFail($id);

            // Update acquisition rows only
            $this->updateAcquisitions($printResource, $data['acquisitions']);

            $printResource->refresh();
        });

        // Rebuild full-text search vector after the transaction commits
        $this->updateSearchVector($id);

        return [
            'resource' => $printResource,
        ];
    }

    // -------------------------------------------------------------------------
    // ACQUISITIONS
    // -------------------------------------------------------------------------

    /**
     * Reconcile submitted acquisition JSON against the DB rows:
     *   - Existing rows with an id  → update or delete (qty = 0)
     *   - New rows without an id    → insert
     *   - DB rows absent from submission → delete
     */
    private function updateAcquisitions(PrintResource $printResource, string $acquisitionsJson): void
    {
        $acquisitions = json_decode($acquisitionsJson, true) ?? [];

        $userId           = Auth::id();
        $now              = now();
        $keptIds          = [];      // ids that survive (update path)
        $masterlistInserts = [];
        $masterlistDeletes = [];
        $zeroQtyIds       = [];     // existing rows zeroed out → delete

        foreach ($acquisitions as $acqData) {
            $totalQty = (int) ($acqData['total_quantity'] ?? 0);

            if (! empty($acqData['id'])) {
                // ── Update existing acquisition ──────────────────────────────
                $result = $this->updateExistingAcquisition(
                    $acqData,
                    $totalQty,
                    $masterlistInserts,
                    $masterlistDeletes,
                );

                if ($result['delete']) {
                    $zeroQtyIds[] = $result['id'];
                } else {
                    $keptIds[] = $result['id'];
                }
            } else {
                // ── Insert new acquisition ───────────────────────────────────
                $newId = $this->createNewAcquisition(
                    $printResource->id,
                    $acqData,
                    $totalQty,
                    $userId,
                    $now,
                    $masterlistInserts,
                );

                if ($newId) {
                    $keptIds[] = $newId;
                }
            }
        }

        // ── Bulk DB operations ───────────────────────────────────────────────
        $this->processBulkMasterlistOperations($masterlistInserts, $masterlistDeletes);
        $this->deleteZeroQuantityAcquisitions($zeroQtyIds);
        $this->deleteRemovedAcquisitions($printResource, $keptIds);
    }

    /**
     * Update an existing PrintAcquisition row.
     * Returns ['delete' => bool, 'id' => string].
     */
    private function updateExistingAcquisition(
        array $acqData,
        int   $totalQty,
        array &$masterlistInserts,
        array &$masterlistDeletes,
    ): array {
        $acquisition = PrintAcquisition::findOrFail($acqData['id']);

        // Zero-quantity rows are queued for deletion instead
        if ($totalQty === 0) {
            return ['delete' => true, 'id' => $acquisition->id];
        }

        $oldQuantities = [
            'usable'            => (int) $acquisition->usable,
            'partially_damaged' => (int) $acquisition->partially_damaged,
            'damaged'           => (int) $acquisition->damaged,
            'lost'              => (int) $acquisition->lost,
            'condemnable'       => (int) $acquisition->condemnable,
        ];

        $newQuantities = [
            'usable'            => (int) ($acqData['usable']            ?? 0),
            'partially_damaged' => (int) ($acqData['partially_damaged'] ?? 0),
            'damaged'           => (int) ($acqData['damaged']           ?? 0),
            'lost'              => (int) ($acqData['lost']              ?? 0),
            'condemnable'       => (int) ($acqData['condemnable']       ?? 0),
        ];

        $acquisition->update([
            'library_id'        => $acqData['library_id']   ?? $acquisition->library_id,
            'library_name'      => $acqData['library_name'] ?? $acquisition->library_name,
            'source'            => $acqData['source'],
            'date_acquired'     => $acqData['date_acquired'],
            'cost'              => $acqData['cost']    !== '' ? $acqData['cost']    : 0,
            'iar'               => $acqData['iar']     !== '' ? $acqData['iar']     : null,
            'remarks'           => $acqData['remarks'] ?? null,
            'usable'            => $newQuantities['usable'],
            'partially_damaged' => $newQuantities['partially_damaged'],
            'damaged'           => $newQuantities['damaged'],
            'lost'              => $newQuantities['lost'],
            'condemnable'       => $newQuantities['condemnable'],
            'total_qty'         => $totalQty,
        ]);

        $this->prepareMasterlistChanges(
            $acquisition,
            $oldQuantities,
            $newQuantities,
            $masterlistInserts,
            $masterlistDeletes,
        );

        return ['delete' => false, 'id' => $acquisition->id];
    }

    /**
     * Insert a brand-new PrintAcquisition and queue its masterlist entries.
     * Returns the new UUID, or null if qty is zero (nothing to insert).
     */
    private function createNewAcquisition(
        string $printResourceId,
        array  $acqData,
        int    $totalQty,
        mixed  $userId,
        mixed  $now,
        array  &$masterlistInserts,
    ): ?string {
        if ($totalQty === 0) {
            return null;
        }

        $acquisitionId = (string) Str::uuid();

        PrintAcquisition::create([
            'id'                => $acquisitionId,
            'print_id'          => $printResourceId,
            'library_id'        => $acqData['library_id']   ?? null,
            'library_name'      => $acqData['library_name'] ?? null,
            'source'            => $acqData['source'],
            'date_acquired'     => $acqData['date_acquired'],
            'cost'              => $acqData['cost']    !== '' ? $acqData['cost']    : 0,
            'iar'               => $acqData['iar']     !== '' ? $acqData['iar']     : null,
            'remarks'           => $acqData['remarks'] ?? null,
            'usable'            => (int) ($acqData['usable']            ?? 0),
            'partially_damaged' => (int) ($acqData['partially_damaged'] ?? 0),
            'damaged'           => (int) ($acqData['damaged']           ?? 0),
            'lost'              => (int) ($acqData['lost']              ?? 0),
            'condemnable'       => (int) ($acqData['condemnable']       ?? 0),
            'total_qty'         => $totalQty,
            'encoded_by'        => $userId,
            'date_encoded'      => $now,
        ]);

        foreach (self::STATUS_MAP as $field => $statusName) {
            $qty = (int) ($acqData[$field] ?? 0);
            for ($i = 0; $i < $qty; $i++) {
                $masterlistInserts[] = [
                    'id'                   => (string) Str::uuid(),
                    'print_acquisition_id' => $acquisitionId,
                    'status'               => $statusName,
                ];
            }
        }

        return $acquisitionId;
    }

    // -------------------------------------------------------------------------
    // MASTERLIST HELPERS
    // -------------------------------------------------------------------------

    /**
     * Diff old vs new quantities and queue inserts / deletes for the masterlist.
     */
    private function prepareMasterlistChanges(
        PrintAcquisition $acquisition,
        array $oldQuantities,
        array $newQuantities,
        array &$masterlistInserts,
        array &$masterlistDeletes,
    ): void {
        foreach (self::STATUS_MAP as $field => $statusName) {
            $diff = (int) $newQuantities[$field] - (int) $oldQuantities[$field];

            if ($diff > 0) {
                for ($i = 0; $i < $diff; $i++) {
                    $masterlistInserts[] = [
                        'id'                   => (string) Str::uuid(),
                        'print_acquisition_id' => $acquisition->id,
                        'status'               => $statusName,
                    ];
                }
            } elseif ($diff < 0) {
                $toDelete = PrintMasterlist::where('print_acquisition_id', $acquisition->id)
                    ->where('status', $statusName)
                    ->limit(abs($diff))
                    ->pluck('id')
                    ->toArray();

                $masterlistDeletes = array_merge($masterlistDeletes, $toDelete);
            }
        }
    }

    private function processBulkMasterlistOperations(array $inserts, array $deletes): void
    {
        if (! empty($inserts)) {
            foreach (array_chunk($inserts, 500) as $chunk) {
                PrintMasterlist::insert($chunk);
            }
        }

        if (! empty($deletes)) {
            PrintMasterlist::whereIn('id', $deletes)->delete();
        }
    }

    private function deleteZeroQuantityAcquisitions(array $ids): void
    {
        if (! empty($ids)) {
            PrintMasterlist::whereIn('print_acquisition_id', $ids)->delete();
            PrintAcquisition::whereIn('id', $ids)->delete();
        }
    }

    /**
     * Delete any DB acquisition rows that were not present in the submission
     * (i.e. the user removed them from the table entirely).
     */
    private function deleteRemovedAcquisitions(PrintResource $printResource, array $keptIds): void
    {
        $existingIds = $printResource->printAcquisitions->pluck('id')->toArray();
        $toDelete    = array_diff($existingIds, $keptIds);

        if (! empty($toDelete)) {
            PrintMasterlist::whereIn('print_acquisition_id', $toDelete)->delete();
            PrintAcquisition::whereIn('id', $toDelete)->delete();
        }
    }

    // -------------------------------------------------------------------------
    // SEARCH VECTOR
    // -------------------------------------------------------------------------

    private function updateSearchVector(string $id): void
    {
        DB::statement('
            UPDATE print_resources
            SET search_vector = build_print_resource_search_vector(id)
            WHERE id = ?
        ', [$id]);
    }
}
