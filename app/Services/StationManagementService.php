<?php

namespace App\Services;

use App\Models\{Division, District, School};
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * Service for managing educational stations (divisions, districts, and schools).
 *
 * Handles CRUD operations and queries for the hierarchical organizational structure:
 * Region -> Division -> District -> School
 */
class StationManagementService
{
    /**
     * Get paginated divisions within a specific region.
     *
     * Retrieves divisions with optional search filtering across name, shortname,
     * address, and email fields. Results are ordered alphabetically by division name.
     *
     * @param string $regionId The ID of the region to filter by
     * @param string|null $search Optional search term for filtering results
     * @return LengthAwarePaginator Paginated collection of divisions with region relationship
     */
    public function getDivisionsByRegion(string $regionId, ?string $search = null): LengthAwarePaginator
    {
        $query = Division::query()
            ->with('region')
            ->where('region_id', $regionId)
            ->orderBy('division_name');

        // Apply search filter if provided
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(division_name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(shortname) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(address) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
            });
        }

        return $query->paginate(15);
    }

    /**
     * Get paginated districts within a specific division.
     *
     * Retrieves districts with optional search filtering across name, shortname,
     * address, and email fields. Results are ordered alphabetically by district name.
     *
     * @param string $divisionId The ID of the division to filter by
     * @param string|null $search Optional search term for filtering results
     * @return LengthAwarePaginator Paginated collection of districts with division relationship
     */
    public function getDistrictsByDivision(string $divisionId, ?string $search = null): LengthAwarePaginator
    {
        $query = District::query()
            ->with('division')
            ->where('division_id', $divisionId)
            ->orderBy('district_name');

        // Apply search filter if provided
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(district_name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(shortname) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(address) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
            });
        }

        return $query->paginate(15);
    }

    /**
     * Get paginated schools within a specific division.
     *
     * Retrieves all schools from any district that belongs to the specified division.
     * Includes search filtering across name, shortname, type, address, and email.
     * Results are ordered alphabetically by school name.
     *
     * @param string $divisionId The ID of the division to filter by
     * @param string|null $search Optional search term for filtering results
     * @return LengthAwarePaginator Paginated collection of schools with district and division relationships
     */
    public function getSchoolsByDivision(string $divisionId, ?string $search = null): LengthAwarePaginator
    {
        $query = School::query()
            ->with('district.division')
            // Filter schools whose parent district belongs to this division
            ->whereHas('district', function ($q) use ($divisionId) {
                $q->where('division_id', $divisionId);
            })
            ->orderBy('school_name');

        // Apply search filter if provided
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

    /**
     * Get paginated schools within a specific district.
     *
     * Retrieves schools with optional search filtering across name, shortname,
     * type, address, and email fields. Results are ordered alphabetically by school name.
     *
     * @param string $districtId The ID of the district to filter by
     * @param string|null $search Optional search term for filtering results
     * @return LengthAwarePaginator Paginated collection of schools with district and division relationships
     */
    public function getSchoolsByDistrict(string $districtId, ?string $search = null): LengthAwarePaginator
    {
        $query = School::query()
            ->with('district.division')
            ->where('district_id', $districtId)
            ->orderBy('school_name');

        // Apply search filter if provided
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

    /**
     * Create a new division within a region.
     *
     * Generates a UUID for the division and associates it with the specified region.
     * All fields except division_name and region_id are optional.
     *
     * @param array $data Division attributes (division_name, shortname, address, etc.)
     * @param string $regionId The ID of the parent region
     * @return Division The newly created division instance
     */
    public function createDivision(array $data, string $regionId): Division
    {
        return Division::create([
            'id' => Str::uuid(),
            'division_name' => $data['division_name'],
            'shortname' => $data['shortname'] ?? null,
            'address' => $data['address'] ?? null,
            'contact_number' => $data['contact_number'] ?? null,
            'email' => $data['email'] ?? null,
            'date_establish' => $data['date_establish'] ?? null,
            'legislative_district' => $data['legislative_district'] ?? null,
            'region_id' => $regionId,
        ]);
    }

    /**
     * Update an existing division.
     *
     * Mass-assigns provided data to the division model.
     * Ensure fillable attributes are properly defined in the Division model.
     *
     * @param Division $division The division instance to update
     * @param array $data Updated attributes
     * @return Division The updated division instance
     */
    public function updateDivision(Division $division, array $data): Division
    {
        $division->update($data);
        return $division;
    }

    /**
     * Delete a division.
     *
     * Note: Consider cascade rules - deleting a division may affect
     * associated districts and schools depending on database constraints.
     *
     * @param Division $division The division instance to delete
     * @return bool True if deletion was successful
     */
    public function deleteDivision(Division $division): bool
    {
        return $division->delete();
    }

    /**
     * Create a new district within a division.
     *
     * Generates a UUID for the district and associates it with the specified division.
     * All fields except district_name and division_id are optional.
     *
     * @param array $data District attributes (district_name, shortname, address, etc.)
     * @param string $divisionId The ID of the parent division
     * @return District The newly created district instance
     */
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

    /**
     * Update an existing district.
     *
     * Mass-assigns provided data to the district model.
     * Ensure fillable attributes are properly defined in the District model.
     *
     * @param District $district The district instance to update
     * @param array $data Updated attributes
     * @return District The updated district instance
     */
    public function updateDistrict(District $district, array $data): District
    {
        $district->update($data);
        return $district;
    }

    /**
     * Delete a district.
     *
     * Note: Consider cascade rules - deleting a district may affect
     * associated schools depending on database constraints.
     *
     * @param District $district The district instance to delete
     * @return bool True if deletion was successful
     */
    public function deleteDistrict(District $district): bool
    {
        return $district->delete();
    }

    /**
     * Create a new school within a district.
     *
     * Generates a UUID for the school and associates it with the specified district.
     * Requires school_name, school_id, and district_id. Other fields are optional.
     *
     * @param array $data School attributes (school_name, school_id, shortname, address, etc.)
     * @param string $districtId The ID of the parent district
     * @return School The newly created school instance
     */
    public function createSchool(array $data, string $districtId): School
    {
        return School::create([
            'id' => Str::uuid(),
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
    }

    /**
     * Update an existing school.
     *
     * Mass-assigns provided data to the school model.
     * Ensure fillable attributes are properly defined in the School model.
     *
     * @param School $school The school instance to update
     * @param array $data Updated attributes
     * @return School The updated school instance
     */
    public function updateSchool(School $school, array $data): School
    {
        $school->update($data);
        return $school;
    }

    /**
     * Delete a school.
     *
     * Note: Consider cascade rules and data integrity. Deleting a school
     * may affect associated records like libraries, students, or resources.
     *
     * @param School $school The school instance to delete
     * @return bool True if deletion was successful
     */
    public function deleteSchool(School $school): bool
    {
        return $school->delete();
    }
}
