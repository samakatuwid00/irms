<?php

namespace App\Services;

use App\Support\GradeColumnMap;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LrAvailabilityService
{
    public function __construct(
        private readonly LibraryScopeService  $libraryScopeService,
        private readonly LrAggregationService $aggregationService,
        private readonly SchoolDashboardCurriculumScopeService $curriculumScopeService,
    ) {}

    public function getChartData(?string $explicitLibraryId, int $userLevel, ?string $stationId, ?string $printTypeId = null): array
    {
        $cacheKey = 'availability_chart_' . sha1(json_encode([
            $explicitLibraryId,
            $userLevel,
            $stationId,
            $printTypeId,
            session('dashboard_chart_cache_version'),
        ]));

        return Cache::remember($cacheKey, 3600, function () use ($explicitLibraryId, $userLevel, $stationId, $printTypeId) {
        $curriculumScope = $this->curriculumScopeService->resolve($userLevel, $stationId);
        $gradeLevels = $curriculumScope['grade_levels'];

        if ($gradeLevels->isEmpty()) {
            Log::warning('No grade levels found in database');
            return [
                'grade_level' => [],
                'series' => [],
                'message' => $curriculumScope['message'] ?? 'No grade levels found',
                'school_curriculum_scoped' => $curriculumScope['is_school_scoped'],
            ];
        }

        $gradeNames = $gradeLevels->pluck('grade')->toArray();
        $gradeIds = $gradeLevels->pluck('id')->toArray();

        $subjects = $curriculumScope['subjects'];
        $subjectIds = $subjects->pluck('id')->toArray();

        // MODIFIED: Now all user levels use real-time queries directly from the schema
        // Materialized views are no longer used for testing purposes
        $useMaterializedView = false; // Force real-time queries for all levels

        if ($useMaterializedView) {
            // This block is now effectively disabled
            // Kept for reference but won't execute
            Log::info('Materialized view path is disabled - using real-time queries');
        }

        // ────────────────────────────────────────────────
        // ALL USER LEVELS - use real-time queries directly from schema
        // This includes: School (1), District (2), Division (3), Region (4)
        // ────────────────────────────────────────────────
        
        $allowedLibraryIds = $this->libraryScopeService->getAllowedLibraryIds(
            $explicitLibraryId,
            $userLevel,
            $stationId
        );

        if ($allowedLibraryIds === null || $allowedLibraryIds->isEmpty()) {
            Log::warning('No libraries in scope → returning zero quantities');
        }

        // Build aggregated LR qty per subject + grade
        $libraryIds = $allowedLibraryIds->values()->toArray();
        $printTypeIds = $printTypeId ? [$printTypeId] : [];
        $aggregated = $this->aggregationService
            ->aggregateBySubjectGrade($libraryIds, $gradeIds, $subjectIds, $printTypeIds);

        $availableSubjectGrades = $curriculumScope['subject_grade_pairs'];

        $series = $this->buildSeriesFromData(
            $subjects,
            $gradeLevels,
            $aggregated,
            $availableSubjectGrades,
            'total_qty'
        );

        // Population
        $popSeriesData = $this->getPopulationData($allowedLibraryIds, $gradeLevels, $userLevel, $stationId);

        $series[] = [
            'name' => 'Population',
            'type' => 'line',
            'smooth' => true,
            'label' => ['position' => 'top'],
            'data' => $popSeriesData,
        ];

        // Determine library scope based on user level and parameters
        $libraryScope = match ($userLevel) {
            4 => 'region',
            3 => 'division',
            2 => 'district',
            1 => 'school',
            default => 'unknown',
        };

        return [
            'grade_level' => $gradeNames,
            'series' => $series,
            'library_scope' => $explicitLibraryId ? 'single_library' : $libraryScope,
            'library_id' => $explicitLibraryId ?: 'auto',
            'source' => 'live_query_direct_schema',
            'user_level' => $userLevel,
            'station_id' => $stationId,
            'print_type_id' => $printTypeId ?: null,
            'school_curriculum_scoped' => $curriculumScope['is_school_scoped'],
        ];
        });
    }

    private function buildSeriesFromData(
        $subjects,
        $gradeLevels,
        Collection $data,
        Collection $availableSubjectGrades,
        string $qtyColumn
    ): array
    {
        $series = [];
        $first = true;

        foreach ($subjects as $subject) {
            $dataPoints = [];
            foreach ($gradeLevels as $gl) {
                $mappingKey = $subject->id.'|'.$gl->id;

                if (! $availableSubjectGrades->has($mappingKey)) {
                    $dataPoints[] = null;
                    continue;
                }

                $row = $data->firstWhere(fn($r) => $r->subject_id == $subject->id && $r->grade_level_id == $gl->id);
                $qty = $row ? (int) $row->{$qtyColumn} : 0;
                $dataPoints[] = $qty;
            }

            $serie = [
                'name' => $subject->abbrv ?? $subject->subject_name,
                'type' => 'bar',
                'data' => $dataPoints
            ];

            if ($first) {
                $serie['barGap'] = 0;
                $first = false;
            }

            $series[] = $serie;
        }

        return $series;
    }

    private function getPopulationData(?Collection $allowedLibraryIds, $gradeLevels, int $userLevel, ?string $stationId): array
    {
        if ($allowedLibraryIds === null || $allowedLibraryIds->isEmpty()) {
            Log::debug('Population: no libraries → zeros');
            return array_fill(0, $gradeLevels->count(), 0);
        }

        // Get school IDs from the libraries
        $schoolIds = DB::table('school_libraries')
            ->whereIn('id', $allowedLibraryIds->toArray())
            ->pluck('school_id')
            ->unique();

        if ($schoolIds->isEmpty()) {
            return array_fill(0, $gradeLevels->count(), 0);
        }

        // Get population data directly from populations table
        $populationQuery = DB::table('populations')
            ->whereIn('school_id', $schoolIds)
            ->selectRaw(GradeColumnMap::sumSelectRaw());

        // For higher levels (region/division/district), we need to aggregate across schools
        // The GradeColumnMap::sumSelectRaw() already provides sum of all grade columns
        
        $row = $populationQuery->first();

        // Build population data for each grade level
        $popData = [];
        foreach ($gradeLevels as $gl) {
            $col = GradeColumnMap::column($gl->grade);
            $popData[] = $col ? (int)($row?->{$col} ?? 0) : 0;
        }

        Log::info('Population data fetched', [
            'user_level' => $userLevel,
            'station_id' => $stationId,
            'school_count' => $schoolIds->count(),
            'pop_data' => $popData
        ]);

        return $popData;
    }
}
