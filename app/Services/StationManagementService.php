<?php

namespace App\Services;

use App\Models\{Division, District, School, SchoolLibrary};
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class StationManagementService
{
    public function getDivisionsByRegion(string $regionId, ?string $search = null): LengthAwarePaginator
    {
        $query = Division::query()
            ->with('region')
            ->where('region_id', $regionId)
            ->orderBy('division_name');

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(division_name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(shortname) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(address) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
            });
        }

        return $query->paginate(5);
    }

    public function getDistrictsByDivision(string $divisionId, ?string $search = null): LengthAwarePaginator
    {
        $query = District::query()
            ->with('division')
            ->where('division_id', $divisionId)
            ->orderBy('district_name');

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(district_name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(shortname) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(address) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
            });
        }

        return $query->paginate(10);
    }

    /**
     * Get districts with their schools for a division
     * Returns all districts with their associated schools
     */
    public function getDistrictsWithSchools(string $divisionId, ?string $schoolSearch = null)
    {
        $query = District::query()
            ->with(['schools' => function ($query) use ($schoolSearch) {
                $query->orderBy('school_name');

                // Apply school search filter if provided
                if ($schoolSearch) {
                    $query->where(function ($q) use ($schoolSearch) {
                        $q->whereRaw('LOWER(school_name) LIKE ?', ["%{$schoolSearch}%"])
                          ->orWhereRaw('LOWER(shortname) LIKE ?', ["%{$schoolSearch}%"])
                          ->orWhereRaw('LOWER(school_type) LIKE ?', ["%{$schoolSearch}%"])
                          ->orWhereRaw('LOWER(address) LIKE ?', ["%{$schoolSearch}%"])
                          ->orWhereRaw('LOWER(email) LIKE ?', ["%{$schoolSearch}%"]);
                    });
                }
            }])
            ->where('division_id', $divisionId)
            ->orderBy('district_name');

        return $query->get();
    }

    public function getSchoolsByDivision(string $divisionId, ?string $search = null): LengthAwarePaginator
    {
        $query = School::query()
            ->with('district.division')
            // Filter schools whose parent district belongs to this division
            ->whereHas('district', function ($q) use ($divisionId) {
                $q->where('division_id', $divisionId);
            })
            ->orderBy('school_name');

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(school_name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(shortname) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(school_type) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(address) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
            });
        }

        return $query->paginate(10);
    }

    public function getSchoolsByDistrict(string $districtId, ?string $search = null): LengthAwarePaginator
    {
        $query = School::query()
            ->with('district.division')
            ->where('district_id', $districtId)
            ->orderBy('school_name');

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(school_name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(shortname) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(school_type) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(address) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
            });
        }

        return $query->paginate(15);
    }

    public function createDivision(array $data, string $regionId): Division
    {
        $id = Str::uuid(); 
        $division = Division::create([
            'id' => $id,
            'division_name' => $data['division_name'],
            'shortname' => $data['shortname'] ?? null,
            'address' => $data['address'] ?? null,
            'contact_number' => $data['contact_number'] ?? null,
            'email' => $data['email'] ?? null,
            'date_establish' => $data['date_establish'] ?? null,
            'legislative_district' => $data['legislative_district'] ?? null,
            'region_id' => $regionId,
        ]);
        DivisionLibrary::create([
            'id' => Str::uuid(),
            'division_id' => $id,
            'library_name' => $data['division_name'],
        ]);

        return $division;
        
    }

    public function updateDivision(Division $division, array $data): Division
    {
        $division->update($data);
        return $division;
    }

    public function deleteDivision(Division $division): bool
    {
        return $division->delete();
    }

    public function createDistrict(array $data, string $divisionId): District
    {
        return District::create([
            'id' => Str::uuid(),
            'district_name' => $data['district_name'],
            'shortname' => $data['shortname'] ?? null,
            'address' => $data['address'] ?? null,
            'contact_number' => $data['contact_number'] ?? null,
            'email' => $data['email'] ?? null,
            'date_establish' => $data['date_establish'] ?? null,
            'legislative_district' => $data['legislative_district'] ?? null,
            'division_id' => $divisionId,
        ]);
    }

    public function updateDistrict(District $district, array $data): District
    {
        $district->update($data);
        return $district;
    }

    public function deleteDistrict(District $district): bool
    {
        return $district->delete();
    }

    public function createSchool(array $data, string $districtId): School
    {
        $id = Str::uuid();
        
        $school = School::create([
            'id' => $id,
            'school_name' => $data['school_name'],
            'shortname' => $data['shortname'] ?? null,
            'school_id' => $data['school_id'],
            'address' => $data['address'] ?? null,
            'contact_number' => $data['contact_number'] ?? null,
            'email' => $data['email'] ?? null,
            'date_establish' => $data['date_establish'] ?? null,
            'legislative_school' => $data['legislative_school'] ?? null,
            'district_id' => $districtId,
            'school_type' => $data['school_type'] ?? null,
        ]);

        SchoolLibrary::create([
            'id' => Str::uuid(),
            'school_id' => $id,
            'library_name' => $data['school_name'],
        ]);

        return $school;
        
    }

    public function updateSchool(School $school, array $data): School
    {
        $school->update($data);
        return $school;
    }

    public function deleteSchool(School $school): bool
    {
        return $school->delete();
    }
}
