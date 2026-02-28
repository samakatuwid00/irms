<?php

namespace App\Services\Resource\Actions;

use App\Models\NonprintAcquisition;
use App\Models\NonprintMasterlist;
use App\Models\NonprintResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EditNonPrintResourceService
{
    private const STATUS_MAP = [
        'usable'            => 'USABLE',
        'partially_damaged' => 'PARTIALLY DAMAGED',
        'damaged'           => 'DAMAGED',
        'lost'              => 'LOST',
        'condemnable'       => 'CONDEMNABLE',
    ];

    // Only acquisition rows are touched here — title/type/cover/etc. are managed
    // by AddNonPrintResourceService::updateNonPrintResource()
    public function updateNonPrintResource(string $id, array $data): array
    {
        $shouldDelete     = false;
        $nonprintResource = null;

        DB::transaction(function () use ($id, $data, &$shouldDelete, &$nonprintResource) {
            $nonprintResource = NonprintResource::with('nonprintAcquisitions')->findOrFail($id);

            $this->updateAcquisitions($nonprintResource, $data['acquisitions']);

            $nonprintResource->refresh();

            // If the user zeroed out everything, clean up the resource entirely
            if ($nonprintResource->nonprintAcquisitions()->count() === 0) {
                $nonprintResource->delete();
                $shouldDelete     = true;
                $nonprintResource = null;
            }
        });

        if (!$shouldDelete) {
            $this->updateSearchVector($id);
        }

        return [
            'deleted'  => $shouldDelete,
            'resource' => $nonprintResource,
        ];
    }

    // Reconcile the submitted JSON against DB rows:
    // - rows with an id  → update or delete (if qty zeroed out)
    // - rows without an id → insert as new
    // - DB rows not present in submission → delete (user removed them)
    private function updateAcquisitions(NonprintResource $nonprintResource, string $acquisitionsJson): void
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
                    $nonprintResource->id,
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
        $this->deleteRemovedAcquisitions($nonprintResource, $keptIds);
    }

    // Returns ['delete' => bool, 'id' => string] so the caller can route to the right bucket
    private function updateExistingAcquisition(
        array $acqData,
        int   $totalQty,
        array &$masterlistInserts,
        array &$masterlistDeletes,
    ): array {
        $acquisition = NonprintAcquisition::findOrFail($acqData['id']);

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
            // Fall back to existing library if the form sent an empty value
            'library_id'        => $acqData['library_id']   ?: ($acquisition->library_id ?? null),
            'library_name'      => $acqData['library_name'] ?: ($acquisition->library_name ?? null),
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
        string $nonprintResourceId,
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

        NonprintAcquisition::create([
            'id'                => $acquisitionId,
            'nonprint_id'       => $nonprintResourceId,
            'library_id'        => $acqData['library_id']   ?: null,
            'library_name'      => $acqData['library_name'] ?: null,
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
                    'id'                      => (string) Str::uuid(),
                    'nonprint_acquisition_id' => $acquisitionId,
                    'status'                  => $statusName,
                ];
            }
        }

        return $acquisitionId;
    }

    // Diff old vs new per-status quantities and queue inserts or deletes accordingly
    private function prepareMasterlistChanges(
        NonprintAcquisition $acquisition,
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
                        'id'                      => (string) Str::uuid(),
                        'nonprint_acquisition_id' => $acquisition->id,
                        'status'                  => $statusName,
                    ];
                }
            } elseif ($diff < 0) {
                // Pick arbitrary rows to delete — the status is what matters, not which specific row
                $toDelete = NonprintMasterlist::where('nonprint_acquisition_id', $acquisition->id)
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
        if (!empty($inserts)) {
            // Batch in chunks of 500 to avoid hitting DB parameter limits
            foreach (array_chunk($inserts, 500) as $chunk) {
                NonprintMasterlist::insert($chunk);
            }
        }

        if (!empty($deletes)) {
            NonprintMasterlist::whereIn('id', $deletes)->delete();
        }
    }

    private function deleteZeroQuantityAcquisitions(array $ids): void
    {
        if (!empty($ids)) {
            // Masterlist first — FK constraint requires it before we can delete the acquisition
            NonprintMasterlist::whereIn('nonprint_acquisition_id', $ids)->delete();
            NonprintAcquisition::whereIn('id', $ids)->delete();
        }
    }

    private function deleteRemovedAcquisitions(NonprintResource $nonprintResource, array $keptIds): void
    {
        $existingIds = $nonprintResource->nonprintAcquisitions->pluck('id')->toArray();
        $toDelete    = array_diff($existingIds, $keptIds);

        if (!empty($toDelete)) {
            NonprintMasterlist::whereIn('nonprint_acquisition_id', $toDelete)->delete();
            NonprintAcquisition::whereIn('id', $toDelete)->delete();
        }
    }

    private function updateSearchVector(string $id): void
    {
        DB::statement('
            UPDATE nonprint_resources
            SET search_vector = build_nonprint_resource_search_vector(id)
            WHERE id = ?
        ', [$id]);
    }
}
