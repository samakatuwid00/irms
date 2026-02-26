<?php

namespace App\Services\Resource\Exports;

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
     * Get all filtered resources for export (no pagination).
     *
     * library_id now lives on nonprint_acquisitions, so we scope the query
     * via whereHas and let the eager-loaded relation carry all acquisition
     * rows to the model accessors (quantities, library_name, showDetails…).
     */
    public function getExportData(Request $request, int $level, string $stationId): Collection
    {
        $libraryIds = $this->getLibraryIds($request, $level, $stationId);

        if ($libraryIds->isEmpty()) {
            return collect();
        }

        $query = NonprintResource::with(['nonprintTitle', 'type', 'nonprintAcquisitions'])
            ->whereHas('nonprintAcquisitions', function ($q) use ($libraryIds) {
                $q->whereIn('library_id', $libraryIds->toArray());
            });

        // Apply search based on level
        $searchParam = $this->getSearchParam($request, $level);
        $this->applySearch($query, $searchParam);

        return $query->get();
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
     * Apply full-text search via nonprint_acquisitions.search_vector.
     *
     * Uses WHERE EXISTS so each nonprint_resources row appears at most once
     * even when multiple acquisitions match. Ranking picks the best-matching
     * acquisition to determine sort order — identical behaviour to
     * NonPrintResourceService::applySearch().
     */
    private function applySearch($query, string $search)
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        $query->whereExists(function ($sub) use ($search) {
            $sub->select(DB::raw(1))
                ->from('nonprint_acquisitions')
                ->whereColumn('nonprint_acquisitions.nonprint_id', 'nonprint_resources.id')
                ->whereRaw(
                    "nonprint_acquisitions.search_vector @@ plainto_tsquery('english', ?)",
                    [$search]
                );
        });

        $query->orderByRaw(
            "(SELECT MAX(ts_rank(na.search_vector, plainto_tsquery('english', ?)))
              FROM nonprint_acquisitions na
              WHERE na.nonprint_id = nonprint_resources.id) DESC",
            [$search]
        );

        return $query;
    }

    /**
     * ILIKE fallback — used when the FTS vector is unavailable.
     * library_name and other acquisition fields are now looked up via
     * the nonprintAcquisitions relation.
     */
    private function applySearchFallback($query, string $search)
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        $searchLower = '%' . strtolower($search) . '%';

        return $query->where(function ($q) use ($searchLower) {
            // Fields that moved to nonprint_acquisitions
            $q->whereHas('nonprintAcquisitions', function ($aq) use ($searchLower) {
                    $aq->where('brand',        'ILIKE', $searchLower)
                       ->orWhere('code',        'ILIKE', $searchLower)
                       ->orWhere('version',     'ILIKE', $searchLower)
                       ->orWhere('url',         'ILIKE', $searchLower)
                       ->orWhere('size',        'ILIKE', $searchLower)
                       ->orWhere('model',       'ILIKE', $searchLower)
                       ->orWhere('library_name','ILIKE', $searchLower);
                })

                // Title
                ->orWhereHas('nonprintTitle', fn($qt) =>
                    $qt->where('title', 'ILIKE', $searchLower)
                )

                // Subject / Grade
                ->orWhereExists(function ($exists) use ($searchLower) {
                    $exists->select(DB::raw(1))
                        ->from('subject_grade_levels as sgl')
                        ->join('subjects as subj', 'sgl.subject_id', '=', 'subj.id')
                        ->join('grade_levels as gl',   'sgl.grade_level_id', '=', 'gl.id')
                        ->whereRaw("sgl.id::text = ANY(string_to_array(nonprint_resources.subject_grade_level_ids, ','))")
                        ->where(function ($match) use ($searchLower) {
                            $match->where('subj.subject_name', 'ILIKE', $searchLower)
                                  ->orWhere('gl.grade',        'ILIKE', $searchLower);
                        });
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
