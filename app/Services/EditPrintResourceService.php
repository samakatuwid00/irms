<?php

namespace App\Services;

use App\Models\Author;
use App\Models\PrintAcquisition;
use App\Models\PrintMasterlist;
use App\Models\PrintResource;
use App\Models\PrintTitle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EditPrintResourceService
{
    private const STATUS_MAP = [
        'usable' => 'USABLE',
        'partially_damaged' => 'PARTIALLY DAMAGED',
        'damaged' => 'DAMAGED',
        'lost' => 'LOST',
        'condemnable' => 'CONDEMNABLE',
    ];

    public function updatePrintResource(string $id, array $data): array
    {
        $shouldDeleteResource = false;
        $printResource = null;

        DB::transaction(function () use ($id, $data, &$shouldDeleteResource, &$printResource) {
            $printResource = PrintResource::findOrFail($id);

            // Step 1: Update title
            $title = $this->updateOrCreateTitle($data['title']);

            // Step 2: Update authors
            $this->updateAuthors($title, $data['authors'] ?? null);

            // Step 3: Handle image upload
            $coverPath = $this->handleImageUpload(
                $printResource,
                $data['image'] ?? null,
                $data['title']
            );

            // Step 4: Update print resource
            $this->updateResourceData($printResource, $title, $data, $coverPath);

            // Step 5: Update acquisitions
            $this->updateAcquisitions($printResource, $data['acquisitions']);

            // Step 6: Check if resource should be deleted
            $printResource->refresh();
            $remainingAcquisitions = $printResource->printAcquisitions()->count();

            if ($remainingAcquisitions === 0) {
                $this->deleteResourceWithCover($printResource);
                $shouldDeleteResource = true;
                $printResource = null;
            }
        });

        // Update search vector after transaction commits
        if (!$shouldDeleteResource) {
            $this->updateSearchVector($id);
        }

        return [
            'deleted' => $shouldDeleteResource,
            'resource' => $printResource,
        ];
    }

    // Update or create a print title
    private function updateOrCreateTitle(string $titleName): PrintTitle
    {
        $titleName = ucwords(strtolower($titleName));

        return PrintTitle::firstOrCreate(
            ['title' => $titleName],
            ['id' => (string) Str::uuid()]
        );
    }

    // Update authors for a title
    private function updateAuthors(PrintTitle $title, ?string $authorsJson): void
    {
        $authorNames = json_decode($authorsJson, true) ?? [];

        if (empty($authorNames)) {
            $title->authors()->detach();
            return;
        }

        // Normalize author names
        $normalizedNames = array_map(
            fn($name) => ucwords(strtolower($name)),
            $authorNames
        );

        // Batch fetch existing authors
        $existingAuthors = Author::whereIn('author_name', $normalizedNames)
            ->get()
            ->keyBy('author_name');

        $authorIds = [];

        foreach ($normalizedNames as $name) {
            if ($existingAuthors->has($name)) {
                $authorIds[] = $existingAuthors->get($name)->id;
            } else {
                // Create new author
                $author = Author::create([
                    'id' => (string) Str::uuid(),
                    'author_name' => $name,
                ]);
                $authorIds[] = $author->id;
            }
        }

        // Sync authors with title
        $title->authors()->sync($authorIds);
    }

    // Handle image upload for print resource
    private function handleImageUpload(PrintResource $printResource, $image, string $title): ? string
    {
        $coverPath = $printResource->cover;

        if ($image) {
            // Delete old cover if it exists
            if ($printResource->cover && Storage::disk('public')->exists($printResource->cover)) {
                Storage::disk('public')->delete($printResource->cover);
            }

            // Upload new cover
            $coverPath = $this->storeImage($image, $title, 'print_cover');
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

    // Update print resource data
    private function updateResourceData(
        PrintResource $printResource,
        PrintTitle $title,
        array $data,
        ?string $coverPath
    ): void {
        $gradeLevelIds = !empty($data['subject_grade_levels'])
            ? implode(',', $data['subject_grade_levels'])
            : null;

        $publisherName = !empty($data['publisher'])
            ? ucwords(strtolower($data['publisher']))
            : 'publisher';

        $printResource->update([
            'print_title_id' => $title->id,
            'print_type_id' => $data['type'],
            'publisher' => $publisherName,
            'volume' => $data['volume'] ?: 'volume',
            'edition' => $data['edition'] ?: 'edition',
            'copyright' => $data['copyright'] ?: 0,
            'pages' => $data['pages'] ?: 0,
            'isbn' => $data['isbn'] ?: 'isbn',
            'subject_grade_level_ids' => $gradeLevelIds,
            'library_id' => $data['library_id'],
            'cover' => $coverPath,
        ]);
    }

    // Update acquisitions for a print resource
    private function updateAcquisitions(PrintResource $printResource, string $acquisitionsJson): void
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
                    $printResource->id,
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
        $this->deleteRemovedAcquisitions($printResource, $submittedAcquisitionIds);
    }

    // Update an existing acquisition
    private function updateExistingAcquisition(
        array $acquisitionData,
        int $totalQty,
        array &$masterlistInserts,
        array &$masterlistDeletes,
        $now
    ): array {
        $acquisition = PrintAcquisition::findOrFail($acquisitionData['id']);

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
        string $printResourceId,
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

        PrintAcquisition::create([
            'id' => $acquisitionId,
            'print_id' => $printResourceId,
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
                    'print_acquisition_id' => $acquisitionId,
                    'status' => $statusName
                ];
            }
        }

        return $acquisitionId;
    }

    // Prepare masterlist changes for batch processing
    private function prepareMasterlistChanges(
        PrintAcquisition $acquisition,
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
                        'print_acquisition_id' => $acquisition->id,
                        'status' => $statusName,
                    ];
                }
            } elseif ($diff < 0) {
                // Prepare entries for bulk delete
                $entriesToDelete = PrintMasterlist::where('print_acquisition_id', $acquisition->id)
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
                PrintMasterlist::insert($chunk);
            }
        }

        // Bulk deletes
        if (!empty($masterlistDeletes)) {
            PrintMasterlist::whereIn('id', $masterlistDeletes)->delete();
        }
    }

    // Delete acquisitions with zero quantity
    private function deleteZeroQuantityAcquisitions(array $acquisitionIds): void
    {
        if (!empty($acquisitionIds)) {
            PrintMasterlist::whereIn('print_acquisition_id', $acquisitionIds)->delete();
            PrintAcquisition::whereIn('id', $acquisitionIds)->delete();
        }
    }

    // Delete acquisitions that were removed from the form
    private function deleteRemovedAcquisitions(PrintResource $printResource, array $submittedIds): void
    {
        $existingIds = $printResource->printAcquisitions->pluck('id')->toArray();
        $idsToDelete = array_diff($existingIds, $submittedIds);

        if (!empty($idsToDelete)) {
            PrintMasterlist::whereIn('print_acquisition_id', $idsToDelete)->delete();
            PrintAcquisition::whereIn('id', $idsToDelete)->delete();
        }
    }

    // Delete resource with its cover image
    private function deleteResourceWithCover(PrintResource $printResource): void
    {
        if ($printResource->cover && Storage::disk('public')->exists($printResource->cover)) {
            Storage::disk('public')->delete($printResource->cover);
        }

        $printResource->delete();
    }

    // Update search vector for the resource
    private function updateSearchVector(string $id): void
    {
        DB::statement('
            UPDATE print_resources
            SET search_vector = build_print_resource_search_vector(id)
            WHERE id = ?
        ', [$id]);
    }
}
