<?php

namespace App\Services;

class RegionNecCalculator
{
    public function forFilter(string $filter, int $divisionNec, int $schoolNec): int
    {
        return match ($filter) {
            'division-hub' => $divisionNec,
            'school-hub' => $schoolNec,
            default => $divisionNec + $schoolNec,
        };
    }
}
