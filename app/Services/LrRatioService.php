<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LrRatioService
{

    public function __construct(
        private readonly LibraryScopeService  $libraryScopeService,
        private readonly LrAggregationService $aggregationService,
        private readonly SchoolDashboardCurriculumScopeService $curriculumScopeService,
    ) {}

    public function getChartDataCached($explicitLibraryId, $userLevel, $stationId, ?string $printTypeId = null, bool $schoolOnly = false)
    {
        $cacheKey = 'ratio_chart_' . sha1(json_encode([
            $explicitLibraryId,
            $userLevel,
            $stationId,
            $printTypeId,
            $schoolOnly,
            session('dashboard_chart_cache_version'),
        ]));

        return Cache::remember($cacheKey, 3600, function () use ($explicitLibraryId, $userLevel, $stationId, $printTypeId, $schoolOnly) {
            return $this->getChartData($explicitLibraryId, $userLevel, $stationId, $printTypeId, $schoolOnly);
        });
    }

    private function getChartData($explicitLibraryId, $userLevel, $stationId, ?string $printTypeId = null, bool $schoolOnly = false)
    {
        $curriculumScope = $this->curriculumScopeService->resolve($userLevel, $stationId);
        $gradeLevels = $curriculumScope['grade_levels'];

        if ($gradeLevels->isEmpty()) {
            return $this->emptyResult(
                $curriculumScope['message'] ?? 'No grade levels found',
                $curriculumScope['is_school_scoped']
            );
        }

        $gradeNames = $gradeLevels->pluck('grade')->toArray();
        $gradeIds   = $gradeLevels->pluck('id')->toArray();

        Log::info('Using real-time direct schema query for LR Ratio', [
            'explicit_library' => $explicitLibraryId,
            'user_level'       => $userLevel,
            'station_id'       => $stationId,
            'print_type_id'    => $printTypeId ?: 'all',
        ]);

        $allowedLibraryIds = $schoolOnly
            ? $this->libraryScopeService->getAllowedSchoolLibraryIds($explicitLibraryId, $userLevel, $stationId)
            : $this->libraryScopeService->getAllowedLibraryIds($explicitLibraryId, $userLevel, $stationId);

        if ($allowedLibraryIds === null || $allowedLibraryIds->isEmpty()) {
            Log::warning('No libraries in scope → returning zero results', [
                'user_level' => $userLevel,
                'station_id' => $stationId
            ]);
            return $this->buildZeroedResult(
                $gradeNames,
                $curriculumScope['is_school_scoped']
            );
        }

        $libraryIds  = $allowedLibraryIds->values()->toArray();
        $printTypeIds = $printTypeId ? [$printTypeId] : [];

        $lrTotals = $this->aggregationService
            ->aggregateByGrade($libraryIds, $gradeIds, $printTypeIds);

        $populationAssoc = $this->getPopulationPerGrade($allowedLibraryIds, $gradeLevels, $userLevel, $stationId);

        $libraryScope = match ($userLevel) {
            4 => 'region',
            3 => 'division',
            2 => 'district',
            1 => 'school',
            default => 'auto_scoped',
        };

        return $this->buildFromLiveData(
            $lrTotals,
            $populationAssoc,
            $gradeLevels,
            $gradeNames,
            $explicitLibraryId,
            $libraryScope,
            $userLevel,
            $stationId,
            $printTypeId,
            $curriculumScope['is_school_scoped'],
            $schoolOnly
        );
    }

    private function getPopulationPerGrade($allowedLibraryIds, $gradeLevels, int $userLevel, ?string $stationId): array
    {
        $schoolIds = DB::table('school_libraries')
            ->whereIn('id', is_array($allowedLibraryIds) ? $allowedLibraryIds : $allowedLibraryIds->toArray())
            ->pluck('school_id')
            ->unique()
            ->all();

        if (empty($schoolIds)) {
            Log::debug('No schools found for population data', [
                'user_level' => $userLevel,
                'station_id' => $stationId
            ]);
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

            if ($col) {
                $popAssoc[$gl->grade] = (int) DB::table('populations')
                    ->whereIn('school_id', $schoolIds)
                    ->sum($col);
            } else {
                $popAssoc[$gl->grade] = 0;
            }
        }

        Log::info('Population data retrieved', [
            'user_level'         => $userLevel,
            'station_id'         => $stationId,
            'school_count'       => count($schoolIds),
            'population_summary' => $popAssoc
        ]);

        return $popAssoc;
    }

    private function buildFromLiveData(
        $lrTotals,
        $populationAssoc,
        $gradeLevels,
        $gradeNames,
        $explicitLibraryId,
        string $libraryScope,
        int $userLevel,
        ?string $stationId,
        ?string $printTypeId = null,
        bool $isSchoolCurriculumScoped = false,
        bool $schoolOnly = false
    ): array {
        $directData  = [];
        $ratioLabels = [];

        foreach ($gradeLevels as $gl) {
            $lr  = $lrTotals[$gl->id] ?? 0;
            $pop = $populationAssoc[$gl->grade] ?? 0;

            $directData[]  = (int) $lr;
            $ratioLabels[] = $this->computeRatioLabel($lr, $pop);
        }

        return [
            'grades'        => $gradeNames,
            'population'    => $populationAssoc,
            'directData'    => $directData,
            'mailData'      => array_fill(0, count($gradeNames), 0),
            'ratioLabels'   => $ratioLabels,
            'library_scope' => $schoolOnly ? 'school_libraries_only' : ($explicitLibraryId ? 'single_library' : $libraryScope),
            'library_id'    => $explicitLibraryId ?: 'auto',
            'source'        => 'live_query_direct_schema',
            'user_level'    => $userLevel,
            'station_id'    => $stationId,
            'print_type_id' => $printTypeId ?: null,
            'school_only'   => $schoolOnly,
            'school_curriculum_scoped' => $isSchoolCurriculumScoped,
        ];
    }

    private function buildZeroedResult(array $gradeNames, bool $isSchoolCurriculumScoped = false): array
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
            'school_curriculum_scoped' => $isSchoolCurriculumScoped,
        ];
    }

    private function emptyResult(string $message, bool $isSchoolCurriculumScoped = false): array
    {
        return [
            'grades'      => [],
            'population'  => [],
            'directData'  => [],
            'mailData'    => [],
            'ratioLabels' => [],
            'message'     => $message,
            'school_curriculum_scoped' => $isSchoolCurriculumScoped,
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
