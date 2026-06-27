<?php

namespace App\Support;

final class GradeOfferingMap
{
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
}
