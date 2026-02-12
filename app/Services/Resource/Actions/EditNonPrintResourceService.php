<?php

namespace App\Services\Resource\Actions;

use App\Models\NonprintAcquisition;
use App\Models\NonprintMasterlist;
use App\Models\NonprintResource;
use App\Models\NonprintTitle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EditNonPrintResourceService
{
    private const STATUS_MAP = [
        'usable' => 'USABLE',
        'partially_damaged' => 'PARTIALLY DAMAGED',
        'damaged' => 'DAMAGED',
        'lost' => 'LOST',
        'condemnable' => 'CONDEMNABLE',
    ];

    public function updateNonPrintResource(string $id, array $data): array
    {
        $shouldDeleteResource = false;
        $nonprintResource = null;

        DB::transaction(function () use ($id, $data, &$shouldDeleteResource, &$nonprintResource) {
            $nonprintResource = NonprintResource::findOrFail($id);

            // Step 1: Update title
            $title = $this->updateOrCreateTitle($data['title']);

            // Step 2: Handle image upload
            $coverPath = $this->handleImageUpload(
                $nonprintResource,
                $data['image'] ?? null,
                $data['title']
            );

            // Step 3: Update non-print resource
            $this->updateResourceData($nonprintResource, $title, $data, $coverPath);

            // Step 4: Update acquisitions
            $this->updateAcquisitions($nonprintResource, $data['acquisitions']);

            // Step 5: Check if resource should be deleted
            $nonprintResource->refresh();
            $remainingAcquisitions = $nonprintResource->nonprintAcquisitions()->count();

            if ($remainingAcquisitions === 0) {
                $this->deleteResourceWithCover($nonprintResource);
                $shouldDeleteResource = true;
                $nonprintResource = null;
            }
        });

        // Update search vector after transaction commits
        if (!$shouldDeleteResource) {
            $this->updateSearchVector($id);
        }

        return [
            'deleted' => $shouldDeleteResource,
            'resource' => $nonprintResource,
        ];
    }

    // Update or create a non-print title
    private function updateOrCreateTitle(string $titleName): NonprintTitle
    {
        $titleName = ucwords(strtolower($titleName));

        return NonprintTitle::firstOrCreate(
            ['title' => $titleName],
            ['id' => (string) Str::uuid()]
        );
    }

    // Handle image upload for non-print resource
    private function handleImageUpload(NonprintResource $nonprintResource, $image, string $title): ? string
    {
        $coverPath = $nonprintResource->cover;

        if ($image) {
            // Delete old cover if it exists
            if ($nonprintResource->cover && Storage::disk('public')->exists($nonprintResource->cover)) {
                Storage::disk('public')->delete($nonprintResource->cover);
            }

            // Upload new cover
            $coverPath = $this->storeImage($image, $title, 'nonprint_cover');
        }

        return $coverPath;
    }

    // Store image to disk
    private function storeImage($image, string $title, string $directory): string
    {
        $baseFileName = Str::slug($title);
        $extension = $image->getClientOriginalExtension();
        $fileName = $baseFileName . '.' . $extension;

        $storagePath = $directory;
        $fullPath = $storagePath . '/' . $fileName;

        // Check if file already exists, append counter if needed
        $counter = 1;
        while (Storage::disk('public')->exists($fullPath)) {
            $fileName = $baseFileName . '_' . $counter . '.' . $extension;
            $fullPath = $storagePath . '/' . $fileName;
            $counter++;
        }

        // Store the image
        $image->storeAs($storagePath, $fileName, 'public');

        return $fullPath;
    }

    // Update non-print resource data
    private function updateResourceData(
        NonprintResource $nonprintResource,
        NonprintTitle $title,
        array $data,
        ? string $coverPath
    ): void {
        $gradeLevelIds = !empty($data['subject_grade_levels'])
            ? implode(',', $data['subject_grade_levels'])
            : null;

        $brandName = !empty($data['brand'])
            ? ucwords(strtolower($data['brand']))
            : 'brand';

        $nonprintResource->update([
            'nonprint_title_id' => $title->id,
            'nonprint_type_id' => $data['type'],
            'brand' => $brandName,
            'code' => $data['code'] ?: 'code',
            'version' => $data['version'] ?: 'version',
            'model' => $data['model'] ?: 'model',
            'url' => $data['url'] ?: 'url',
            'size' => $data['size'] ?: 'size',
            'subject_grade_level_ids' => $gradeLevelIds,
            'library_id' => $data['library_id'],
            'cover' => $coverPath,
        ]);
    }

    // Update acquisitions for a non-print resource
    private function updateAcquisitions(NonprintResource $nonprintResource, string $acquisitionsJson): void
    {
        $acquisitions = json_decode($acquisitionsJson, true);

        $userId = Auth::id();
        $now = now();
        $submittedAcquisitionIds = [];
        $masterlistInserts = [];
        $masterlistDeletes = [];
        $acquisitionsToDeleteZeroQty = [];

        foreach ($acquisitions as $acquisitionData) {
            $totalQty = (int) ($acquisitionData['total_quantity'] ?? 0);

            if (!empty($acquisitionData['id'])) {
                // Update existing acquisition
                $result = $this->updateExistingAcquisition(
                    $acquisitionData,
                    $totalQty,
                    $masterlistInserts,
                    $masterlistDeletes,
                    $now
                );

                if ($result['delete']) {
                    $acquisitionsToDeleteZeroQty[] = $result['id'];
                } else {
                    $submittedAcquisitionIds[] = $result['id'];
                }
            } else {
                // Create new acquisition
                $acquisitionId = $this->createNewAcquisition(
                    $nonprintResource->id,
                    $acquisitionData,
                    $totalQty,
                    $userId,
                    $now,
                    $masterlistInserts
                );

                if ($acquisitionId) {
                    $submittedAcquisitionIds[] = $acquisitionId;
                }
            }
        }

        // Process bulk operations
        $this->processBulkMasterlistOperations($masterlistInserts, $masterlistDeletes);
        $this->deleteZeroQuantityAcquisitions($acquisitionsToDeleteZeroQty);
        $this->deleteRemovedAcquisitions($nonprintResource, $submittedAcquisitionIds);
    }

    // Update an existing acquisition
    private function updateExistingAcquisition(
        array $acquisitionData,
        int $totalQty,
        array &$masterlistInserts,
        array &$masterlistDeletes,
        $now
    ): array {
        $acquisition = NonprintAcquisition::findOrFail($acquisitionData['id']);

        // If total quantity is zero, mark for deletion
        if ($totalQty === 0) {
            return ['delete' => true, 'id' => $acquisition->id];
        }

        // Store old quantities
        $oldQuantities = [
            'usable' => $acquisition->usable,
            'partially_damaged' => $acquisition->partially_damaged,
            'damaged' => $acquisition->damaged,
            'lost' => $acquisition->lost,
            'condemnable' => $acquisition->condemnable,
        ];

        $newQuantities = [
            'usable' => (int) ($acquisitionData['usable'] ?? 0),
            'partially_damaged' => (int) ($acquisitionData['partially_damaged'] ?? 0),
            'damaged' => (int) ($acquisitionData['damaged'] ?? 0),
            'lost' => (int) ($acquisitionData['lost'] ?? 0),
            'condemnable' => (int) ($acquisitionData['condemnable'] ?? 0),
        ];

        // Update acquisition
        $acquisition->update([
            'source' => $acquisitionData['source'],
            'date_acquired' => $acquisitionData['date_acquired'],
            'cost' => $acquisitionData['cost'] ?: 0,
            'iar' => $acquisitionData['iar'] ?: 'iar',
            'usable' => $newQuantities['usable'],
            'partially_damaged' => $newQuantities['partially_damaged'],
            'damaged' => $newQuantities['damaged'],
            'lost' => $newQuantities['lost'],
            'condemnable' => $newQuantities['condemnable'],
            'total_qty' => $totalQty,
            'remarks' => $acquisitionData['remarks'] ?? 'remarks',
        ]);

        // Prepare masterlist changes
        $this->prepareMasterlistChanges(
            $acquisition,
            $oldQuantities,
            $newQuantities,
            $masterlistInserts,
            $masterlistDeletes,
            $now
        );

        return ['delete' => false, 'id' => $acquisition->id];
    }

    // Create a new acquisition
    private function createNewAcquisition(
        string $nonprintResourceId,
        array $acquisitionData,
        int $totalQty,
        $userId,
        $now,
        array &$masterlistInserts
    ): ?string {
        // Skip if total quantity is zero
        if ($totalQty === 0) {
            return null;
        }

        $acquisitionId = (string) Str::uuid();

        NonprintAcquisition::create([
            'id' => $acquisitionId,
            'nonprint_id' => $nonprintResourceId,
            'source' => $acquisitionData['source'],
            'date_acquired' => $acquisitionData['date_acquired'],
            'cost' => $acquisitionData['cost'] ?: 0,
            'iar' => $acquisitionData['iar'] ?: 'iar',
            'usable' => (int) ($acquisitionData['usable'] ?? 0),
            'partially_damaged' => (int) ($acquisitionData['partially_damaged'] ?? 0),
            'damaged' => (int) ($acquisitionData['damaged'] ?? 0),
            'lost' => (int) ($acquisitionData['lost'] ?? 0),
            'condemnable' => (int) ($acquisitionData['condemnable'] ?? 0),
            'total_qty' => $totalQty,
            'remarks' => $acquisitionData['remarks'] ?? 'remarks',
            'encoded_by' => $userId,
            'date_encoded' => $now,
        ]);

        // Prepare masterlist entries
        foreach (self::STATUS_MAP as $field => $statusName) {
            $qty = (int) ($acquisitionData[$field] ?? 0);

            for ($i = 0; $i < $qty; $i++) {
                $masterlistInserts[] = [
                    'id' => (string) Str::uuid(),
                    'nonprint_acquisition_id' => $acquisitionId,
                    'status' => $statusName,
                ];
            }
        }

        return $acquisitionId;
    }

    // Prepare masterlist changes for batch processing
    private function prepareMasterlistChanges(
        NonprintAcquisition $acquisition,
        array $oldQuantities,
        array $newQuantities,
        array &$masterlistInserts,
        array &$masterlistDeletes,
    ): void {
        foreach (self::STATUS_MAP as $field => $statusName) {
            $oldQty = (int) $oldQuantities[$field];
            $newQty = (int) $newQuantities[$field];
            $diff = $newQty - $oldQty;

            if ($diff > 0) {
                // Prepare new entries for bulk insert
                for ($i = 0; $i < $diff; $i++) {
                    $masterlistInserts[] = [
                        'id' => (string) Str::uuid(),
                        'nonprint_acquisition_id' => $acquisition->id,
                        'status' => $statusName,
                    ];
                }
            } elseif ($diff < 0) {
                // Prepare entries for bulk delete
                $entriesToDelete = NonprintMasterlist::where('nonprint_acquisition_id', $acquisition->id)
                    ->where('status', $statusName)
                    ->limit(abs($diff))
                    ->pluck('id')
                    ->toArray();

                $masterlistDeletes = array_merge($masterlistDeletes, $entriesToDelete);
            }
        }
    }

    // Process bulk masterlist operations
    private function processBulkMasterlistOperations(array $masterlistInserts, array $masterlistDeletes): void
    {
        // Bulk inserts
        if (!empty($masterlistInserts)) {
            foreach (array_chunk($masterlistInserts, 500) as $chunk) {
                NonprintMasterlist::insert($chunk);
            }
        }

        // Bulk deletes
        if (!empty($masterlistDeletes)) {
            NonprintMasterlist::whereIn('id', $masterlistDeletes)->delete();
        }
    }

    // Delete acquisitions with zero quantity
    private function deleteZeroQuantityAcquisitions(array $acquisitionIds): void
    {
        if (!empty($acquisitionIds)) {
            NonprintMasterlist::whereIn('nonprint_acquisition_id', $acquisitionIds)->delete();
            NonprintAcquisition::whereIn('id', $acquisitionIds)->delete();
        }
    }

    // Delete acquisitions that were removed from the form
    private function deleteRemovedAcquisitions(NonprintResource $nonprintResource, array $submittedIds): void
    {
        $existingIds = $nonprintResource->nonprintAcquisitions->pluck('id')->toArray();
        $idsToDelete = array_diff($existingIds, $submittedIds);

        if (!empty($idsToDelete)) {
            NonprintMasterlist::whereIn('nonprint_acquisition_id', $idsToDelete)->delete();
            NonprintAcquisition::whereIn('id', $idsToDelete)->delete();
        }
    }

    // Delete resource with its cover image
    private function deleteResourceWithCover(NonprintResource $nonprintResource): void
    {
        if ($nonprintResource->cover && Storage::disk('public')->exists($nonprintResource->cover)) {
            Storage::disk('public')->delete($nonprintResource->cover);
        }

        $nonprintResource->delete();
    }

    // Update search vector for the resource
    private function updateSearchVector(string $id): void
    {
        DB::statement('
            UPDATE nonprint_resources
            SET search_vector = build_nonprint_resource_search_vector(id)
            WHERE id = ?
        ', [$id]);
    }
}
