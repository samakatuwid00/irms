<?php

namespace App\Services\Resource\Exports;

use App\Models\PrintResource;
use App\Models\School;
use App\Models\District;
use App\Models\Division;
use App\Models\SchoolLibrary;
use App\Models\DivisionLibrary;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ExportPrintResourceService
{
    private const CACHE_TTL_LIBRARIES = 1800;
    private const CACHE_TTL_HIERARCHY = 7200;

    public const LEVEL_SCHOOL   = 1;
    public const LEVEL_DISTRICT = 2;
    public const LEVEL_DIVISION = 3;
    public const LEVEL_REGION   = 4;

    // No pagination — exports dump everything matching the current filter.
    // Returns an array: ['resources' => Collection, 'libraryIds' => Collection]
    // so the caller can use scopedQuantities() to show only this library's counts.
    public function getExportData(Request $request, int $level, string $stationId): array
    {
        $libraryIds = $this->getLibraryIds($request, $level, $stationId);

        if ($libraryIds->isEmpty()) {
            return ['resources' => collect(), 'libraryIds' => collect()];
        }

        // library_id lives on print_acquisitions, not on the resource itself —
        // scope via whereHas so only resources held by these libraries are returned.
        // Eager-load printAcquisitions WITHOUT a scope — scopedQuantities() on the
        // model will filter to the right library IDs at display time.
        $query = PrintResource::with(['printTitle.authors', 'type', 'printAcquisitions'])
            ->whereHas('printAcquisitions', function ($q) use ($libraryIds) {
                $q->whereIn('library_id', $libraryIds->toArray());
            });

        $this->applySearch($query, $this->getSearchParam($request, $level));

        $resources = $query->get();

        $resources = $resources->sortByDesc(function ($resource) use ($libraryIds) {
            return $resource->printAcquisitions
                ->whereIn('library_id', $libraryIds)
                ->pluck('date_acquired')
                ->filter()
                ->max();
        })->values();

        return ['resources' => $resources, 'libraryIds' => $libraryIds];
    }

    private function getSearchParam(Request $request, int $level): string
    {
        if ($level === self::LEVEL_REGION && $request->input('tab') === 'library-hub') {
            return (string) $request->input('hub_search', '');
        }

        // Level 1 division tab uses 'division_search' to avoid colliding with
        // the school tab's 'search' param — mirror the same logic as the list view.
        if ($level === self::LEVEL_SCHOOL && $request->input('tab') === 'division') {
            return (string) $request->input('division_search', '');
        }

        if ($level === self::LEVEL_DIVISION) {
            // Division has two separate search boxes — one for its own tab, one for the school tab
            return $request->has('district') || $request->has('school')
                ? (string) $request->input('school_search', '')
                : (string) $request->input('division_search', '');
        }

        return (string) $request->input('search', '');
    }

    private function getLibraryIds(Request $request, int $level, string $stationId): Collection
    {
        return match($level) {
            self::LEVEL_SCHOOL   => $this->getLevel1LibraryIds($request, $stationId),
            self::LEVEL_DISTRICT => $this->getLevel2LibraryIds($request, $stationId),
            self::LEVEL_DIVISION => $this->getLevel3LibraryIds($request, $stationId),
            self::LEVEL_REGION   => $this->getLevel4LibraryIds($request, $stationId),
            default              => collect(),
        };
    }

    private function getLevel1LibraryIds(Request $request, string $schoolId): Collection
    {
        // When the user clicks Export from the Division tab, resolve the parent
        // division's library IDs instead of the school's own.
        if ($request->input('tab') === 'division') {
            return Cache::remember(
                "school_division_libraries_{$schoolId}",
                self::CACHE_TTL_HIERARCHY,
                function () use ($schoolId) {
                    $school = School::find($schoolId);
                    if (!$school || !$school->district_id) {
                        return collect();
                    }

                    $district = District::find($school->district_id);
                    if (!$district || !$district->division_id) {
                        return collect();
                    }

                    return DivisionLibrary::where('division_id', $district->division_id)->pluck('id');
                }
            );
        }

        // Default: school tab — export only this school's own library resources.
        return Cache::remember(
            "school_libraries_{$schoolId}",
            self::CACHE_TTL_HIERARCHY,
            fn() => SchoolLibrary::where('school_id', $schoolId)->pluck('id')
        );
    }

    private function getLevel2LibraryIds(Request $request, string $districtId): Collection
    {
        $selectedSchool = $request->input('school');

        // Require a filter — exporting everything without one would be too broad
        if (!$request->has('school')) {
            return collect();
        }

        if ($selectedSchool && $selectedSchool !== 'all') {
            return Cache::remember(
                "school_libraries_{$selectedSchool}",
                self::CACHE_TTL_HIERARCHY,
                fn() => SchoolLibrary::where('school_id', $selectedSchool)->pluck('id')
            );
        }

        // "All schools" selected
        return Cache::remember(
            "district_school_libraries_{$districtId}",
            self::CACHE_TTL_HIERARCHY,
            function () use ($districtId) {
                $schoolIds = School::where('district_id', $districtId)->pluck('id');
                return SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');
            }
        );
    }

    private function getLevel3LibraryIds(Request $request, string $divisionId): Collection
    {
        // If a filter is active, export the filtered (school) tab; otherwise export the division tab
        if ($request->has('district') || $request->has('school')) {
            return $this->getLevel3FilteredLibraryIds($request, $divisionId);
        }

        return Cache::remember(
            "division_libraries_{$divisionId}",
            self::CACHE_TTL_HIERARCHY,
            fn() => DivisionLibrary::where('division_id', $divisionId)->pluck('id')
        );
    }

    private function getLevel3FilteredLibraryIds(Request $request, string $divisionId): Collection
    {
        $selectedDistrict = $request->input('district');
        $selectedSchool   = $request->input('school');

        if ($selectedSchool && $selectedSchool !== 'all') {
            return Cache::remember(
                "school_libraries_{$selectedSchool}",
                self::CACHE_TTL_HIERARCHY,
                fn() => SchoolLibrary::where('school_id', $selectedSchool)->pluck('id')
            );
        }

        if ($selectedDistrict && $selectedDistrict !== 'all') {
            return Cache::remember(
                "district_school_libraries_{$selectedDistrict}",
                self::CACHE_TTL_HIERARCHY,
                function () use ($selectedDistrict) {
                    $schoolIds = School::where('district_id', $selectedDistrict)->pluck('id');
                    return SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');
                }
            );
        }

        // "All districts" selected — walk the full hierarchy
        return Cache::remember(
            "division_all_school_libraries_{$divisionId}",
            self::CACHE_TTL_HIERARCHY,
            function () use ($divisionId) {
                $districtIds = District::where('division_id', $divisionId)->pluck('id');
                $schoolIds   = School::whereIn('district_id', $districtIds)->pluck('id');
                return SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');
            }
        );
    }

    private function getLevel4LibraryIds(Request $request, string $stationId): Collection
    {
        if ($request->input('tab') === 'library-hub') {
            return $this->getLevel4HubLibraryIds($request, $stationId);
        }

        $selectedDivision = $request->input('division');
        $selectedDistrict = $request->input('district');
        $selectedSchool   = $request->input('school');

        // Require at least one filter — exporting the entire region without one is too broad
        if (!$request->has('division') && !$request->has('district') && !$request->has('school')) {
            return collect();
        }

        // Most specific filter wins — school > district > division > all
        if ($selectedSchool && $selectedSchool !== 'all') {
            return Cache::remember(
                "school_libraries_{$selectedSchool}",
                self::CACHE_TTL_HIERARCHY,
                fn() => SchoolLibrary::where('school_id', $selectedSchool)->pluck('id')
            );
        }

        if ($selectedDistrict && $selectedDistrict !== 'all') {
            return Cache::remember(
                "district_school_libraries_{$selectedDistrict}",
                self::CACHE_TTL_HIERARCHY,
                function () use ($selectedDistrict) {
                    $schoolIds = School::where('district_id', $selectedDistrict)->pluck('id');
                    return SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');
                }
            );
        }

        if ($selectedDivision && $selectedDivision !== 'all') {
            return $this->getLevel4DivisionLibraries($selectedDivision);
        }

        return $this->getLevel4RegionSchoolLibraries($stationId);
    }

    private function getLevel4HubLibraryIds(Request $request, string $regionId): Collection
    {
        $selectedDivision = (string) $request->input('hub_division', '');
        $selectedLibrary  = (string) $request->input('hub_library', '');

        if ($selectedDivision === '' || !Division::where('region_id', $regionId)->whereKey($selectedDivision)->exists()) {
            return collect();
        }

        if ($selectedLibrary !== '' && $selectedLibrary !== 'all') {
            return DivisionLibrary::where('division_id', $selectedDivision)
                ->whereKey($selectedLibrary)
                ->pluck('id');
        }

        return Cache::remember(
            "division_libraries_{$selectedDivision}",
            self::CACHE_TTL_LIBRARIES,
            fn() => DivisionLibrary::where('division_id', $selectedDivision)->pluck('id')
        );
    }

    private function getLevel4DivisionLibraries(string $divisionId): Collection
    {
        return Cache::remember(
            "division_school_libraries_{$divisionId}",
            self::CACHE_TTL_LIBRARIES,
            function () use ($divisionId) {
                $districtIds  = District::where('division_id', $divisionId)->pluck('id');
                $schoolIds    = School::whereIn('district_id', $districtIds)->pluck('id');

                return SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');
            }
        );
    }

    private function getLevel4RegionSchoolLibraries(string $stationId): Collection
    {
        return Cache::remember(
            "region_school_libraries_{$stationId}",
            self::CACHE_TTL_LIBRARIES,
            function () use ($stationId) {
                $divisionIds  = Division::where('region_id', $stationId)->pluck('id');
                $districtIds  = District::whereIn('division_id', $divisionIds)->pluck('id');
                $schoolIds    = School::whereIn('district_id', $districtIds)->pluck('id');

                return SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');
            }
        );
    }

    // WHERE EXISTS keeps each resource row appearing once even when multiple
    // acquisitions match — MAX(ts_rank) floats the best-matching resource to the top
    private function applySearch($query, string $search)
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        $query->whereExists(function ($sub) use ($search) {
            $sub->select(DB::raw(1))
                ->from('print_acquisitions')
                ->whereColumn('print_acquisitions.print_id', 'print_resources.id')
                ->whereRaw(
                    "print_acquisitions.search_vector @@ plainto_tsquery('english', ?)",
                    [$search]
                );
        });

        $query->orderByRaw(
            "(SELECT MAX(ts_rank(pa.search_vector, plainto_tsquery('english', ?)))
              FROM print_acquisitions pa
              WHERE pa.print_id = print_resources.id) DESC",
            [$search]
        );

        return $query;
    }

    // ILIKE fallback for when the FTS vector hasn't been built yet
    private function applySearchFallback($query, string $search)
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        $searchLower = '%' . strtolower($search) . '%';

        return $query->where(function ($q) use ($searchLower) {
            $q->whereHas('printAcquisitions', function ($aq) use ($searchLower) {
                    $aq->where('isbn',         'ILIKE', $searchLower)
                       ->orWhere('publisher',   'ILIKE', $searchLower)
                       ->orWhere('copyright',   'ILIKE', $searchLower)
                       ->orWhere('library_name','ILIKE', $searchLower);
                })
                ->orWhereHas('printTitle', fn($qt) =>
                    $qt->where('title', 'ILIKE', $searchLower)
                )
                ->orWhereHas('printTitle.authors', fn($qa) =>
                    $qa->where('author_name', 'ILIKE', $searchLower)
                )
                ->orWhereExists(function ($exists) use ($searchLower) {
                    $exists->select(DB::raw(1))
                        ->from('subject_grade_levels as sgl')
                        ->join('subjects as subj', 'sgl.subject_id', '=', 'subj.id')
                        ->join('grade_levels as gl', 'sgl.grade_level_id', '=', 'gl.id')
                        ->whereRaw("sgl.id::text = ANY(string_to_array(print_resources.subject_grade_level_ids, ','))")
                        ->where(function ($match) use ($searchLower) {
                            $match->where('subj.subject_name', 'ILIKE', $searchLower)
                                  ->orWhere('gl.grade',        'ILIKE', $searchLower);
                        });
                });
        });
    }

    public function clearStationCache(string $stationId, int $level): void
    {
        $patterns = match($level) {
            self::LEVEL_SCHOOL   => ["school_libraries_{$stationId}"],
            self::LEVEL_DISTRICT => ["district_school_libraries_{$stationId}"],
            self::LEVEL_DIVISION => [
                "division_libraries_{$stationId}",
                "division_all_school_libraries_{$stationId}",
                "division_school_libraries_{$stationId}",
                "division_all_libraries_{$stationId}",
            ],
            self::LEVEL_REGION   => [
                "region_school_libraries_{$stationId}",
                "region_all_libraries_{$stationId}",
            ],
            default              => [],
        };

        foreach ($patterns as $key) {
            Cache::forget($key);
        }
    }

    public function clearLibraryCache(): void
    {
        // Intentionally empty — Cache::flush() would wipe unrelated app caches.
        // Add targeted key eviction here if library_name keys are tracked explicitly.
    }
}
