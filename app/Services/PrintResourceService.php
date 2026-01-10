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

/**
 * Service for managing print resource data across different organizational levels.
 *
 * Handles resource retrieval, filtering, and search functionality for a hierarchical
 * structure: School -> District -> Division -> Region.
 *
 * Note: Libraries exist at School, Division, and Region levels only. Districts do not have libraries.
 * The library_id is stored directly in the print_resources table.
 */
class PrintResourceService
{
    /** Number of items per page for pagination */
    private const PER_PAGE = 5;

    /** Cache time-to-live in seconds (1 hour) */
    private const CACHE_TTL = 3600;

    /** Organizational hierarchy level constants */
    public const LEVEL_SCHOOL = 1;
    public const LEVEL_DISTRICT = 2;
    public const LEVEL_DIVISION = 3;
    public const LEVEL_REGION = 4;

    /**
     * Get resources data for a specific organizational level.
     *
     * Main entry point that orchestrates fetching dropdown data, determining
     * library IDs, and retrieving resources based on the level and filters.
     *
     * @param Request $request The HTTP request containing filter parameters
     * @param int $level The organizational level (1-4)
     * @param string $stationId The ID of the current station/entity
     * @return array Contains level, resources, filtered resources, and dropdown data
     */
    public function getResourcesData(Request $request, int $level, string $stationId): array
    {
        // Get dropdown options for filters (schools, districts, divisions)
        $dropdownData = $this->getDropdownData($level, $stationId);

        // Determine which library IDs to query based on level and filters
        $libraryIds = $this->getLibraryIds($request, $level, $stationId, $dropdownData);

        // Get main resources and filtered resources
        $resources = $this->getResources($request, $level, $libraryIds['main']);
        $filteredResources = $this->getFilteredResources($request, $level, $libraryIds['filtered']);

        // Attach library names to resources
        $this->attachLibraryNames($resources);
        $this->attachLibraryNames($filteredResources);

        return array_merge([
            'level' => $level,
            'resources' => $resources,
            'filteredResources' => $filteredResources,
        ], $dropdownData);
    }

    /**
     * Attach library names to paginated resources.
     *
     * Fetches library names from three library tables (School, Division, Region).
     * Since library_id can point to any of these three tables, we fetch from all
     * and match based on the ID.
     *
     * @param LengthAwarePaginator $resources The paginated resources
     * @return void
     */
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

        // Fetch libraries from all three tables using the same IDs
        $schoolLibraries = SchoolLibrary::whereIn('id', $libraryIds->toArray())
            ->get(['id', 'library_name'])
            ->keyBy('id');

        $divisionLibraries = DivisionLibrary::whereIn('id', $libraryIds->toArray())
            ->get(['id', 'library_name'])
            ->keyBy('id');

        $regionLibraries = RegionLibrary::whereIn('id', $libraryIds->toArray())
            ->get(['id', 'library_name'])
            ->keyBy('id');

        // Merge all library collections into one lookup array
        $allLibraries = $schoolLibraries
            ->merge($divisionLibraries)
            ->merge($regionLibraries);

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

