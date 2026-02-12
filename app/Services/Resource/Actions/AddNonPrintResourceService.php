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

class AddNonPrintResourceService
{
    // Add a new non-print resource with all related data
    public function addNonPrintResource(array $data): ? string
    {
        $nonprintResourceId = null;

        DB::transaction(function () use ($data, &$nonprintResourceId) {
            // Create or get title
            $title = $this->createOrGetTitle($data['title']);

            // Handle image upload
            $coverPath = null;
            if (isset($data['image'])) {
                $coverPath = $this->handleImageUpload($data['image'], $data['title']);
            }

            // Create non-print resource
            $nonprintResource = $this->createNonprintResource($data, $title->id, $coverPath);
            $nonprintResourceId = $nonprintResource->id;

            // Handle acquisitions
            $this->handleAcquisitions($data['acquisitions'], $nonprintResource->id);
        });

        // Update search vector after transaction commits
        if ($nonprintResourceId) {
            $this->updateSearchVector($nonprintResourceId);
        }

        return $nonprintResourceId;
    }

    // Create or retrieve existing title
    private function createOrGetTitle(string $titleName): NonprintTitle
    {
        $normalizedTitle = ucwords(strtolower($titleName));

        return NonprintTitle::firstOrCreate(
            ['title' => $normalizedTitle],
            ['id' => (string) Str::uuid()]
        );
    }

    // Handle image upload
    private function handleImageUpload($image, string $title): string
    {
        $baseFileName = Str::slug($title);
        $extension = $image->getClientOriginalExtension();
        $fileName = $baseFileName . '.' . $extension;

        $storagePath = 'nonprint_cover';
        $fullPath = $storagePath . '/' . $fileName;

        // Check if file already exists, if so, append a counter
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

    // Create non-print resource
    private function createNonprintResource(array $data, string $titleId, ?string $coverPath): NonprintResource
    {
        $gradeLevelIds = !empty($data['subject_grade_levels'])
            ? implode(',', $data['subject_grade_levels'])
            : null;

        $brandName = !empty($data['brand'])
            ? ucwords(strtolower($data['brand']))
            : 'brand';

        return NonprintResource::create([
            'id' => (string) Str::uuid(),
            'nonprint_title_id' => $titleId,
            'nonprint_type_id' => $data['type'],
            'brand' => $brandName,
            'code' => $data['code'] ?? 'code',
            'version' => $data['version'] ?? 'version',
            'url' => $data['url'] ?? 'url',
            'size' => $data['size'] ?? 'size',
            'model' => $data['model'] ?? 'model',
            'subject_grade_level_ids' => $gradeLevelIds,
            'library_id' => $data['library_id'],
            'cover' => $coverPath,
        ]);
    }

    // Handle acquisitions and masterlist creation
    private function handleAcquisitions(string $acquisitionsJson, string $nonprintResourceId): void
    {
        $acquisitions = json_decode($acquisitionsJson, true);

        $statusMap = [
            'usable' => 'USABLE',
            'partially_damaged' => 'PARTIALLY DAMAGED',
            'damaged' => 'DAMAGED',
            'lost' => 'LOST',
            'condemnable' => 'CONDEMNABLE',
        ];

        $userId = Auth::id();
        $now = now();
        $masterlistInserts = [];

        foreach ($acquisitions as $acquisition) {
            $acquisitionId = (string) Str::uuid();

            // Create acquisition record
            NonprintAcquisition::create([
                'id' => $acquisitionId,
                'nonprint_id' => $nonprintResourceId,
                'source' => $acquisition['source'],
                'date_acquired' => $acquisition['date_acquired'],
                'cost' => $acquisition['cost'] !== '' ? $acquisition['cost'] : 0,
                'iar' => $acquisition['iar'] !== '' ? $acquisition['iar'] : 'iar',
                'usable' => $acquisition['usable'] !== '' ? (int)$acquisition['usable'] : 0,
                'partially_damaged' => $acquisition['partially_damaged'] !== '' ? (int)$acquisition['partially_damaged'] : 0,
                'damaged' => $acquisition['damaged'] !== '' ? (int)$acquisition['damaged'] : 0,
                'lost' => $acquisition['lost'] !== '' ? (int)$acquisition['lost'] : 0,
                'condemnable' => $acquisition['condemnable'] !== '' ? (int)$acquisition['condemnable'] : 0,
                'total_qty' => $acquisition['total_quantity'] !== '' ? (int)$acquisition['total_quantity'] : 0,
                'remarks' => $acquisition['remarks'] ?? 'remarks',
                'encoded_by' => $userId,
                'date_encoded' => $now,
            ]);

            // Prepare masterlist records for bulk insert
            foreach ($statusMap as $field => $statusName) {
                $qty = (int) ($acquisition[$field] ?? 0);

                for ($i = 0; $i < $qty; $i++) {
                    $masterlistInserts[] = [
                        'id' => (string) Str::uuid(),
                        'nonprint_acquisition_id' => $acquisitionId,
                        'status' => $statusName,
                    ];
                }
            }
        }

        // Bulk insert masterlist records (chunks to avoid max query size)
        if (!empty($masterlistInserts)) {
            foreach (array_chunk($masterlistInserts, 500) as $chunk) {
                NonprintMasterlist::insert($chunk);
            }
        }
    }

    // Update search vector for the non-print resource
    private function updateSearchVector(string $nonprintResourceId): void
    {
        DB::statement('
            UPDATE nonprint_resources
            SET search_vector = build_nonprint_resource_search_vector(id)
            WHERE id = ?
        ', [$nonprintResourceId]);
    }
}
