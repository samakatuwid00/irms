<?php

namespace App\Support;

/**
 * Single source of truth for grade name → populations table column mapping.
 *
 * Previously duplicated as a match() block in:
 *   - LrAvailabilityService::getPopulationColumn()
 *   - LrRatioService::getPopulationPerGrade()
 *   - LrSufficiencyService::calculateLivePopulation()
 */
final class GradeColumnMap
{
    private const MAP = [
        'Kindergarten' => 'k_total',
        'Grade 1'      => 'g1_total',
        'Grade 2'      => 'g2_total',
        'Grade 3'      => 'g3_total',
        'Grade 4'      => 'g4_total',
        'Grade 5'      => 'g5_total',
        'Grade 6'      => 'g6_total',
        'Grade 7'      => 'g7_total',
        'Grade 8'      => 'g8_total',
        'Grade 9'      => 'g9_total',
        'Grade 10'     => 'g10_total',
        'Grade 11'     => 'g11_total',
        'Grade 12'     => 'g12_total',
    ];

    public static function column(string $grade): ?string
    {
        return self::MAP[trim($grade)] ?? null;
    }

    public static function all(): array
    {
        return self::MAP;
    }

    /**
     * Build a single-query SELECT with SUM per grade column.
     * Use this to fetch all grade population totals in one DB round-trip.
     *
     * Usage:
     *   $row = DB::table('populations')
     *       ->whereIn('school_id', $schoolIds)
     *       ->selectRaw(GradeColumnMap::sumSelectRaw())
     *       ->first();
     *
     *   $total = $row->g1_total;
     */
    public static function sumSelectRaw(): string
    {
        return implode(', ', array_map(
            fn($col) => "COALESCE(SUM({$col}), 0) AS {$col}",
            self::MAP
        ));
    }
}