    /**
     * Get dropdown data for filters based on organizational level.
     *
     * Retrieves and caches the relevant entities (schools, districts, divisions)
     * that should be available for filtering at the current level.
     *
     * @param int $level The organizational level
     * @param string $stationId The ID of the current station/entity
     * @return array Contains divisions, districts, schools collections
     */
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
                    fn() => collect(District::getDistricts()->getData() ?? [])
                );

                $data['allSchools'] = Cache::remember(
                    'all_schools',
                    self::CACHE_TTL,
                    fn() => collect(School::getSchools()->getData() ?? [])
                );
                break;
        }

        return $data;
    }

    /**
     * Get library IDs to query based on level and request filters.
     *
     * Returns two sets of IDs:
     * - 'main': Primary libraries for the current level
     * - 'filtered': Libraries based on user's filter selections
     *
     * @param Request $request The HTTP request containing filter parameters
     * @param int $level The organizational level
     * @param string $stationId The ID of the current station/entity
     * @param array $dropdownData Available dropdown options
     * @return array Contains 'main' and 'filtered' library ID collections
     */
    private function getLibraryIds(Request $request, int $level, string $stationId, array $dropdownData): array
    {
        return match($level) {
            self::LEVEL_SCHOOL => $this->getLevel1LibraryIds($stationId),
            self::LEVEL_DISTRICT => $this->getLevel2LibraryIds($request, $dropdownData),
            self::LEVEL_DIVISION => $this->getLevel3LibraryIds($request, $stationId, $dropdownData),
            self::LEVEL_REGION => $this->getLevel4LibraryIds($request, $stationId),
            default => ['main' => collect(), 'filtered' => collect()],
        };
    }

    /**
     * Get library IDs for school level (Level 1).
     *
     * At school level, only show resources from this specific school's library.
     *
     * @param string $stationId The school ID
     * @return array Contains 'main' with school library IDs, 'filtered' is empty
     */
    private function getLevel1LibraryIds(string $stationId): array
    {
        $main = SchoolLibrary::where('school_id', $stationId)->pluck('id');

        return ['main' => $main, 'filtered' => collect()];
    }

    /**
     * Get library IDs for district level (Level 2).
     *
     * Districts don't have their own libraries. Shows resources from school libraries.
     * No main resources - only filtered based on school selection.
     *
     * @param Request $request The HTTP request containing school filter
     * @param array $dropdownData Available schools in this district
     * @return array Contains empty 'main' and 'filtered' with selected school library IDs
     */
    private function getLevel2LibraryIds(Request $request, array $dropdownData): array
    {
        if (!$request->has('school')) {
            return ['main' => collect(), 'filtered' => collect()];
        }

        $selectedSchool = $request->input('school');
        $schools = $dropdownData['schools'];

        // If specific school selected, get its libraries; otherwise get all schools in district
        $filtered = ($selectedSchool && $selectedSchool !== 'all')
            ? SchoolLibrary::where('school_id', $selectedSchool)->pluck('id')
            : SchoolLibrary::whereIn('school_id', $schools->pluck('id'))->pluck('id');

        return ['main' => collect(), 'filtered' => $filtered];
    }

    /**
     * Get library IDs for division level (Level 3).
     *
     * Main resources from division libraries, filtered by district/school selection.
     *
     * @param Request $request The HTTP request containing filter parameters
     * @param string $stationId The division ID
     * @param array $dropdownData Available districts and schools
     * @return array Contains 'main' with division library IDs and 'filtered' based on selection
     */
    private function getLevel3LibraryIds(Request $request, string $stationId, array $dropdownData): array
    {
        $districts = $dropdownData['districts'];

        // Main resources come from division libraries
        $main = DivisionLibrary::where('division_id', $stationId)->pluck('id');

        // Filtered resources based on district/school selection
        $filtered = $this->getLevel3FilteredIds($request, $districts);

        return ['main' => $main, 'filtered' => $filtered];
    }

    /**
     * Get filtered library IDs for division level based on user selections.
     *
     * Handles three scenarios:
     * 1. Specific school selected -> SchoolLibrary
     * 2. Specific district selected -> SchoolLibraries in that district
     * 3. All districts selected -> SchoolLibraries in all districts within division
     *
     * Note: Districts don't have libraries, so we only get school libraries.
     *
     * @param Request $request The HTTP request containing filter parameters
     * @param Collection $districts Available districts in this division
     * @return Collection School library IDs based on filter selection
     */
    private function getLevel3FilteredIds(Request $request, Collection $districts): Collection
    {
        $selectedSchool = $request->input('school');
        $selectedDistrict = $request->input('district');

        // Priority 1: Specific school
        if ($selectedSchool && $selectedSchool !== 'all') {
            return SchoolLibrary::where('school_id', $selectedSchool)->pluck('id');
        }

        // Priority 2: Specific district (get all school libraries in that district)
        if ($selectedDistrict && $selectedDistrict !== 'all') {
            $schoolIds = School::where('district_id', $selectedDistrict)->pluck('id');
            return SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');
        }

        // Priority 3: All districts (get all school libraries in all districts)
        if ($selectedDistrict === 'all') {
            $schoolIds = School::whereIn('district_id', $districts->pluck('id'))->pluck('id');
            return SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');
        }

        return collect();
    }

    /**
     * Get library IDs for region level (Level 4).
     *
     * No main resources at region level - all resources are filtered based on
     * division/district/school selection.
     *
     * @param Request $request The HTTP request containing filter parameters
     * @param string $stationId The region ID
     * @return array Contains empty 'main' and 'filtered' based on user selection
     */
    private function getLevel4LibraryIds(Request $request, string $stationId): array
    {
        $selectedDivision = $request->input('division');
        $selectedDistrict = $request->input('district');
        $selectedSchool = $request->input('school');

        // Require at least one filter to be selected
        if (!$request->has('division') && !$request->has('district') && !$request->has('school')) {
            return ['main' => collect(), 'filtered' => collect()];
        }

        // Priority 1: Specific school selected -> only school libraries
        if ($selectedSchool && $selectedSchool !== 'all') {
            return [
                'main' => collect(),
                'filtered' => SchoolLibrary::where('school_id', $selectedSchool)->pluck('id')
            ];
        }

        // Priority 2: Specific district selected -> school libraries in that district
        if ($selectedDistrict && $selectedDistrict !== 'all') {
            $schoolIds = School::where('district_id', $selectedDistrict)->pluck('id');
            return [
                'main' => collect(),
                'filtered' => SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id')
            ];
        }

        // Priority 3: Specific division selected -> division + school libraries
        if ($selectedDivision && $selectedDivision !== 'all') {
            return [
                'main' => collect(),
                'filtered' => $this->getLevel4DivisionLibraries($selectedDivision)
            ];
        }

        // Default: All divisions in region -> region + division + school libraries
        return [
            'main' => collect(),
            'filtered' => $this->getLevel4RegionLibraries($stationId)
        ];
    }

    /**
     * Get all library IDs within a specific division.
     *
     * Includes division-level libraries and all school libraries
     * within districts that belong to this division.
     *
     * Note: Districts don't have libraries.
     *
     * @param string $divisionId The division ID
     * @return Collection Combined division and school library IDs
     */
    private function getLevel4DivisionLibraries(string $divisionId): Collection
    {
        // Get division libraries
        $divisionLibs = DivisionLibrary::where('division_id', $divisionId)->pluck('id');

        // Get all school libraries within this division
        $districtIds = District::where('division_id', $divisionId)->pluck('id');
        $schoolIds = School::whereIn('district_id', $districtIds)->pluck('id');
        $schoolLibs = SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');

        return $divisionLibs->merge($schoolLibs);
    }

    /**
     * Get all library IDs within a specific region.
     *
     * Includes region libraries, all division libraries, and all school libraries
     * within divisions that belong to this region.
     *
     * Note: Districts don't have libraries.
     *
     * @param string $stationId The region ID
     * @return Collection Combined region, division, and school library IDs
     */
    private function getLevel4RegionLibraries(string $stationId): Collection
    {
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

    /**
     * Get paginated print resources for the given library IDs.
     *
     * Special handling for division level which uses 'division_search' parameter.
     * Includes related data (title, authors, type, acquisitions).
     * Filters resources by library_id field in print_resources table.
     *
     * @param Request $request The HTTP request containing search parameters
     * @param int $level The organizational level
     * @param Collection $libraryIds Library IDs to query
     * @return LengthAwarePaginator Paginated resources
     */
    private function getResources(Request $request, int $level, Collection $libraryIds)
    {
        // Division level uses different search parameter
        if ($level === self::LEVEL_DIVISION) {
            return $this->getDivisionResources($request, $libraryIds);
        }

        if ($libraryIds->isEmpty()) {
            return $this->emptyPaginator($request);
        }

        // Query resources by library_id field directly in print_resources table
        $query = PrintResource::with(['printTitle.authors', 'type', 'printAcquisitions'])
            ->whereIn('library_id', $libraryIds->toArray());

        $this->applySearch($query, (string) $request->input('search', ''));

        return $query->paginate(self::PER_PAGE)->withQueryString();
    }

    /**
     * Get paginated resources specifically for division level.
     *
     * Uses 'division_search' parameter instead of 'search'.
     * Filters resources by library_id field in print_resources table.
     *
     * @param Request $request The HTTP request containing division_search parameter
     * @param Collection $libraryIds Library IDs to query
     * @return LengthAwarePaginator Paginated resources
     */
    private function getDivisionResources(Request $request, Collection $libraryIds)
    {
        if ($libraryIds->isEmpty()) {
            return $this->emptyPaginator($request);
        }

        // Query resources by library_id field directly in print_resources table
        $query = PrintResource::with(['printTitle.authors', 'type', 'printAcquisitions'])
            ->whereIn('library_id', $libraryIds->toArray());

        $this->applySearch($query, (string) $request->input('division_search', ''));

        return $query->paginate(self::PER_PAGE)->withQueryString();
    }

    /**
     * Get paginated filtered resources based on user selections.
     *
     * Only returns results when appropriate filters are applied for the level.
     * Uses different search parameters for division level.
     * Filters resources by library_id field in print_resources table.
     *
     * @param Request $request The HTTP request containing search and filter parameters
     * @param int $level The organizational level
     * @param Collection $libraryIds Filtered library IDs to query
     * @return LengthAwarePaginator Paginated filtered resources
     */
    private function getFilteredResources(Request $request, int $level, Collection $libraryIds)
    {
        $shouldShowFiltered = $this->shouldShowFilteredResources($request, $level);

        if (!$shouldShowFiltered || $libraryIds->isEmpty()) {
            return $this->emptyPaginator($request);
        }

        // Query resources by library_id field directly in print_resources table
        $query = PrintResource::with(['printTitle.authors', 'type', 'printAcquisitions'])
            ->whereIn('library_id', $libraryIds->toArray());

        // Division level uses 'school_search', others use 'search'
        $searchParam = $level === self::LEVEL_DIVISION ? 'school_search' : 'search';
        $this->applySearch($query, (string) $request->input($searchParam, ''));

        return $query->paginate(self::PER_PAGE)->withQueryString();
    }

    /**
     * Determine if filtered resources should be displayed.
     *
     * Based on whether appropriate filters have been applied for the level:
     * - District: requires school selection
     * - Division: requires district or school selection
     * - Region: requires division, district, or school selection
     *
     * @param Request $request The HTTP request containing filter parameters
     * @param int $level The organizational level
     * @return bool True if filtered resources should be shown
     */
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
     * Apply search filters to the query.
     *
     * Searches across multiple fields:
     * - ISBN, Publisher, Copyright (direct resource fields)
     * - Title (from related printTitle)
     * - Author names (from related authors through printTitle)
     * - Subject names and grade levels (from related subject_grade_levels)
     * - Library name (from SchoolLibrary, DivisionLibrary, or RegionLibrary)
     *
     * @param mixed $query The Eloquent query builder
     * @param string $search The search term
     * @return mixed The modified query builder
     */
    private function applySearch($query, string $search)
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        $searchLower = '%' . strtolower($search) . '%';

        return $query->where(function ($q) use ($searchLower) {
            // Search in direct resource fields
            $q->whereRaw('LOWER(isbn) LIKE ?', [$searchLower])
            ->orWhereRaw('LOWER(publisher) LIKE ?', [$searchLower])
            ->orWhereRaw('LOWER(copyright) LIKE ?', [$searchLower])
            // Search in title
            ->orWhereHas('printTitle', fn($qt) =>
                $qt->whereRaw('LOWER(title) LIKE ?', [$searchLower])
            )
            // Search in author names
            ->orWhereHas('printTitle.authors', fn($qa) =>
                $qa->whereRaw('LOWER(author_name) LIKE ?', [$searchLower])
            )
            // Search in subjects and grade levels
            ->orWhereExists(function ($exists) use ($searchLower) {
                $exists->select(DB::raw(1))
                        ->from('subject_grade_levels as sgl')
                        ->join('subjects as subj', 'sgl.subject_id', '=', 'subj.id')
                        ->join('grade_levels as gl', 'sgl.grade_level_id', '=', 'gl.id')
                        ->whereRaw("sgl.id::text = ANY(string_to_array(print_resources.subject_grade_level_ids, ','))")
                        ->where(function ($match) use ($searchLower) {
                            $match->whereRaw('LOWER(subj.subject_name) LIKE ?', [$searchLower])
                                ->orWhereRaw('LOWER(gl.grade) LIKE ?', [$searchLower]);
                        });
            })
            // Search in library names (check all three library tables)
            ->orWhereExists(function ($exists) use ($searchLower) {
                $exists->select(DB::raw(1))
                        ->from('school_libraries as sl')
                        ->whereColumn('print_resources.library_id', 'sl.id')
                        ->whereRaw('LOWER(sl.library_name) LIKE ?', [$searchLower]);
            })
            ->orWhereExists(function ($exists) use ($searchLower) {
                $exists->select(DB::raw(1))
                        ->from('division_libraries as dl')
                        ->whereColumn('print_resources.library_id', 'dl.id')
                        ->whereRaw('LOWER(dl.library_name) LIKE ?', [$searchLower]);
            })
            ->orWhereExists(function ($exists) use ($searchLower) {
                $exists->select(DB::raw(1))
                        ->from('region_libraries as rl')
                        ->whereColumn('print_resources.library_id', 'rl.id')
                        ->whereRaw('LOWER(rl.library_name) LIKE ?', [$searchLower]);
            });
        });
    }

    /**
     * Create an empty paginator for cases with no results.
     *
     * Maintains proper pagination structure and query strings.
     *
     * @param Request $request The HTTP request for path and query context
     * @return LengthAwarePaginator Empty paginator with correct metadata
     */
    private function emptyPaginator(Request $request)
    {
        return new LengthAwarePaginator([], 0, self::PER_PAGE, 1, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);
    }
}
