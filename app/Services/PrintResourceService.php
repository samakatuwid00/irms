<?php

namespace App\Services;

use App\Models\PrintResource;
use App\Models\School;
use App\Models\District;
use App\Models\Division;
use App\Models\SchoolLibrary;
use App\Models\DivisionLibrary;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PrintResourceService
{
    public function getResourcesData(Request $request, int $level, string $stationId): array
    {
        // Get dropdown data
        $dropdownData = $this->getDropdownData($level, $stationId);

        // Get library IDs based on level and filters
        $libraryIds = $this->getLibraryIds($request, $level, $stationId, $dropdownData);

        // Get resources
        $resources = $this->getResources($request, $level, $libraryIds['main']);
        $filteredResources = $this->getFilteredResources($request, $level, $libraryIds['filtered']);

        return array_merge([
            'level' => $level,
            'resources' => $resources,
            'filteredResources' => $filteredResources,
        ], $dropdownData);
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
            case 2: // District
                $data['schools'] = School::where('district_id', $stationId)
                    ->orderBy('school_name')
                    ->get();
                break;

            case 3: // Division
                $data['districts'] = District::where('division_id', $stationId)
                    ->orderBy('district_name')
                    ->get();
                $data['allSchools'] = School::whereIn('district_id', $data['districts']->pluck('id'))
                    ->orderBy('school_name')
                    ->get(['id', 'school_name', 'district_id']);
                break;

            case 4: // Region
                $data['divisions'] = Division::where('region_id', $stationId)
                    ->orderBy('division_name')
                    ->get();
                $data['allDistricts'] = collect(District::getDistricts()->getData() ?? []);
                $data['allSchools'] = collect(School::getSchools()->getData() ?? []);
                break;
        }

        return $data;
    }

    private function getLibraryIds(Request $request, int $level, string $stationId, array $dropdownData): array
    {
        return match($level) {
            1 => $this->getLevel1LibraryIds($stationId),
            2 => $this->getLevel2LibraryIds($request, $dropdownData),
            3 => $this->getLevel3LibraryIds($request, $stationId, $dropdownData),
            4 => $this->getLevel4LibraryIds($request, $stationId),
            default => ['main' => collect(), 'filtered' => collect()],
        };
    }

    private function getLevel1LibraryIds(string $stationId): array
    {
        $main = SchoolLibrary::where('school_id', $stationId)
            ->pluck('id')
            ->map(fn($id) => (string) $id);

        return ['main' => $main, 'filtered' => collect()];
    }

    private function getLevel2LibraryIds(Request $request, array $dropdownData): array
    {
        $schools = $dropdownData['schools'];
        $filtered = collect();

        if ($request->has('school')) {
            $selectedSchool = $request->input('school');

            if ($selectedSchool === 'all' || !$selectedSchool) {
                $schoolIds = $schools->pluck('id')->map(fn($id) => (string) $id);
                $filtered = SchoolLibrary::whereIn('school_id', $schoolIds)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);
            } elseif ($selectedSchool) {
                $filtered = SchoolLibrary::where('school_id', $selectedSchool)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);
            }
        }

        return ['main' => collect(), 'filtered' => $filtered];
    }

    private function getLevel3LibraryIds(Request $request, string $stationId, array $dropdownData): array
    {
        $districts = $dropdownData['districts'];

        $main = DivisionLibrary::where('division_id', $stationId)
            ->pluck('id')
            ->map(fn($id) => (string) $id);

        $filtered = $this->getLevel3FilteredIds($request, $districts);

        return ['main' => $main, 'filtered' => $filtered];
    }

    private function getLevel3FilteredIds(Request $request, Collection $districts): Collection
    {
        $selectedSchool = $request->input('school');
        $selectedDistrict = $request->input('district');

        if ($selectedSchool && $selectedSchool !== 'all') {
            return SchoolLibrary::where('school_id', $selectedSchool)
                ->pluck('id')
                ->map(fn($id) => (string) $id);
        }

        if ($selectedDistrict && $selectedDistrict !== 'all') {
            $schoolIds = School::where('district_id', $selectedDistrict)
                ->pluck('id')
                ->map(fn($id) => (string) $id);
            return SchoolLibrary::whereIn('school_id', $schoolIds)
                ->pluck('id')
                ->map(fn($id) => (string) $id);
        }

        if ($selectedDistrict === 'all') {
            $schoolIds = School::whereIn('district_id', $districts->pluck('id'))
                ->pluck('id')
                ->map(fn($id) => (string) $id);
            return SchoolLibrary::whereIn('school_id', $schoolIds)
                ->pluck('id')
                ->map(fn($id) => (string) $id);
        }

        return collect();
    }

    private function getLevel4LibraryIds(Request $request, string $stationId): array
    {
        $selectedDivision = $request->input('division');
        $selectedDistrict = $request->input('district');
        $selectedSchool = $request->input('school');

        // Specific school selected
        if ($request->has('school') && $selectedSchool !== 'all' && $selectedSchool) {
            return [
                'main' => collect(),
                'filtered' => SchoolLibrary::where('school_id', $selectedSchool)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id)
            ];
        }

        // Specific district selected
        if ($request->has('district') && $selectedDistrict !== 'all' && $selectedDistrict) {
            $schoolIds = School::where('district_id', $selectedDistrict)
                ->pluck('id')
                ->map(fn($id) => (string) $id);

            return [
                'main' => collect(),
                'filtered' => SchoolLibrary::whereIn('school_id', $schoolIds)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id)
            ];
        }

        // Specific division selected
        if ($request->has('division') && $selectedDivision !== 'all' && $selectedDivision) {
            return [
                'main' => collect(),
                'filtered' => $this->getLevel4DivisionLibraries($selectedDivision)
            ];
        }

        // "All" selected - get all libraries in the region
        if ($request->has('division') || $request->has('district') || $request->has('school')) {
            return [
                'main' => collect(),
                'filtered' => $this->getLevel4RegionLibraries($stationId)
            ];
        }

        return ['main' => collect(), 'filtered' => collect()];
    }

    private function getLevel4DivisionLibraries(string $divisionId): Collection
    {
        $divisionLibs = DivisionLibrary::where('division_id', $divisionId)
            ->pluck('id')
            ->map(fn($id) => (string) $id);

        $districtIds = District::where('division_id', $divisionId)
            ->pluck('id')
            ->map(fn($id) => (string) $id);

        $schoolIds = School::whereIn('district_id', $districtIds)
            ->pluck('id')
            ->map(fn($id) => (string) $id);

        $schoolLibs = SchoolLibrary::whereIn('school_id', $schoolIds)
            ->pluck('id')
            ->map(fn($id) => (string) $id);

        return $divisionLibs->merge($schoolLibs);
    }

    private function getLevel4RegionLibraries(string $stationId): Collection
    {
        $divisionIds = Division::where('region_id', $stationId)
            ->pluck('id')
            ->map(fn($id) => (string) $id);

        $divisionLibs = DivisionLibrary::whereIn('division_id', $divisionIds)
            ->pluck('id')
            ->map(fn($id) => (string) $id);

        $districtIds = District::whereIn('division_id', $divisionIds)
            ->pluck('id')
            ->map(fn($id) => (string) $id);

        $schoolIds = School::whereIn('district_id', $districtIds)
            ->pluck('id')
            ->map(fn($id) => (string) $id);

        $schoolLibs = SchoolLibrary::whereIn('school_id', $schoolIds)
            ->pluck('id')
            ->map(fn($id) => (string) $id);

        return $divisionLibs->merge($schoolLibs);
    }

    private function getResources(Request $request, int $level, Collection $libraryIds)
    {
        // Level 3 handles resources differently
        if ($level === 3) {
            return $this->getDivisionResources($request, $libraryIds);
        }

        // For other levels
        if ($libraryIds->isEmpty()) {
            return $this->emptyPaginator($request);
        }

        $query = PrintResource::with(['printTitle.authors', 'type', 'printAcquisitions'])
            ->whereHas('printAcquisitions', fn($q) =>
                $q->whereIn('library_id', $libraryIds->toArray())
            );

        $this->applySearch($query, (string) $request->input('search', ''));

        return $query->paginate(15)->withQueryString();
    }

    private function getDivisionResources(Request $request, Collection $libraryIds)
    {
        if ($libraryIds->isEmpty()) {
            return $this->emptyPaginator($request);
        }

        $query = PrintResource::with(['printTitle.authors', 'type', 'printAcquisitions'])
            ->whereHas('printAcquisitions', fn($q) =>
                $q->whereIn('library_id', $libraryIds->toArray())
            );

        $this->applySearch($query, (string) $request->input('division_search', ''));

        return $query->paginate(15)->withQueryString();
    }

    private function getFilteredResources(Request $request, int $level, Collection $libraryIds)
    {
        $shouldShowFiltered = $this->shouldShowFilteredResources($request, $level);

        if (!$shouldShowFiltered || $libraryIds->isEmpty()) {
            return $this->emptyPaginator($request);
        }

        $query = PrintResource::with(['printTitle.authors', 'type', 'printAcquisitions'])
            ->whereHas('printAcquisitions', fn($q) =>
                $q->whereIn('library_id', $libraryIds->toArray())
            );

        // Apply appropriate search parameter based on level
        $searchParam = $level === 3 ? 'school_search' : 'search';
        $this->applySearch($query, (string) $request->input($searchParam, ''));

        return $query->paginate(15)->withQueryString();
    }

    private function shouldShowFilteredResources(Request $request, int $level): bool
    {
        return match($level) {
            2 => $request->has('school'),
            3 => $request->has('district') || $request->has('school'),
            4 => $request->has('division') || $request->has('district') || $request->has('school'),
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
            $q->whereRaw('LOWER(isbn) LIKE ?', [$searchLower])
              ->orWhereRaw('LOWER(publisher) LIKE ?', [$searchLower])
              ->orWhereRaw('LOWER(copyright) LIKE ?', [$searchLower])
              ->orWhereHas('printTitle', fn($qt) =>
                  $qt->whereRaw('LOWER(title) LIKE ?', [$searchLower])
              )
              ->orWhereHas('printTitle.authors', fn($qa) =>
                  $qa->whereRaw('LOWER(author_name) LIKE ?', [$searchLower])
              )
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
              });
        });
    }

    private function emptyPaginator(Request $request)
    {
        return new LengthAwarePaginator([], 0, 15, 1, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);
    }
}
