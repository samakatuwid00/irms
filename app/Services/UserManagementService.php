<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class UserManagementService
{
    private const LEVEL_REGION = 4;
    private const LEVEL_DIVISION = 3;
    private const LEVEL_DISTRICT = 2;
    private const LEVEL_SCHOOL = 1;

    public function getHierarchicalUsers(User $authUser, array $filters = []): array
    {
        $level = $authUser->userType?->level;

        return [
            'mainUsers' => $this->getMainUsers($authUser, $level, $filters['main'] ?? []),
            'subUsers' => $this->getSubUsers($authUser, $level, $filters['sub'] ?? []),
            'subSubUsers' => $this->getSubSubUsers($authUser, $level, $filters['subsub'] ?? []),
        ];
    }

    private function getMainUsers(User $authUser, ?int $level, array $filters): Collection|LengthAwarePaginator
    {
        if (!$level || $level < self::LEVEL_SCHOOL || $level > self::LEVEL_REGION) {
            return collect();
        }

        $query = User::query()
            ->with('userType')
            ->where('station_id', $authUser->station_id)
            ->whereHas('userType', fn($q) => $q->where('level', $level));

        return $this->applyFiltersAndPaginate($query, $filters, 'main_page');
    }

    private function getSubUsers(User $authUser, ?int $level, array $filters): Collection|LengthAwarePaginator
    {
        if (!$level) {
            return collect();
        }

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

    private function getSubSubUsers(User $authUser, ?int $level, array $filters): Collection|LengthAwarePaginator
    {
        if (!$level) {
            return collect();
        }

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

    private function getRegionSubUsers(User $authUser): Builder
    {
        return User::query()
            ->with('userType')
            ->whereHas('userType', fn($q) => $q->where('level', self::LEVEL_DIVISION))
            ->join('divisions', 'users.station_id', '=', 'divisions.id')
            ->where('divisions.region_id', $authUser->station_id)
            ->select('users.*');
    }

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

    private function getDivisionSubUsers(User $authUser): Builder
    {
        return User::query()
            ->with('userType')
            ->whereHas('userType', fn($q) => $q->where('level', self::LEVEL_DISTRICT))
            ->join('districts', 'users.station_id', '=', 'districts.id')
            ->where('districts.division_id', $authUser->station_id)
            ->select('users.*');
    }

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

    private function getDistrictSubUsers(User $authUser): Builder
    {
        return User::query()
            ->with('userType')
            ->whereHas('userType', fn($q) => $q->where('level', self::LEVEL_SCHOOL))
            ->join('schools', 'users.station_id', '=', 'schools.id')
            ->where('schools.district_id', $authUser->station_id)
            ->select('users.*');
    }

    private function applyFiltersAndPaginate(Builder $query, array $filters, string $pageName): LengthAwarePaginator
    {
        // Apply search filter
        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(users.firstname) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(users.lastname) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(users.username) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(users.email) LIKE ?', ["%{$search}%"]);
            });
        }

        // Apply user type filter
        if (!empty($filters['usertype'])) {
            $query->where('usertype_id', $filters['usertype']);
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate(10, ['*'], $pageName)->withQueryString();
    }

    public function updateUserStatus(User $user, string $status): bool
    {
        return $user->update(['status' => $status]);
    }
}
