<?php

namespace App\Services;

use App\Models\GradeLevel;
use App\Models\Subject;
use App\Services\LibraryScopeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class LrAvailabilityService
{
    public function __construct(
        private readonly LibraryScopeService $libraryScopeService
    ) {
    }

    private function getPopulationColumn(string $grade): ?string
    {
        return match (trim($grade)) {
            'Kindergarten' => 'k_total',
            'Grade 1' => 'g1_total',
            'Grade 2' => 'g2_total',
            'Grade 3' => 'g3_total',
            'Grade 4' => 'g4_total',
            'Grade 5' => 'g5_total',
            'Grade 6' => 'g6_total',
            'Grade 7' => 'g7_total',
            'Grade 8' => 'g8_total',
            'Grade 9' => 'g9_total',
            'Grade 10' => 'g10_total',
            'Grade 11' => 'g11_total',
            'Grade 12' => 'g12_total',
            default => null,
        };
    }

    public function getChartData(?string $explicitLibraryId, int $userLevel, ?string $stationId): array
    {
        $gradeLevels = GradeLevel::query()
            ->select('id', 'grade', 'sort_order')
            ->orderBy('sort_order')
            ->get();

        if ($gradeLevels->isEmpty()) {
            Log::warning('No grade levels found in database');
            return [
                'grade_level' => [],
                'series' => [],
                'message' => 'No grade levels found'
            ];
        }

        $gradeNames = $gradeLevels->pluck('grade')->toArray();
        $gradeIds = $gradeLevels->pluck('id')->toArray();

        $subjects = Subject::query()->select('id', 'subject_name')->orderBy('subject_name')->get();
        $subjectIds = $subjects->pluck('id')->toArray();
        $useMaterializedView = in_array($userLevel, [2, 3, 4]);

        if ($useMaterializedView) {
            // ────────────────────────────────────────────────
            // REGION / DIVISION / DISTRICT levels – keep original MV logic
            // ────────────────────────────────────────────────
            if ($userLevel === 4) {
                $mvTable = 'mv_lr_charts_by_region';
                $idColumn = 'region_id';
            } elseif ($userLevel === 3) {
                $mvTable = 'mv_lr_charts_by_division';
                $idColumn = 'division_id';
            } elseif ($userLevel === 2) {
                $mvTable = 'mv_lr_charts_by_district';
                $idColumn = 'district_id';
            } else {
                $mvTable = null;
                $idColumn = null;
            }

            if ($mvTable && $idColumn && $stationId) {
                $mvData = DB::table("lrmis.$mvTable")
                    ->where($idColumn, $stationId)
                    ->whereIn('subject_id', $subjectIds)
                    ->whereIn('grade_level_id', $gradeIds)
                    ->select([
                        'subject_id',
                        'grade_level_id',
                        'total_lr_qty',
                        'pop_total',
                    ])
                    ->get()
                    ->keyBy(fn($row) => $row->subject_id . '_' . $row->grade_level_id);

                Log::info('MV data rows fetched', ['count' => $mvData->count()]);

                $series = $this->buildSeriesFromMv($subjects, $gradeLevels, $mvData);

                $popData = [];
                foreach ($gradeLevels as $gl) {
                    $sample = $mvData->firstWhere('grade_level_id', $gl->id);
                    $popData[] = $sample ? (int) $sample->pop_total : 0;
                }

                $series[] = [
                    'name' => 'Population',
                    'type' => 'line',
                    'smooth' => true,
                    'label' => ['position' => 'top'],
                    'data' => $popData,
                ];

                $libraryScope = match ($userLevel) {
                    4 => 'region',
                    3 => 'division',
                    2 => 'district',
                    default => 'unknown',
                };

                return [
                    'grade_level' => $gradeNames,
                    'series' => $series,
                    'library_scope' => $libraryScope,
                    'source' => 'materialized_view',
                    'user_level' => $userLevel,
                ];
            }

            Log::warning('MV path selected but missing config', ['user_level' => $userLevel, 'station_id' => $stationId]);
        }

        // ────────────────────────────────────────────────
        // SCHOOL LEVEL (1) + single library – use denormalized field
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
        $aggregated = $this->getLiveLrAggregation($allowedLibraryIds, $gradeIds, $subjectIds);

        $series = $this->buildSeriesFromData($subjects, $gradeLevels, $aggregated, 'total_qty');

        // Population
        $popSeriesData = $this->getPopulationData($allowedLibraryIds, $gradeLevels);

        $series[] = [
            'name' => 'Population',
            'type' => 'line',
            'smooth' => true,
            'label' => ['position' => 'top'],
            'data' => $popSeriesData,
        ];

        return [
            'grade_level' => $gradeNames,
            'series' => $series,
            'library_scope' => $explicitLibraryId ? 'single_library' : 'auto_scoped',
            'library_id' => $explicitLibraryId ?: 'auto',
            'source' => 'live_query_denormalized',
            'user_level' => $userLevel,
        ];
    }

private function getLiveLrAggregation(?Collection $allowedLibraryIds, array $gradeIds, array $subjectIds): Collection
{
    if ($allowedLibraryIds === null || $allowedLibraryIds->isEmpty()) {
        return collect();
    }

    $libraryIdsArray = $allowedLibraryIds->values()->toArray();

    // Sum acquisitions per print resource, filtered by library_id from acquisitions
    $qtyPerPrint = DB::table('print_acquisitions')
        ->select('print_id', DB::raw('COALESCE(SUM(total_qty), 0)::integer as total_per_print'))
        ->whereIn('library_id', $libraryIdsArray)  // ← Filter by library_id here!
        ->groupBy('print_id');

    // Explode comma-separated sgl_ids (text) and cast each to uuid for joining
    $base = DB::table('print_resources')
        ->joinSub($qtyPerPrint, 'acq', fn($j) => $j->on('print_resources.id', '=', 'acq.print_id'))
        ->select([
            'print_resources.id',
            DB::raw("unnest(string_to_array(subject_grade_level_ids, ','))::uuid as sgl_id"),
            'acq.total_per_print',
        ])
        ->whereNotNull('print_resources.subject_grade_level_ids')
        ->where('print_resources.subject_grade_level_ids', '<>', '');

    $aggregated = DB::table(DB::raw("({$base->toSql()}) as exploded"))
        ->mergeBindings($base)
        ->join('subject_grade_levels as sgl', 'exploded.sgl_id', '=', 'sgl.id')
        ->select([
            'sgl.subject_id',
            'sgl.grade_level_id',
            DB::raw('SUM(exploded.total_per_print)::integer as total_qty')
        ])
        ->whereIn('sgl.subject_id', $subjectIds)
        ->whereIn('sgl.grade_level_id', $gradeIds)
        ->groupBy('sgl.subject_id', 'sgl.grade_level_id')
        ->get();

    return $aggregated;
}

    private function buildSeriesFromData($subjects, $gradeLevels, Collection $data, string $qtyColumn): array
    {
        $series = [];
        $first = true;

        foreach ($subjects as $subject) {
            $dataPoints = [];
            foreach ($gradeLevels as $gl) {
                $row = $data->firstWhere(fn($r) => $r->subject_id == $subject->id && $r->grade_level_id == $gl->id);
                $qty = $row ? (int) $row->{$qtyColumn} : 0;
                $dataPoints[] = $qty;
            }

            $serie = [
                'name' => $subject->subject_name,
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

    private function buildSeriesFromMv($subjects, $gradeLevels, Collection $mvData): array
    {
        $series = [];
        $first = true;

        foreach ($subjects as $subject) {
            $data = [];
            foreach ($gradeLevels as $gl) {
                $key = $subject->id . '_' . $gl->id;
                $qty = $mvData->get($key)?->total_lr_qty ?? 0;
                $data[] = (int) $qty;
            }

            $serie = [
                'name' => $subject->subject_name,
                'type' => 'bar',
                'data' => $data
            ];

            if ($first) {
                $serie['barGap'] = 0;
                $first = false;
            }

            $series[] = $serie;
        }

        return $series;
    }

    private function getPopulationData(?Collection $allowedLibraryIds, $gradeLevels): array
    {
        if ($allowedLibraryIds === null || $allowedLibraryIds->isEmpty()) {
            Log::debug('Population: no libraries → zeros');
            return array_fill(0, $gradeLevels->count(), 0);
        }

        $schoolIds = DB::table('school_libraries')
            ->whereIn('id', $allowedLibraryIds->toArray())
            ->pluck('school_id')
            ->unique();

        $popData = [];
        foreach ($gradeLevels as $gl) {
            $column = $this->getPopulationColumn($gl->grade);
            if (!$column) {
                $popData[] = 0;
                continue;
            }

            $sum = DB::table('populations')
                ->whereIn('school_id', $schoolIds)
                ->sum($column);

            $popData[] = (int) $sum;
        }

        return $popData;
    }
}