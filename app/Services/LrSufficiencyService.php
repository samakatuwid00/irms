<?php

namespace App\Services;

use App\Models\GradeLevel;
use App\Models\Subject;
use App\Models\SubjectGradeLevel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LrSufficiencyService
{
    public function __construct(
        private readonly LibraryScopeService  $libraryScopeService,
        private readonly LrAggregationService $aggregationService,  // ← injected
    ) {}

    public function getSufficiencyData(
        ?string $explicitLibraryId,
        int     $userLevel,
        ?string $stationId
    ): array {
        $gradeLevels = GradeLevel::query()
            ->select('id', 'grade', 'sort_order')
            ->orderBy('sort_order')
            ->get();

        if ($gradeLevels->isEmpty()) {
            return ['error' => 'No grade levels found'];
        }

        $subjects = Subject::query()->orderBy('subject_name')->get();

        $allowedLibraryIds = $this->libraryScopeService->getAllowedLibraryIds(
            $explicitLibraryId, $userLevel, $stationId
        );

        [$useMv, $mvTable, $mvIdColumn, $mvScope] = $this->resolveMvConfig($userLevel, $stationId);

        Log::info('LR Sufficiency data source decision', [
            'user_level'    => $userLevel,
            'station_id'    => $stationId,
            'using_mv'      => $useMv,
            'mv_scope'      => $mvScope,
            'library_count' => $allowedLibraryIds?->count() ?? 0,
        ]);

        if ($useMv) {
            return $this->buildFromMv(
                $mvTable, $mvIdColumn, $mvScope,
                $stationId, $subjects, $gradeLevels
            );
        }

        return $this->buildFromLiveQuery(
            $subjects, $gradeLevels, $allowedLibraryIds,
            $userLevel, $stationId, $explicitLibraryId
        );
    }

    // ── MV path ─────────────────────────────────────────────────────────────

    private function buildFromMv(
        string $mvTable, string $mvIdColumn, string $mvScope,
        string $stationId, Collection $subjects, Collection $gradeLevels
    ): array {
        // One query to fetch the entire MV result set for this station.
        // Previously this was one query PER subject PER grade inside the loops.
        $mvRows = DB::table($mvTable)
            ->where($mvIdColumn, $stationId)
            ->select(['subject_id', 'grade_level_id', 'total_lr_qty', 'pop_total'])
            ->get()
            ->keyBy(fn($r) => $r->subject_id . '_' . $r->grade_level_id);

        $tableData = [];

        foreach ($subjects as $subject) {
            foreach ($gradeLevels as $grade) {
                $row = $mvRows->get($subject->id . '_' . $grade->id);
                $lrQty      = (int) ($row?->total_lr_qty ?? 0);
                $population = (int) ($row?->pop_total    ?? 0);
                $tableData[] = $this->makeRow($subject, $grade, $lrQty, $population);
            }
        }

        return $this->wrapResult($tableData, $gradeLevels, true, $mvScope, 4);
    }

    // ── Live query path ──────────────────────────────────────────────────────

    private function buildFromLiveQuery(
        Collection          $subjects,
        Collection          $gradeLevels,
        ?Collection         $allowedLibraryIds,
        int                 $userLevel,
        ?string             $stationId,
        ?string             $explicitLibraryId
    ): array {
        $libraryIds = $this->normalizeLibraryIds($allowedLibraryIds);
        $gradeIds   = $gradeLevels->pluck('id')->all();
        $subjectIds = $subjects->pluck('id')->all();

        // ── OPTIMIZATION 1: Bulk-load ALL SubjectGradeLevels in ONE query ──
        // Previously: SubjectGradeLevel::where(...)->first() called once per
        // subject×grade pair = up to 130 queries for 10 subjects × 13 grades.
        // Now: one query, keyed in PHP.
        $sgls = SubjectGradeLevel::whereIn('subject_id', $subjectIds)
            ->whereIn('grade_level_id', $gradeIds)
            ->get()
            ->keyBy(fn($s) => $s->subject_id . '_' . $s->grade_level_id);

        // ── OPTIMIZATION 2: Bulk-load ALL LR quantities in ONE query ──
        // Previously: one aggregation query per SGL inside the loop.
        // Now: one query returning all subject×grade combos at once.
        $lrData = empty($libraryIds)
            ? collect()
            : $this->aggregationService
                ->aggregateBySubjectGrade($libraryIds, $gradeIds, $subjectIds)
                ->keyBy(fn($r) => $r->subject_id . '_' . $r->grade_level_id);

        // ── OPTIMIZATION 3: Bulk-load ALL population totals in ONE query ──
        // Previously: one populations query per grade inside the loop.
        // Now: one query for all grades at once.
        $populationByGrade = $this->bulkPopulationByGrade($libraryIds, $gradeLevels);

        // ── Assemble the matrix in PHP, zero DB calls ──
        $tableData = [];

        foreach ($subjects as $subject) {
            foreach ($gradeLevels as $grade) {
                $sgl = $sgls->get($subject->id . '_' . $grade->id);

                if (!$sgl) {
                    $tableData[] = $this->makeEmptyRow($subject->abbrv ?? $subject->subject_name, $grade->grade);
                    continue;
                }

                $lrQty      = (int) ($lrData->get($subject->id . '_' . $grade->id)?->total_qty ?? 0);
                $population = $populationByGrade[$grade->id] ?? 0;

                $tableData[] = $this->makeRow($subject, $grade, $lrQty, $population);
            }
        }

        return $this->wrapResult(
            $tableData, $gradeLevels, false, 'none', $userLevel,
            $explicitLibraryId
        );
    }

    /**
     * Fetch population totals for ALL grade levels in a SINGLE query.
     *
     * Uses a CASE-based SUM so the DB does one pass over populations
     * instead of one query per grade.
     *
     * Returns [ grade_level_id => total_population ]
     */
    private function bulkPopulationByGrade(array $libraryIds, Collection $gradeLevels): array
    {
        if (empty($libraryIds)) {
            return [];
        }

        $schoolIds = DB::table('school_libraries')
            ->whereIn('id', $libraryIds)
            ->pluck('school_id')
            ->unique()
            ->all();

        if (empty($schoolIds)) {
            return [];
        }

        $columnMap = $this->gradeColumnMap();

        // Build one SELECT with a SUM(CASE...) per grade column
        $selects = ['school_id'];
        foreach ($columnMap as $col) {
            $selects[] = DB::raw("SUM({$col}) AS {$col}");
        }

        $row = DB::table('populations')
            ->whereIn('school_id', $schoolIds)
            ->select($selects)
            ->groupBy('school_id')
            ->first();

        if (!$row) {
            return [];
        }

        // Map grade_level_id → population total
        $result = [];
        foreach ($gradeLevels as $gl) {
            $col = $columnMap[trim($gl->grade)] ?? null;
            $result[$gl->id] = $col ? (int) ($row->{$col} ?? 0) : 0;
        }

        return $result;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function resolveMvConfig(int $userLevel, ?string $stationId): array
    {
        if (!$stationId) {
            return [false, null, null, 'none'];
        }

        return match ($userLevel) {
            4 => [true, 'lrmis.mv_lr_charts_by_region',   'region_id',   'region'],
            3 => [true, 'lrmis.mv_lr_charts_by_division', 'division_id', 'division'],
            2 => [true, 'lrmis.mv_lr_charts_by_district', 'district_id', 'district'],
            default => [false, null, null, 'none'],
        };
    }

    private function makeRow($subject, $grade, int $lrQty, int $population): array
    {
        $diff = $lrQty - $population;

        return [
            'subject'     => $subject->abbrv ?? $subject->subject_name,
            'grade'       => $grade->grade,
            'population'  => $population,
            'lr_quantity' => $lrQty,
            'difference'  => $diff,
            'status'      => $this->getStatusLabel($diff),
            'shortfall'   => $diff < 0 ? abs($diff) : 0,
            'excess'      => $diff > 0 ? $diff       : 0,
        ];
    }

    private function makeEmptyRow(string $subjectName, string $grade): array
    {
        return [
            'subject'     => $subjectName,
            'grade'       => $grade,
            'population'  => 0,
            'lr_quantity' => 0,
            'difference'  => 0,
            'status'      => 'Adequate',
            'shortfall'   => 0,
            'excess'      => 0,
        ];
    }

    private function wrapResult(
        array      $tableData,
        Collection $gradeLevels,
        bool       $useMv,
        string     $mvScope,
        int        $userLevel,
        ?string    $explicitLibraryId = null
    ): array {
        return [
            'grade_levels'  => $gradeLevels->pluck('grade')->toArray(),
            'table_data'    => $tableData,
            'library_scope' => $explicitLibraryId ? 'single' : 'aggregated',
            'using_mv'      => $useMv,
            'mv_type'       => $mvScope,
            'user_level'    => $userLevel,
            'source'        => $useMv ? 'materialized_view' : 'live_query',
        ];
    }

    private function normalizeLibraryIds(Collection|array|null $ids): array
    {
        if ($ids === null)              return [];
        if ($ids instanceof Collection) $ids = $ids->values()->all();
        if (!is_array($ids))            return [];
        return array_values(array_filter(array_map('strval', $ids)));
    }

    /**
     * Single source of truth for grade name → population column mapping.
     * Shared by LrSufficiencyService, LrRatioService, LrAvailabilityService
     * via GradeColumnMap helper (see suggestion below) or inline here.
     */
    private function gradeColumnMap(): array
    {
        return [
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
    }

    private function getStatusLabel(int $diff): string
    {
        if ($diff > 0) return 'Excess';
        if ($diff < 0) return 'Deficient';
        return 'Adequate';
    }
}
