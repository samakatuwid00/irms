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
    ) {}

    /**
     * Get the LR Availability chart data.
     *
     * @param string|null $explicitLibraryId
     * @param int $userLevel
     * @param string|null $stationId
     * @return array
     */
    public function getChartData(?string $explicitLibraryId, int $userLevel, ?string $stationId): array
    {
        // Fetch grade levels
        $gradeLevels = GradeLevel::query()
            ->select('id', 'grade', 'sort_order')
            ->orderBy('sort_order')
            ->get();

        if ($gradeLevels->isEmpty()) {
            Log::warning('No grade levels found in database');
            return [
                'grade_level' => [],
                'series'      => [],
                'message'     => 'No grade levels found'
            ];
        }

        $gradeIds = $gradeLevels->pluck('id')->toArray();
        $gradeNames = $gradeLevels->pluck('grade')->toArray();

        // Fetch subjects
        $subjects = Subject::query()
            ->select('id', 'subject_name')
            ->orderBy('subject_name')
            ->get();

        $subjectIds = $subjects->pluck('id')->toArray();

        // Get allowed libraries based on scope
        $allowedLibraryIds = $this->libraryScopeService->getAllowedLibraryIds(
            $explicitLibraryId,
            $userLevel,
            $stationId
        );

        Log::info('Resolved allowed libraries for LR Availability chart', [
            'level'         => $userLevel,
            'station'       => $stationId,
            'library_count' => $allowedLibraryIds?->count() ?? 0,
        ]);

        // =========================================
        // AGGREGATED QUERY USING PRINT_RESOURCE_SGL
        // =========================================
        $query = DB::table('print_resource_sgl as prs')
            ->join('subject_grade_levels as sgl', 'prs.sgl_id', '=', 'sgl.id')
            ->join('print_acquisitions as pa', 'pa.print_id', '=', 'prs.print_id')
            ->select([
                'sgl.subject_id',
                'sgl.grade_level_id',
                DB::raw('COALESCE(SUM(pa.total_qty),0)::integer as total_qty')
            ])
            ->whereIn('sgl.subject_id', $subjectIds)
            ->whereIn('sgl.grade_level_id', $gradeIds);

        if ($allowedLibraryIds !== null && $allowedLibraryIds->isNotEmpty()) {
            $query->whereIn('prs.print_id', function ($subQuery) use ($allowedLibraryIds) {
                $subQuery->select('id')
                    ->from('print_resources')
                    ->whereIn('library_id', $allowedLibraryIds->toArray());
            });
        }

        $aggregatedData = $query
            ->groupBy('sgl.subject_id', 'sgl.grade_level_id')
            ->get()
            ->keyBy(fn($item) => $item->subject_id . '_' . $item->grade_level_id);

        // =========================================
        // BUILD SERIES FOR ECHARTS
        // =========================================
        $series = [];
        $first = true;

        foreach ($subjects as $subject) {
            $data = [];

            foreach ($gradeLevels as $gradeLevel) {
                $key = $subject->id . '_' . $gradeLevel->id;
                $totalQty = $aggregatedData->get($key)?->total_qty ?? 0;
                $data[] = (int) $totalQty;
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

        // Population line (replace with dynamic data if needed)
        $populationData = [10000, 11000, 12000, 13000, 14000, 14000, 14000, 14000, 14000, 14000, 20000, 20000, 20000, 20000];

        $series[] = [
            'name'   => 'Population',
            'type'   => 'line',
            'smooth' => true,
            'label'  => ['position' => 'top'],
            'data'   => $populationData
        ];

        return [
            'grade_level'   => $gradeNames,
            'series'        => $series,
            'library_scope' => $explicitLibraryId ? 'single_library' : 'auto_scoped',
            'library_id'    => $explicitLibraryId ?: 'auto',
        ];
    }
}
