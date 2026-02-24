<?php

namespace App\Services;

use App\Models\GradeLevel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LrRatioService
{
    private $libraryScopeService;

    public function __construct(LibraryScopeService $libraryScopeService)
    {
        $this->libraryScopeService = $libraryScopeService;
    }

    public function getChartDataCached($explicitLibraryId, $userLevel, $stationId)
    {
        return $this->getChartData($explicitLibraryId, $userLevel, $stationId);
    }

    private function getChartData($explicitLibraryId, $userLevel, $stationId)
    {
        $gradeLevels = GradeLevel::query()
            ->select('id', 'grade', 'sort_order')
            ->orderBy('sort_order')
            ->get();

        if ($gradeLevels->isEmpty()) {
            return $this->emptyResult('No grade levels found');
        }

        $gradeNames = $gradeLevels->pluck('grade')->toArray();
        $gradeIds   = $gradeLevels->pluck('id')->toArray();

        // ────────────────────────────────────────────────
        // Materialized views for levels 2–4
        // ────────────────────────────────────────────────
        if (in_array($userLevel, [2, 3, 4]) && $stationId !== null) {
            $mvInfo = $this->getMvInfo($userLevel);
            if ($mvInfo) {
                Log::info("Using MV for LR Ratio", [
                    'level' => $userLevel,
                    'scope' => $mvInfo['scope'],
                    'table' => $mvInfo['table'],
                    'station_id' => $stationId,
                ]);

                $mvData = DB::table("lrmis.{$mvInfo['table']}")
                    ->where($mvInfo['id_column'], $stationId)
                    ->whereIn('grade_level_id', $gradeIds)
                    ->select([
                        'grade_level_id',
                        'grade_name',
                        DB::raw('COALESCE(SUM(total_lr_qty), 0) as total_lr_qty'),
                        DB::raw('MAX(pop_total) as pop_total'),
                    ])
                    ->groupBy('grade_level_id', 'grade_name')
                    ->get()
                    ->keyBy('grade_level_id');

                return $this->buildFromMvData($mvData, $gradeLevels, $gradeNames, $mvInfo['scope']);
            }
        }

        // ────────────────────────────────────────────────
        // Level 1 (school) → use denormalized / optimized live query
        // ────────────────────────────────────────────────
        Log::info('Using optimized live query for school-level LR Ratio', [
            'explicit_library' => $explicitLibraryId,
            'user_level' => $userLevel,
        ]);

        $allowedLibraryIds = $this->libraryScopeService->getAllowedLibraryIds(
            $explicitLibraryId,
            $userLevel,
            $stationId
        );

        if (empty($allowedLibraryIds)) {
            Log::warning('No libraries in scope for level 1 → returning zeros');
            return $this->buildZeroedResult($gradeNames);
        }

        // ── Optimized LR aggregation (same pattern as LrAvailabilityService) ──
        $lrTotals = $this->getOptimizedLrTotalsPerGrade($allowedLibraryIds, $gradeIds);

        // Population
        $populationAssoc = $this->getPopulationPerGrade($allowedLibraryIds, $gradeLevels);

        return $this->buildFromLiveData(
            $lrTotals,
            $populationAssoc,
            $gradeLevels,
            $gradeNames,
            $explicitLibraryId
        );
    }

    private function getMvInfo(int $userLevel): ?array
    {
        return match ($userLevel) {
            4 => ['table' => 'mv_lr_charts_by_region',  'id_column' => 'region_id',   'scope' => 'region'],
            3 => ['table' => 'mv_lr_charts_by_division','id_column' => 'division_id', 'scope' => 'division'],
            2 => ['table' => 'mv_lr_charts_by_district','id_column' => 'district_id', 'scope' => 'district'],
            default => null,
        };
    }

    private function getOptimizedLrTotalsPerGrade($allowedLibraryIds, array $gradeIds): array
    {
        $libraryIdsArray = is_array($allowedLibraryIds) ? $allowedLibraryIds : $allowedLibraryIds->values()->toArray();

        // Sum acquisitions per print resource, filtered by library_id from acquisitions
        $qtyPerPrint = DB::table('print_acquisitions')
            ->select('print_id', DB::raw('COALESCE(SUM(total_qty), 0)::integer as total_per_print'))
            ->whereIn('library_id', $libraryIdsArray)  // ← Filter by library_id here!
            ->groupBy('print_id');

        $exploded = DB::table('print_resources')
            ->joinSub($qtyPerPrint, 'acq', fn($j) => $j->on('print_resources.id', '=', 'acq.print_id'))
            ->select([
                'print_resources.id',
                DB::raw("unnest(string_to_array(subject_grade_level_ids, ','))::uuid as sgl_id"),
                'acq.total_per_print',
            ])
            // Removed: ->whereIn('print_resources.library_id', $libraryIdsArray)
            ->whereNotNull('print_resources.subject_grade_level_ids')
            ->where('print_resources.subject_grade_level_ids', '<>', '');

        $totals = DB::table(DB::raw("({$exploded->toSql()}) as exploded"))
            ->mergeBindings($exploded)
            ->join('subject_grade_levels as sgl', 'exploded.sgl_id', '=', 'sgl.id')
            ->select([
                'sgl.grade_level_id',
                DB::raw('SUM(exploded.total_per_print)::integer as total_lr_qty')
            ])
            ->whereIn('sgl.grade_level_id', $gradeIds)
            ->groupBy('sgl.grade_level_id')
            ->pluck('total_lr_qty', 'grade_level_id')
            ->all();

        return $totals;
    }

    private function getPopulationPerGrade($allowedLibraryIds, $gradeLevels): array
    {
        $schoolIds = DB::table('school_libraries')
            ->whereIn('id', is_array($allowedLibraryIds) ? $allowedLibraryIds : $allowedLibraryIds->toArray())
            ->pluck('school_id')
            ->unique()
            ->all();

        if (empty($schoolIds)) {
            return array_fill_keys($gradeLevels->pluck('grade')->all(), 0);
        }

        $popAssoc = [];

        foreach ($gradeLevels as $gl) {
            $col = match (trim($gl->grade)) {
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
                default        => null,
            };

            $popAssoc[$gl->grade] = $col
                ? (int) DB::table('populations')->whereIn('school_id', $schoolIds)->sum($col)
                : 0;
        }

        return $popAssoc;
    }

    // ── Builders ────────────────────────────────────────────────────────────────

    private function buildFromMvData($mvData, $gradeLevels, $gradeNames, $scope): array
    {
        $directData = [];
        $ratioLabels = [];
        $populationAssoc = [];

        foreach ($gradeLevels as $gl) {
            $row = $mvData[$gl->id] ?? null;
            $lr  = $row ? (int) $row->total_lr_qty : 0;
            $pop = $row ? (int) $row->pop_total    : 0;

            $directData[] = $lr;
            $populationAssoc[$gl->grade] = $pop;
            $ratioLabels[] = $this->computeRatioLabel($lr, $pop);
        }

        return [
            'grades'        => $gradeNames,
            'population'    => $populationAssoc,
            'directData'    => $directData,
            'mailData'      => array_fill(0, count($gradeNames), 0),
            'ratioLabels'   => $ratioLabels,
            'library_scope' => $scope,
            'source'        => 'materialized_view',
        ];
    }

    private function buildFromLiveData($lrTotals, $populationAssoc, $gradeLevels, $gradeNames, $explicitLibraryId): array
    {
        $directData = [];
        $ratioLabels = [];

        foreach ($gradeLevels as $gl) {
            $lr  = $lrTotals[$gl->id] ?? 0;
            $pop = $populationAssoc[$gl->grade] ?? 0;

            $directData[] = (int) $lr;
            $ratioLabels[] = $this->computeRatioLabel($lr, $pop);
        }

        return [
            'grades'        => $gradeNames,
            'population'    => $populationAssoc,
            'directData'    => $directData,
            'mailData'      => array_fill(0, count($gradeNames), 0),
            'ratioLabels'   => $ratioLabels,
            'library_scope' => $explicitLibraryId ? 'single_library' : 'auto_scoped',
            'library_id'    => $explicitLibraryId ?: 'auto',
            'source'        => 'live_optimized',
        ];
    }

    private function buildZeroedResult(array $gradeNames): array
    {
        $count = count($gradeNames);
        return [
            'grades'        => $gradeNames,
            'population'    => array_fill_keys($gradeNames, 0),
            'directData'    => array_fill(0, $count, 0),
            'mailData'      => array_fill(0, $count, 0),
            'ratioLabels'   => array_fill(0, $count, 'N/A'),
            'library_scope' => 'no_scope',
            'source'        => 'empty',
        ];
    }

    private function emptyResult(string $message): array
    {
        return [
            'grades'      => [],
            'population'  => [],
            'directData'  => [],
            'mailData'    => [],
            'ratioLabels' => [],
            'message'     => $message,
        ];
    }

    private function computeRatioLabel($totalLR, $pop): string
    {
        if ($totalLR <= 0 || $pop <= 0) {
            return 'N/A';
        }

        $peoplePerBook = $pop / $totalLR;

        if ($peoplePerBook >= 1) {
            return round($peoplePerBook) . ' : 1';
        }

        return round($totalLR / $pop, 1) . ' : 1';
    }
}