<?php

namespace App\Services\Resource\Exports;

use App\Models\PrintResource;
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

class ExportPrintResourceService
{
    /** Cache TTL constants */
    private const CACHE_TTL_LIBRARIES = 1800; // 30 minutes
    private const CACHE_TTL_HIERARCHY = 7200; // 2 hours

    /** Organizational hierarchy level constants */
    public const LEVEL_SCHOOL    = 1;
    public const LEVEL_DISTRICT  = 2;
    public const LEVEL_DIVISION  = 3;
    public const LEVEL_REGION    = 4;

    // ─── Public API ───────────────────────────────────────────────────────────

    /**
     * Get all filtered resources for export (no pagination).
     *
     * library_id now lives on print_acquisitions, so we scope the query
     * via whereHas and let the eager-loaded relation carry all acquisition
     * rows to the model accessors (quantities, library_name, showDetails…).
     */
    public function getExportData(Request $request, int $level, string $stationId): Collection
    {
        $libraryIds = $this->getLibraryIds($request, $level, $stationId);

        if ($libraryIds->isEmpty()) {
            return collect();
        }

        $query = PrintResource::with(['printTitle.authors', 'type', 'printAcquisitions'])
            ->whereHas('printAcquisitions', function ($q) use ($libraryIds) {
                $q->whereIn('library_id', $libraryIds->toArray());
            });

        $searchParam = $this->getSearchParam($request, $level);
        $this->applySearch($query, $searchParam);

        return $query->get();
    }

    // ─── Search param helper ──────────────────────────────────────────────────

    private function getSearchParam(Request $request, int $level): string
    {
        if ($level === self::LEVEL_DIVISION) {
            if ($request->has('district') || $request->has('school')) {
                return (string) $request->input('school_search', '');
            }
            return (string) $request->input('division_search', '');
        }

        return (string) $request->input('search', '');
    }

    // ─── Library ID resolution (unchanged logic, caching intact) ─────────────

    private function getLibraryIds(Request $request, int $level, string $stationId): Collection
    {
        return match($level) {
            self::LEVEL_SCHOOL    => $this->getLevel1LibraryIds($stationId),
            self::LEVEL_DISTRICT  => $this->getLevel2LibraryIds($request, $stationId),
            self::LEVEL_DIVISION  => $this->getLevel3LibraryIds($request, $stationId),
            self::LEVEL_REGION    => $this->getLevel4LibraryIds($request, $stationId),
            default               => collect(),
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
        $selectedDivision = $request->input('division');
        $selectedDistrict = $request->input('district');
        $selectedSchool   = $request->input('school');

        if (!$request->has('division') && !$request->has('district') && !$request->has('school')) {
            return collect();
        }

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

        return $this->getLevel4RegionLibraries($stationId);
    }

    private function getLevel4DivisionLibraries(string $divisionId): Collection
    {
        return Cache::remember(
            "division_all_libraries_{$divisionId}",
            self::CACHE_TTL_LIBRARIES,
            function () use ($divisionId) {
                $divisionLibs = DivisionLibrary::where('division_id', $divisionId)->pluck('id');
                $districtIds  = District::where('division_id', $divisionId)->pluck('id');
                $schoolIds    = School::whereIn('district_id', $districtIds)->pluck('id');
                $schoolLibs   = SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');

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
                $regionLibs   = RegionLibrary::where('region_id', $stationId)->pluck('id');
                $divisionIds  = Division::where('region_id', $stationId)->pluck('id');
                $divisionLibs = DivisionLibrary::whereIn('division_id', $divisionIds)->pluck('id');
                $districtIds  = District::whereIn('division_id', $divisionIds)->pluck('id');
                $schoolIds    = School::whereIn('district_id', $districtIds)->pluck('id');
                $schoolLibs   = SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');

                return $regionLibs->merge($divisionLibs)->merge($schoolLibs);
            }
        );
    }

    // ─── Search ───────────────────────────────────────────────────────────────

    /**
     * Apply full-text search via print_acquisitions.search_vector.
     *
     * Uses WHERE EXISTS so each print_resources row appears at most once
     * even when multiple acquisitions match. Ranking picks the best-matching
     * acquisition to determine sort order — identical behaviour to
     * PrintResourceService::applySearch().
     */
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

    /**
     * ILIKE fallback — used when the FTS vector is unavailable.
     * library_name is now looked up via the printAcquisitions relation.
     */
    private function applySearchFallback($query, string $search)
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        $searchLower = '%' . strtolower($search) . '%';

        return $query->where(function ($q) use ($searchLower) {
            // Fields that moved to print_acquisitions
            $q->whereHas('printAcquisitions', function ($aq) use ($searchLower) {
                    $aq->where('isbn',         'ILIKE', $searchLower)
                       ->orWhere('publisher',   'ILIKE', $searchLower)
                       ->orWhere('copyright',   'ILIKE', $searchLower)
                       ->orWhere('library_name','ILIKE', $searchLower);
                })

                // Title
                ->orWhereHas('printTitle', fn($qt) =>
                    $qt->where('title', 'ILIKE', $searchLower)
                )

                // Authors
                ->orWhereHas('printTitle.authors', fn($qa) =>
                    $qa->where('author_name', 'ILIKE', $searchLower)
                )

                // Subject / Grade
                ->orWhereExists(function ($exists) use ($searchLower) {
                    $exists->select(DB::raw(1))
                        ->from('subject_grade_levels as sgl')
                        ->join('subjects as subj', 'sgl.subject_id', '=', 'subj.id')
                        ->join('grade_levels as gl',   'sgl.grade_level_id', '=', 'gl.id')
                        ->whereRaw("sgl.id::text = ANY(string_to_array(print_resources.subject_grade_level_ids, ','))")
                        ->where(function ($match) use ($searchLower) {
                            $match->where('subj.subject_name', 'ILIKE', $searchLower)
                                  ->orWhere('gl.grade',        'ILIKE', $searchLower);
                        });
                });
        });
    }

    // ─── Cache management ─────────────────────────────────────────────────────

    public function clearStationCache(string $stationId, int $level): void
    {
        $patterns = match($level) {
            self::LEVEL_SCHOOL    => ["school_libraries_{$stationId}"],
            self::LEVEL_DISTRICT  => ["district_school_libraries_{$stationId}"],
            self::LEVEL_DIVISION  => [
                "division_libraries_{$stationId}",
                "division_all_school_libraries_{$stationId}",
                "division_all_libraries_{$stationId}",
            ],
            self::LEVEL_REGION    => ["region_all_libraries_{$stationId}"],
            default               => [],
        };

        foreach ($patterns as $key) {
            Cache::forget($key);
        }
    }

    public function clearLibraryCache(): void
    {
        // Targeted cache eviction can be added here if library_name cache
        // keys are tracked explicitly. Cache::flush() intentionally omitted
        // to avoid clearing unrelated application caches.
    }
}
