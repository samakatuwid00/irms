<?php

namespace App\Services;

use App\Models\GradeLevel;
use App\Models\GradeOffering;
use App\Models\Subject;
use App\Support\GradeOfferingMap;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SchoolDashboardCurriculumScopeService
{
    /**
     * Resolve the grade, subject, and subject-grade axes used by dashboard charts.
     * School accounts are restricted to their configured grade offerings. Other
     * account levels retain the complete curriculum axes for their aggregate view.
     *
     * @return array{
     *     grade_levels: Collection,
     *     subjects: Collection,
     *     subject_grade_pairs: Collection,
     *     is_school_scoped: bool,
     *     message: string|null
     * }
     */
    public function resolve(int $userLevel, ?string $stationId): array
    {
        $isSchoolScoped = $userLevel === 1;
        $message = null;

        $gradeLevelQuery = GradeLevel::query()
            ->select('id', 'grade', 'sort_order')
            ->orderBy('sort_order');

        if ($isSchoolScoped) {
            $offering = $stationId
                ? GradeOffering::query()->where('school_id', $stationId)->first()
                : null;

            $offeredGradeNames = $offering
                ? collect(GradeOfferingMap::necEligible())
                    ->filter(fn (string $column): bool => strtolower((string) ($offering->{$column} ?? 'no')) === 'yes')
                    ->map(fn (string $column): ?string => GradeOfferingMap::gradeLevel($column))
                    ->filter()
                    ->values()
                    ->all()
                : [];

            $gradeLevelQuery->whereIn('grade', $offeredGradeNames);

            if ($offering === null) {
                $message = 'No grade offerings are configured for this school.';
            } elseif ($offeredGradeNames === []) {
                $message = 'This school has no offered grade levels configured.';
            }
        }

        $gradeLevels = $gradeLevelQuery->get();
        $gradeIds = $gradeLevels->pluck('id')->all();

        $mappingQuery = DB::table('subject_grade_levels')
            ->whereIn('grade_level_id', $gradeIds);

        $mappedSubjectIds = (clone $mappingQuery)
            ->distinct()
            ->pluck('subject_id')
            ->all();

        $subjects = Subject::query()
            ->select('id', 'subject_name', 'abbrv')
            ->when($isSchoolScoped, fn ($query) => $query->whereIn('id', $mappedSubjectIds))
            ->orderBy('subject_name')
            ->get();

        $subjectIds = $subjects->pluck('id')->all();
        $subjectGradePairs = (clone $mappingQuery)
            ->whereIn('subject_id', $subjectIds)
            ->get(['subject_id', 'grade_level_id'])
            ->mapWithKeys(fn ($mapping): array => [
                $mapping->subject_id.'|'.$mapping->grade_level_id => true,
            ]);

        if ($isSchoolScoped && $message === null && $subjects->isEmpty()) {
            $message = 'No subjects are mapped to this school\'s offered grade levels.';
        }

        return [
            'grade_levels' => $gradeLevels,
            'subjects' => $subjects,
            'subject_grade_pairs' => $subjectGradePairs,
            'is_school_scoped' => $isSchoolScoped,
            'message' => $message,
        ];
    }
}
