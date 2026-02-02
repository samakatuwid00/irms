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
            $this->attachLibraryNames($divisionResources);
        }

        // Attach library names to resources
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

        // Query division resources
        $query = NonprintResource::with(['nonprintTitle', 'type', 'nonprintAcquisitions'])
            ->whereIn('library_id', $divisionLibraryIds->toArray());

        // Apply search if provided
        $this->applySearch($query, (string) $request->input('division_search', ''));

        return $query->paginate(self::PER_PAGE, ['*'], 'division_page')->withQueryString();
    }

    private function attachLibraryNames(LengthAwarePaginator $resources): void
    {
        if ($resources->isEmpty()) {
            return;
        }

        // Collect all unique library IDs from resources
        $libraryIds = $resources->pluck('library_id')->unique()->filter();

        if ($libraryIds->isEmpty()) {
            // If no library_ids, set default for all resources
            foreach ($resources as $resource) {
                $resource->library_name = 'No Library Assigned';
            }
            return;
        }

        // OPTIMIZATION: Cache library lookups using UNION ALL
        // This is critical for performance at scale
        $libraryIdsKey = $libraryIds->sort()->values()->implode('_');
        $cacheKey = 'library_names_' . md5($libraryIdsKey);

        $allLibraries = Cache::remember(
            $cacheKey,
            self::CACHE_TTL_LIBRARIES,
            function () use ($libraryIds) {
                // Use UNION ALL instead of 3 separate queries
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

        // Attach library name to each resource
        foreach ($resources as $resource) {
            if (!$resource->library_id) {
                $resource->library_name = 'No Library Assigned';
                continue;
            }

            // Look up the library name from the merged collection
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
        // School level: Get only this school's libraries
        $libraries = Cache::remember(
            "school_libraries_{$schoolId}",
            self::CACHE_TTL_LIBRARIES,
            fn() => SchoolLibrary::where('school_id', $schoolId)->pluck('id')
        );

        return [
            'main' => $libraries,
            'filtered' => collect()
        ];
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
        // Cache the complete set of libraries for this division
        return Cache::remember(
            "division_all_libraries_{$divisionId}",
            self::CACHE_TTL_LIBRARIES,
            function () use ($divisionId) {
                // Get division libraries
                $divisionLibs = DivisionLibrary::where('division_id', $divisionId)->pluck('id');

                // Get all school libraries within this division
                $districtIds = District::where('division_id', $divisionId)->pluck('id');
                $schoolIds = School::whereIn('district_id', $districtIds)->pluck('id');
                $schoolLibs = SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');

                return $divisionLibs->merge($schoolLibs);
            }
        );
    }

    private function getLevel4RegionLibraries(string $stationId): Collection
    {
        // Cache the complete set of libraries for this region
        return Cache::remember(
            "region_all_libraries_{$stationId}",
            self::CACHE_TTL_LIBRARIES,
            function () use ($stationId) {
                // Get region libraries
                $regionLibs = RegionLibrary::where('region_id', $stationId)->pluck('id');

                // Get all division libraries in this region
                $divisionIds = Division::where('region_id', $stationId)->pluck('id');
                $divisionLibs = DivisionLibrary::whereIn('division_id', $divisionIds)->pluck('id');

                // Get all school libraries in this region
                $districtIds = District::whereIn('division_id', $divisionIds)->pluck('id');
                $schoolIds = School::whereIn('district_id', $districtIds)->pluck('id');
                $schoolLibs = SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');

                return $regionLibs->merge($divisionLibs)->merge($schoolLibs);
            }
        );
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

        // Query resources by library_id field directly in nonprint_resources table
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

        // Query resources by library_id field directly in nonprint_resources table
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

        // Query resources by library_id field directly in nonprint_resources table
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
     * Apply full-text search using PostgreSQL's tsvector
     */
    private function applySearch($query, string $search)
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        // Use PostgreSQL full-text search with ranking
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
     * Use this if full-text search is not available or for debugging
     *
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
                    ->whereColumn('nonprint_resources.library_id', 'all_libraries.id')
                    ->whereRaw('LOWER(all_libraries.library_name) LIKE ?', [$searchLower]);
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
     * Clear library name caches
     */
    public function clearLibraryCache(): void
    {
        Cache::flush();
    }
}
