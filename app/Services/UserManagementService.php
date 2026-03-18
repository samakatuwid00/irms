<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class UserManagementService
{
    /** Organizational hierarchy level constants */
    private const LEVEL_REGION = 4;
    private const LEVEL_DIVISION = 3;
    private const LEVEL_DISTRICT = 2;
    private const LEVEL_SCHOOL = 1;

    /**
     * Get all hierarchical users based on authenticated user's level
     */
    public function getHierarchicalUsers(User $authUser, array $filters = []): array
    {
        $level = $authUser->userType?->level;

        return [
            'mainUsers'   => $this->getMainUsers($authUser, $level, $filters['main'] ?? []),
            'subUsers'    => $this->getSubUsers($authUser, $level, $filters['sub'] ?? []),
            'subSubUsers' => $this->getSubSubUsers($authUser, $level, $filters['subsub'] ?? []),
        ];
    }

    /**
     * Users at the same level as the authenticated user (e.g. other region users for region admin)
     */
    private function getMainUsers(User $authUser, ?int $level, array $filters): Collection|LengthAwarePaginator
    {
        if (!$level || $level < self::LEVEL_SCHOOL || $level > self::LEVEL_REGION) {
            return collect();
        }

        // Determine which station relationship to eager load based on level
        $stationRelation = match ($level) {
            self::LEVEL_REGION   => 'regionStation',
            self::LEVEL_DIVISION => 'divisionStation',
            self::LEVEL_DISTRICT => 'districtStation',
            self::LEVEL_SCHOOL   => 'schoolStation',
            default              => null,
        };

        $query = User::query()
            ->with(['userType', $stationRelation])
            ->where('station_id', $authUser->station_id)
            ->whereHas('userType', fn($q) => $q->where('level', $level));

        return $this->applyFiltersAndPaginate($query, $filters, 'main_page');
    }

    /**
     * Get immediate subordinates (one level down)
     */
    private function getSubUsers(User $authUser, ?int $level, array $filters): Collection|LengthAwarePaginator
    {
        if (!$level || $level === self::LEVEL_SCHOOL) {
            return User::query()
                ->whereRaw('1 = 0')           // always false condition
                ->paginate(10, ['*'], 'sub_page');
        }

        $query = match($level) {
            self::LEVEL_REGION   => $this->getRegionSubUsers($authUser),
            self::LEVEL_DIVISION => $this->getDivisionSubUsers($authUser),
            self::LEVEL_DISTRICT => $this->getDistrictSubUsers($authUser),
            default              => null
        };

        if (!$query) {
            return User::query()->where('id', 0)->paginate(10, ['*'], 'sub_page');
        }

        return $this->applyFiltersAndPaginate($query, $filters, 'sub_page');
    }

    /**
     * Get second-level subordinates (two levels down)
     */
    private function getSubSubUsers(User $authUser, ?int $level, array $filters): Collection|LengthAwarePaginator
    {
        if (!$level || $level <= self::LEVEL_DISTRICT) {
            return User::query()
                ->whereRaw('1 = 0')
                ->paginate(10, ['*'], 'subsub_page');
        }

        $query = match($level) {
            self::LEVEL_REGION   => $this->getRegionSubSubUsers($authUser),
            self::LEVEL_DIVISION => $this->getDivisionSubSubUsers($authUser),
            default              => null
        };

        if (!$query) {
            return User::query()->where('id', 0)->paginate(10, ['*'], 'subsub_page');
        }

        return $this->applyFiltersAndPaginate($query, $filters, 'subsub_page');
    }

    // ────────────────────────────────────────────────────────────────────────────────
    //                          SUB-USER QUERIES (ONE LEVEL DOWN)
    // ────────────────────────────────────────────────────────────────────────────────

    private function getRegionSubUsers(User $authUser): Builder
    {
        return User::query()
            ->with(['userType', 'divisionStation'])
            ->whereHas('userType', fn($q) => $q->where('level', self::LEVEL_DIVISION))
            ->join('divisions', 'users.station_id', '=', 'divisions.id')
            ->where('divisions.region_id', $authUser->station_id)
            ->select('users.*');
    }

    private function getDivisionSubUsers(User $authUser): Builder
    {
        return User::query()
            ->with(['userType', 'districtStation'])
            ->whereHas('userType', fn($q) => $q->where('level', self::LEVEL_DISTRICT))
            ->join('districts', 'users.station_id', '=', 'districts.id')
            ->where('districts.division_id', $authUser->station_id)
            ->select('users.*');
    }

    private function getDistrictSubUsers(User $authUser): Builder
    {
        return User::query()
            ->with(['userType', 'schoolStation'])
            ->whereHas('userType', fn($q) => $q->where('level', self::LEVEL_SCHOOL))
            ->join('schools', 'users.station_id', '=', 'schools.id')
            ->where('schools.district_id', $authUser->station_id)
            ->select('users.*');
    }

    // ────────────────────────────────────────────────────────────────────────────────
    //                        SUB-SUB-USER QUERIES (TWO LEVELS DOWN)
    // ────────────────────────────────────────────────────────────────────────────────

    private function getRegionSubSubUsers(User $authUser): Builder
    {
        return User::query()
            ->with(['userType', 'districtStation'])
            ->whereHas('userType', fn($q) => $q->where('level', self::LEVEL_DISTRICT))
            ->join('districts', 'users.station_id', '=', 'districts.id')
            ->whereIn('districts.division_id', function ($query) use ($authUser) {
                $query->select('id')
                    ->from('divisions')
                    ->where('region_id', $authUser->station_id);
            })
            ->select('users.*');
    }

    private function getDivisionSubSubUsers(User $authUser): Builder
    {
        return User::query()
            ->with(['userType', 'schoolStation'])
            ->whereHas('userType', fn($q) => $q->where('level', self::LEVEL_SCHOOL))
            ->join('schools', 'users.station_id', '=', 'schools.id')
            ->whereIn('schools.district_id', function ($query) use ($authUser) {
                $query->select('id')
                    ->from('districts')
                    ->where('division_id', $authUser->station_id);
            })
            ->select('users.*');
    }

    /**
     * Apply common filters and pagination
     */
    private function applyFiltersAndPaginate(Builder $query, array $filters, string $pageName): LengthAwarePaginator
    {
        // Case-insensitive search across multiple fields
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

        // Filter by status (still allows explicit filtering when needed)
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // ─── Add default sorting: pending → active → deactivated ───────────────
        $query->orderByRaw("
            CASE users.status
                WHEN 'pending'     THEN 1
                WHEN 'deactivated' THEN 2
                WHEN 'active'      THEN 3
                ELSE 4
            END ASC
        ");

        // You can add secondary sorting if desired (most common choice)
        $query->orderBy('users.lastname')
            ->orderBy('users.firstname');

        // Paginate with unique page parameter name and preserve query string
        return $query->paginate(10, ['*'], $pageName)->withQueryString();
    }

    /**
     * Update user status (active / pending / deactivated)
     */
    public function updateUserStatus(User $user, string $status): bool
    {
        $user->status = $status;
        return $user->save();
    }
}
