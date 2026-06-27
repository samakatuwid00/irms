<?php

namespace App\Services;

use App\Models\User;
use App\Support\GradeColumnMap;
use Illuminate\Support\Facades\DB;

class SchoolPopulationRequirementService
{
    public const NOTICE = "Our records show that your school's population has not been entered, which sets your Net Expected Count (NEC) to zero. To proceed and gain full access to the system, please update your school population.";

    public function isRequired(User $user): bool
    {
        if ((int) $user->userType?->level !== 1) {
            return false;
        }

        if (! $user->station_id) {
            return true;
        }

        $columns = array_values(GradeColumnMap::all());

        $population = DB::table('populations as p')
            ->join('school_years as sy', 'sy.id', '=', 'p.sy_id')
            ->where('p.school_id', $user->station_id)
            ->orderByDesc('sy.year_end')
            ->orderByDesc('sy.year_start')
            ->select(array_map(fn (string $column) => "p.{$column}", $columns))
            ->first();

        if (! $population) {
            return true;
        }

        return $this->total($population) <= 0;
    }

    public function total(array|object $population): int
    {
        return array_sum(array_map(
            fn (string $column) => (int) data_get($population, $column, 0),
            array_values(GradeColumnMap::all())
        ));
    }
}
