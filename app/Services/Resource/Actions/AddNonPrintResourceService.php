<?php

namespace App\Services\Resource\Actions;

use App\Models\District;
use App\Models\DivisionLibrary;
use App\Models\NonprintResource;
use App\Models\NonprintTitle;
use App\Models\RegionLibrary;
use App\Models\School;
use App\Models\SchoolLibrary;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AddNonPrintResourceService
{
    public function addNonPrintResource(array $data): ?string
    {
        $nonprintResourceId = null;

        DB::transaction(function () use ($data, &$nonprintResourceId) {
            $title = $this->createOrGetTitle($data['title']);

            $coverPath = null;
            if (isset($data['image'])) {
                $coverPath = $this->handleImageUpload($data['image'], $data['title']);
                // Generate a ≤20 KB thumbnail for fast table rendering
                $this->generateThumbnail($coverPath);
            }

            $resource = $this->createNonprintResource($data, $title->id, $coverPath);
            $nonprintResourceId = $resource->id;
        });

        // Rebuild outside the transaction so the committed row is visible to the index
        if ($nonprintResourceId) {
            $this->updateSearchVector($nonprintResourceId);
        }

        return $nonprintResourceId;
    }

    // Acquisitions are intentionally NOT changed on edit — only resource metadata
    public function updateNonPrintResource(NonprintResource $resource, array $data): void
    {
        DB::transaction(function () use ($resource, $data) {
            $title = $this->createOrGetTitle($data['title']);

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

            $uniquenessHash = $this->buildUniquenessHash($title->id, $data['type'], $data);

            $gradeLevelIds = ! empty($data['subject_grade_levels'])
                ? implode(',', array_unique($data['subject_grade_levels']))
                : null;

            $brandName = ! empty($data['brand'])
                ? ucwords(strtolower($data['brand']))
                : null;

            $resource->update([
                'nonprint_title_id' => $title->id,
                'nonprint_type_id' => $data['type'],
                'brand' => $brandName,
                'code' => $data['code'] ?? null,
                'version' => $data['version'] ?? null,
                'url' => $data['url'] ?? null,
                'size' => $data['size'] ?? null,
                'model' => $data['model'] ?? null,
                'subject_grade_level_ids' => $gradeLevelIds,
                'cover' => $coverPath,
                'uniqueness_hash' => $uniquenessHash,
            ]);
        });

        $this->updateSearchVector($resource->id);
    }

    private function createOrGetTitle(string $titleName): NonprintTitle
    {
        $normalizedTitle = ucwords(strtolower($titleName));

        return NonprintTitle::firstOrCreate(
            ['title' => $normalizedTitle],
            ['id' => (string) Str::uuid()]
        );
    }

    private function handleImageUpload($image, string $title): string
    {
        $baseFileName = Str::slug($title);
        $extension = $image->getClientOriginalExtension();
        $fileName = $baseFileName.'.'.$extension;
        $storagePath = 'nonprint_cover';
        $fullPath = $storagePath.'/'.$fileName;

        // Increment the suffix until we find a filename that doesn't exist
        $counter = 1;
        while (Storage::disk('public')->exists($fullPath)) {
            $fileName = $baseFileName.'_'.$counter.'.'.$extension;
            $fullPath = $storagePath.'/'.$fileName;
            $counter++;
        }

        $image->storeAs($storagePath, $fileName, 'public');

        return $fullPath;
    }

    /**
     * Derive the thumbnail storage path from a cover path.
     * e.g. "nonprint_cover/my-item.jpg" → "nonprint-thumbnails/my-item.jpg"
     * Returns null when $coverPath is null/empty.
     */
    public function thumbnailPathFromCover(?string $coverPath): ?string
    {
        if (! $coverPath) {
            return null;
        }

        return preg_replace('#^nonprint_cover/#', 'nonprint-thumbnails/', $coverPath);
    }

    /**
     * Generate a JPEG thumbnail kept at or below 20 KB.
     * Stored in nonprint-thumbnails/ with the same filename as the cover.
     * Uses GD (always available in PHP); falls back silently on failure
     * so a missing thumbnail never breaks the upload flow.
     */
    private function generateThumbnail(string $coverPath): void
    {
        try {
            $disk = Storage::disk('public');
            $thumbPath = $this->thumbnailPathFromCover($coverPath);
            $rawContent = $disk->get($coverPath);

            // Load into GD from the raw bytes — avoids needing the real filesystem path
            $srcImage = @imagecreatefromstring($rawContent);
            if (! $srcImage) {
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
            $quality = 75;

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

    // SHA-256 over all fields that make a resource unique — same title with different
    // type/brand/model/version is a different resource, not a duplicate
    private function buildUniquenessHash(string $titleId, string $typeId, array $data): string
    {
        $sglIds = ! empty($data['subject_grade_levels']) ? $data['subject_grade_levels'] : [];
        sort($sglIds);

        // __none__ sentinel keeps hashes distinct when optional fields are absent vs empty string
        $sentinel = '__none__';

        $parts = [
            'title_id' => $titleId,
            'type_id' => strtolower(trim($typeId)),
            'brand' => strtolower(trim($data['brand'] ?? '')) ?: $sentinel,
            'model' => strtolower(trim($data['model'] ?? '')) ?: $sentinel,
            'version' => strtolower(trim($data['version'] ?? '')) ?: $sentinel,
            'code' => strtolower(trim($data['code'] ?? '')) ?: $sentinel,
            'url' => strtolower(trim($data['url'] ?? '')) ?: $sentinel,
            'size' => strtolower(trim($data['size'] ?? '')) ?: $sentinel,
            'sgl_ids' => implode(',', $sglIds) ?: $sentinel,
        ];

        return hash('sha256', json_encode($parts, JSON_UNESCAPED_UNICODE));
    }

    // Try all three library tables — library_id could belong to any of them
    private function resolveLibraryName(?string $libraryId): string
    {
        if (! $libraryId) {
            return 'Unknown Library';
        }

        return SchoolLibrary::find($libraryId)?->library_name
            ?? DivisionLibrary::find($libraryId)?->library_name
            ?? RegionLibrary::find($libraryId)?->library_name
            ?? 'Unknown Library';
    }

    // Walk school → district → division to find which division should approve this request
    private function resolveDivisionId(string $stationId): ?string
    {
        $school = School::with('district.division')->find($stationId);

        return $school?->district?->division?->id ?? null;
    }

    private function createNonprintResource(array $data, string $titleId, ?string $coverPath): NonprintResource
    {
        $gradeLevelIds = ! empty($data['subject_grade_levels'])
            ? implode(',', array_unique($data['subject_grade_levels']))
            : null;

        $brandName = ! empty($data['brand'])
            ? ucwords(strtolower($data['brand']))
            : null;

        $uniquenessHash = $this->buildUniquenessHash($titleId, $data['type'], $data);

        $user = Auth::user();
        $level = $user->userType?->level ?? 0;

        // Division (3): auto-approve and skip the queue
        // School (1) or anything else: pending, needs division approval
        if ($level === 3) {
            $status = 1;
            $stationType = 'division';
            $approverStation = null;
        } else {
            $status = 0;
            $stationType = 'school';
            $approverStation = $this->resolveDivisionId($user->station_id);
        }

        $libraryId = $data['library_id'] ?? null;
        $libraryName = $this->resolveLibraryName($libraryId);

        try {
            return NonprintResource::firstOrCreate(
                ['uniqueness_hash' => $uniquenessHash],
                [
                    'id' => (string) Str::uuid(),
                    'nonprint_title_id' => $titleId,
                    'nonprint_type_id' => $data['type'],
                    'brand' => $brandName,
                    'code' => $data['code'] ?? null,
                    'version' => $data['version'] ?? null,
                    'url' => $data['url'] ?? null,
                    'size' => $data['size'] ?? null,
                    'model' => $data['model'] ?? null,
                    'subject_grade_level_ids' => $gradeLevelIds,
                    'library_id' => $libraryId,
                    'library_name' => $libraryName,
                    'cover' => $coverPath,
                    'status' => $status,
                    'station_type' => $stationType,
                    'station_id' => $user->station_id,
                    'encoded_by' => $user->id,
                    'approver_station' => $approverStation,
                ]
            );
        } catch (UniqueConstraintViolationException) {
            // Race condition — another request created the same hash between our check and insert
            return NonprintResource::where('uniqueness_hash', $uniquenessHash)->firstOrFail();
        }
    }

    private function updateSearchVector(string $nonprintResourceId): void
    {
        DB::statement('
            UPDATE nonprint_resources
            SET search_vector = build_nonprint_resource_search_vector(id)
            WHERE id = ?
        ', [$nonprintResourceId]);
    }
}
