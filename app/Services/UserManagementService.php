<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Service for managing users within a hierarchical organizational structure.
 *
 * Provides functionality to retrieve and manage users based on the authenticated
 * user's level in the hierarchy: Region -> Division -> District -> School
 *
 * Each level can view:
 * - Main users: Users at the same level and station
 * - Sub users: Users one level below
 * - Sub-sub users: Users two levels below (where applicable)
 */
class UserManagementService
{
    /** Organizational hierarchy level constants */
    private const LEVEL_REGION = 4;
    private const LEVEL_DIVISION = 3;
    private const LEVEL_DISTRICT = 2;
    private const LEVEL_SCHOOL = 1;

    /**
     * Get hierarchical users based on authenticated user's level and filters.
     *
     * Returns three groups of users:
     * - Main users: Same level as auth user at the same station
     * - Sub users: One level below auth user
     * - Sub-sub users: Two levels below auth user
     *
     * Example for Division level user:
     * - Main: Division users at same division
     * - Sub: District users in divisions under this division
     * - Sub-sub: School users in districts under this division
     *
     * @param User $authUser The authenticated user requesting the data
     * @param array $filters Filter arrays for each group: ['main' => [...], 'sub' => [...], 'subsub' => [...]]
     * @return array Contains 'mainUsers', 'subUsers', and 'subSubUsers' collections/paginators
     */
    public function getHierarchicalUsers(User $authUser, array $filters = []): array
    {
        $level = $authUser->userType?->level;

        return [
            'mainUsers' => $this->getMainUsers($authUser, $level, $filters['main'] ?? []),
            'subUsers' => $this->getSubUsers($authUser, $level, $filters['sub'] ?? []),
            'subSubUsers' => $this->getSubSubUsers($authUser, $level, $filters['subsub'] ?? []),
        ];
    }

    /**
     * Get users at the same level and station as the authenticated user.
     *
     * Filters users by matching both the station_id and user level.
     * For example, if auth user is a Division admin, this returns all Division
     * admins at the same division.
     *
     * @param User $authUser The authenticated user
     * @param int|null $level The organizational level of the auth user
     * @param array $filters Search, usertype, and status filters
     * @return Collection|LengthAwarePaginator Paginated users or empty collection if invalid level
     */
    private function getMainUsers(User $authUser, ?int $level, array $filters): Collection|LengthAwarePaginator
    {
        // Validate level is within acceptable range
        if (!$level || $level < self::LEVEL_SCHOOL || $level > self::LEVEL_REGION) {
            return collect();
        }

        $query = User::query()
            ->with('userType')
            ->where('station_id', $authUser->station_id)
            ->whereHas('userType', fn($q) => $q->where('level', $level));

        return $this->applyFiltersAndPaginate($query, $filters, 'main_page');
    }

    /**
     * Get users one level below the authenticated user.
     *
     * Retrieves users at the immediate subordinate level:
     * - Region users see Division users in their region
     * - Division users see District users in their division
     * - District users see School users in their district
     * - School users see nothing (no sub level)
     *
     * @param User $authUser The authenticated user
     * @param int|null $level The organizational level of the auth user
     * @param array $filters Search, usertype, and status filters
     * @return Collection|LengthAwarePaginator Paginated users or empty collection if no sub level exists
     */
    private function getSubUsers(User $authUser, ?int $level, array $filters): Collection|LengthAwarePaginator
    {
        if (!$level) {
            return collect();
        }

        // Determine appropriate sub-user query based on auth user's level
        $query = match($level) {
            self::LEVEL_REGION => $this->getRegionSubUsers($authUser),
            self::LEVEL_DIVISION => $this->getDivisionSubUsers($authUser),
            self::LEVEL_DISTRICT => $this->getDistrictSubUsers($authUser),
            default => null
        };

        if (!$query) {
            return collect();
        }

        return $this->applyFiltersAndPaginate($query, $filters, 'sub_page');
    }

    /**
     * Get users two levels below the authenticated user.
     *
     * Retrieves users at the second subordinate level:
     * - Region users see District users in their region's divisions
     * - Division users see School users in their division's districts
     * - District and School users see nothing (no sub-sub level)
     *
     * @param User $authUser The authenticated user
     * @param int|null $level The organizational level of the auth user
     * @param array $filters Search, usertype, and status filters
     * @return Collection|LengthAwarePaginator Paginated users or empty collection if no sub-sub level exists
     */
    private function getSubSubUsers(User $authUser, ?int $level, array $filters): Collection|LengthAwarePaginator
    {
        if (!$level) {
            return collect();
        }

        // Determine appropriate sub-sub-user query based on auth user's level
        $query = match($level) {
            self::LEVEL_REGION => $this->getRegionSubSubUsers($authUser),
            self::LEVEL_DIVISION => $this->getDivisionSubSubUsers($authUser),
            default => null
        };

        if (!$query) {
            return collect();
        }

        return $this->applyFiltersAndPaginate($query, $filters, 'subsub_page');
    }

