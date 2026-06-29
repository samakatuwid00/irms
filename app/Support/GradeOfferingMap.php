<?php

namespace App\Support;

final class GradeOfferingMap
{
    private const GRADE_LEVELS = [
        'K' => 'Kindergarten',
        'g1' => 'Grade 1',
        'g2' => 'Grade 2',
        'g3' => 'Grade 3',
        'g4' => 'Grade 4',
        'g5' => 'Grade 5',
        'g6' => 'Grade 6',
        'g7' => 'Grade 7',
        'g8' => 'Grade 8',
        'g9' => 'Grade 9',
        'g10' => 'Grade 10',
        'g11' => 'Grade 11',
        'g12' => 'Grade 12',
    ];

    private const COLUMNS = [
        'K',
        'g1',
        'g2',
        'g3',
        'g4',
        'g5',
        'g6',
        'g7',
        'g8',
        'g9',
        'g10',
        'g11',
        'g12',
        'ng',
    ];

    public static function all(): array
    {
        return self::COLUMNS;
    }

    /**
     * Grade-offering columns that participate in the school NEC formula.
     * Non-Graded (ng) is deliberately excluded.
     */
    public static function necEligible(): array
    {
        return array_keys(self::GRADE_LEVELS);
    }

    public static function gradeLevel(string $column): ?string
    {
        return self::GRADE_LEVELS[$column] ?? null;
    }
}
