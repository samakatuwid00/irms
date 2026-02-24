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

    public function getHeatmapData(?string $explicitLibraryId, int $userLevel, ?string $stationId): array
    {
        $gradeLevels = GradeLevel::query()
            ->select('id', 'grade', 'sort_order')
            ->orderBy('sort_order')
            ->get();

        if ($gradeLevels->isEmpty()) {
            return $this->emptyResponse('No grade levels found');
        }

        $subjects = Subject::query()
            ->orderBy('subject_name')
            ->get();

        if ($subjects->isEmpty()) {
            return $this->emptyResponse('No subjects found');
        }

        $gradeIndexMap   = $gradeLevels->pluck('id')->values()->flip()->toArray();
        $subjectIndexMap = $subjects->pluck('id')->values()->flip()->toArray();

        $heatmapData = []; // [ [subjectIndex, gradeIndex, lr_qty], ... ]

        // Decide which MV to use (if any)
        $useMv       = false;
        $mvTable     = null;
        $mvIdColumn  = null;
        $mvScope     = 'live';

        if ($userLevel === 4 && $stationId !== null) {
            $useMv      = true;
            $mvTable    = 'lrmis.mv_lr_charts_by_region';
            $mvIdColumn = 'region_id';
            $mvScope    = 'region_mv';
        } elseif ($userLevel === 3 && $stationId !== null) {
            $useMv      = true;
            $mvTable    = 'lrmis.mv_lr_charts_by_division';
            $mvIdColumn = 'division_id';
            $mvScope    = 'division_mv';
        } elseif ($userLevel === 2 && $stationId !== null) {
            $useMv      = true;
            $mvTable    = 'lrmis.mv_lr_charts_by_district';
            $mvIdColumn = 'district_id';
            $mvScope    = 'district_mv';
        }
        // level 1 → always live / real-time

        if ($useMv) {
            // ────────────────────────────────────────────────
            // MATERIALIZED VIEW PATH (region 4, division 3, district 2)
            // ────────────────────────────────────────────────
            Log::info("Using materialized view for LR subject-grade heatmap", [
                'user_level' => $userLevel,
                'mv_scope'   => $mvScope,
                'mv_table'   => $mvTable,
                'station_id' => $stationId,
            ]);

            if (!$stationId) {
                Log::warning("No {$mvIdColumn} provided for level {$userLevel} heatmap");
                return $this->emptyResponse("{$mvIdColumn} required for this level");
            }

            $rows = DB::table($mvTable)
                ->select([
                    'subject_id',
                    'grade_level_id',
                    'total_lr_qty',
                ])
                ->where($mvIdColumn, $stationId)
                ->get();

            foreach ($rows as $row) {
                $subjIdx = $subjectIndexMap[$row->subject_id] ?? null;
                $gradeIdx = $gradeIndexMap[$row->grade_level_id] ?? null;

                if ($subjIdx === null || $gradeIdx === null) {
                    continue;
                }

                $heatmapData[] = [
                    $subjIdx,
                    $gradeIdx,
                    (int) $row->total_lr_qty
                ];
            }
        } else {
            // ────────────────────────────────────────────────
            // LIVE QUERY PATH (school level 1, explicit single library, fallback)
            // ────────────────────────────────────────────────
            $allowedLibraryIds = $this->libraryScopeService->getAllowedLibraryIds(
                $explicitLibraryId,
                $userLevel,
                $stationId
            );

            // Safe handling — assume it's array (common pattern in your codebase)
            $libraryIdsArray = is_array($allowedLibraryIds) ? $allowedLibraryIds : ($allowedLibraryIds?->toArray() ?? []);

            Log::info('Using live aggregation for LR subject-grade heatmap', [
                'user_level'    => $userLevel,
                'explicit_lib'  => $explicitLibraryId,
                'station_id'    => $stationId,
                'library_count' => count($libraryIdsArray),
            ]);

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

                    if (!empty($libraryIdsArray)) {
                        // Modified: Filter by library_id on print_acquisitions instead of print_resources
                        $query->whereIn('print_acquisitions.library_id', $libraryIdsArray);
                        // Removed: $query->whereIn('print_resources.library_id', $libraryIdsArray);
                    } elseif ($explicitLibraryId === null && $allowedLibraryIds !== null) {
                            // No libraries allowed → force zero
                            $totalQty = 0;
                            goto next_cell;
                        }

                        $totalQty = (int) $query->sum('print_acquisitions.total_qty');
                    }

                    next_cell:
                    $heatmapData[] = [$subjIdx, $gradeIdx, $totalQty];
                }
            }
        }

        // ────────────────────────────────────────────────
        // Final response (common)
        // ────────────────────────────────────────────────
        $gradeNames   = $gradeLevels->pluck('grade')->toArray();
        $subjectNames = $subjects->pluck('subject_name')->toArray();

        return [
            'x_axis'        => $subjectNames,           // subjects = columns
            'y_axis'        => $gradeNames,             // grades   = rows
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
        $values = array_column($data, 2);
        $max = $values ? max($values) : 0;
        return max(20, (int) ceil($max / 10) * 10);
    }
}