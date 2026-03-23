<?php

namespace App\Services;

use App\Models\GradeLevel;
use App\Models\Subject;
use App\Services\LibraryScopeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LrSubjectGradeHeatmapService
{
    public function __construct(
        private readonly LibraryScopeService  $libraryScopeService,
        private readonly LrAggregationService $aggregationService,  // ← injected
    ) {}

    public function getHeatmapData(?string $explicitLibraryId, int $userLevel, ?string $stationId): array
    {
        $gradeLevels = GradeLevel::query()
            ->select('id', 'grade', 'sort_order')
            ->orderBy('sort_order')
            ->get();

        if ($gradeLevels->isEmpty()) {
            return $this->emptyResponse('No grade levels found');
        }

        $subjects = Subject::query()->orderBy('subject_name')->get();

        if ($subjects->isEmpty()) {
            return $this->emptyResponse('No subjects found');
        }

        // Index maps: model id → position in the axis arrays
        $gradeIndexMap   = $gradeLevels->pluck('id')->values()->flip()->toArray();
        $subjectIndexMap = $subjects->pluck('id')->values()->flip()->toArray();

        [$useMv, $mvTable, $mvIdColumn, $mvScope] = $this->resolveMvConfig($userLevel, $stationId);

        if ($useMv) {
            $heatmapData = $this->buildFromMv($mvTable, $mvIdColumn, $stationId, $subjectIndexMap, $gradeIndexMap);
        } else {
            $heatmapData = $this->buildFromLiveQuery(
                $explicitLibraryId, $userLevel, $stationId,
                $subjects, $gradeLevels, $subjectIndexMap, $gradeIndexMap
            );
        }

        return [
            'x_axis'        => $subjects->pluck('abbrv')->toArray(),
            'y_axis'        => $gradeLevels->pluck('grade')->toArray(),
            'series_data'   => $heatmapData,
            'library_scope' => $mvScope,
            'scope_id'      => $useMv ? ($stationId ?? 'unknown') : ($explicitLibraryId ?: 'auto'),
            'min_value'     => 0,
            'max_value'     => $this->getApproximateMax($heatmapData),
            'using_mv'      => $useMv,
            'mv_type'       => $mvScope,
            'user_level'    => $userLevel,
            'source'        => $useMv ? 'materialized_view' : 'live_query',
        ];
    }

    // ── MV path ─────────────────────────────────────────────────────────────

    private function buildFromMv(
        string $mvTable,
        string $mvIdColumn,
        string $stationId,
        array  $subjectIndexMap,
        array  $gradeIndexMap
    ): array {
        $rows = DB::table($mvTable)
            ->select(['subject_id', 'grade_level_id', 'total_lr_qty'])
            ->where($mvIdColumn, $stationId)
            ->get();

        $heatmapData = [];

        foreach ($rows as $row) {
            $subjIdx  = $subjectIndexMap[$row->subject_id]    ?? null;
            $gradeIdx = $gradeIndexMap[$row->grade_level_id]  ?? null;

            if ($subjIdx === null || $gradeIdx === null) {
                continue;
            }

            $heatmapData[] = [$subjIdx, $gradeIdx, (int) $row->total_lr_qty];
        }

        return $heatmapData;
    }

    // ── Live query path ──────────────────────────────────────────────────────

    private function buildFromLiveQuery(
        ?string $explicitLibraryId,
        int     $userLevel,
        ?string $stationId,
        $subjects,
        $gradeLevels,
        array   $subjectIndexMap,
        array   $gradeIndexMap
    ): array {
        $allowedLibraryIds = $this->libraryScopeService->getAllowedLibraryIds(
            $explicitLibraryId, $userLevel, $stationId
        );

        $libraryIds = $allowedLibraryIds?->values()->toArray() ?? [];

        Log::info('Using live aggregation for LR subject-grade heatmap', [
            'user_level'    => $userLevel,
            'library_count' => count($libraryIds),
        ]);

        // No libraries in scope → return all-zero matrix
        if (empty($libraryIds)) {
            return $this->buildZeroMatrix($subjectIndexMap, $gradeIndexMap);
        }

        $gradeIds   = $gradeLevels->pluck('id')->all();
        $subjectIds = $subjects->pluck('id')->all();

        // ── OPTIMIZATION: one aggregation query replaces the nested loop ──
        // Previously: SubjectGradeLevel lookup + acquisition SUM per subject×grade
        // = up to 130 DB queries for 10 subjects × 13 grades.
        // Now: one query via LrAggregationService.
        $aggregated = $this->aggregationService
            ->aggregateBySubjectGrade($libraryIds, $gradeIds, $subjectIds)
            ->keyBy(fn($r) => $r->subject_id . '_' . $r->grade_level_id);

        $heatmapData = [];

        foreach ($subjectIndexMap as $subjectId => $subjIdx) {
            foreach ($gradeIndexMap as $gradeId => $gradeIdx) {
                $qty = (int) ($aggregated->get("{$subjectId}_{$gradeId}")?->total_qty ?? 0);
                $heatmapData[] = [$subjIdx, $gradeIdx, $qty];
            }
        }

        return $heatmapData;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function resolveMvConfig(int $userLevel, ?string $stationId): array
    {
        if (!$stationId) {
            return [false, null, null, 'live'];
        }

        return match ($userLevel) {
            4 => [true, 'lrmis.mv_lr_charts_by_region',   'region_id',   'region_mv'],
            3 => [true, 'lrmis.mv_lr_charts_by_division', 'division_id', 'division_mv'],
            2 => [true, 'lrmis.mv_lr_charts_by_district', 'district_id', 'district_mv'],
            default => [false, null, null, 'live'],
        };
    }

    /**
     * All-zero matrix for when there are no libraries in scope.
     * Preserves the expected shape of the series_data array.
     */
    private function buildZeroMatrix(array $subjectIndexMap, array $gradeIndexMap): array
    {
        $heatmapData = [];

        foreach ($subjectIndexMap as $subjIdx) {
            foreach ($gradeIndexMap as $gradeIdx) {
                $heatmapData[] = [$subjIdx, $gradeIdx, 0];
            }
        }

        return $heatmapData;
    }

    private function emptyResponse(string $message): array
    {
        return ['x_axis' => [], 'y_axis' => [], 'series_data' => [], 'message' => $message];
    }

    private function getApproximateMax(array $data): int
    {
        if (empty($data)) return 100;
        $max = max(array_column($data, 2));
        return max(20, (int) ceil($max / 10) * 10);
    }
}
