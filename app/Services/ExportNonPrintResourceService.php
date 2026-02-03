<?php

namespace App\Services;

use App\Models\NonprintResource;
use App\Models\School;
use App\Models\District;
use App\Models\Division;
use App\Models\SchoolLibrary;
use App\Models\DivisionLibrary;
use App\Models\RegionLibrary;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ExportNonPrintResourceService
{
    /** Cache TTL constants */
    private const CACHE_TTL_LIBRARIES = 1800;
    private const CACHE_TTL_HIERARCHY = 7200;

    /** Organizational hierarchy level constants */
    public const LEVEL_SCHOOL = 1;
    public const LEVEL_DISTRICT = 2;
    public const LEVEL_DIVISION = 3;
    public const LEVEL_REGION = 4;

    /**
     * Get all filtered resources for export (no pagination)
     */
    public function getExportData(Request $request, int $level, string $stationId): Collection
    {
        $libraryIds = $this->getLibraryIds($request, $level, $stationId);

        if ($libraryIds->isEmpty()) {
            return collect();
        }

        $query = NonprintResource::with(['nonprintTitle', 'type', 'nonprintAcquisitions'])
            ->whereIn('library_id', $libraryIds->toArray());

        // Apply search based on level
        $searchParam = $this->getSearchParam($request, $level);
        $this->applySearch($query, $searchParam);

        $resources = $query->get();

        // Attach library names using optimized caching
        $this->attachLibraryNames($resources);

        return $resources;
    }

    /**
     * Get search parameter based on level and request
     */
    private function getSearchParam(Request $request, int $level): string
    {
        if ($level === self::LEVEL_DIVISION) {
            // Division level: check if viewing school resources or division resources
            if ($request->has('district') || $request->has('school')) {
                return (string) $request->input('school_search', '');
            }
            return (string) $request->input('division_search', '');
        }

        return (string) $request->input('search', '');
    }

    /**
     * Determine library IDs to query based on level and filters
     */
    private function getLibraryIds(Request $request, int $level, string $stationId): Collection
    {
        return match($level) {
            self::LEVEL_SCHOOL => $this->getLevel1LibraryIds($stationId),
            self::LEVEL_DISTRICT => $this->getLevel2LibraryIds($request, $stationId),
            self::LEVEL_DIVISION => $this->getLevel3LibraryIds($request, $stationId),
            self::LEVEL_REGION => $this->getLevel4LibraryIds($request, $stationId),
            default => collect(),
        };
    }

    private function getLevel1LibraryIds(string $schoolId): Collection
    {
        return Cache::remember(
            "school_libraries_{$schoolId}",
            self::CACHE_TTL_HIERARCHY,
            fn() => SchoolLibrary::where('school_id', $schoolId)->pluck('id')
        );
    }

    private function getLevel2LibraryIds(Request $request, string $districtId): Collection
    {
        $selectedSchool = $request->input('school');

        // If no filter applied, return empty (requires selection)
        if (!$request->has('school')) {
            return collect();
        }

        // Specific school selected
        if ($selectedSchool && $selectedSchool !== 'all') {
            return Cache::remember(
                "school_libraries_{$selectedSchool}",
                self::CACHE_TTL_HIERARCHY,
                fn() => SchoolLibrary::where('school_id', $selectedSchool)->pluck('id')
            );
        }

        // All schools in district
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
        // If viewing school resources (filtered view)
        if ($request->has('district') || $request->has('school')) {
            return $this->getLevel3FilteredLibraryIds($request, $divisionId);
        }

        // Default: Division libraries
        return Cache::remember(
            "division_libraries_{$divisionId}",
            self::CACHE_TTL_HIERARCHY,
            fn() => DivisionLibrary::where('division_id', $divisionId)->pluck('id')
        );
    }

    private function getLevel3FilteredLibraryIds(Request $request, string $divisionId): Collection
    {
        $selectedDistrict = $request->input('district');
        $selectedSchool = $request->input('school');

        // Specific school selected
        if ($selectedSchool && $selectedSchool !== 'all') {
            return Cache::remember(
                "school_libraries_{$selectedSchool}",
                self::CACHE_TTL_HIERARCHY,
                fn() => SchoolLibrary::where('school_id', $selectedSchool)->pluck('id')
            );
        }

        // Specific district selected
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

        // All districts in division
        return Cache::remember(
            "division_all_school_libraries_{$divisionId}",
            self::CACHE_TTL_HIERARCHY,
            function () use ($divisionId) {
                $districtIds = District::where('division_id', $divisionId)->pluck('id');
                $schoolIds = School::whereIn('district_id', $districtIds)->pluck('id');
                return SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');
            }
        );
    }

    private function getLevel4LibraryIds(Request $request, string $stationId): Collection
    {
        $selectedDivision = $request->input('division');
        $selectedDistrict = $request->input('district');
        $selectedSchool = $request->input('school');

        // Require at least one filter to be selected
        if (!$request->has('division') && !$request->has('district') && !$request->has('school')) {
            return collect();
        }

        // Priority 1: Specific school selected
        if ($selectedSchool && $selectedSchool !== 'all') {
            return Cache::remember(
                "school_libraries_{$selectedSchool}",
                self::CACHE_TTL_HIERARCHY,
                fn() => SchoolLibrary::where('school_id', $selectedSchool)->pluck('id')
            );
        }

        // Priority 2: Specific district selected
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

        // Priority 3: Specific division selected
        if ($selectedDivision && $selectedDivision !== 'all') {
            return $this->getLevel4DivisionLibraries($selectedDivision);
        }

        // Default: All divisions in region
        return $this->getLevel4RegionLibraries($stationId);
    }

    private function getLevel4DivisionLibraries(string $divisionId): Collection
    {
        return Cache::remember(
            "division_all_libraries_{$divisionId}",
            self::CACHE_TTL_LIBRARIES,
            function () use ($divisionId) {
                $divisionLibs = DivisionLibrary::where('division_id', $divisionId)->pluck('id');

                $districtIds = District::where('division_id', $divisionId)->pluck('id');
                $schoolIds = School::whereIn('district_id', $districtIds)->pluck('id');
                $schoolLibs = SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');

                return $divisionLibs->merge($schoolLibs);
            }
        );
    }

    private function getLevel4RegionLibraries(string $stationId): Collection
    {
        return Cache::remember(
            "region_all_libraries_{$stationId}",
            self::CACHE_TTL_LIBRARIES,
            function () use ($stationId) {
                $regionLibs = RegionLibrary::where('region_id', $stationId)->pluck('id');

                $divisionIds = Division::where('region_id', $stationId)->pluck('id');
                $divisionLibs = DivisionLibrary::whereIn('division_id', $divisionIds)->pluck('id');

                $districtIds = District::whereIn('division_id', $divisionIds)->pluck('id');
                $schoolIds = School::whereIn('district_id', $districtIds)->pluck('id');
                $schoolLibs = SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');

                return $regionLibs->merge($divisionLibs)->merge($schoolLibs);
            }
        );
    }

    /**
     * Attach library names to resources collection using optimized caching
     */
    private function attachLibraryNames(Collection $resources): void
    {
        if ($resources->isEmpty()) {
            return;
        }

        $libraryIds = $resources->pluck('library_id')->unique()->filter();

        if ($libraryIds->isEmpty()) {
            foreach ($resources as $resource) {
                $resource->library_name = 'No Library Assigned';
            }
            return;
        }

        // Cache library lookups - critical for performance at scale
        $libraryIdsKey = $libraryIds->sort()->values()->implode('_');
        $cacheKey = 'library_names_' . md5($libraryIdsKey);

        $allLibraries = Cache::remember(
            $cacheKey,
            self::CACHE_TTL_LIBRARIES,
            function () use ($libraryIds) {
                // OPTIMIZATION: Use UNION ALL instead of 3 separate queries
                $results = DB::select("
                    SELECT id, library_name, 'school' as type FROM school_libraries WHERE id = ANY(?)
                    UNION ALL
                    SELECT id, library_name, 'division' as type FROM division_libraries WHERE id = ANY(?)
                    UNION ALL
                    SELECT id, library_name, 'region' as type FROM region_libraries WHERE id = ANY(?)
                ", [
                    '{' . $libraryIds->implode(',') . '}',
                    '{' . $libraryIds->implode(',') . '}',
                    '{' . $libraryIds->implode(',') . '}'
                ]);

                return collect($results)->keyBy('id');
            }
        );

        foreach ($resources as $resource) {
            if (!$resource->library_id) {
                $resource->library_name = 'No Library Assigned';
                continue;
            }

            $library = $allLibraries->get($resource->library_id);

            if ($library) {
                $resource->library_name = $library->library_name;
            } else {
                $resource->library_name = 'Unknown Library';
            }
        }
    }

    /**
     * Apply full-text search using PostgreSQL's tsvector
     */
    private function applySearch($query, string $search)
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        // Use PostgreSQL full-text search with ranking (same as main service)
        return $query->whereRaw(
            "search_vector @@ plainto_tsquery('english', ?)",
            [$search]
        )->orderByRaw(
            "ts_rank(search_vector, plainto_tsquery('english', ?)) DESC",
            [$search]
        );
    }

    /**
     * Fallback search method using LIKE queries
     * This is kept for backward compatibility but should not be needed
     */
    private function applySearchFallback($query, string $search)
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        $searchLower = '%' . strtolower($search) . '%';

        return $query->where(function ($q) use ($searchLower) {
            // Search in direct resource fields
            $q->whereRaw('LOWER(brand) LIKE ?', [$searchLower])
            ->orWhereRaw('LOWER(code) LIKE ?', [$searchLower])
            ->orWhereRaw('LOWER(version) LIKE ?', [$searchLower])
            ->orWhereRaw('LOWER(url) LIKE ?', [$searchLower])
            ->orWhereRaw('LOWER(size) LIKE ?', [$searchLower])
            ->orWhereRaw('LOWER(model) LIKE ?', [$searchLower])

            // Search in title
            ->orWhereHas('nonprintTitle', fn($qt) =>
                $qt->whereRaw('LOWER(title) LIKE ?', [$searchLower])
            )

            // Search in subjects and grade levels
            ->orWhereExists(function ($exists) use ($searchLower) {
                $exists->select(DB::raw(1))
                        ->from('subject_grade_levels as sgl')
                        ->join('subjects as subj', 'sgl.subject_id', '=', 'subj.id')
                        ->join('grade_levels as gl', 'sgl.grade_level_id', '=', 'gl.id')
                        ->whereRaw("sgl.id::text = ANY(string_to_array(nonprint_resources.subject_grade_level_ids, ','))")
                        ->where(function ($match) use ($searchLower) {
                            $match->whereRaw('LOWER(subj.subject_name) LIKE ?', [$searchLower])
                                ->orWhereRaw('LOWER(gl.grade) LIKE ?', [$searchLower]);
                        });
            })

            // Combined library search (optimized with UNION ALL)
            ->orWhereExists(function ($exists) use ($searchLower) {
                $exists->selectRaw('1')
                    ->fromRaw('(
                        SELECT id, library_name FROM school_libraries
                        UNION ALL
                        SELECT id, library_name FROM division_libraries
                        UNION ALL
                        SELECT id, library_name FROM region_libraries
                    ) as all_libraries')
                    ->whereColumn('nonprint_resources.library_id', 'all_libraries.id')
                    ->whereRaw('LOWER(all_libraries.library_name) LIKE ?', [$searchLower]);
            });
        });
    }

    /**
     * Clear caches when organizational structure changes
     */
    public function clearStationCache(string $stationId, int $level): void
    {
        $patterns = match($level) {
            self::LEVEL_SCHOOL => ["school_libraries_{$stationId}"],
            self::LEVEL_DISTRICT => ["district_school_libraries_{$stationId}"],
            self::LEVEL_DIVISION => [
                "division_libraries_{$stationId}",
                "division_all_school_libraries_{$stationId}",
                "division_all_libraries_{$stationId}"
            ],
            self::LEVEL_REGION => [
                "region_all_libraries_{$stationId}"
            ],
            default => []
        };

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Clear library name caches
     */
    public function clearLibraryCache(): void
    {
    }
}
