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
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AddPrintResourceService
{
    public function addPrintResource(array $data): ?string
    {
        $printResourceId  = null;
        $acquisitionIds   = [];

        DB::transaction(function () use ($data, &$printResourceId, &$acquisitionIds) {
            // 1. Create or get title
            $title = $this->createOrGetTitle($data['title']);

            // 2. Handle authors
            $authorIds = $this->handleAuthors($data['authors'] ?? null);
            if (!empty($authorIds)) {
                $title->authors()->syncWithoutDetaching($authorIds);
            }

            // 3. Handle image upload
            $coverPath = null;
            if (isset($data['image'])) {
                $coverPath = $this->handleImageUpload($data['image'], $data['title']);
            }

            // 4. Create or retrieve the print resource
            $printResource   = $this->createPrintResource($data, $title->id, $authorIds, $coverPath);
            $printResourceId = $printResource->id;

            // 5. Handle acquisitions — collect IDs for post-commit vector rebuild
            $acquisitionIds = $this->handleAcquisitions($data['acquisitions'], $printResource->id);
        });

        // ---------------------------------------------------------------
        // Post-commit: rebuild search vectors AFTER the transaction so all
        // rows are visible to the PG functions.
        // ---------------------------------------------------------------
        if ($printResourceId) {
            $this->updateSearchVectors($printResourceId, $acquisitionIds);
        }

        return $printResourceId;
    }

    // -----------------------------------------------------------------------
    // Private — core steps
    // -----------------------------------------------------------------------

    private function createOrGetTitle(string $titleName): PrintTitle
    {
        $normalizedTitle = ucwords(strtolower($titleName));

        return PrintTitle::firstOrCreate(
            ['title' => $normalizedTitle],
            ['id'    => (string) Str::uuid()]
        );
    }

    private function handleAuthors(?string $authorsJson): array
    {
        $authorNames = json_decode($authorsJson, true) ?? [];

        if (empty($authorNames)) {
            return [];
        }

        $authorIds       = [];
        $normalizedNames = array_map(fn($name) => ucwords(strtolower($name)), $authorNames);

        $existingAuthors = Author::whereIn('author_name', $normalizedNames)
            ->get()
            ->keyBy('author_name');

        foreach ($normalizedNames as $name) {
            if ($existingAuthors->has($name)) {
                $authorIds[] = $existingAuthors->get($name)->id;
            } else {
                $author      = Author::create([
                    'id'          => (string) Str::uuid(),
                    'author_name' => $name,
                ]);
                $authorIds[] = $author->id;
            }
        }

        return $authorIds;
    }

    private function handleImageUpload($image, string $title): ?string
    {
        $baseFileName = Str::slug($title);
        $extension    = $image->getClientOriginalExtension();
        $fileName     = $baseFileName . '.' . $extension;
        $storagePath  = 'print_cover';
        $fullPath     = $storagePath . '/' . $fileName;

        $counter = 1;
        while (Storage::disk('public')->exists($fullPath)) {
            $fileName = $baseFileName . '_' . $counter . '.' . $extension;
            $fullPath = $storagePath . '/' . $fileName;
            $counter++;
        }

        $image->storeAs($storagePath, $fileName, 'public');

        return $fullPath;
    }

    /**
     * Build a deterministic SHA-256 hash over all fields that define uniqueness.
     * Sorted + normalised so order and casing never create phantom duplicates.
     */
    private function buildUniquenessHash(string $titleId, string $typeId, array $authorIds, array $data): string
    {
        $sglIds = !empty($data['subject_grade_levels']) ? $data['subject_grade_levels'] : [];
        sort($sglIds);

        $sortedAuthorIds = $authorIds;
        sort($sortedAuthorIds);

        $sentinel = '__none__';

        $parts = [
            'title_id'  => $titleId,
            'type_id'   => strtolower(trim($typeId)),
            'authors'   => implode('|', $sortedAuthorIds),
            'publisher' => strtolower(trim($data['publisher'] ?? '')) ?: $sentinel,
            'volume'    => strtolower(trim($data['volume']    ?? '')) ?: $sentinel,
            'edition'   => strtolower(trim($data['edition']   ?? '')) ?: $sentinel,
            'copyright' => (string) ($data['copyright'] ?? $sentinel),
            'pages'     => (string) ($data['pages']     ?? $sentinel),
            'isbn'      => strtolower(trim($data['isbn'] ?? '')) ?: $sentinel,
            'sgl_ids'   => implode(',', $sglIds) ?: $sentinel,
        ];

        return hash('sha256', json_encode($parts, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Create a print resource, or return the existing one if the same metadata
     * has already been recorded.
     *
     * FIX: do NOT include uniqueness_hash in the values array — Laravel merges
     * the lookup key into the INSERT automatically, so passing it twice causes
     * the values array to override the lookup and can silently skip the insert.
     */
    private function createPrintResource(array $data, string $titleId, array $authorIds, ?string $coverPath): PrintResource
    {
        $gradeLevelIds = !empty($data['subject_grade_levels'])
            ? implode(',', array_unique($data['subject_grade_levels']))
            : null;

        $publisherName = !empty($data['publisher'])
            ? ucwords(strtolower($data['publisher']))
            : null;

        $uniquenessHash = $this->buildUniquenessHash($titleId, $data['type'], $authorIds, $data);

        try {
            return PrintResource::firstOrCreate(
                // Lookup key — hits the unique index directly
                ['uniqueness_hash' => $uniquenessHash],
                // Values used ONLY when creating a new row
                // NOTE: do NOT repeat uniqueness_hash here — Laravel merges it in automatically
                [
                    'id'                      => (string) Str::uuid(),
                    'print_title_id'          => $titleId,
                    'print_type_id'           => $data['type'],
                    'publisher'               => $publisherName,
                    'volume'                  => $data['volume']    ?? 'volume',
                    'edition'                 => $data['edition']   ?? 'edition',
                    'copyright'               => $data['copyright'] ?? 'copyright',
                    'pages'                   => $data['pages']     ?? null,
                    'isbn'                    => $data['isbn']      ?? 'isbn',
                    'subject_grade_level_ids' => $gradeLevelIds,
                    'cover'                   => $coverPath,
                ]
            );
        } catch (UniqueConstraintViolationException) {
            // Concurrent request inserted the same hash between our SELECT and INSERT.
            return PrintResource::where('uniqueness_hash', $uniquenessHash)->firstOrFail();
        }
    }

    /**
     * Handle acquisitions — returns the list of new acquisition IDs so the
     * caller can rebuild their search vectors after the transaction commits.
     *
     * @return string[]
     */
    private function handleAcquisitions(string $acquisitionsJson, string $printResourceId): array
    {
        $acquisitions = json_decode($acquisitionsJson, true);

        $statusMap = [
            'usable'            => 'USABLE',
            'partially_damaged' => 'PARTIALLY DAMAGED',
            'damaged'           => 'DAMAGED',
            'lost'              => 'LOST',
            'condemnable'       => 'CONDEMNABLE',
        ];

        $userId            = Auth::id();
        $now               = now();
        $masterlistInserts = [];
        $acquisitionIds    = [];

        foreach ($acquisitions as $acquisition) {
            $acquisitionId = (string) Str::uuid();
            $libraryId     = $acquisition['library_id'];

            PrintAcquisition::create([
                'id'                => $acquisitionId,
                'print_id'          => $printResourceId,
                'library_id'        => $libraryId,
                'library_name'      => $this->resolveLibraryName($libraryId),
                'source'            => $acquisition['source'],
                'date_acquired'     => $acquisition['date_acquired'],
                'cost'              => $acquisition['cost'] !== '' ? $acquisition['cost'] : 0,
                'iar'               => $acquisition['iar'] !== '' ? $acquisition['iar'] : 'iar',
                'usable'            => $acquisition['usable'] !== '' ? (int) $acquisition['usable'] : 0,
                'partially_damaged' => $acquisition['partially_damaged'] !== '' ? (int) $acquisition['partially_damaged'] : 0,
                'damaged'           => $acquisition['damaged'] !== '' ? (int) $acquisition['damaged'] : 0,
                'lost'              => $acquisition['lost'] !== '' ? (int) $acquisition['lost'] : 0,
                'condemnable'       => $acquisition['condemnable'] !== '' ? (int) $acquisition['condemnable'] : 0,
                'total_qty'         => $acquisition['total_quantity'] !== '' ? (int) $acquisition['total_quantity'] : 0,
                'remarks'           => $acquisition['remarks'],
                'encoded_by'        => $userId,
                'date_encoded'      => $now,
            ]);

            $acquisitionIds[] = $acquisitionId;

            foreach ($statusMap as $field => $statusName) {
                $qty = (int) ($acquisition[$field] ?? 0);
                for ($i = 0; $i < $qty; $i++) {
                    $masterlistInserts[] = [
                        'id'                   => (string) Str::uuid(),
                        'print_acquisition_id' => $acquisitionId,
                        'status'               => $statusName,
                    ];
                }
            }
        }

        if (!empty($masterlistInserts)) {
            foreach (array_chunk($masterlistInserts, 500) as $chunk) {
                PrintMasterlist::insert($chunk);
            }
        }

        return $acquisitionIds;
    }

    // -----------------------------------------------------------------------
    // Private — helpers
    // -----------------------------------------------------------------------

    private function resolveLibraryName(string $libraryId): string
    {
        return SchoolLibrary::find($libraryId)?->library_name
            ?? DivisionLibrary::find($libraryId)?->library_name
            ?? RegionLibrary::find($libraryId)?->library_name
            ?? 'Unknown Library';
    }

    /**
     * Rebuild search vectors AFTER the transaction commits so all rows are
     * visible to the PostgreSQL functions.
     *
     * - print_resources vector : title + authors + isbn + publisher (no library)
     * - print_acquisitions vector : full metadata + library info per copy
     *
     * @param string   $printResourceId
     * @param string[] $acquisitionIds
     */
    private function updateSearchVectors(string $printResourceId, array $acquisitionIds): void
    {
        // Rebuild the resource-level vector
        DB::statement('
            UPDATE print_resources
            SET search_vector = build_print_resource_search_vector(id)
            WHERE id = ?
        ', [$printResourceId]);

        // Rebuild each acquisition vector — pass print_id + library_id directly
        // to avoid the chicken-and-egg problem in the PG function
        if (!empty($acquisitionIds)) {
            $placeholders = implode(',', array_fill(0, count($acquisitionIds), '?'));

            DB::statement("
                UPDATE print_acquisitions
                SET search_vector = build_print_acquisition_search_vector(print_id, library_id)
                WHERE id IN ({$placeholders})
            ", $acquisitionIds);
        }
    }
}
