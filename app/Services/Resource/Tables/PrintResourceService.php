<?php

namespace App\Services\Resource\Tables;

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
    private const PER_PAGE_DEFAULT    = 10;
    private const PER_PAGE_ALLOWED    = [5, 10, 15, 20];
    private const CACHE_TTL           = 3600;
    private const CACHE_TTL_LIBRARIES = 1800;
    private const CACHE_TTL_HIERARCHY = 7200;

    public const LEVEL_SCHOOL   = 1;
    public const LEVEL_DISTRICT = 2;
    public const LEVEL_DIVISION = 3;
    public const LEVEL_REGION   = 4;

public function getResourcesData(Request $request, int $level, string $stationId): array
{
    $dropdownData = $this->getDropdownData($level, $stationId);
    $libraryIds   = $this->getLibraryIds($request, $level, $stationId, $dropdownData);
 
    $resources         = $this->getResources($request, $level, $libraryIds['main']);
    $filteredResources = $this->getFilteredResources($request, $level, $libraryIds['filtered']);
 
    $divisionResources  = null;
    $divisionLibraryIds = [];
    if ($level === self::LEVEL_SCHOOL) {
        [$divisionResources, $divisionLibraryIds] = $this->getDivisionResourcesForSchool($request, $stationId);
    }
 
    // NEW: Library Hub data for region-level users
    $hubData = [];
    if ($level === self::LEVEL_REGION) {
        $hubData = $this->getHubData($request);
    }
 
    return array_merge([
        'level'              => $level,
        'resources'          => $resources,
        'filteredResources'  => $filteredResources,
        'divisionResources'  => $divisionResources,
        'mainLibraryIds'     => $libraryIds['main']->values()->all(),
        'filteredLibraryIds' => $libraryIds['filtered']->values()->all(),
        'divisionLibraryIds' => $divisionLibraryIds,
        // Hub defaults so the blade never needs isset() guards
        'hubResources'       => null,
        'hubLibraryIds'      => [],
        'perPage'            => $this->resolvePerPage($request),
        'perPageOptions'     => self::PER_PAGE_ALLOWED,
    ], $dropdownData, $hubData);
}


    /** Validate and return the requested per-page value, falling back to the default. */
    private function resolvePerPage(Request $request): int
    {
        $requested = (int) $request->input('per_page', self::PER_PAGE_DEFAULT);
        return in_array($requested, self::PER_PAGE_ALLOWED, true) ? $requested : self::PER_PAGE_DEFAULT;
    }

    // Walk school → district → division to find which division libraries to show
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
            return [$this->emptyPaginator($request), []];
        }

        $query = $this->buildLibraryQuery($divisionLibraryIds);

        // Separate search param so it doesn't collide with the school tab's 'search'
        $this->applySearch($query, (string) $request->input('division_search', ''));

        $paginated = $query->paginate($this->resolvePerPage($request), ['*'], 'division_page')->withQueryString();
        $this->attachLibraryNames($paginated);

        // Return both the paginator and the raw IDs so the blade can scope
        // quantity breakdowns and modals to division libraries only.
        return [$paginated, $divisionLibraryIds->values()->all()];
    }

    // library_name lives on the acquisition, not the resource — pick the first non-null one
    private function attachLibraryNames(LengthAwarePaginator $resources): void
    {
        if ($resources->isEmpty()) {
            return;
        }

        foreach ($resources as $resource) {
            if (empty($resource->library_name)) {
                $firstName = $resource->printAcquisitions
                    ->whereNotNull('library_name')
                    ->value('library_name');

                $resource->library_name = $firstName
                    ?? ($resource->printAcquisitions->isNotEmpty()
                        ? 'Unknown Library'
                        : 'No Library Assigned');
            }
        }
    }

    // Default everything to empty collections so the blade never needs isset() guards
    private function getDropdownData(int $level, string $stationId): array
    {
        $data = [
            'divisions'    => collect(),
            'districts'    => collect(),
            'schools'      => collect(),
            'allDistricts' => collect(),
            'allSchools'   => collect(),
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

                // allSchools depends on districts being loaded first — same request, no extra query
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
                    fn() => Division::where('region_id', $stationId)->orderBy('division_name')->get()
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
            
                // NEW: map of division_id → DivisionLibrary collection (id + library_name)
                // Used by the Library Hub tab to populate the library dropdown client-side.
                $divisionIds = $data['divisions']->pluck('id');
                $data['divisionLibrariesMap'] = Cache::remember(
                    "division_libraries_map_region_{$stationId}",
                    self::CACHE_TTL_LIBRARIES,
                    fn() => DivisionLibrary::whereIn('division_id', $divisionIds)
                        ->orderBy('library_name')
                        ->get(['id', 'division_id', 'library_name'])
                        ->groupBy('division_id')
                );
                break;

        }

        return $data;
    }

    private function getLibraryIds(Request $request, int $level, string $stationId, array $dropdownData): array
    {
        return match($level) {
            self::LEVEL_SCHOOL   => $this->getLevel1Libraries($stationId),
            self::LEVEL_DISTRICT => $this->getLevel2Libraries($request, $stationId, $dropdownData),
            self::LEVEL_DIVISION => $this->getLevel3Libraries($request, $stationId, $dropdownData),
            self::LEVEL_REGION   => $this->getLevel4Libraries($request, $stationId),
            default              => ['main' => collect(), 'filtered' => collect()],
        };
    }

    private function getLevel1Libraries(string $schoolId): array
    {
        $libraries = Cache::remember(
            "school_libraries_{$schoolId}",
            self::CACHE_TTL_LIBRARIES,
            fn() => SchoolLibrary::where('school_id', $schoolId)->pluck('id')
        );

        // School has no filtered tab — just return main
        return ['main' => $libraries, 'filtered' => collect()];
    }

    private function getLevel2Libraries(Request $request, string $districtId, array $dropdownData): array
    {
        $selectedSchool = $request->input('school');
        $schoolIds      = $dropdownData['schools']->pluck('id');

        // No filter: show all schools in the district as the main table
        if (!$request->has('school')) {
            $libraries = Cache::remember(
                "district_school_libraries_{$districtId}",
                self::CACHE_TTL_LIBRARIES,
                fn() => SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id')
            );

            return ['main' => $libraries, 'filtered' => collect()];
        }

        // "All Schools" or specific school: move results to the filtered tab
        if ($selectedSchool === 'all') {
            $libraries = Cache::remember(
                "district_all_school_libraries_{$districtId}",
                self::CACHE_TTL_LIBRARIES,
                fn() => SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id')
            );

            return ['main' => collect(), 'filtered' => $libraries];
        }

        // Specific school selected
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
        $selectedSchool   = $request->input('school');

        // No filter: show only division libraries in the main tab
        if (!$request->has('district') && !$request->has('school')) {
            $libraries = Cache::remember(
                "division_libraries_{$divisionId}",
                self::CACHE_TTL_LIBRARIES,
                fn() => DivisionLibrary::where('division_id', $divisionId)->pluck('id')
            );

            return ['main' => $libraries, 'filtered' => collect()];
        }

        // Division libraries always go in main when a school filter is active
        $divisionLibraries = Cache::remember(
            "division_libraries_{$divisionId}",
            self::CACHE_TTL_LIBRARIES,
            fn() => DivisionLibrary::where('division_id', $divisionId)->pluck('id')
        );

        // Specific school: show that school's libraries in the filtered tab
        if ($selectedSchool && $selectedSchool !== 'all') {
            $schoolLibraries = Cache::remember(
                "school_libraries_{$selectedSchool}",
                self::CACHE_TTL_LIBRARIES,
                fn() => SchoolLibrary::where('school_id', $selectedSchool)->pluck('id')
            );

            return ['main' => $divisionLibraries, 'filtered' => $schoolLibraries];
        }

        // Specific district (but not a specific school)
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

        // "All Districts" or "All Schools" — show every school in the division
        $allSchoolLibraries = Cache::remember(
            "division_all_school_libraries_{$divisionId}",
            self::CACHE_TTL_LIBRARIES,
            function () use ($divisionId) {
                $districtIds = District::where('division_id', $divisionId)->pluck('id');
                $schoolIds   = School::whereIn('district_id', $districtIds)->pluck('id');
                return SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');
            }
        );

        return ['main' => $divisionLibraries, 'filtered' => $allSchoolLibraries];
    }

    private function getLevel4Libraries(Request $request, string $stationId): array
    {
        $selectedDivision = $request->input('division');
        $selectedDistrict = $request->input('district');
        $selectedSchool   = $request->input('school');

        // Region shows nothing until a filter is picked — the dataset would be enormous otherwise
        if (!$request->has('division') && !$request->has('district') && !$request->has('school')) {
            return ['main' => collect(), 'filtered' => collect()];
        }

        // Most specific filter wins — school > district > division > all
        if ($selectedSchool && $selectedSchool !== 'all') {
            $libraries = Cache::remember(
                "school_libraries_{$selectedSchool}",
                self::CACHE_TTL_LIBRARIES,
                fn() => SchoolLibrary::where('school_id', $selectedSchool)->pluck('id')
            );

            return ['main' => collect(), 'filtered' => $libraries];
        }

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

        if ($selectedDivision && $selectedDivision !== 'all') {
            $libraries = Cache::remember(
                "division_all_libraries_{$selectedDivision}",
                self::CACHE_TTL_LIBRARIES,
                function () use ($selectedDivision) {
                    $divisionLibs = DivisionLibrary::where('division_id', $selectedDivision)->pluck('id');
                    $districtIds  = District::where('division_id', $selectedDivision)->pluck('id');
                    $schoolIds    = School::whereIn('district_id', $districtIds)->pluck('id');
                    $schoolLibs   = SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');

                    return $divisionLibs->merge($schoolLibs);
                }
            );

            return ['main' => collect(), 'filtered' => $libraries];
        }

        // "All" selected — walk the entire region hierarchy
        $libraries = Cache::remember(
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

        return ['main' => collect(), 'filtered' => $libraries];
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

    // The second whereHas guarantees at least one acquisition exists globally —
    // resources with no acquisitions at any level shouldn't appear in any view
    private function buildLibraryQuery(Collection $libraryIds)
    {
        return PrintResource::with(['printTitle.authors', 'type', 'printAcquisitions'])
            ->whereHas('printAcquisitions', function ($q) use ($libraryIds) {
                $q->whereIn('library_id', $libraryIds->toArray());
            })
            ->whereHas('printAcquisitions');
    }

    private function getResources(Request $request, int $level, Collection $libraryIds)
    {
        if ($level === self::LEVEL_DIVISION) {
            // Division uses a different search param name to avoid collision with school_search
            return $this->getDivisionResources($request, $libraryIds);
        }

        if ($libraryIds->isEmpty()) {
            return $this->emptyPaginator($request);
        }

        $query = $this->buildLibraryQuery($libraryIds);
        $this->applySearch($query, (string) $request->input('search', ''));

        $paginated = $query->paginate($this->resolvePerPage($request))->withQueryString();
        $this->attachLibraryNames($paginated);
        return $paginated;
    }

    private function getDivisionResources(Request $request, Collection $libraryIds)
    {
        if ($libraryIds->isEmpty()) {
            return $this->emptyPaginator($request);
        }

        $query = $this->buildLibraryQuery($libraryIds);
        $this->applySearch($query, (string) $request->input('division_search', ''));

        $paginated = $query->paginate($this->resolvePerPage($request))->withQueryString();
        $this->attachLibraryNames($paginated);
        return $paginated;
    }

    private function getFilteredResources(Request $request, int $level, Collection $libraryIds)
    {
        if (!$this->shouldShowFilteredResources($request, $level) || $libraryIds->isEmpty()) {
            return $this->emptyPaginator($request);
        }

        $query = $this->buildLibraryQuery($libraryIds);

        // Division filtered tab uses 'school_search' to avoid clashing with 'division_search'
        $searchParam = $level === self::LEVEL_DIVISION ? 'school_search' : 'search';
        $this->applySearch($query, (string) $request->input($searchParam, ''));

        $paginated = $query->paginate($this->resolvePerPage($request))->withQueryString();
        $this->attachLibraryNames($paginated);
        return $paginated;
    }

    private function shouldShowFilteredResources(Request $request, int $level): bool
    {
        return match($level) {
            self::LEVEL_DISTRICT => $request->has('school'),
            self::LEVEL_DIVISION => $request->has('district') || $request->has('school'),
            self::LEVEL_REGION   => $request->has('division') || $request->has('district') || $request->has('school'),
            default              => false,
        };
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

    // Returns an empty paginator with the right metadata so the blade pagination
    // links render correctly even when there's nothing to show
    private function emptyPaginator(Request $request)
    {
        return new LengthAwarePaginator([], 0, self::PER_PAGE_DEFAULT, 1, [
            'path'  => $request->url(),
            'query' => $request->query(),
        ]);
    }

    public function clearStationCache(string $stationId, int $level): void
    {
        $patterns = match($level) {
            self::LEVEL_SCHOOL   => [
                "school_libraries_{$stationId}",
                "school_division_libraries_{$stationId}",
            ],
            self::LEVEL_DISTRICT => [
                "schools_district_{$stationId}",
                "district_school_libraries_{$stationId}",
            ],
            self::LEVEL_DIVISION => [
                "districts_division_{$stationId}",
                "schools_division_{$stationId}",
                "division_libraries_{$stationId}",
                "division_all_libraries_{$stationId}",
            ],
            self::LEVEL_REGION   => [
                "divisions_region_{$stationId}",
                "region_all_libraries_{$stationId}",
                "all_districts",
                "all_schools",
            ],
            default => [],
        };

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    public function clearLibraryCache(): void
    {
        Cache::flush();
    }

    public function getHubData(Request $request): array
    {
        $selectedDivision = $request->input('hub_division', '');
        $selectedLibrary  = $request->input('hub_library', '');
    
        // No division selected yet → return empty state
        if (!$selectedDivision) {
            return [
                'hubResources'  => $this->emptyPaginator($request),
                'hubLibraryIds' => [],
            ];
        }
    
        // Resolve which DivisionLibrary IDs to query
        if ($selectedLibrary && $selectedLibrary !== 'all') {
            // Specific library
            $libraryIds = collect([$selectedLibrary]);
        } else {
            // All libraries in the selected division
            $libraryIds = Cache::remember(
                "division_libraries_{$selectedDivision}",
                self::CACHE_TTL_LIBRARIES,
                fn() => DivisionLibrary::where('division_id', $selectedDivision)->pluck('id')
            );
        }
    
        if ($libraryIds->isEmpty()) {
            return [
                'hubResources'  => $this->emptyPaginator($request),
                'hubLibraryIds' => [],
            ];
        }
    
        $query = $this->buildLibraryQuery($libraryIds);
        $this->applySearch($query, (string) $request->input('hub_search', ''));
    
        $paginated = $query->paginate($this->resolvePerPage($request), ['*'], 'hub_page')
                        ->withQueryString();
        $this->attachLibraryNames($paginated);
    
        return [
            'hubResources'  => $paginated,
            'hubLibraryIds' => $libraryIds->values()->all(),
        ];
    }
}