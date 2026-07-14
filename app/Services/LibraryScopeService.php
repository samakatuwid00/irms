<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use App\Models\SchoolLibrary;
use App\Models\DivisionLibrary;
use App\Models\RegionLibrary;
use App\Models\District;
use App\Models\School;
use App\Models\Division;

class LibraryScopeService
{
    /**
     * Get the library IDs the current user is allowed to see.
     *
     * @param string|null $explicitLibraryId When provided, only this library is returned
     * @param int $userLevel The organization level (1=school, 3=division, 4=region, 0=unknown/national)
     * @param string|null $stationId The ID of the user's station (school/division/region)
     * @return Collection|null Collection of library IDs or null/empty when no access
     */
    public function getAllowedLibraryIds(?string $explicitLibraryId, int $userLevel, ?string $stationId): ?Collection
    {
        if ($explicitLibraryId !== null && $explicitLibraryId !== '') {
            return collect([$explicitLibraryId]);
        }

        if (!$stationId || $userLevel === 0) {
            return collect();
        }

        $cacheKey = "library_scope_{$userLevel}_{$stationId}";
        $ttl = 3600 * 4; // 4 hours - organizational structure doesn't change often

        return Cache::remember($cacheKey, $ttl, function () use ($userLevel, $stationId) {
            return match ($userLevel) {
                1 => $this->getSchoolLibraries($stationId),
                2 => $this->getDistrictLibraries($stationId),  // ← NEW: Add this line
                3 => $this->getDivisionLibraries($stationId),
                4 => $this->getRegionLibraries($stationId),
                default => collect(),
            };
        });
    }

    /**
     * Get only school library IDs within the same account scope.
     *
     * Regional and division dashboards use this when the UI hides Division
     * Library Hub data but still needs all school LR data in scope.
     */
    public function getAllowedSchoolLibraryIds(?string $explicitLibraryId, int $userLevel, ?string $stationId): Collection
    {
        if ($explicitLibraryId !== null && $explicitLibraryId !== '') {
            return SchoolLibrary::where('id', $explicitLibraryId)->pluck('id');
        }

        if (!$stationId || $userLevel === 0) {
            return collect();
        }

        $cacheKey = "school_library_scope_{$userLevel}_{$stationId}";
        $ttl = 3600 * 4;

        return Cache::remember($cacheKey, $ttl, function () use ($userLevel, $stationId) {
            return match ($userLevel) {
                1 => $this->getSchoolLibraries($stationId),
                2 => $this->getDistrictLibraries($stationId),
                3 => $this->getDivisionSchoolLibraries($stationId),
                4 => $this->getRegionSchoolLibraries($stationId),
                default => collect(),
            };
        });
    }

    // ← NEW: Add this method
    private function getDistrictLibraries(string $districtId): Collection
    {
        $schoolIds = School::where('district_id', $districtId)->pluck('id');
        return SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');
    }

    private function getSchoolLibraries(string $schoolId): Collection
    {
        return SchoolLibrary::where('school_id', $schoolId)->pluck('id');
    }

    private function getDivisionLibraries(string $divisionId): Collection
    {
        $ownLibs = DivisionLibrary::where('division_id', $divisionId)->pluck('id');

        $districtIds = District::where('division_id', $divisionId)->pluck('id');
        $schoolIds = School::whereIn('district_id', $districtIds)->pluck('id');
        $schoolLibs = SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');

        return $ownLibs->merge($schoolLibs);
    }

    private function getDivisionSchoolLibraries(string $divisionId): Collection
    {
        $districtIds = District::where('division_id', $divisionId)->pluck('id');
        $schoolIds = School::whereIn('district_id', $districtIds)->pluck('id');

        return SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');
    }

    private function getRegionLibraries(string $regionId): Collection
    {
        $regionLibs = RegionLibrary::where('region_id', $regionId)->pluck('id');

        $divisionIds = Division::where('region_id', $regionId)->pluck('id');
        $divisionLibs = DivisionLibrary::whereIn('division_id', $divisionIds)->pluck('id');

        $districtIds = District::whereIn('division_id', $divisionIds)->pluck('id');
        $schoolIds = School::whereIn('district_id', $districtIds)->pluck('id');
        $schoolLibs = SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');

        return $regionLibs->merge($divisionLibs)->merge($schoolLibs);
    }

    private function getRegionSchoolLibraries(string $regionId): Collection
    {
        $divisionIds = Division::where('region_id', $regionId)->pluck('id');
        $districtIds = District::whereIn('division_id', $divisionIds)->pluck('id');
        $schoolIds = School::whereIn('district_id', $districtIds)->pluck('id');

        return SchoolLibrary::whereIn('school_id', $schoolIds)->pluck('id');
    }
}
