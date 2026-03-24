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
        private readonly LrAggregationService $aggregationService,
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

        // MODIFIED: Force use of live queries for ALL user levels
        // Materialized views are no longer used for testing purposes
        $useMv = false; // Force real-time queries for all levels

        Log::info('LR Subject-Grade Heatmap data source', [
            'user_level'    => $userLevel,
            'station_id'    => $stationId,
            'explicit_library' => $explicitLibraryId,
            'using_mv'      => $useMv,
            'source'        => 'live_query_direct_schema'
        ]);

        // Removed MV path - now all levels use live queries
        // if ($useMv) {
        //     $heatmapData = $this->buildFromMv(...);
        // } else {
        $heatmapData = $this->buildFromLiveQuery(
            $explicitLibraryId, $userLevel, $stationId,
            $subjects, $gradeLevels, $subjectIndexMap, $gradeIndexMap
        );
        // }

        // Determine library scope based on user level
        $libraryScope = match ($userLevel) {
            4 => 'region',
            3 => 'division',
            2 => 'district',
            1 => 'school',
            default => 'auto_scoped',
        };

        return [
            'x_axis'        => $subjects->pluck('abbrv')->toArray(),
            'y_axis'        => $gradeLevels->pluck('grade')->toArray(),
            'series_data'   => $heatmapData,
            'library_scope' => $explicitLibraryId ? 'single_library' : $libraryScope,
            'library_id'    => $explicitLibraryId ?: 'auto',
            'station_id'    => $stationId,
            'min_value'     => 0,
            'max_value'     => $this->getApproximateMax($heatmapData),
            'using_mv'      => false, // Always false now
            'user_level'    => $userLevel,
            'source'        => 'live_query_direct_schema', // Changed from 'live_query'
        ];
    }

    // ── Live query path (now used for ALL user levels) ──────────────────────

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
            'station_id'    => $stationId,
            'explicit_library' => $explicitLibraryId,
            'library_count' => count($libraryIds),
            'subject_count' => count($subjectIndexMap),
            'grade_count'   => count($gradeIndexMap),
        ]);

        // No libraries in scope → return all-zero matrix
        if (empty($libraryIds)) {
            Log::warning('No libraries in scope for heatmap', [
                'user_level' => $userLevel,
                'station_id' => $stationId
            ]);
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
        $nonZeroCount = 0;

        foreach ($subjectIndexMap as $subjectId => $subjIdx) {
            foreach ($gradeIndexMap as $gradeId => $gradeIdx) {
                $qty = (int) ($aggregated->get("{$subjectId}_{$gradeId}")?->total_qty ?? 0);
                $heatmapData[] = [$subjIdx, $gradeIdx, $qty];
                if ($qty > 0) {
                    $nonZeroCount++;
                }
            }
        }

        Log::info('Heatmap data assembled', [
            'total_cells' => count($heatmapData),
            'non_zero_cells' => $nonZeroCount,
            'max_value' => !empty($heatmapData) ? max(array_column($heatmapData, 2)) : 0
        ]);

        return $heatmapData;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

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
        return [
            'x_axis' => [], 
            'y_axis' => [], 
            'series_data' => [], 
            'message' => $message,
            'source' => 'empty'
        ];
    }

    private function getApproximateMax(array $data): int
    {
        if (empty($data)) return 100;
        $max = max(array_column($data, 2));
        // Return at least 20, rounded up to nearest 10
        return max(20, (int) ceil($max / 10) * 10);
    }
}