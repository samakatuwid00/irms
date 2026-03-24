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
        private readonly LrAggregationService $aggregationService,
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

        // MODIFIED: Force use of live queries for ALL user levels
        // Materialized views are no longer used for testing purposes
        $useMv = false; // Force real-time queries for all levels

        Log::info('LR Sufficiency data source decision', [
            'user_level'    => $userLevel,
            'station_id'    => $stationId,
            'using_mv'      => $useMv,
            'library_count' => $allowedLibraryIds?->count() ?? 0,
            'source'        => 'live_query_direct_schema'
        ]);

        // Removed MV path - now all levels use live queries
        // if ($useMv) {
        //     return $this->buildFromMv(...);
        // }

        return $this->buildFromLiveQuery(
            $subjects, $gradeLevels, $allowedLibraryIds,
            $userLevel, $stationId, $explicitLibraryId
        );
    }

    // ── Live query path (now used for ALL user levels) ──────────────────────

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
        $sgls = SubjectGradeLevel::whereIn('subject_id', $subjectIds)
            ->whereIn('grade_level_id', $gradeIds)
            ->get()
            ->keyBy(fn($s) => $s->subject_id . '_' . $s->grade_level_id);

        // ── OPTIMIZATION 2: Bulk-load ALL LR quantities in ONE query ──
        $lrData = empty($libraryIds)
            ? collect()
            : $this->aggregationService
                ->aggregateBySubjectGrade($libraryIds, $gradeIds, $subjectIds)
                ->keyBy(fn($r) => $r->subject_id . '_' . $r->grade_level_id);

        // ── OPTIMIZATION 3: Bulk-load ALL population totals in ONE query ──
        $populationByGrade = $this->bulkPopulationByGrade($libraryIds, $gradeLevels, $userLevel, $stationId);

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

        // Determine library scope based on user level
        $libraryScope = match ($userLevel) {
            4 => 'region',
            3 => 'division',
            2 => 'district',
            1 => 'school',
            default => 'aggregated',
        };

        return $this->wrapResult(
            $tableData, $gradeLevels, false, $libraryScope, $userLevel,
            $explicitLibraryId, $stationId
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
    private function bulkPopulationByGrade(array $libraryIds, Collection $gradeLevels, int $userLevel, ?string $stationId): array
    {
        if (empty($libraryIds)) {
            Log::debug('No library IDs for population query', [
                'user_level' => $userLevel,
                'station_id' => $stationId
            ]);
            return [];
        }

        $schoolIds = DB::table('school_libraries')
            ->whereIn('id', $libraryIds)
            ->pluck('school_id')
            ->unique()
            ->all();

        if (empty($schoolIds)) {
            Log::debug('No schools found for population query', [
                'user_level' => $userLevel,
                'station_id' => $stationId,
                'library_count' => count($libraryIds)
            ]);
            return [];
        }

        $columnMap = $this->gradeColumnMap();

        // Build one SELECT with a SUM(CASE...) per grade column
        $selects = [];
        foreach ($columnMap as $gradeName => $col) {
            $selects[] = DB::raw("SUM({$col}) AS {$col}");
        }

        $row = DB::table('populations')
            ->whereIn('school_id', $schoolIds)
            ->select($selects)
            ->first();

        if (!$row) {
            Log::warning('No population data found for schools', [
                'school_count' => count($schoolIds),
                'user_level' => $userLevel,
                'station_id' => $stationId
            ]);
            return [];
        }

        // Map grade_level_id → population total
        $result = [];
        foreach ($gradeLevels as $gl) {
            $col = $columnMap[trim($gl->grade)] ?? null;
            $result[$gl->id] = $col ? (int) ($row->{$col} ?? 0) : 0;
        }

        Log::info('Population data retrieved', [
            'user_level' => $userLevel,
            'station_id' => $stationId,
            'school_count' => count($schoolIds),
            'population_totals' => $result
        ]);

        return $result;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

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
        string     $libraryScope,
        int        $userLevel,
        ?string    $explicitLibraryId = null,
        ?string    $stationId = null
    ): array {
        return [
            'grade_levels'  => $gradeLevels->pluck('grade')->toArray(),
            'table_data'    => $tableData,
            'library_scope' => $explicitLibraryId ? 'single' : $libraryScope,
            'library_id'    => $explicitLibraryId ?: 'auto',
            'using_mv'      => $useMv,
            'user_level'    => $userLevel,
            'station_id'    => $stationId,
            'source'        => 'live_query_direct_schema', // Changed from 'live_query'
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