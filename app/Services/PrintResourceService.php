<?php

namespace App\Services;

use App\Models\PrintResource;
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

class PrintResourceService
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
        $dropdownData = $this->getDropdownData($level, $stationId);
        $libraryIds = $this->getLibraryIds($request, $level, $stationId, $dropdownData);

        $resources = $this->getResources($request, $level, $libraryIds['main']);
        $filteredResources = $this->getFilteredResources($request, $level, $libraryIds['filtered']);

        $divisionResources = null;
        if ($level === self::LEVEL_SCHOOL) {
            $divisionResources = $this->getDivisionResourcesForSchool($request, $stationId);
            $this->attachLibraryNames($divisionResources);
        }

        $this->attachLibraryNames($resources);
        $this->attachLibraryNames($filteredResources);

        return array_merge([
            'level' => $level,
            'resources' => $resources,
            'filteredResources' => $filteredResources,
            'divisionResources' => $divisionResources,
        ], $dropdownData);
    }

    private function getDivisionResourcesForSchool(Request $request, string $schoolId)
    {
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

        $query = PrintResource::with(['printTitle.authors', 'type', 'printAcquisitions'])
            ->whereIn('library_id', $divisionLibraryIds->toArray());

        $this->applySearch($query, (string) $request->input('division_search', ''));

        return $query->paginate(self::PER_PAGE, ['*'], 'division_page')->withQueryString();
    }

    private function attachLibraryNames(LengthAwarePaginator $resources): void
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
                $data['schools'] = Cache::remember(
                    "schools_district_{$stationId}",
                    self::CACHE_TTL,
                    fn() => School::where('district_id', $stationId)
                        ->orderBy('school_name')
                        ->get()
                );
                break;

            case self::LEVEL_DIVISION:
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

        // Get all school IDs in this district
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

            // KEY: Put in 'filtered', not 'main'
            return [
                'main' => collect(),
                'filtered' => $libraries
            ];
        }

        // CASE 3: Specific school selected
        $libraries = Cache::remember(
            "school_libraries_{$selectedSchool}",
            self::CACHE_TTL_LIBRARIES,
            fn() => SchoolLibrary::where('school_id', $selectedSchool)->pluck('id')
        );

        return [
            'main' => collect(),
            'filtered' => $libraries
        ];
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

        // Get division libraries (shown as 'main' when filtering)
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

            return [
                'main' => $divisionLibraries,
                'filtered' => $schoolLibraries
            ];
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

            return [
                'main' => $divisionLibraries,
                'filtered' => $schoolLibraries
            ];
        }

        // CASE 4: "All Districts" OR "All Schools" selected
        // Show ALL schools in the entire division
        $allSchoolLibraries = Cache::remember(
            "division_all_school_libraries_{$divisionId}",
            self::CACHE_TTL_LIBRARIES,
            function () use ($divisionId) {
                // Get all districts in this division
                $districtIds = District::where('division_id', $divisionId)->pluck('id');
                // Get all schools in those districts
                $schoolIds = School::whereIn('district_id', $districtIds)->pluck('id');
                // Get all school libraries
                return SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');
            }
        );

        return [
            'main' => $divisionLibraries,
            'filtered' => $allSchoolLibraries
        ];
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

            return [
                'main' => collect(),
                'filtered' => $libraries
            ];
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

            return [
                'main' => collect(),
                'filtered' => $libraries
            ];
        }

        // CASE 4: Specific division selected (but not specific district/school)
        if ($selectedDivision && $selectedDivision !== 'all') {
            $libraries = Cache::remember(
                "division_all_libraries_{$selectedDivision}",
                self::CACHE_TTL_LIBRARIES,
                function () use ($selectedDivision) {
                    // Division libraries
                    $divisionLibs = DivisionLibrary::where('division_id', $selectedDivision)->pluck('id');

                    // All school libraries in this division
                    $districtIds = District::where('division_id', $selectedDivision)->pluck('id');
                    $schoolIds = School::whereIn('district_id', $districtIds)->pluck('id');
                    $schoolLibs = SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');

                    return $divisionLibs->merge($schoolLibs);
                }
            );

            return [
                'main' => collect(),
                'filtered' => $libraries
            ];
        }

        // CASE 5: "All" selected (All Divisions, All Districts, or All Schools)
        // Show everything in the entire region
        $libraries = Cache::remember(
            "region_all_libraries_{$stationId}",
            self::CACHE_TTL_LIBRARIES,
            function () use ($stationId) {
                // Region libraries
                $regionLibs = RegionLibrary::where('region_id', $stationId)->pluck('id');

                // All division libraries
                $divisionIds = Division::where('region_id', $stationId)->pluck('id');
                $divisionLibs = DivisionLibrary::whereIn('division_id', $divisionIds)->pluck('id');

                // All school libraries
                $districtIds = District::whereIn('division_id', $divisionIds)->pluck('id');
                $schoolIds = School::whereIn('district_id', $districtIds)->pluck('id');
                $schoolLibs = SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');

                return $regionLibs->merge($divisionLibs)->merge($schoolLibs);
            }
        );

        return [
            'main' => collect(),
            'filtered' => $libraries
        ];
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

    private function getResources(Request $request, int $level, Collection $libraryIds)
    {
        if ($level === self::LEVEL_DIVISION) {
            return $this->getDivisionResources($request, $libraryIds);
        }

        if ($libraryIds->isEmpty()) {
            return $this->emptyPaginator($request);
        }

        $query = PrintResource::with(['printTitle.authors', 'type', 'printAcquisitions'])
            ->whereIn('library_id', $libraryIds->toArray());

        $this->applySearch($query, (string) $request->input('search', ''));

        return $query->paginate(self::PER_PAGE)->withQueryString();
    }

    private function getDivisionResources(Request $request, Collection $libraryIds)
    {
        if ($libraryIds->isEmpty()) {
            return $this->emptyPaginator($request);
        }

        $query = PrintResource::with(['printTitle.authors', 'type', 'printAcquisitions'])
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

        $query = PrintResource::with(['printTitle.authors', 'type', 'printAcquisitions'])
            ->whereIn('library_id', $libraryIds->toArray());

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

    private function applySearchFallback($query, string $search)
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        $searchLower = '%' . strtolower($search) . '%';

        return $query->where(function ($q) use ($searchLower) {
            $q->where('isbn', 'ILIKE', $searchLower)
            ->orWhere('publisher', 'ILIKE', $searchLower)
            ->orWhere('copyright', 'ILIKE', $searchLower)

            // Title search
            ->orWhereHas('printTitle', fn($qt) =>
                $qt->where('title', 'ILIKE', $searchLower)
            )

            // Author search
            ->orWhereHas('printTitle.authors', fn($qa) =>
                $qa->where('author_name', 'ILIKE', $searchLower)
            )

            // Subject/Grade search
            ->orWhereExists(function ($exists) use ($searchLower) {
                $exists->select(DB::raw(1))
                    ->from('subject_grade_levels as sgl')
                    ->join('subjects as subj', 'sgl.subject_id', '=', 'subj.id')
                    ->join('grade_levels as gl', 'sgl.grade_level_id', '=', 'gl.id')
                    ->whereRaw("sgl.id::text = ANY(string_to_array(print_resources.subject_grade_level_ids, ','))")
                    ->where(function ($match) use ($searchLower) {
                        $match->where('subj.subject_name', 'ILIKE', $searchLower)
                            ->orWhere('gl.grade', 'ILIKE', $searchLower);
                    });
            })

            // Combined library search (3 queries → 1)
            ->orWhereExists(function ($exists) use ($searchLower) {
                $exists->selectRaw('1')
                    ->fromRaw('(
                        SELECT id, library_name FROM school_libraries
                        UNION ALL
                        SELECT id, library_name FROM division_libraries
                        UNION ALL
                        SELECT id, library_name FROM region_libraries
                    ) as all_libraries')
                    ->whereColumn('print_resources.library_id', 'all_libraries.id')
                    ->where('all_libraries.library_name', 'ILIKE', $searchLower);
            });
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
     * Clear caches when organizational structure changes
     */
    public function clearStationCache(string $stationId, int $level): void
    {
        $patterns = match($level) {
            self::LEVEL_SCHOOL => ["school_libraries_{$stationId}", "school_division_libraries_{$stationId}"],
            self::LEVEL_DISTRICT => ["schools_district_{$stationId}", "district_school_libraries_{$stationId}"],
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
     * Clear library name caches
     */
    public function clearLibraryCache(): void
    {
        Cache::flush();
    }
}
