<?php

namespace App\Services;

use App\Services\LibraryScopeService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LrNeedsService
{
    public function __construct(
        private readonly LibraryScopeService $libraryScopeService,
        private readonly LrSufficiencyService $sufficiencyService
    ) {}

    /**
     * Returns:
     * - total_needs : sum of the requested largest shortfalls
     * - needs       : array of the most deficient entries
     */
    public function getLrNeeds(
        ?string $explicitLibraryId,
        int $userLevel,
        ?string $stationId,
        int $limit = 3
    ): array {
        $cacheKey = 'lr_needs_' . sha1(json_encode([
            $explicitLibraryId,
            $userLevel,
            $stationId,
            $limit,
            session('dashboard_chart_cache_version'),
        ]));

        return Cache::remember($cacheKey, now()->addHour(), function () use ($explicitLibraryId, $userLevel, $stationId, $limit) {
        $sufficiencyData = $this->sufficiencyService->getSufficiencyData(
            $explicitLibraryId,
            $userLevel,
            $stationId
        );

        if (isset($sufficiencyData['error']) || empty($sufficiencyData['table_data'])) {
            return [
                'total_needs' => 0,
                'needs'       => [],
                'has_data'    => false,
            ];
        }

        $tableData = $sufficiencyData['table_data'];

        // Only keep deficient combinations + sort by largest shortfall first
        $deficientRows = collect($tableData)
            ->filter(fn($row) => ($row['shortfall'] ?? 0) > 0)
            ->sortByDesc('shortfall')
            ->take($limit);

        if ($deficientRows->isEmpty()) {
            return [
                'total_needs' => 0,
                'needs'       => [],
                'has_data'    => true,
                'message'     => 'No LR deficiencies detected',
            ];
        }

        $topNeeds = $deficientRows
            ->map(fn($row) => [
                'subject_grade' => "{$row['grade']} – {$row['subject']}",
                'needed'        => (int) $row['shortfall'],
                'shortfall'     => (int) $row['shortfall'],     // optional - for clarity
                'lr_qty'        => (int) ($row['lr_quantity'] ?? 0),
                'population'    => (int) ($row['population'] ?? 0),
            ])
            ->values()
            ->toArray();

        $totalNeeds = array_sum(array_column($topNeeds, 'needed'));
        return [
            'total_needs' => $totalNeeds,
            'needs'       => $topNeeds,
            'has_data'    => true,
            'library_scope' => $sufficiencyData['library_scope'] ?? 'unknown',
        ];
        });
    }
}
