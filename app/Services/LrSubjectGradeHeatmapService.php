<?php

namespace App\Services;

use App\Models\GradeLevel;
use App\Models\Subject;
use App\Models\SubjectGradeLevel;
use App\Services\LibraryScopeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LrSubjectGradeHeatmapService
{
    public function __construct(
        private readonly LibraryScopeService $libraryScopeService
    ) {
    }

    /**
     * Returns data structured for a heatmap chart:
     * - xAxis: subjects
     * - yAxis: grade levels
     * - series data: [subjectIndex, gradeIndex, total_qty]
     */
    public function getHeatmapData(?string $explicitLibraryId, int $userLevel, ?string $stationId): array
    {
        // 1. Get ordered grade levels
        $gradeLevels = GradeLevel::query()
            ->select('id', 'grade', 'sort_order')
            ->orderBy('sort_order')
            ->get();

        if ($gradeLevels->isEmpty()) {
            Log::warning('No grade levels found for heatmap');
            return $this->emptyResponse('No grade levels found');
        }

        // 2. Get ordered subjects
        $subjects = Subject::query()
            ->orderBy('subject_name')
            ->get();

        if ($subjects->isEmpty()) {
            Log::warning('No subjects found for heatmap');
            return $this->emptyResponse('No subjects found');
        }

        // 3. Resolve allowed libraries (same scope logic as LrAvailabilityService)
        $allowedLibraryIds = $this->libraryScopeService->getAllowedLibraryIds(
            $explicitLibraryId,
            $userLevel,
            $stationId
        );

        Log::info('Resolved allowed libraries for LR Heatmap', [
            'level'         => $userLevel,
            'station'       => $stationId,
            'library_count' => $allowedLibraryIds?->count() ?? 0,
        ]);

        // 4. Prepare indices
        $gradeIndexMap = $gradeLevels->pluck('id')->values()->flip()->toArray(); // id → index
        $subjectIndexMap = $subjects->pluck('id')->values()->flip()->toArray(); // id → index

        $heatmapData = []; // [ [subjectIdx, gradeIdx, value], ... ]

        // 5. For each subject × grade combination
        foreach ($subjects as $subject) {
            $subjIdx = $subjectIndexMap[$subject->id] ?? null;
            if ($subjIdx === null) continue;

            foreach ($gradeLevels as $grade) {
                $gradeIdx = $gradeIndexMap[$grade->id] ?? null;
                if ($gradeIdx === null) continue;

                $sgl = SubjectGradeLevel::where('subject_id', $subject->id)
                    ->where('grade_level_id', $grade->id)
                    ->first();

                $totalQty = 0;

                if ($sgl) {
                    $query = DB::table('print_acquisitions')
                        ->join('print_resources', 'print_acquisitions.print_id', '=', 'print_resources.id')
                        ->whereRaw("? = ANY(string_to_array(subject_grade_level_ids, ','))", [$sgl->id]);

                    if ($allowedLibraryIds !== null) {
                        if ($allowedLibraryIds->isNotEmpty()) {
                            $query->whereIn('print_resources.library_id', $allowedLibraryIds->toArray());
                        } else {
                            $totalQty = 0; // no libraries allowed → zero
                        }
                    }

                    $totalQty = (int) $query->sum('print_acquisitions.total_qty');
                }

                $heatmapData[] = [$subjIdx, $gradeIdx, $totalQty];
            }
        }

        // 6. Prepare labels (for frontend)
        $gradeNames = $gradeLevels->pluck('grade')->toArray();
        $subjectNames = $subjects->pluck('subject_name')->toArray();

        return [
            'x_axis'        => $subjectNames,           // subjects (horizontal)
            'y_axis'        => $gradeNames,             // grades (vertical)
            'series_data'   => $heatmapData,            // [ [subjIdx, gradeIdx, qty], ... ]
            'library_scope' => $explicitLibraryId ? 'single_library' : 'auto_scoped',
            'library_id'    => $explicitLibraryId ?: 'auto',
            'min_value'     => 0,                       // you can compute real min/max if needed
            'max_value'     => $this->getApproximateMax($heatmapData),
        ];
    }

    private function emptyResponse(string $message): array
    {
        return [
            'x_axis'      => [],
            'y_axis'      => [],
            'series_data' => [],
            'message'     => $message,
        ];
    }

    private function getApproximateMax(array $data): int
    {
        if (empty($data)) return 100;

        $max = max(array_column($data, 2));
        // Nice round number for visualMap
        return max(20, (int) ceil($max / 10) * 10);
    }
}