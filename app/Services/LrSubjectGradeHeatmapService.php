<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class LrSubjectGradeHeatmapService
{
    public function __construct(
        private readonly LibraryScopeService  $libraryScopeService,
        private readonly LrAggregationService $aggregationService,
        private readonly SchoolDashboardCurriculumScopeService $curriculumScopeService,
    ) {}

    public function getHeatmapData(?string $explicitLibraryId, int $userLevel, ?string $stationId, ?string $printTypeId = null): array
    {
        $curriculumScope = $this->curriculumScopeService->resolve($userLevel, $stationId);
        $gradeLevels = $curriculumScope['grade_levels'];

        if ($gradeLevels->isEmpty()) {
            return $this->emptyResponse(
                $curriculumScope['message'] ?? 'No grade levels found',
                $curriculumScope['is_school_scoped']
            );
        }

        $subjects = $curriculumScope['subjects'];

        if ($subjects->isEmpty()) {
            return $this->emptyResponse(
                $curriculumScope['message'] ?? 'No subjects found',
                $curriculumScope['is_school_scoped']
            );
        }

        $gradeIndexMap   = $gradeLevels->pluck('id')->values()->flip()->toArray();
        $subjectIndexMap = $subjects->pluck('id')->values()->flip()->toArray();

        Log::info('LR Subject-Grade Heatmap data source', [
            'user_level'    => $userLevel,
            'station_id'    => $stationId,
            'explicit_library' => $explicitLibraryId,
            'print_type_id' => $printTypeId ?: 'all',
            'using_mv'      => false,
            'source'        => 'live_query_direct_schema'
        ]);

        $heatmapData = $this->buildFromLiveQuery(
            $explicitLibraryId, $userLevel, $stationId, $printTypeId,
            $subjects, $gradeLevels, $subjectIndexMap, $gradeIndexMap,
            $curriculumScope['subject_grade_pairs'],
            $curriculumScope['is_school_scoped']
        );

        $libraryScope = match ($userLevel) {
            4 => 'region',
            3 => 'division',
            2 => 'district',
            1 => 'school',
            default => 'auto_scoped',
        };

        return [
            'x_axis'        => $subjects
                ->map(fn ($subject) => $subject->abbrv ?? $subject->subject_name)
                ->values()
                ->toArray(),
            'y_axis'        => $gradeLevels->pluck('grade')->toArray(),
            'series_data'   => $heatmapData,
            'library_scope' => $explicitLibraryId ? 'single_library' : $libraryScope,
            'library_id'    => $explicitLibraryId ?: 'auto',
            'station_id'    => $stationId,
            'min_value'     => 0,
            'max_value'     => $this->getApproximateMax($heatmapData),
            'print_type_id' => $printTypeId,
            'using_mv'      => false,
            'user_level'    => $userLevel,
            'source'        => 'live_query_direct_schema',
            'school_curriculum_scoped' => $curriculumScope['is_school_scoped'],
        ];
    }

    private function buildFromLiveQuery(
        ?string $explicitLibraryId,
        int     $userLevel,
        ?string $stationId,
        ?string $printTypeId,
        $subjects,
        $gradeLevels,
        array   $subjectIndexMap,
        array   $gradeIndexMap,
        Collection $subjectGradePairs,
        bool    $isSchoolCurriculumScoped
    ): array {
        $allowedLibraryIds = $this->libraryScopeService->getAllowedLibraryIds(
            $explicitLibraryId, $userLevel, $stationId
        );

        $libraryIds = $allowedLibraryIds?->values()->toArray() ?? [];

        Log::info('Using live aggregation for LR subject-grade heatmap', [
            'user_level'    => $userLevel,
            'station_id'    => $stationId,
            'explicit_library' => $explicitLibraryId,
            'print_type_id' => $printTypeId ?: 'all',
            'library_count' => count($libraryIds),
        ]);

        if (empty($libraryIds)) {
            Log::warning('No libraries in scope for heatmap');
            return $this->buildZeroMatrix(
                $subjectIndexMap,
                $gradeIndexMap,
                $subjectGradePairs,
                $isSchoolCurriculumScoped
            );
        }

        $gradeIds     = $gradeLevels->pluck('id')->all();
        $subjectIds   = $subjects->pluck('id')->all();
        $printTypeIds = $printTypeId ? [$printTypeId] : [];

        $aggregated = $this->aggregationService
            ->aggregateBySubjectGrade($libraryIds, $gradeIds, $subjectIds, $printTypeIds)
            ->keyBy(fn($r) => $r->subject_id . '_' . $r->grade_level_id);

        $heatmapData = [];
        $nonZeroCount = 0;

        foreach ($subjectIndexMap as $subjectId => $subjIdx) {
            foreach ($gradeIndexMap as $gradeId => $gradeIdx) {
                if ($isSchoolCurriculumScoped && ! $subjectGradePairs->has($subjectId.'|'.$gradeId)) {
                    continue;
                }

                $qty = (int) ($aggregated->get("{$subjectId}_{$gradeId}")?->total_qty ?? 0);
                $heatmapData[] = [$subjIdx, $gradeIdx, $qty];
                if ($qty > 0) $nonZeroCount++;
            }
        }

        Log::info('Heatmap data assembled', [
            'total_cells'   => count($heatmapData),
            'non_zero_cells'=> $nonZeroCount,
        ]);

        return $heatmapData;
    }

    private function buildZeroMatrix(
        array $subjectIndexMap,
        array $gradeIndexMap,
        Collection $subjectGradePairs,
        bool $isSchoolCurriculumScoped
    ): array
    {
        $heatmapData = [];
        foreach ($subjectIndexMap as $subjectId => $subjIdx) {
            foreach ($gradeIndexMap as $gradeId => $gradeIdx) {
                if ($isSchoolCurriculumScoped && ! $subjectGradePairs->has($subjectId.'|'.$gradeId)) {
                    continue;
                }

                $heatmapData[] = [$subjIdx, $gradeIdx, 0];
            }
        }
        return $heatmapData;
    }

    private function emptyResponse(string $message, bool $isSchoolCurriculumScoped = false): array
    {
        return [
            'x_axis' => [], 
            'y_axis' => [], 
            'series_data' => [], 
            'message' => $message,
            'source' => 'empty',
            'school_curriculum_scoped' => $isSchoolCurriculumScoped,
        ];
    }

    private function getApproximateMax(array $data): int
    {
        if (empty($data)) return 100;
        $max = max(array_column($data, 2));
        return max(20, (int) ceil($max / 10) * 10);
    }
}
