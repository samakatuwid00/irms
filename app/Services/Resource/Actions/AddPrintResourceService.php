<?php

namespace App\Services\Resource\Actions;

use App\Models\Author;
use App\Models\District;
use App\Models\PrintResource;
use App\Models\PrintTitle;
use App\Models\School;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AddPrintResourceService
{
    public function addPrintResource(array $data): ?string
    {
        $printResourceId = null;

        DB::transaction(function () use ($data, &$printResourceId) {
            $title = $this->createOrGetTitle($data['title']);

            $authorIds = $this->handleAuthors($data['authors'] ?? null);
            if (!empty($authorIds)) {
                // syncWithoutDetaching so we don't blow away authors added by other resources
                $title->authors()->syncWithoutDetaching($authorIds);
            }

            $coverPath = null;
            if (isset($data['image'])) {
                $coverPath = $this->handleImageUpload($data['image'], $data['title']);
                // Generate a ≤20 KB thumbnail for fast table rendering
                $this->generateThumbnail($coverPath);
            }

            $resource        = $this->createPrintResource($data, $title->id, $authorIds, $coverPath);
            $printResourceId = $resource->id;
        });

        // Rebuild outside the transaction so the committed row is visible to the index
        if ($printResourceId) {
            $this->updateSearchVector($printResourceId);
        }

        return $printResourceId;
    }

    // Acquisitions are intentionally NOT changed on edit — only resource metadata
    public function updatePrintResource(PrintResource $resource, array $data): void
    {
        DB::transaction(function () use ($resource, $data) {
            $title = $this->createOrGetTitle($data['title']);

            $authorIds = $this->handleAuthors($data['authors'] ?? null);
            // Full sync on edit — the user is explicitly choosing the final author list
            $title->authors()->sync($authorIds);

            $coverPath = $resource->cover;
            if (isset($data['image'])) {
                // Delete old cover + its thumbnail before replacing — otherwise orphaned on disk
                if ($resource->cover && Storage::disk('public')->exists($resource->cover)) {
                    Storage::disk('public')->delete($resource->cover);
                }
                $oldThumb = $this->thumbnailPathFromCover($resource->cover);
                if ($oldThumb && Storage::disk('public')->exists($oldThumb)) {
                    Storage::disk('public')->delete($oldThumb);
                }

                $coverPath = $this->handleImageUpload($data['image'], $data['title']);
                $this->generateThumbnail($coverPath);
            }

            $uniquenessHash = $this->buildUniquenessHash($title->id, $data['type'], $authorIds, $data);

            $gradeLevelIds = !empty($data['subject_grade_levels'])
                ? implode(',', array_unique($data['subject_grade_levels']))
                : null;

            $publisherName = !empty($data['publisher'])
                ? ucwords(strtolower($data['publisher']))
                : null;

            $updateData = [
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
            ];

            if (array_key_exists('verified', $data)) {
                $isVerified = !empty($data['verified']);
                $updateData['verified']    = $isVerified;
                $updateData['verified_by'] = $isVerified ? Auth::id() : null;
                $updateData['verified_at'] = $isVerified ? now() : null;
            }

            $resource->update($updateData);
        });

        $this->updateSearchVector($resource->id);
    }

    // ── THUMBNAIL HELPERS ────────────────────────────────────────────────

    /**
     * Derive the thumbnail storage path from a cover path.
     * e.g. "print_cover/my-book.jpg" → "print-thumbnails/my-book.jpg"
     * Returns null when $coverPath is null/empty.
     */
    public function thumbnailPathFromCover(?string $coverPath): ?string
    {
        if (!$coverPath) {
            return null;
        }

        return preg_replace('#^print_cover/#', 'print-thumbnails/', $coverPath);
    }

    /**
     * Generate a JPEG thumbnail kept at or below 20 KB.
     * Stored in print-thumbnails/ with the same filename as the cover.
     * Uses GD (always available in PHP); falls back silently on failure
     * so a missing thumbnail never breaks the upload flow.
     */
    private function generateThumbnail(string $coverPath): void
    {
        try {
            $disk       = Storage::disk('public');
            $thumbPath  = $this->thumbnailPathFromCover($coverPath);
            $rawContent = $disk->get($coverPath);

            // Load into GD from the raw bytes — avoids needing the real filesystem path
            $srcImage = @imagecreatefromstring($rawContent);
            if (!$srcImage) {
                return;
            }

            $srcW = imagesx($srcImage);
            $srcH = imagesy($srcImage);

            // Scale so the longest edge is ≤ 200 px — plenty for a table thumbnail
            $maxSide = 200;
            if ($srcW >= $srcH) {
                $thumbW = $maxSide;
                $thumbH = (int) round($srcH * ($maxSide / $srcW));
            } else {
                $thumbH = $maxSide;
                $thumbW = (int) round($srcW * ($maxSide / $srcH));
            }

            $thumbW = max(1, $thumbW);
            $thumbH = max(1, $thumbH);

            $thumbImage = imagecreatetruecolor($thumbW, $thumbH);

            // Preserve transparency for PNG sources
            imagealphablending($thumbImage, false);
            imagesavealpha($thumbImage, true);
            $transparent = imagecolorallocatealpha($thumbImage, 255, 255, 255, 127);
            imagefilledrectangle($thumbImage, 0, 0, $thumbW, $thumbH, $transparent);
            imagealphablending($thumbImage, true);

            imagecopyresampled($thumbImage, $srcImage, 0, 0, 0, 0, $thumbW, $thumbH, $srcW, $srcH);
            imagedestroy($srcImage);

            // Compress iteratively until ≤ 20 KB, starting at quality 75
            $targetBytes = 20 * 1024;
            $quality     = 75;

            do {
                ob_start();
                imagejpeg($thumbImage, null, $quality);
                $jpegData = ob_get_clean();
                $quality -= 5;
            } while (strlen($jpegData) > $targetBytes && $quality >= 10);

            imagedestroy($thumbImage);

            $disk->put($thumbPath, $jpegData);

        } catch (\Throwable) {
            // Thumbnail generation is best-effort; never fail the upload
        }
    }

    // ── PRIVATE HELPERS ──────────────────────────────────────────────────

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

        // Batch-load existing authors to avoid N+1 inserts
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

        // Increment the suffix until we find a filename that doesn't exist
        $counter = 1;
        while (Storage::disk('public')->exists($fullPath)) {
            $fileName = $baseFileName . '_' . $counter . '.' . $extension;
            $fullPath = $storagePath . '/' . $fileName;
            $counter++;
        }

        $image->storeAs($storagePath, $fileName, 'public');

        return $fullPath;
    }

    // SHA-256 over all fields that make a resource unique — same title with different
    // publisher/edition/isbn is a different resource, not a duplicate
    private function buildUniquenessHash(string $titleId, string $typeId, array $authorIds, array $data): string
    {
        $sglIds = !empty($data['subject_grade_levels']) ? $data['subject_grade_levels'] : [];
        sort($sglIds);

        // Sort author IDs so order of entry doesn't affect the hash
        $sortedAuthorIds = $authorIds;
        sort($sortedAuthorIds);

        // __none__ sentinel keeps hashes distinct when optional fields are absent vs empty string
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

    // Walk school → district → division to find which division should approve this request
    private function resolveDivisionId(string $stationId): ?string
    {
        $school = School::with('district.division')->find($stationId);

        return $school?->district?->division?->id ?? null;
    }

    private function createPrintResource(array $data, string $titleId, array $authorIds, ?string $coverPath): PrintResource
    {
        $gradeLevelIds = !empty($data['subject_grade_levels'])
            ? implode(',', array_unique($data['subject_grade_levels']))
            : null;

        $publisherName = !empty($data['publisher'])
            ? ucwords(strtolower($data['publisher']))
            : null;

        $uniquenessHash = $this->buildUniquenessHash($titleId, $data['type'], $authorIds, $data);

        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        // Division (3): auto-approve and skip the queue
        // School (1) or anything else: pending, needs division approval
        if ($level === 3) {
            $status          = 1;
            $stationType     = 'division';
            $approverStation = null;
        } else {
            $status          = 0;
            $stationType     = 'school';
            $approverStation = $this->resolveDivisionId($user->station_id);
        }

        try {
            return PrintResource::firstOrCreate(
                ['uniqueness_hash' => $uniquenessHash],
                [
                    'id'                      => (string) Str::uuid(),
                    'print_title_id'          => $titleId,
                    'print_type_id'           => $data['type'],
                    'publisher'               => $publisherName,
                    'volume'                  => $data['volume']    ?? 'No Volume',
                    'edition'                 => $data['edition']   ?? 'No Edition',
                    'copyright'               => $data['copyright'] ?? 'No Copyright',
                    'pages'                   => $data['pages']     ?? null,
                    'isbn'                    => $data['isbn']      ?? 'No ISBN',
                    'subject_grade_level_ids' => $gradeLevelIds,
                    'cover'                   => $coverPath,
                    'status'                  => $status,
                    'station_type'            => $stationType,
                    'station_id'              => $user->station_id,
                    'encoded_by'              => $user->id,
                    'approver_station'        => $approverStation,
                ]
            );
        } catch (UniqueConstraintViolationException) {
            // Race condition — another request created the same hash between our check and insert
            return PrintResource::where('uniqueness_hash', $uniquenessHash)->firstOrFail();
        }
    }

    private function updateSearchVector(string $printResourceId): void
    {
        DB::statement('
            UPDATE print_resources
            SET search_vector = build_print_resource_search_vector(id)
            WHERE id = ?
        ', [$printResourceId]);
    }
}