<?php

namespace App\Services\Resource\Actions;

use App\Models\PrintAcquisition;
use App\Models\PrintMasterlist;
use App\Models\PrintResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EditPrintResourceService
{
    private const STATUS_MAP = [
        'usable'            => 'USABLE',
        'partially_damaged' => 'PARTIALLY DAMAGED',
        'damaged'           => 'DAMAGED',
        'lost'              => 'LOST',
        'condemnable'       => 'CONDEMNABLE',
    ];

    private const PROTECTED_BORROW_STATUSES = ['borrowed', 'reserved'];

    // Only acquisition rows are touched here — title/authors/cover/etc. are managed
    // by AddPrintResourceService::updatePrintResource()
    public function updatePrintResource(string $id, array $data): array
    {
        $printResource = null;

        DB::transaction(function () use ($id, $data, &$printResource) {
            $printResource = PrintResource::with('printAcquisitions')->findOrFail($id);

            $this->updateAcquisitions($printResource, $data['acquisitions']);

            $printResource->refresh();
        });

        $this->updateSearchVector($id);

        return ['resource' => $printResource];
    }

    // Reconcile the submitted JSON against DB rows:
    // - rows with an id  → update or delete (if qty zeroed out)
    // - rows without an id → insert as new
    // - DB rows not present in submission → delete (user removed them)
    private function updateAcquisitions(PrintResource $printResource, string $acquisitionsJson): void
    {
        $acquisitions = json_decode($acquisitionsJson, true) ?? [];

        $userId            = Auth::id();
        $now               = now();
        $keptIds           = [];
        $masterlistInserts = [];
        $masterlistDeletes = [];
        $zeroQtyIds        = [];

        foreach ($acquisitions as $acqData) {
            $totalQty = (int) ($acqData['total_quantity'] ?? 0);

            if (!empty($acqData['id'])) {
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

        $this->processBulkMasterlistOperations($masterlistInserts, $masterlistDeletes);
        $this->deleteZeroQuantityAcquisitions($zeroQtyIds);
        $this->deleteRemovedAcquisitions($printResource, $keptIds);
    }

    // Returns ['delete' => bool, 'id' => string] so the caller can route to the right bucket
    private function updateExistingAcquisition(
        array $acqData,
        int   $totalQty,
        array &$masterlistInserts,
        array &$masterlistDeletes,
    ): array {
        $acquisition = PrintAcquisition::findOrFail($acqData['id']);

        // Zero-quantity rows get queued for deletion, not updated
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
            'cost'              => $acqData['cost'] !== '' ? $acqData['cost'] : 0,
            'iar'               => $acqData['iar']  !== '' ? $acqData['iar']  : null,
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

    // Returns the new UUID so the caller can add it to $keptIds, or null if qty was 0
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
            'cost'              => $acqData['cost'] !== '' ? $acqData['cost'] : 0,
            'iar'               => $acqData['iar']  !== '' ? $acqData['iar']  : null,
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

    // Diff old vs new per-status quantities and queue inserts or deletes accordingly.
    // Only masterlist rows that are not currently borrowed or reserved may be deleted.
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
                // Only delete rows that are not currently borrowed or reserved
                $toDelete = PrintMasterlist::where('print_acquisition_id', $acquisition->id)
                    ->where('status', $statusName)
                    ->whereNotIn('borrow_status', self::PROTECTED_BORROW_STATUSES)
                    ->limit(abs($diff))
                    ->pluck('id')
                    ->toArray();

                $masterlistDeletes = array_merge($masterlistDeletes, $toDelete);
            }
        }
    }

    private function processBulkMasterlistOperations(array $inserts, array $deletes): void
    {
        if (!empty($inserts)) {
            // Batch in chunks of 500 to avoid hitting DB parameter limits
            foreach (array_chunk($inserts, 500) as $chunk) {
                PrintMasterlist::insert($chunk);
            }
        }

        if (!empty($deletes)) {
            PrintMasterlist::whereIn('id', $deletes)->delete();
        }
    }

    // Guard: throw a validation error if any masterlist rows under these acquisition IDs
    // are currently borrowed or reserved. The FK on print_borrowings would otherwise
    // prevent the delete and bubble up as an unhandled DB exception.
    private function assertNoProtectedCopies(array $acquisitionIds, string $context): void
    {
        $protectedCount = PrintMasterlist::whereIn('print_acquisition_id', $acquisitionIds)
            ->whereIn('borrow_status', self::PROTECTED_BORROW_STATUSES)
            ->count();

        if ($protectedCount > 0) {
            throw ValidationException::withMessages([
                'acquisitions' => "Cannot remove {$context}: {$protectedCount} copy/copies are currently borrowed or reserved.",
            ]);
        }
    }

    private function deleteZeroQuantityAcquisitions(array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        // Block if any copies are still out on loan or reserved
        $this->assertNoProtectedCopies($ids, 'acquisition(s) with zero quantity');

        // Masterlist first — FK constraint requires it before we can delete the acquisition
        PrintMasterlist::whereIn('print_acquisition_id', $ids)->delete();
        PrintAcquisition::whereIn('id', $ids)->delete();
    }

    private function deleteRemovedAcquisitions(PrintResource $printResource, array $keptIds): void
    {
        $existingIds = $printResource->printAcquisitions->pluck('id')->toArray();
        $toDelete    = array_diff($existingIds, $keptIds);

        if (empty($toDelete)) {
            return;
        }

        // Block if any copies are still out on loan or reserved
        $this->assertNoProtectedCopies(array_values($toDelete), 'removed acquisition(s)');

        PrintMasterlist::whereIn('print_acquisition_id', $toDelete)->delete();
        PrintAcquisition::whereIn('id', $toDelete)->delete();
    }

    private function updateSearchVector(string $id): void
    {
        DB::statement('
            UPDATE print_resources
            SET search_vector = build_print_resource_search_vector(id)
            WHERE id = ?
        ', [$id]);
    }
}