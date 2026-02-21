<?php

namespace App\Services\Resource\Actions;

use App\Models\Author;
use App\Models\PrintResource;
use App\Models\PrintTitle;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AddPrintResourceService
{
    // -----------------------------------------------------------------------
    // PUBLIC API
    // -----------------------------------------------------------------------

    /**
     * Create a new pending print-resource request from a school user.
     * No acquisition data is attached at this stage.
     *
     * @param  array  $data  Validated form data (no 'acquisitions' key expected).
     * @return string|null   The new PrintResource UUID, or null on failure.
     */
    public function addPrintResource(array $data): ?string
    {
        $printResourceId = null;

        DB::transaction(function () use ($data, &$printResourceId) {
            // 1. Create or get title
            $title = $this->createOrGetTitle($data['title']);

            // 2. Handle authors
            $authorIds = $this->handleAuthors($data['authors'] ?? null);
            if (!empty($authorIds)) {
                $title->authors()->syncWithoutDetaching($authorIds);
            }

            // 3. Handle optional image upload
            $coverPath = null;
            if (isset($data['image'])) {
                $coverPath = $this->handleImageUpload($data['image'], $data['title']);
            }

            // 4. Create the resource (pending status, school station)
            $resource        = $this->createPrintResource($data, $title->id, $authorIds, $coverPath);
            $printResourceId = $resource->id;
        });

        // Rebuild search vector outside the transaction so the row is visible
        if ($printResourceId) {
            $this->updateSearchVector($printResourceId);
        }

        return $printResourceId;
    }

    /**
     * Update an existing pending print-resource request.
     * Only the resource's own fields (title, authors, metadata, cover) change.
     */
    public function updatePrintResource(PrintResource $resource, array $data): void
    {
        DB::transaction(function () use ($resource, $data) {
            // 1. Update or replace the title
            $title = $this->createOrGetTitle($data['title']);

            // 2. Sync authors on the (potentially new) title
            $authorIds = $this->handleAuthors($data['authors'] ?? null);
            $title->authors()->sync($authorIds);   // full sync for an edit

            // 3. Handle optional new image
            $coverPath = $resource->cover; // keep existing cover by default
            if (isset($data['image'])) {
                // Delete old file if it exists and differs
                if ($resource->cover && Storage::disk('public')->exists($resource->cover)) {
                    Storage::disk('public')->delete($resource->cover);
                }
                $coverPath = $this->handleImageUpload($data['image'], $data['title']);
            }

            // 4. Rebuild uniqueness hash with new values
            $uniquenessHash = $this->buildUniquenessHash($title->id, $data['type'], $authorIds, $data);

            $gradeLevelIds = !empty($data['subject_grade_levels'])
                ? implode(',', array_unique($data['subject_grade_levels']))
                : null;

            $publisherName = !empty($data['publisher'])
                ? ucwords(strtolower($data['publisher']))
                : null;

            $resource->update([
                'print_title_id'          => $title->id,
                'print_type_id'           => $data['type'],
                'publisher'               => $publisherName,
                'volume'                  => $data['volume']    ?? null,
                'edition'                 => $data['edition']   ?? null,
                'copyright'               => $data['copyright'] ?? null,
                'pages'                   => $data['pages']     ?? null,
                'isbn'                    => $data['isbn']      ?? null,
                'subject_grade_level_ids' => $gradeLevelIds,
                'cover'                   => $coverPath,
                'uniqueness_hash'         => $uniquenessHash,
            ]);
        });

        $this->updateSearchVector($resource->id);
    }

    // -----------------------------------------------------------------------
    // PRIVATE — core steps
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
     * Create (or retrieve an existing) print resource row.
     *
     * For school users this is always a PENDING request:
     *   status       = 0  (0 = pending, 1 = approved, 2 = rejected)
     *   station_type = 'school'
     *   station_id   = encoder's station_id
     *   encoded_by   = Auth user id
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

        $user = Auth::user();

        try {
            return PrintResource::firstOrCreate(
                ['uniqueness_hash' => $uniquenessHash],
                [
                    'id'                      => (string) Str::uuid(),
                    'print_title_id'          => $titleId,
                    'print_type_id'           => $data['type'],
                    'publisher'               => $publisherName,
                    'volume'                  => $data['volume']    ?? null,
                    'edition'                 => $data['edition']   ?? null,
                    'copyright'               => $data['copyright'] ?? null,
                    'pages'                   => $data['pages']     ?? null,
                    'isbn'                    => $data['isbn']      ?? null,
                    'subject_grade_level_ids' => $gradeLevelIds,
                    'cover'                   => $coverPath,
                    // ── Request / approval fields ──────────────────────────
                    'status'                  => 0,           // 0 = pending
                    'station_type'            => 'school',
                    'station_id'              => $user->station_id,
                    'encoded_by'              => $user->id,
                ]
            );
        } catch (UniqueConstraintViolationException) {
            return PrintResource::where('uniqueness_hash', $uniquenessHash)->firstOrFail();
        }
    }

    // -----------------------------------------------------------------------
    // PRIVATE — helpers
    // -----------------------------------------------------------------------

    private function updateSearchVector(string $printResourceId): void
    {
        DB::statement('
            UPDATE print_resources
            SET search_vector = build_print_resource_search_vector(id)
            WHERE id = ?
        ', [$printResourceId]);
    }
}
