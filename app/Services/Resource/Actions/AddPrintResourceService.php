<?php

namespace App\Services\Resource\Actions;

use App\Models\Author;
use App\Models\DivisionLibrary;
use App\Models\PrintAcquisition;
use App\Models\PrintMasterlist;
use App\Models\PrintResource;
use App\Models\PrintTitle;
use App\Models\RegionLibrary;
use App\Models\SchoolLibrary;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AddPrintResourceService
{
    // Add a new print resource with all related data
    public function addPrintResource(array $data): ? string
    {
        $printResourceId = null;

        DB::transaction(function () use ($data, &$printResourceId) {
            // Create or get title
            $title = $this->createOrGetTitle($data['title']);

            // Handle authors
            $authorIds = $this->handleAuthors($data['authors'] ?? null);
            if (!empty($authorIds)) {
                $title->authors()->syncWithoutDetaching($authorIds);
            }

            // Handle image upload
            $coverPath = null;
            if (isset($data['image'])) {
                $coverPath = $this->handleImageUpload($data['image'], $data['title']);
            }

            // Create print resource
            $printResource = $this->createPrintResource($data, $title->id, $coverPath);
            $printResourceId = $printResource->id;

            // Handle acquisitions
            $this->handleAcquisitions($data['acquisitions'], $printResource->id);
        });

        // Update search vector after transaction commits
        if ($printResourceId) {
            $this->updateSearchVector($printResourceId);
        }

        return $printResourceId;
    }

    // Create or retrieve existing title
    private function createOrGetTitle(string $titleName): PrintTitle
    {
        $normalizedTitle = ucwords(strtolower($titleName));

        return PrintTitle::firstOrCreate(
            ['title' => $normalizedTitle],
            ['id' => (string) Str::uuid()]
        );
    }

    // Handle author creation and retrieval
    private function handleAuthors(?string $authorsJson): array
    {
        $authorNames = json_decode($authorsJson, true) ?? [];

        if (empty($authorNames)) {
            return [];
        }

        $authorIds = [];
        $normalizedNames = array_map(fn($name) => ucwords(strtolower($name)), $authorNames);

        // Batch fetch existing authors
        $existingAuthors = Author::whereIn('author_name', $normalizedNames)
            ->get()
            ->keyBy('author_name');

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

        return $authorIds;
    }

    // Handle image upload
    private function handleImageUpload($image, string $title): ? string
    {
        $baseFileName = Str::slug($title);
        $extension = $image->getClientOriginalExtension();
        $fileName = $baseFileName . '.' . $extension;

        $storagePath = 'print_cover';
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

    // Resolve library_name from whichever table owns this library_id
    private function resolveLibraryName(string $libraryId): string
    {
        return SchoolLibrary::find($libraryId)?->library_name
            ?? DivisionLibrary::find($libraryId)?->library_name
            ?? RegionLibrary::find($libraryId)?->library_name
            ?? 'Unknown Library';
    }

    // Create print resource
    private function createPrintResource(array $data, string $titleId, ?string $coverPath): PrintResource
    {
        $gradeLevelIds = !empty($data['subject_grade_levels'])
            ? implode(',', $data['subject_grade_levels'])
            : null;

        $publisherName = !empty($data['publisher'])
            ? ucwords(strtolower($data['publisher']))
            : 'publisher';

        return PrintResource::create([
            'id' => (string) Str::uuid(),
            'print_title_id' => $titleId,
            'print_type_id' => $data['type'],
            'publisher' => $publisherName,
            'volume' => $data['volume'] ?? 'volume',
            'edition' => $data['edition'] ?? 'edition',
            'copyright' => $data['copyright'] ?? 0,
            'pages' => $data['pages'] ?? 0,
            'isbn' => $data['isbn'] ?? 'isbn',
            'subject_grade_level_ids' => $gradeLevelIds,
            'library_id' => $data['library_id'],
            'library_name' => $this->resolveLibraryName($data['library_id']),
            'cover' => $coverPath,
        ]);
    }

    // Handle acquisitions and masterlist creation
    private function handleAcquisitions(string $acquisitionsJson, string $printResourceId): void
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
            PrintAcquisition::create([
                'id' => $acquisitionId,
                'print_id' => $printResourceId,
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
                'remarks' => $acquisition['remarks'],
                'encoded_by' => $userId,
                'date_encoded' => $now,
            ]);

            // Prepare masterlist records for bulk insert
            foreach ($statusMap as $field => $statusName) {
                $qty = (int) ($acquisition[$field] ?? 0);

                for ($i = 0; $i < $qty; $i++) {
                    $masterlistInserts[] = [
                        'id' => (string) Str::uuid(),
                        'print_acquisition_id' => $acquisitionId,
                        'status' => $statusName
                    ];
                }
            }
        }

        // Bulk insert masterlist records (chunks to avoid max query size)
        if (!empty($masterlistInserts)) {
            foreach (array_chunk($masterlistInserts, 500) as $chunk) {
                PrintMasterlist::insert($chunk);
            }
        }
    }

    // Update search vector for the print resource
    private function updateSearchVector(string $printResourceId): void
    {
        DB::statement('
            UPDATE print_resources
            SET search_vector = build_print_resource_search_vector(id)
            WHERE id = ?
        ', [$printResourceId]);
    }
}
