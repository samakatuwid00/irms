<?php

namespace App\Services;

use App\Models\SubjectGradeLevel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LrSufficiencyService
{
    public function __construct(
        private readonly LibraryScopeService  $libraryScopeService,
        private readonly LrAggregationService $aggregationService,
        private readonly SchoolDashboardCurriculumScopeService $curriculumScopeService,
    ) {}

    public function getSufficiencyData(
        ?string $explicitLibraryId,
        int     $userLevel,
        ?string $stationId,
        ?string $printTypeId = null,
        bool    $schoolOnly = false
    ): array {
        $cacheKey = 'exdef_chart_' . sha1(json_encode([
            $explicitLibraryId,
            $userLevel,
            $stationId,
            $printTypeId,
            $schoolOnly,
            session('dashboard_chart_cache_version'),
        ]));

        return Cache::remember($cacheKey, 3600, function () use ($explicitLibraryId, $userLevel, $stationId, $printTypeId, $schoolOnly) {
        $curriculumScope = $this->curriculumScopeService->resolve($userLevel, $stationId);
        $gradeLevels = $curriculumScope['grade_levels'];

        if ($gradeLevels->isEmpty()) {
            return $this->emptyResult(
                $curriculumScope['message'] ?? 'No grade levels found',
                $userLevel,
                $stationId,
                $curriculumScope['is_school_scoped']
            );
        }

        $subjects = $curriculumScope['subjects'];

        if ($subjects->isEmpty()) {
            return $this->emptyResult(
                $curriculumScope['message'] ?? 'No subjects found',
                $userLevel,
                $stationId,
                $curriculumScope['is_school_scoped']
            );
        }

        $allowedLibraryIds = $schoolOnly
            ? $this->libraryScopeService->getAllowedSchoolLibraryIds($explicitLibraryId, $userLevel, $stationId)
            : $this->libraryScopeService->getAllowedLibraryIds($explicitLibraryId, $userLevel, $stationId);

        Log::info('LR Sufficiency data source decision', [
            'user_level'     => $userLevel,
            'station_id'     => $stationId,
            'print_type_id'  => $printTypeId ?: 'all',
            'school_only'    => $schoolOnly,
            'library_count'  => $allowedLibraryIds?->count() ?? 0,
            'source'         => 'live_query_direct_schema',
        ]);

        return $this->buildFromLiveQuery(
            $subjects, $gradeLevels, $allowedLibraryIds,
            $userLevel, $stationId, $explicitLibraryId, $printTypeId,
            $curriculumScope['is_school_scoped'],
            $schoolOnly
        );
        });
    }

    // ── Live query path ──────────────────────────────────────────────────────

    private function buildFromLiveQuery(
        Collection  $subjects,
        Collection  $gradeLevels,
        ?Collection $allowedLibraryIds,
        int         $userLevel,
        ?string     $stationId,
        ?string     $explicitLibraryId,
        ?string     $printTypeId = null,
        bool        $isSchoolCurriculumScoped = false,
        bool        $schoolOnly = false
    ): array {
        $libraryIds  = $this->normalizeLibraryIds($allowedLibraryIds);
        $gradeIds    = $gradeLevels->pluck('id')->all();
        $subjectIds  = $subjects->pluck('id')->all();
        $printTypeIds = $printTypeId ? [$printTypeId] : [];

        // Bulk-load all SubjectGradeLevels in one query
        $sgls = SubjectGradeLevel::whereIn('subject_id', $subjectIds)
            ->whereIn('grade_level_id', $gradeIds)
            ->get()
            ->keyBy(fn($s) => $s->subject_id . '_' . $s->grade_level_id);

        // Bulk-load all LR quantities in one query, filtered by print type if set
        $lrData = empty($libraryIds)
            ? collect()
            : $this->aggregationService
                ->aggregateBySubjectGrade($libraryIds, $gradeIds, $subjectIds, $printTypeIds)
                ->keyBy(fn($r) => $r->subject_id . '_' . $r->grade_level_id);

        // Bulk-load all population totals in one query
        $populationByGrade = $this->bulkPopulationByGrade($libraryIds, $gradeLevels, $userLevel, $stationId);

        // Assemble the matrix in PHP — zero additional DB calls
        $tableData = [];

        foreach ($subjects as $subject) {
            foreach ($gradeLevels as $grade) {
                $sgl = $sgls->get($subject->id . '_' . $grade->id);

                if (!$sgl) {
                    if ($isSchoolCurriculumScoped) {
                        continue;
                    }

                    $tableData[] = $this->makeEmptyRow($subject->abbrv ?? $subject->subject_name, $grade->grade);
                    continue;
                }

                $lrQty      = (int) ($lrData->get($subject->id . '_' . $grade->id)?->total_qty ?? 0);
                $population = $populationByGrade[$grade->id] ?? 0;

                $tableData[] = $this->makeRow($subject, $grade, $lrQty, $population);
            }
        }

        $libraryScope = match ($userLevel) {
            4 => 'region',
            3 => 'division',
            2 => 'district',
            1 => 'school',
            default => 'aggregated',
        };

        return $this->wrapResult(
            $tableData, $gradeLevels, false, $libraryScope, $userLevel,
            $explicitLibraryId, $stationId, $printTypeId,
            $isSchoolCurriculumScoped,
            $schoolOnly
        );
    }

    /**
     * Fetch population totals for ALL grade levels in a single query.
     * Returns [ grade_level_id => total_population ]
     */
    private function bulkPopulationByGrade(array $libraryIds, Collection $gradeLevels, int $userLevel, ?string $stationId): array
    {
        if (empty($libraryIds)) {
            Log::debug('No library IDs for population query', [
                'user_level' => $userLevel,
                'station_id' => $stationId,
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
                'user_level'    => $userLevel,
                'station_id'    => $stationId,
                'library_count' => count($libraryIds),
            ]);
            return [];
        }

        $columnMap = $this->gradeColumnMap();

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
                'user_level'   => $userLevel,
                'station_id'   => $stationId,
            ]);
            return [];
        }

        $result = [];
        foreach ($gradeLevels as $gl) {
            $col = $columnMap[trim($gl->grade)] ?? null;
            $result[$gl->id] = $col ? (int) ($row->{$col} ?? 0) : 0;
        }

        Log::info('Population data retrieved', [
            'user_level'        => $userLevel,
            'station_id'        => $stationId,
            'school_count'      => count($schoolIds),
            'population_totals' => $result,
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
        ?string    $stationId = null,
        ?string    $printTypeId = null,
        bool       $isSchoolCurriculumScoped = false,
        bool       $schoolOnly = false
    ): array {
        return [
            'grade_levels'  => $gradeLevels->pluck('grade')->toArray(),
            'table_data'    => $tableData,
            'library_scope' => $schoolOnly ? 'school_libraries_only' : ($explicitLibraryId ? 'single' : $libraryScope),
            'library_id'    => $explicitLibraryId ?: 'auto',
            'using_mv'      => $useMv,
            'user_level'    => $userLevel,
            'station_id'    => $stationId,
            'print_type_id' => $printTypeId ?: null,
            'school_only'   => $schoolOnly,
            'source'        => 'live_query_direct_schema',
            'school_curriculum_scoped' => $isSchoolCurriculumScoped,
        ];
    }

    private function emptyResult(
        string $message,
        int $userLevel,
        ?string $stationId,
        bool $isSchoolCurriculumScoped
    ): array {
        return [
            'grade_levels' => [],
            'table_data' => [],
            'message' => $message,
            'user_level' => $userLevel,
            'station_id' => $stationId,
            'source' => 'empty',
            'school_curriculum_scoped' => $isSchoolCurriculumScoped,
        ];
    }

    private function normalizeLibraryIds(Collection|array|null $ids): array
    {
        if ($ids === null)              return [];
        if ($ids instanceof Collection) $ids = $ids->values()->all();
        if (!is_array($ids))            return [];
        return array_values(array_filter(array_map('strval', $ids)));
    }

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
