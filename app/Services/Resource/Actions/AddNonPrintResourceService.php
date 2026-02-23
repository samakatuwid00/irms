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
    // -----------------------------------------------------------------------
    // PUBLIC API
    // -----------------------------------------------------------------------

    /**
     * Create a new non-print resource.
     * - Level 1 (school):   status = 0 (pending), approver_station = division UUID
     * - Level 3 (division): status = 1 (approved), no approver_station needed
     *
     * @param  array  $data  Validated form data.
     * @return string|null   The new NonprintResource UUID, or null on failure.
     */
    public function addNonPrintResource(array $data): ?string
    {
        $nonprintResourceId = null;

        DB::transaction(function () use ($data, &$nonprintResourceId) {
            // 1. Create or get title
            $title = $this->createOrGetTitle($data['title']);

            // 2. Handle optional image upload
            $coverPath = null;
            if (isset($data['image'])) {
                $coverPath = $this->handleImageUpload($data['image'], $data['title']);
            }

            // 3. Create the resource
            $resource           = $this->createNonprintResource($data, $title->id, $coverPath);
            $nonprintResourceId = $resource->id;
        });

        if ($nonprintResourceId) {
            $this->updateSearchVector($nonprintResourceId);
        }

        return $nonprintResourceId;
    }

    /**
     * Update an existing non-print resource (pending school request).
     * Acquisitions are NOT changed on edit — only resource metadata.
     */
    public function updateNonPrintResource(NonprintResource $resource, array $data): void
    {
        DB::transaction(function () use ($resource, $data) {
            // 1. Update or replace the title
            $title = $this->createOrGetTitle($data['title']);

            // 2. Handle optional new image
            $coverPath = $resource->cover;
            if (isset($data['image'])) {
                if ($resource->cover && Storage::disk('public')->exists($resource->cover)) {
                    Storage::disk('public')->delete($resource->cover);
                }
                $coverPath = $this->handleImageUpload($data['image'], $data['title']);
            }

            // 3. Rebuild uniqueness hash with new values
            $uniquenessHash = $this->buildUniquenessHash($title->id, $data['type'], $data);

            $gradeLevelIds = !empty($data['subject_grade_levels'])
                ? implode(',', array_unique($data['subject_grade_levels']))
                : null;

            $brandName = !empty($data['brand'])
                ? ucwords(strtolower($data['brand']))
                : null;

            $resource->update([
                'nonprint_title_id'       => $title->id,
                'nonprint_type_id'        => $data['type'],
                'brand'                   => $brandName,
                'code'                    => $data['code']    ?? null,
                'version'                 => $data['version'] ?? null,
                'url'                     => $data['url']     ?? null,
                'size'                    => $data['size']    ?? null,
                'model'                   => $data['model']   ?? null,
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

    private function createOrGetTitle(string $titleName): NonprintTitle
    {
        $normalizedTitle = ucwords(strtolower($titleName));

        return NonprintTitle::firstOrCreate(
            ['title' => $normalizedTitle],
            ['id'    => (string) Str::uuid()]
        );
    }

    private function handleImageUpload($image, string $title): string
    {
        $baseFileName = Str::slug($title);
        $extension    = $image->getClientOriginalExtension();
        $fileName     = $baseFileName . '.' . $extension;
        $storagePath  = 'nonprint_cover';
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
     * Build a deterministic SHA-256 hash over all fields that define uniqueness
     * for a non-print resource (title + type + brand + model + version + code + url + size + sgls).
     */
    private function buildUniquenessHash(string $titleId, string $typeId, array $data): string
    {
        $sglIds = !empty($data['subject_grade_levels']) ? $data['subject_grade_levels'] : [];
        sort($sglIds);

        $sentinel = '__none__';

        $parts = [
            'title_id' => $titleId,
            'type_id'  => strtolower(trim($typeId)),
            'brand'    => strtolower(trim($data['brand']   ?? '')) ?: $sentinel,
            'model'    => strtolower(trim($data['model']   ?? '')) ?: $sentinel,
            'version'  => strtolower(trim($data['version'] ?? '')) ?: $sentinel,
            'code'     => strtolower(trim($data['code']    ?? '')) ?: $sentinel,
            'url'      => strtolower(trim($data['url']     ?? '')) ?: $sentinel,
            'size'     => strtolower(trim($data['size']    ?? '')) ?: $sentinel,
            'sgl_ids'  => implode(',', $sglIds) ?: $sentinel,
        ];

        return hash('sha256', json_encode($parts, JSON_UNESCAPED_UNICODE));
    }

    // Resolve library_name from whichever table owns this library_id
    private function resolveLibraryName(?string $libraryId): string
    {
        if (!$libraryId) return 'Unknown Library';

        return SchoolLibrary::find($libraryId)?->library_name
            ?? DivisionLibrary::find($libraryId)?->library_name
            ?? RegionLibrary::find($libraryId)?->library_name
            ?? 'Unknown Library';
    }

    /**
     * Resolve the division UUID for a school user.
     * School → District → Division.
     */
    private function resolveDivisionId(string $stationId): ?string
    {
        $school = School::with('district.division')->find($stationId);

        return $school?->district?->division?->id ?? null;
    }

    /**
     * Create a new NonprintResource row.
     *
     * Level 1 (school):
     *   status           = 0  (pending)
     *   station_type     = 'school'
     *   approver_station = division UUID
     *
     * Level 3 (division):
     *   status           = 1  (auto-approved)
     *   station_type     = 'division'
     *   approver_station = null
     */
    private function createNonprintResource(array $data, string $titleId, ?string $coverPath): NonprintResource
    {
        $gradeLevelIds = !empty($data['subject_grade_levels'])
            ? implode(',', array_unique($data['subject_grade_levels']))
            : null;

        $brandName = !empty($data['brand'])
            ? ucwords(strtolower($data['brand']))
            : null;

        $uniquenessHash = $this->buildUniquenessHash($titleId, $data['type'], $data);

        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        if ($level === 3) {
            $status          = 1;
            $stationType     = 'division';
            $approverStation = null;
        } else {
            $status          = 0;
            $stationType     = 'school';
            $approverStation = $this->resolveDivisionId($user->station_id);
        }

        $libraryId   = $data['library_id'] ?? null;
        $libraryName = $this->resolveLibraryName($libraryId);

        try {
            return NonprintResource::firstOrCreate(
                ['uniqueness_hash' => $uniquenessHash],
                [
                    'id'                      => (string) Str::uuid(),
                    'nonprint_title_id'       => $titleId,
                    'nonprint_type_id'        => $data['type'],
                    'brand'                   => $brandName,
                    'code'                    => $data['code']    ?? null,
                    'version'                 => $data['version'] ?? null,
                    'url'                     => $data['url']     ?? null,
                    'size'                    => $data['size']    ?? null,
                    'model'                   => $data['model']   ?? null,
                    'subject_grade_level_ids' => $gradeLevelIds,
                    'library_id'              => $libraryId,
                    'library_name'            => $libraryName,
                    'cover'                   => $coverPath,
                    'status'                  => $status,
                    'station_type'            => $stationType,
                    'station_id'              => $user->station_id,
                    'encoded_by'              => $user->id,
                    'approver_station'        => $approverStation,
                ]
            );
        } catch (UniqueConstraintViolationException) {
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
