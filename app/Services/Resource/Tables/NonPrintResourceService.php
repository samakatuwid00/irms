<?php

namespace App\Services\Resource\Tables;

use App\Models\NonprintResource;
use App\Models\School;
use App\Models\District;
use App\Models\Division;
use App\Models\SchoolLibrary;
use App\Models\DivisionLibrary;
use App\Models\RegionLibrary;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class NonPrintResourceService
{
    private const PER_PAGE = 5;

    private const CACHE_TTL = 3600;
    private const CACHE_TTL_LIBRARIES = 1800;
    private const CACHE_TTL_HIERARCHY = 7200;

    public const LEVEL_SCHOOL = 1;
    public const LEVEL_DISTRICT = 2;
    public const LEVEL_DIVISION = 3;
    public const LEVEL_REGION = 4;

    public function getResourcesData(Request $request, int $level, string $stationId): array
    {
        // Get dropdown options for filters (schools, districts, divisions)
        $dropdownData = $this->getDropdownData($level, $stationId);

        // Determine which library IDs to query based on level and filters
        $libraryIds = $this->getLibraryIds($request, $level, $stationId, $dropdownData);

        // Get main resources and filtered resources
        $resources = $this->getResources($request, $level, $libraryIds['main']);
        $filteredResources = $this->getFilteredResources($request, $level, $libraryIds['filtered']);

        // For school level, get division resources
        $divisionResources = null;
        if ($level === self::LEVEL_SCHOOL) {
            $divisionResources = $this->getDivisionResourcesForSchool($request, $stationId);
        }

        return array_merge([
            'level' => $level,
            'resources' => $resources,
            'filteredResources' => $filteredResources,
            'divisionResources' => $divisionResources,
        ], $dropdownData);
    }

    private function getDivisionResourcesForSchool(Request $request, string $schoolId)
    {
        // Cache the division library IDs lookup for this school
        $divisionLibraryIds = Cache::remember(
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

        if ($divisionLibraryIds->isEmpty()) {
            return $this->emptyPaginator($request);
        }

        $query = NonprintResource::with(['nonprintTitle', 'type', 'nonprintAcquisitions'])
            ->whereIn('library_id', $divisionLibraryIds->toArray());

        $this->applySearch($query, (string) $request->input('division_search', ''));

        return $query->paginate(self::PER_PAGE, ['*'], 'division_page')->withQueryString();
    }

    /**
     * OPTIMIZED: library_name is now a stored column on nonprint_resources.
     * No more cross-table UNION ALL lookups or cache round-trips per page —
     * the value is already on every hydrated model.
     *
     * The only thing we still handle here is the fallback for rows whose
     * library_name column was not yet populated (e.g. legacy rows or a null
     * library_id), keeping the exact same display behaviour as before.
     */
    private function attachLibraryNames(LengthAwarePaginator $resources): void
    {
        if ($resources->isEmpty()) {
            return;
        }

        foreach ($resources as $resource) {
            // Column already populated by DB / observer — use it directly.
            if (!empty($resource->library_name)) {
                continue;
            }

            // Fallback for rows that pre-date the column or have no library.
            $resource->library_name = $resource->library_id
                ? 'Unknown Library'
                : 'No Library Assigned';
        }
    }

    private function getDropdownData(int $level, string $stationId): array
    {
        $data = [
            'divisions' => collect(),
            'districts' => collect(),
            'schools' => collect(),
            'allDistricts' => collect(),
            'allSchools' => collect(),
        ];

        switch ($level) {
            case self::LEVEL_DISTRICT:
                // District level: show schools within this district
                $data['schools'] = Cache::remember(
                    "schools_district_{$stationId}",
                    self::CACHE_TTL,
                    fn() => School::where('district_id', $stationId)
                        ->orderBy('school_name')
                        ->get()
                );
                break;

            case self::LEVEL_DIVISION:
                // Division level: show districts and their schools
                $data['districts'] = Cache::remember(
                    "districts_division_{$stationId}",
                    self::CACHE_TTL,
                    fn() => District::where('division_id', $stationId)
                        ->orderBy('district_name')
                        ->get()
                );

                $data['allSchools'] = Cache::remember(
                    "schools_division_{$stationId}",
                    self::CACHE_TTL,
                    fn() => School::whereIn('district_id', $data['districts']->pluck('id'))
                        ->orderBy('school_name')
                        ->get(['id', 'school_name', 'district_id'])
                );
                break;

            case self::LEVEL_REGION:
                // Region level: show all divisions, districts, and schools
                $data['divisions'] = Cache::remember(
                    "divisions_region_{$stationId}",
                    self::CACHE_TTL,
                    fn() => Division::where('region_id', $stationId)
                        ->orderBy('division_name')
                        ->get()
                );

                $data['allDistricts'] = Cache::remember(
                    'all_districts',
                    self::CACHE_TTL,
                    fn() => District::orderBy('district_name')->get()
                );

                $data['allSchools'] = Cache::remember(
                    'all_schools',
                    self::CACHE_TTL,
                    fn() => School::orderBy('school_name')->get(['id', 'school_name', 'district_id'])
                );
                break;
        }

        return $data;
    }

    private function getLibraryIds(Request $request, int $level, string $stationId, array $dropdownData): array
    {
        return match($level) {
            self::LEVEL_SCHOOL => $this->getLevel1Libraries($stationId),
            self::LEVEL_DISTRICT => $this->getLevel2Libraries($request, $stationId, $dropdownData),
            self::LEVEL_DIVISION => $this->getLevel3Libraries($request, $stationId, $dropdownData),
            self::LEVEL_REGION => $this->getLevel4Libraries($request, $stationId),
            default => ['main' => collect(), 'filtered' => collect()],
        };
    }

    private function getLevel1Libraries(string $schoolId): array
    {
        $libraries = Cache::remember(
            "school_libraries_{$schoolId}",
            self::CACHE_TTL_LIBRARIES,
            fn() => SchoolLibrary::where('school_id', $schoolId)->pluck('id')
        );

        return ['main' => $libraries, 'filtered' => collect()];
    }

    private function getLevel2Libraries(Request $request, string $districtId, array $dropdownData): array
    {
        $selectedSchool = $request->input('school');

        $schoolIds = $dropdownData['schools']->pluck('id');

        // CASE 1: No filter selected
        if (!$request->has('school')) {
            $libraries = Cache::remember(
                "district_school_libraries_{$districtId}",
                self::CACHE_TTL_LIBRARIES,
                fn() => SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id')
            );

            return ['main' => $libraries, 'filtered' => collect()];
        }

        // CASE 2: "All Schools" selected
        if ($selectedSchool === 'all') {
            $libraries = Cache::remember(
                "district_all_school_libraries_{$districtId}",
                self::CACHE_TTL_LIBRARIES,
                fn() => SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id')
            );

            return ['main' => collect(), 'filtered' => $libraries];
        }

        // CASE 3: Specific school selected
        $libraries = Cache::remember(
            "school_libraries_{$selectedSchool}",
            self::CACHE_TTL_LIBRARIES,
            fn() => SchoolLibrary::where('school_id', $selectedSchool)->pluck('id')
        );

        return ['main' => collect(), 'filtered' => $libraries];
    }

    private function getLevel3Libraries(Request $request, string $divisionId, array $dropdownData): array
    {
        $selectedDistrict = $request->input('district');
        $selectedSchool = $request->input('school');

        // CASE 1: No filters selected -> show division libraries only
        if (!$request->has('district') && !$request->has('school')) {
            $libraries = Cache::remember(
                "division_libraries_{$divisionId}",
                self::CACHE_TTL_LIBRARIES,
                fn() => DivisionLibrary::where('division_id', $divisionId)->pluck('id')
            );

            return ['main' => $libraries, 'filtered' => collect()];
        }

        $divisionLibraries = Cache::remember(
            "division_libraries_{$divisionId}",
            self::CACHE_TTL_LIBRARIES,
            fn() => DivisionLibrary::where('division_id', $divisionId)->pluck('id')
        );

        // CASE 2: Specific school selected
        if ($selectedSchool && $selectedSchool !== 'all') {
            $schoolLibraries = Cache::remember(
                "school_libraries_{$selectedSchool}",
                self::CACHE_TTL_LIBRARIES,
                fn() => SchoolLibrary::where('school_id', $selectedSchool)->pluck('id')
            );

            return ['main' => $divisionLibraries, 'filtered' => $schoolLibraries];
        }

        // CASE 3: Specific district selected (but not specific school)
        if ($selectedDistrict && $selectedDistrict !== 'all') {
            $schoolLibraries = Cache::remember(
                "district_school_libraries_{$selectedDistrict}",
                self::CACHE_TTL_LIBRARIES,
                function () use ($selectedDistrict) {
                    $schoolIds = School::where('district_id', $selectedDistrict)->pluck('id');
                    return SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');
                }
            );

            return ['main' => $divisionLibraries, 'filtered' => $schoolLibraries];
        }

        // CASE 4: "All Districts" OR "All Schools" selected
        $allSchoolLibraries = Cache::remember(
            "division_all_school_libraries_{$divisionId}",
            self::CACHE_TTL_LIBRARIES,
            function () use ($divisionId) {
                $districtIds = District::where('division_id', $divisionId)->pluck('id');
                $schoolIds = School::whereIn('district_id', $districtIds)->pluck('id');
                return SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');
            }
        );

        return ['main' => $divisionLibraries, 'filtered' => $allSchoolLibraries];
    }

    private function getLevel4Libraries(Request $request, string $stationId): array
    {
        $selectedDivision = $request->input('division');
        $selectedDistrict = $request->input('district');
        $selectedSchool = $request->input('school');

        // CASE 1: No filters selected
        if (!$request->has('division') && !$request->has('district') && !$request->has('school')) {
            return ['main' => collect(), 'filtered' => collect()];
        }

        // CASE 2: Specific school selected
        if ($selectedSchool && $selectedSchool !== 'all') {
            $libraries = Cache::remember(
                "school_libraries_{$selectedSchool}",
                self::CACHE_TTL_LIBRARIES,
                fn() => SchoolLibrary::where('school_id', $selectedSchool)->pluck('id')
            );

            return ['main' => collect(), 'filtered' => $libraries];
        }

        // CASE 3: Specific district selected (but not specific school)
        if ($selectedDistrict && $selectedDistrict !== 'all') {
            $libraries = Cache::remember(
                "district_school_libraries_{$selectedDistrict}",
                self::CACHE_TTL_LIBRARIES,
                function () use ($selectedDistrict) {
                    $schoolIds = School::where('district_id', $selectedDistrict)->pluck('id');
                    return SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');
                }
            );

            return ['main' => collect(), 'filtered' => $libraries];
        }

        // CASE 4: Specific division selected (but not specific district/school)
        if ($selectedDivision && $selectedDivision !== 'all') {
            $libraries = Cache::remember(
                "division_all_libraries_{$selectedDivision}",
                self::CACHE_TTL_LIBRARIES,
                function () use ($selectedDivision) {
                    $divisionLibs = DivisionLibrary::where('division_id', $selectedDivision)->pluck('id');
                    $districtIds = District::where('division_id', $selectedDivision)->pluck('id');
                    $schoolIds = School::whereIn('district_id', $districtIds)->pluck('id');
                    $schoolLibs = SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');

                    return $divisionLibs->merge($schoolLibs);
                }
            );

            return ['main' => collect(), 'filtered' => $libraries];
        }

        // CASE 5: "All" selected — show everything in the entire region
        $libraries = Cache::remember(
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

        return ['main' => collect(), 'filtered' => $libraries];
    }

    private function getResources(Request $request, int $level, Collection $libraryIds)
    {
        // Division level uses different search parameter
        if ($level === self::LEVEL_DIVISION) {
            return $this->getDivisionResources($request, $libraryIds);
        }

        if ($libraryIds->isEmpty()) {
            return $this->emptyPaginator($request);
        }

        $query = NonprintResource::with(['nonprintTitle', 'type', 'nonprintAcquisitions'])
            ->whereIn('library_id', $libraryIds->toArray());

        $this->applySearch($query, (string) $request->input('search', ''));

        return $query->paginate(self::PER_PAGE)->withQueryString();
    }

    private function getDivisionResources(Request $request, Collection $libraryIds)
    {
        if ($libraryIds->isEmpty()) {
            return $this->emptyPaginator($request);
        }

        $query = NonprintResource::with(['nonprintTitle', 'type', 'nonprintAcquisitions'])
            ->whereIn('library_id', $libraryIds->toArray());

        $this->applySearch($query, (string) $request->input('division_search', ''));

        return $query->paginate(self::PER_PAGE)->withQueryString();
    }

    private function getFilteredResources(Request $request, int $level, Collection $libraryIds)
    {
        $shouldShowFiltered = $this->shouldShowFilteredResources($request, $level);

        if (!$shouldShowFiltered || $libraryIds->isEmpty()) {
            return $this->emptyPaginator($request);
        }

        $query = NonprintResource::with(['nonprintTitle', 'type', 'nonprintAcquisitions'])
            ->whereIn('library_id', $libraryIds->toArray());

        // Division level uses 'school_search', others use 'search'
        $searchParam = $level === self::LEVEL_DIVISION ? 'school_search' : 'search';
        $this->applySearch($query, (string) $request->input($searchParam, ''));

        return $query->paginate(self::PER_PAGE)->withQueryString();
    }

    private function shouldShowFilteredResources(Request $request, int $level): bool
    {
        return match($level) {
            self::LEVEL_DISTRICT => $request->has('school'),
            self::LEVEL_DIVISION => $request->has('district') || $request->has('school'),
            self::LEVEL_REGION => $request->has('division') || $request->has('district') || $request->has('school'),
            default => false,
        };
    }

    /**
     * Apply full-text search using PostgreSQL's tsvector.
     */
    private function applySearch($query, string $search)
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        return $query->whereRaw(
            "search_vector @@ plainto_tsquery('english', ?)",
            [$search]
        )->orderByRaw(
            "ts_rank(search_vector, plainto_tsquery('english', ?)) DESC",
            [$search]
        );
    }

    /**
     * Fallback search method using LIKE queries.
     * Use this if full-text search is not available or for debugging.
     */
    private function applySearchFallback($query, string $search)
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        $searchLower = '%' . strtolower($search) . '%';

        return $query->where(function ($q) use ($searchLower) {
            $q->whereRaw('LOWER(brand) LIKE ?', [$searchLower])
            ->orWhereRaw('LOWER(code) LIKE ?', [$searchLower])
            ->orWhereRaw('LOWER(version) LIKE ?', [$searchLower])
            ->orWhereRaw('LOWER(url) LIKE ?', [$searchLower])
            ->orWhereRaw('LOWER(size) LIKE ?', [$searchLower])
            ->orWhereRaw('LOWER(model) LIKE ?', [$searchLower])

            ->orWhereHas('nonprintTitle', fn($qt) =>
                $qt->whereRaw('LOWER(title) LIKE ?', [$searchLower])
            )

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

            // library_name is now a stored column — search it directly
            ->orWhereRaw('LOWER(library_name) LIKE ?', [$searchLower]);
        });
    }

    private function emptyPaginator(Request $request)
    {
        return new LengthAwarePaginator([], 0, self::PER_PAGE, 1, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);
    }

    /**
     * Clear caches when organisational structure changes.
     */
    public function clearStationCache(string $stationId, int $level): void
    {
        $patterns = match($level) {
            self::LEVEL_SCHOOL => [
                "school_libraries_{$stationId}",
                "school_division_libraries_{$stationId}"
            ],
            self::LEVEL_DISTRICT => [
                "schools_district_{$stationId}",
                "district_school_libraries_{$stationId}"
            ],
            self::LEVEL_DIVISION => [
                "districts_division_{$stationId}",
                "schools_division_{$stationId}",
                "division_libraries_{$stationId}",
                "division_all_libraries_{$stationId}"
            ],
            self::LEVEL_REGION => [
                "divisions_region_{$stationId}",
                "region_all_libraries_{$stationId}",
                "all_districts",
                "all_schools"
            ],
            default => []
        };

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Clear library name caches.
     */
    public function clearLibraryCache(): void
    {
        Cache::flush();
    }
}
