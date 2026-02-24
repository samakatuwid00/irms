<?php

namespace App\Services;

use App\Models\GradeLevel;
use App\Models\Subject;
use App\Models\SubjectGradeLevel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LrSufficiencyService
{
    public function __construct(
        private readonly LibraryScopeService $libraryScopeService
    ) {}

    public function getSufficiencyData(
        ?string $explicitLibraryId,
        int $userLevel,
        ?string $stationId
    ): array {
        $gradeLevels = GradeLevel::query()
            ->select('id', 'grade', 'sort_order')
            ->orderBy('sort_order')
            ->get();

        if ($gradeLevels->isEmpty()) {
            return ['error' => 'No grade levels found'];
        }

        $subjects = Subject::query()
            ->orderBy('subject_name')
            ->get();

        $allowedLibraryIds = $this->libraryScopeService->getAllowedLibraryIds(
            $explicitLibraryId,
            $userLevel,
            $stationId
        );

        $gradeNames = $gradeLevels->pluck('grade')->toArray();
        $tableData = [];

        // Determine which materialized view to use (if any)
        $useMv = false;
        $mvTable = null;
        $mvIdColumn = null;
        $mvScope = 'none';

        if ($userLevel === 4 && $stationId !== null) {
            $useMv = true;
            $mvTable = 'lrmis.mv_lr_charts_by_region';
            $mvIdColumn = 'region_id';
            $mvScope = 'region';
        } elseif ($userLevel === 3 && $stationId !== null) {
            $useMv = true;
            $mvTable = 'lrmis.mv_lr_charts_by_division';
            $mvIdColumn = 'division_id';
            $mvScope = 'division';
        } elseif ($userLevel === 2 && $stationId !== null) {
            $useMv = true;
            $mvTable = 'lrmis.mv_lr_charts_by_district';
            $mvIdColumn = 'district_id';
            $mvScope = 'district';
        }
        // level 1 (school) → always live, no MV

        Log::info('LR Sufficiency data source decision', [
            'user_level'    => $userLevel,
            'station_id'    => $stationId,
            'using_mv'      => $useMv,
            'mv_table'      => $mvTable,
            'mv_scope'      => $mvScope,
            'library_count' => $this->getLibraryCountForLogging($allowedLibraryIds),
        ]);

        foreach ($subjects as $subject) {
            foreach ($gradeLevels as $grade) {
                $sgl = SubjectGradeLevel::where('subject_id', $subject->id)
                    ->where('grade_level_id', $grade->id)
                    ->first();

                if (!$sgl) {
                    $tableData[] = $this->makeEmptyRow($subject->subject_name, $grade->grade);
                    continue;
                }

                // ───────────────────────────────────────────────
                //  LR Quantity + Population
                // ───────────────────────────────────────────────
                if ($useMv && $mvTable && $mvIdColumn) {
                    // Use materialized view
                    $row = DB::table($mvTable)
                        ->where($mvIdColumn, $stationId)
                        ->where('subject_id', $subject->id)
                        ->where('grade_level_id', $grade->id)
                        ->select(['total_lr_qty', 'pop_total'])
                        ->first();

                    $lrQty = (int) ($row?->total_lr_qty ?? 0);
                    $population = (int) ($row?->pop_total ?? 0);
                } else {
                    // Live calculation
                    $lrQty = $this->calculateLiveLrQuantity(
                        $sgl->id,
                        $allowedLibraryIds
                    );

                    $population = $this->calculateLivePopulation(
                        $grade->id,
                        $allowedLibraryIds,
                        $userLevel,
                        $stationId
                    );
                }

                $diff = $lrQty - $population;

                $tableData[] = [
                    'subject'    => $subject->subject_name,
                    'grade'      => $grade->grade,
                    'population' => $population,
                    'lr_quantity' => $lrQty,
                    'difference' => $diff,
                    'status'     => $this->getStatusLabel($diff),
                    'shortfall'  => $diff < 0 ? abs($diff) : 0,
                    'excess'     => $diff > 0 ? $diff : 0,
                ];
            }
        }

        return [
            'grade_levels'  => $gradeNames,
            'table_data'    => $tableData,
            'library_scope' => $explicitLibraryId ? 'single' : 'aggregated',
            'using_mv'      => $useMv,
            'mv_type'       => $mvScope,
            'user_level'    => $userLevel,
            'source'        => $useMv ? 'materialized_view' : 'live_query',
        ];
    }

    private function calculateLiveLrQuantity(
        string $sglId,
        Collection|array|null $allowedLibraryIds
    ): int {
        $libraryIds = $this->normalizeLibraryIds($allowedLibraryIds);

        if ($libraryIds === [] || $libraryIds === null) {
            return 0;
        }

        // Sum acquisitions per print resource, filtered by library_id from acquisitions
        $qtyPerPrint = DB::table('print_acquisitions')
            ->select('print_id', DB::raw('SUM(total_qty) as total_per_print'))
            ->whereIn('library_id', $libraryIds)  // ← Filter by library_id here!
            ->groupBy('print_id');

        $query = DB::table('print_resources')
            ->joinSub($qtyPerPrint, 'acq_sum', function ($join) {
                $join->on('print_resources.id', '=', 'acq_sum.print_id');
            })
            ->whereRaw("? = ANY(string_to_array(subject_grade_level_ids, ','))", [$sglId]);

        // Removed: ->whereIn('print_resources.library_id', $libraryIds)

        return (int) $query->sum('acq_sum.total_per_print');
    }
    private function calculateLivePopulation(
        string $gradeLevelId,
        Collection|array|null $allowedLibraryIds,
        int $userLevel,
        ?string $stationId
    ): int {
        $libraryIds = $this->normalizeLibraryIds($allowedLibraryIds);

        if ($libraryIds === [] || $libraryIds === null) {
            return 0;
        }

        $grade = GradeLevel::find($gradeLevelId);
        if (!$grade) {
            return 0;
        }

        $column = match (trim($grade->grade)) {
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

        if (!$column) {
            return 0;
        }

        $query = DB::table('populations')
            ->join('schools', 'populations.school_id', '=', 'schools.id')
            ->selectRaw("COALESCE(SUM({$column}), 0) as total_pop");

        // Apply library filter when we have specific IDs
        $query->join('school_libraries', 'schools.id', '=', 'school_libraries.school_id')
              ->whereIn('school_libraries.id', $libraryIds);

        // Fallback district filter only if no libraries specified and we're at district level
        if (empty($libraryIds) && $userLevel === 2 && $stationId) {
            $query->where('schools.district_id', $stationId);
        }

        return (int) $query->first()?->total_pop ?? 0;
    }

    /**
     * Normalize allowed library IDs to plain array (or null)
     */
    private function normalizeLibraryIds(Collection|array|null $ids): ?array
    {
        if ($ids === null) {
            return null;
        }

        if ($ids instanceof Collection) {
            $ids = $ids->values()->all();
        }

        if (!is_array($ids)) {
            return null;
        }

        // Ensure numeric/string consistency — adjust casting if your IDs are UUIDs/strings
        return array_map('strval', array_filter($ids, fn($id) => !empty($id)));
    }

    /**
     * Helper for logging library count safely
     */
    private function getLibraryCountForLogging(Collection|array|null $ids): int
    {
        if ($ids === null) {
            return 0;
        }
        if ($ids instanceof Collection) {
            return $ids->count();
        }
        if (is_array($ids)) {
            return count($ids);
        }
        return 0;
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

    private function getStatusLabel(int $diff): string
    {
        if ($diff > 0) return 'Excess';
        if ($diff < 0) return 'Deficient';
        return 'Adequate';
    }
}