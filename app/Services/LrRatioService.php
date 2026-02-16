<?php

namespace App\Services;

use App\Models\GradeLevel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class LrRatioService
{
    public function __construct(
        private readonly LibraryScopeService $libraryScopeService
    ) {}

    /**
     * Get cached chart data
     */
    public function getChartDataCached(
        ?string $explicitLibraryId,
        int $userLevel,
        ?string $stationId
    ): array {
        $cacheKey = "lr_ratio_chart:{$explicitLibraryId}:{$userLevel}:{$stationId}";
        $cacheTtl = now()->addMinutes(10); // Adjust cache duration

        return Cache::remember($cacheKey, $cacheTtl, function () use ($explicitLibraryId, $userLevel, $stationId) {
            return $this->getChartDataFromDatabase($explicitLibraryId, $userLevel, $stationId);
        });
    }

    /**
     * Query database and compute chart data
     */
    private function getChartDataFromDatabase(
        ?string $explicitLibraryId,
        int $userLevel,
        ?string $stationId
    ): array {

        // 1️⃣ Load grade levels
        $gradeLevels = GradeLevel::query()
            ->select('id', 'grade', 'sort_order')
            ->orderBy('sort_order')
            ->get();

        if ($gradeLevels->isEmpty()) {
            return [
                'grades'      => [],
                'population'  => [],
                'directData'  => [],
                'mailData'    => [],
                'ratioLabels' => [],
                'message'     => 'No grade levels found',
            ];
        }

        $gradeNames = $gradeLevels->pluck('grade')->toArray();
        $gradeIds   = $gradeLevels->pluck('id')->toArray();

        // 2️⃣ Resolve library scope
        $allowedLibraryIds = $this->libraryScopeService->getAllowedLibraryIds(
            $explicitLibraryId,
            $userLevel,
            $stationId
        );

        // 3️⃣ Aggregate total_qty per grade using pivot table
        $lrTotalsQuery = DB::table('print_acquisitions as pa')
            ->join('print_resources as pr', 'pa.print_id', '=', 'pr.id')
            ->join('print_resource_sgl as prsgl', 'pr.id', '=', 'prsgl.print_id')
            ->join('subject_grade_levels as sgl', 'prsgl.sgl_id', '=', 'sgl.id')
            ->select(
                'sgl.grade_level_id',
                DB::raw('SUM(pa.total_qty) as total_qty')
            );

        // Apply library scope
        if ($allowedLibraryIds !== null) {
            if ($allowedLibraryIds->isEmpty()) {
                $lrTotalsQuery->whereRaw('1 = 0');
            } else {
                $lrTotalsQuery->whereIn('pr.library_id', $allowedLibraryIds->toArray());
            }
        }

        $lrTotals = $lrTotalsQuery
            ->groupBy('sgl.grade_level_id')
            ->pluck('total_qty', 'sgl.grade_level_id');

        // 4️⃣ Map results for chart
        $directData   = [];
        $mailData     = [];
        $ratioLabels  = [];

        // Population placeholder (replace with real population if available)
        $populationPlaceholder = [
            18500, 19200, 19800, 20400, 21000,
            21500, 22000, 22500, 21800, 21000,
            19500, 18000, 16500
        ];

        $population = array_slice($populationPlaceholder, 0, count($gradeNames));
        while (count($population) < count($gradeNames)) {
            $population[] = 950;
        }
        $populationAssoc = array_combine($gradeNames, $population);

        // Compute data + ratios
        foreach ($gradeLevels as $index => $gradeLevel) {
            $totalLR = (int) ($lrTotals[$gradeLevel->id] ?? 0);
            $directData[] = $totalLR;
            $mailData[]   = 0; // placeholder

            $pop = $populationAssoc[$gradeLevel->grade] ?? 0;

            // Precompute people per LR ratio
            if ($totalLR > 0 && $pop > 0) {
                $peoplePerLR = $pop / $totalLR;
                if ($peoplePerLR >= 1) {
                    $ratioLabels[] = round($peoplePerLR) . ' : 1';
                } else {
                    $ratioLabels[] = round($totalLR / $pop) . ' : 1';
                }
            } else {
                $ratioLabels[] = 'N/A';
            }
        }

        return [
            'grades'        => $gradeNames,
            'population'    => $populationAssoc,
            'directData'    => $directData,
            'mailData'      => $mailData,
            'ratioLabels'   => $ratioLabels,
            'library_scope' => $explicitLibraryId ? 'single_library' : 'auto_scoped',
            'library_id'    => $explicitLibraryId ?: 'auto',
        ];
    }
}
