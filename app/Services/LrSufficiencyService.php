<?php

namespace App\Services;

use App\Models\GradeLevel;
use App\Models\Population;
use App\Models\Subject;
use App\Models\SubjectGradeLevel;
use App\Services\LibraryScopeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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

        Log::info('LrSufficiencyService START', [
            'explicitLibraryId' => $explicitLibraryId,
            'userLevel' => $userLevel,
            'stationId' => $stationId,
        ]);

        $gradeLevels = GradeLevel::query()
            ->select('id', 'grade', 'sort_order')
            ->orderBy('sort_order')
            ->get();

        if ($gradeLevels->isEmpty()) {
            Log::warning('No grade levels found');
            return ['error' => 'No grade levels found'];
        }

        $subjects = Subject::query()->orderBy('subject_name')->get();

        $allowedLibraryIds = $this->libraryScopeService->getAllowedLibraryIds(
            $explicitLibraryId,
            $userLevel,
            $stationId
        );

        Log::info('Resolved library scope', [
            'allowedLibraryIds' => $allowedLibraryIds?->toArray()
        ]);

        $gradeNames = $gradeLevels->pluck('grade')->toArray();
        $tableData = [];

        foreach ($subjects as $subject) {
            foreach ($gradeLevels as $grade) {

                $sgl = SubjectGradeLevel::where('subject_id', $subject->id)
                    ->where('grade_level_id', $grade->id)
                    ->first();

                $lrQty = 0;

                if ($sgl) {
                    $qtyPerPrint = DB::table('print_acquisitions')
                        ->select('print_id', DB::raw('SUM(total_qty) as total_per_print'))
                        ->groupBy('print_id');

                    $query = DB::table('print_resources')
                        ->joinSub($qtyPerPrint, 'acq_sum', function ($join) {
                            $join->on('print_resources.id', '=', 'acq_sum.print_id');
                        })
                        ->whereRaw("? = ANY(string_to_array(subject_grade_level_ids, ','))", [$sgl->id]);

                    if ($allowedLibraryIds !== null) {
                        if ($allowedLibraryIds->isEmpty()) {
                            $lrQty = 0;
                        } else {
                            $query->whereIn('print_resources.library_id', $allowedLibraryIds->toArray());
                        }
                    }

                    $lrQty = (int) $query->sum('acq_sum.total_per_print');
                }

                $population = $this->getPopulationForSubjectGrade(
                    $subject->id,
                    $grade->id,
                    $stationId,
                    $userLevel
                );

                // ─── Core logic change ───────────────────────────────────────
                $diff = $lrQty - $population;

                Log::info('Computed subject-grade sufficiency', [
                    'subject'     => $subject->subject_name,
                    'grade'       => $grade->grade,
                    'lrQty'       => $lrQty,
                    'population'  => $population,
                    'difference'  => $diff,
                ]);

                $tableData[] = [
                    'subject'       => $subject->subject_name,
                    'grade'         => $grade->grade,
                    'population'    => $population,
                    'lr_quantity'   => $lrQty,
                    'difference'    => $diff,
                    'status'        => $this->getStatusLabel($diff),
                    'shortfall'     => $diff < 0 ? abs($diff) : 0,
                    'excess'        => $diff > 0 ? $diff : 0,
                ];
            }
        }

        Log::info('LrSufficiencyService END', [
            'rowsGenerated' => count($tableData)
        ]);

        return [
            'grade_levels'  => $gradeNames,
            'table_data'    => $tableData,
            'library_scope' => $explicitLibraryId ? 'single' : 'aggregated',
        ];
    }

    private function getPopulationForSubjectGrade(
        string $subjectId,
        string $gradeLevelId,
        ?string $stationId,
        int $userLevel
    ): int {
        $grade = GradeLevel::find($gradeLevelId);
        if (!$grade) {
            Log::warning('Missing grade level, fallback population used', [
                'gradeLevelId' => $gradeLevelId
            ]);
            return 150;
        }

        $base = 1000;

        $subject = Subject::find($subjectId);
        if ($subject) {
            $subjectName = $subject->subject_name;

            if (str_contains($subjectName, 'Mathematics') || str_contains($subjectName, 'Science')) {
                $base = (int) ($base * 1.08);
            }

            if (str_contains($subjectName, 'MAPEH') || str_contains($subjectName, 'TLE')) {
                $base = (int) ($base * 0.92);
            }
        }

        Log::debug('Population calculated', [
            'subjectId'     => $subjectId,
            'gradeLevelId'  => $gradeLevelId,
            'population'    => $base
        ]);

        return $base;
    }

    private function getStatusLabel(int $diff): string
    {
        if ($diff > 0) {
            return 'Excess';
        }

        if ($diff < 0) {
            return 'Deficient';
        }

        return 'Adequate';
    }
}