    /**
     * Get Division-level users for a Region administrator.
     *
     * Joins with divisions table to find all Division users whose station
     * (division) belongs to the auth user's region.
     *
     * @param User $authUser The Region-level authenticated user
     * @return Builder Query builder for Division users in this region
     */
    private function getRegionSubUsers(User $authUser): Builder
    {
        return User::query()
            ->with('userType')
            ->whereHas('userType', fn($q) => $q->where('level', self::LEVEL_DIVISION))
            ->join('divisions', 'users.station_id', '=', 'divisions.id')
            ->where('divisions.region_id', $authUser->station_id)
            ->select('users.*');
    }

    /**
     * Get District-level users for a Region administrator.
     *
     * Joins with districts table and uses subquery to find all District users
     * whose station (district) belongs to any division within the auth user's region.
     *
     * @param User $authUser The Region-level authenticated user
     * @return Builder Query builder for District users in this region's divisions
     */
    private function getRegionSubSubUsers(User $authUser): Builder
    {
        return User::query()
            ->with('userType')
            ->whereHas('userType', fn($q) => $q->where('level', self::LEVEL_DISTRICT))
            ->join('districts', 'users.station_id', '=', 'districts.id')
            ->whereIn('districts.division_id', function($query) use ($authUser) {
                $query->select('id')
                    ->from('divisions')
                    ->where('region_id', $authUser->station_id);
            })
            ->select('users.*');
    }

    /**
     * Get District-level users for a Division administrator.
     *
     * Joins with districts table to find all District users whose station
     * (district) belongs to the auth user's division.
     *
     * @param User $authUser The Division-level authenticated user
     * @return Builder Query builder for District users in this division
     */
    private function getDivisionSubUsers(User $authUser): Builder
    {
        return User::query()
            ->with('userType')
            ->whereHas('userType', fn($q) => $q->where('level', self::LEVEL_DISTRICT))
            ->join('districts', 'users.station_id', '=', 'districts.id')
            ->where('districts.division_id', $authUser->station_id)
            ->select('users.*');
    }

    /**
     * Get School-level users for a Division administrator.
     *
     * Joins with schools table and uses subquery to find all School users
     * whose station (school) belongs to any district within the auth user's division.
     *
     * @param User $authUser The Division-level authenticated user
     * @return Builder Query builder for School users in this division's districts
     */
    private function getDivisionSubSubUsers(User $authUser): Builder
    {
        return User::query()
            ->with('userType')
            ->whereHas('userType', fn($q) => $q->where('level', self::LEVEL_SCHOOL))
            ->join('schools', 'users.station_id', '=', 'schools.id')
            ->whereIn('schools.district_id', function($query) use ($authUser) {
                $query->select('id')
                    ->from('districts')
                    ->where('division_id', $authUser->station_id);
            })
            ->select('users.*');
    }

    /**
     * Get School-level users for a District administrator.
     *
     * Joins with schools table to find all School users whose station
     * (school) belongs to the auth user's district.
     *
     * @param User $authUser The District-level authenticated user
     * @return Builder Query builder for School users in this district
     */
    private function getDistrictSubUsers(User $authUser): Builder
    {
        return User::query()
            ->with('userType')
            ->whereHas('userType', fn($q) => $q->where('level', self::LEVEL_SCHOOL))
            ->join('schools', 'users.station_id', '=', 'schools.id')
            ->where('schools.district_id', $authUser->station_id)
            ->select('users.*');
    }

    /**
     * Apply filters and paginate the user query.
     *
     * Supports three filter types:
     * - search: Case-insensitive search across firstname, lastname, username, email
     * - usertype: Filter by specific user type ID
     * - status: Filter by user status (active, inactive, etc.)
     *
     * @param Builder $query The base query builder to filter
     * @param array $filters Array containing 'search', 'usertype', and/or 'status' keys
     * @param string $pageName Pagination parameter name (for multiple paginators on same page)
     * @return LengthAwarePaginator Paginated and filtered user results (10 per page)
     */
    private function applyFiltersAndPaginate(Builder $query, array $filters, string $pageName): LengthAwarePaginator
    {
        // Apply case-insensitive search across multiple user fields
        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(users.firstname) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(users.lastname) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(users.username) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(users.email) LIKE ?', ["%{$search}%"]);
            });
        }

        // Filter by specific user type
        if (!empty($filters['usertype'])) {
            $query->where('usertype_id', $filters['usertype']);
        }

        // Filter by user status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Paginate with unique page name and preserve query string parameters
        return $query->paginate(10, ['*'], $pageName)->withQueryString();
    }

    /**
     * Update a user's status.
     *
     * Common statuses might include: 'active', 'inactive', 'suspended', 'pending'.
     * Validation of status values should be handled at the controller/request level.
     *
     * @param User $user The user instance to update
     * @param string $status The new status value
     * @return bool True if update was successful
     */
    public function updateUserStatus(User $user, string $status): bool
    {
        return $user->update(['status' => $status]);
    }
}
