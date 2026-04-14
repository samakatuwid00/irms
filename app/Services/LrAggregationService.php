<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for the print LR aggregation query.
 *
 * All three previous services (LrAvailabilityService, LrRatioService,
 * LrSufficiencyService) duplicated the same unnest subquery chain.
 * This class owns it once.
 *
 * The query stays on the text column (subject_grade_level_ids) to retain
 * the existing schema. The GIN index on string_to_array(subject_grade_level_ids, ',')
 * makes the ANY() lookup index-backed instead of a full scan.
 */
class LrAggregationService
{
    /**
     * Return SUM(total_qty) grouped by subject_id + grade_level_id.
     *
     * Replaces getLiveLrAggregation(), getOptimizedLrTotalsPerGrade(),
     * and calculateLiveLrQuantity() across the three services.
     *
     * @param  array  $libraryIds   plain array of UUIDs
     * @param  array  $gradeIds     filter to these grade_level_ids (pass [] for all)
     * @param  array  $subjectIds   filter to these subject_ids (pass [] for all)
     * @return Collection  rows with ->subject_id, ->grade_level_id, ->total_qty
     */
    public function aggregateBySubjectGrade(
        array $libraryIds,
        array $gradeIds = [],
        array $subjectIds = [],
        array $printTypeIds = []
    ): Collection {
        if (empty($libraryIds)) {
            return collect();
        }

        // Step 1: sum acquisitions per print resource, scoped to allowed libraries.
        //         Filtering here (not on print_resources) is correct — library
        //         ownership lives on the acquisition, not the resource.
        $qtyPerPrint = DB::table('print_acquisitions')
            ->select('print_id', DB::raw('SUM(total_qty)::integer AS total_per_print'))
            ->whereIn('library_id', $libraryIds)
            ->groupBy('print_id');

        // Step 2: explode the comma-separated SGL text column once,
        //         then join to subject_grade_levels for the real IDs.
        $exploded = DB::table('print_resources')
            ->joinSub($qtyPerPrint, 'acq', fn($j) => $j->on('print_resources.id', '=', 'acq.print_id'))
            ->select([
                DB::raw("unnest(string_to_array(subject_grade_level_ids, ','))::uuid AS sgl_id"),
                'acq.total_per_print',
            ])
            ->whereNotNull('subject_grade_level_ids')
            ->where('subject_grade_level_ids', '<>', '');

        // Optional: filter by print type(s)
        if (!empty($printTypeIds)) {
            $exploded->whereIn('print_resources.print_type_id', $printTypeIds);
        }

        // Step 3: aggregate at subject + grade level.
        $query = DB::table(DB::raw("({$exploded->toSql()}) AS exploded"))
            ->mergeBindings($exploded)
            ->join('subject_grade_levels AS sgl', 'exploded.sgl_id', '=', 'sgl.id')
            ->select([
                'sgl.subject_id',
                'sgl.grade_level_id',
                DB::raw('SUM(exploded.total_per_print)::integer AS total_qty'),
            ])
            ->groupBy('sgl.subject_id', 'sgl.grade_level_id');

        if (!empty($gradeIds)) {
            $query->whereIn('sgl.grade_level_id', $gradeIds);
        }

        if (!empty($subjectIds)) {
            $query->whereIn('sgl.subject_id', $subjectIds);
        }

        return $query->get();
    }

    /**
     * Same aggregation but grouped only by grade_level_id (for LrRatioService).
     *
     * @param  array  $libraryIds
     * @param  array  $gradeIds
     * @return array  [ grade_level_id => total_qty ]
     */
    public function aggregateByGrade(array $libraryIds, array $gradeIds = [], array $printTypeIds = []): array
    {
        if (empty($libraryIds)) {
            return [];
        }

        $qtyPerPrint = DB::table('print_acquisitions')
            ->select('print_id', DB::raw('SUM(total_qty)::integer AS total_per_print'))
            ->whereIn('library_id', $libraryIds)
            ->groupBy('print_id');

        $exploded = DB::table('print_resources')
            ->joinSub($qtyPerPrint, 'acq', fn($j) => $j->on('print_resources.id', '=', 'acq.print_id'))
            ->select([
                DB::raw("unnest(string_to_array(subject_grade_level_ids, ','))::uuid AS sgl_id"),
                'acq.total_per_print',
            ])
            ->whereNotNull('subject_grade_level_ids')
            ->where('subject_grade_level_ids', '<>', '');

        // Optional: filter by print type(s)
        if (!empty($printTypeIds)) {
            $exploded->whereIn('print_resources.print_type_id', $printTypeIds);
        }

        $query = DB::table(DB::raw("({$exploded->toSql()}) AS exploded"))
            ->mergeBindings($exploded)
            ->join('subject_grade_levels AS sgl', 'exploded.sgl_id', '=', 'sgl.id')
            ->select([
                'sgl.grade_level_id',
                DB::raw('SUM(exploded.total_per_print)::integer AS total_qty'),
            ])
            ->groupBy('sgl.grade_level_id');

        if (!empty($gradeIds)) {
            $query->whereIn('sgl.grade_level_id', $gradeIds);
        }

        return $query->pluck('total_qty', 'grade_level_id')->all();
    }
}