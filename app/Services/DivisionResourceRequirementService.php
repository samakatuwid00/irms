<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class DivisionResourceRequirementService
{
    public const NOTICE = "Our records show that your division's Net Expected Count (NEC) has not been entered or is zero. To proceed and gain full access to the system, please update your division library hub NEC.";

    public function isRequired(User $user): bool
    {
        if ((int) $user->userType?->level !== 3) {
            return false;
        }

        if (! $user->station_id) {
            return true;
        }

        return (int) DB::table('division_libraries')
            ->where('division_id', $user->station_id)
            ->sum('net_expected_count') <= 0;
    }
}
