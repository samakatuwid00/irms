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
    /** Number of items per page for pagination */
    private const PER_PAGE = 5;

    /** Cache time-to-live in seconds (1 hour) */
    private const CACHE_TTL = 3600;

    /** Organizational hierarchy level constants */
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
        // Get the school's division
        $school = School::find($schoolId);
        if (!$school || !$school->district_id) {
            return $this->emptyPaginator($request);
        }

        $district = District::find($school->district_id);
        if (!$district || !$district->division_id) {
            return $this->emptyPaginator($request);
        }

        // Get division library IDs
        $divisionLibraryIds = DivisionLibrary::where('division_id', $district->division_id)->pluck('id');

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

    private function getLevel1LibraryIds(string $stationId): array
    {
        $main = SchoolLibrary::where('school_id', $stationId)->pluck('id');

        return ['main' => $main, 'filtered' => collect()];
    }

    private function getLevel2LibraryIds(Request $request, array $dropdownData): array
    {
        if (!$request->has('school')) {
            return ['main' => collect(), 'filtered' => collect()];
        }

        $selectedSchool = $request->input('school');

        // Handle "All Schools" selection
        if ($selectedSchool === 'all') {
            $schoolIds = $dropdownData['schools']->pluck('id');
            return [
                'main' => collect(),
                'filtered' => SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id')
            ];
        }

        // Specific school selected
        return [
            'main' => collect(),
            'filtered' => SchoolLibrary::where('school_id', $selectedSchool)->pluck('id')
        ];
    }

    private function getLevel3LibraryIds(Request $request, string $stationId, array $dropdownData): array
    {
        $selectedDistrict = $request->input('district');
        $selectedSchool = $request->input('school');

        // No filters selected -> show division resources only
        if (!$request->has('district') && !$request->has('school')) {
            $main = DivisionLibrary::where('division_id', $stationId)->pluck('id');
            return ['main' => $main, 'filtered' => collect()];
        }

        // Specific school selected -> only school resources
        if ($selectedSchool && $selectedSchool !== 'all') {
            return [
                'main' => DivisionLibrary::where('division_id', $stationId)->pluck('id'),
                'filtered' => SchoolLibrary::where('school_id', $selectedSchool)->pluck('id')
            ];
        }

        // Specific district selected -> all schools in that district
        if ($selectedDistrict && $selectedDistrict !== 'all') {
            $schoolIds = School::where('district_id', $selectedDistrict)->pluck('id');
            return [
                'main' => DivisionLibrary::where('division_id', $stationId)->pluck('id'),
                'filtered' => SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id')
            ];
        }

        // "All Districts" or "All Schools" -> all schools in division
        $districtIds = $dropdownData['districts']->pluck('id');
        $schoolIds = School::whereIn('district_id', $districtIds)->pluck('id');

        return [
            'main' => DivisionLibrary::where('division_id', $stationId)->pluck('id'),
            'filtered' => SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id')
        ];
    }

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

    private function applySearch($query, string $search)
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
            // Search in library names (check all three library tables)
            ->orWhereExists(function ($exists) use ($searchLower) {
                $exists->select(DB::raw(1))
                        ->from('school_libraries as sl')
                        ->whereColumn('nonprint_resources.library_id', 'sl.id')
                        ->whereRaw('LOWER(sl.library_name) LIKE ?', [$searchLower]);
            })
            ->orWhereExists(function ($exists) use ($searchLower) {
                $exists->select(DB::raw(1))
                        ->from('division_libraries as dl')
                        ->whereColumn('nonprint_resources.library_id', 'dl.id')
                        ->whereRaw('LOWER(dl.library_name) LIKE ?', [$searchLower]);
            })
            ->orWhereExists(function ($exists) use ($searchLower) {
                $exists->select(DB::raw(1))
                        ->from('region_libraries as rl')
                        ->whereColumn('nonprint_resources.library_id', 'rl.id')
                        ->whereRaw('LOWER(rl.library_name) LIKE ?', [$searchLower]);
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
}
