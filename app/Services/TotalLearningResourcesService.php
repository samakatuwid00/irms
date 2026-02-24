<?php

namespace App\Services;

use App\Models\NonprintAcquisition;
use App\Models\NonprintResource;
use App\Models\PrintAcquisition;
use App\Models\PrintResource;
use App\Models\Population;
use App\Services\LibraryScopeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;


class TotalLearningResourcesService
{
    private LibraryScopeService $libraryScopeService;

    public function __construct(LibraryScopeService $libraryScopeService)
    {
        $this->libraryScopeService = $libraryScopeService;
    }

    public function getTotalResourcesData(?string $explicitLibraryId, int $userLevel, ?string $stationId): array
    {
        $allowedLibraryIds = $this->libraryScopeService->getAllowedLibraryIds(
            $explicitLibraryId,
            $userLevel,
            $stationId
        );

        if ($allowedLibraryIds->isEmpty()) {
            return [
                'total' => 0,
                'print' => 0,
                'non_print' => 0
            ];
        }

        if ($userLevel === 1) {
            // Level 1: School → real-time calculation (single or few libraries)
            $printTotal = PrintResource::whereIn('library_id', $allowedLibraryIds)
                ->join('print_acquisitions', 'print_resources.id', '=', 'print_acquisitions.print_id')
                ->sum('print_acquisitions.total_qty');

            $nonPrintTotal = NonprintResource::whereIn('library_id', $allowedLibraryIds)
                ->join('nonprint_acquisitions', 'nonprint_resources.id', '=', 'nonprint_acquisitions.nonprint_id')
                ->sum('nonprint_acquisitions.total_qty');

            $total = $printTotal + $nonPrintTotal;

            return [
                'total' => (int) $total,
                'print' => (int) $printTotal,
                'non_print' => (int) $nonPrintTotal,
            ];
        }

        // Levels 2–4: Use different materialized views per level
        $row = null;

        switch ($userLevel) {
            case 4: // Region
                $row = DB::table('mv_learning_resources_region')
                    ->select(['total_lr', 'total_print', 'total_nonprint'])
                    ->where('region_id', $stationId)
                    ->first();
                break;

            case 3: // Division
                $row = DB::table('mv_learning_resources_division')
                    ->select(['total_lr', 'total_print', 'total_nonprint'])
                    ->where('division_id', $stationId)
                    ->first();
                break;

            case 2: // District → use the district MV
                $row = DB::table('mv_district_learning_resources_summary')
                    ->select(['total_lr', 'total_print', 'total_nonprint'])
                    ->where('district_id', $stationId)
                    ->first();
                break;

            default:
                return [
                    'total' => 0,
                    'print' => 0,
                    'non_print' => 0
                ];
        }

        // If no matching row in the materialized view → return zeros
        if (!$row) {
            return [
                'total' => 0,
                'print' => 0,
                'non_print' => 0
            ];
        }

        return [
            'total' => (int) $row->total_lr,
            'print' => (int) $row->total_print,
            'non_print' => (int) $row->total_nonprint,
        ];
    }

    /**
     * Get population summary:
     *   - Level 1 (school)     → real-time from populations table
     *   - Level 2–4 (district/division/region) → from materialized view mv_population_summary
     *   - No data → return zeros
     */
    public function getPopulationData(?string $explicitLibraryId, int $userLevel, ?string $stationId): array
    {
        if (!$stationId) {
            return $this->zeroPopulation();
        }

        if ($userLevel === 1) {
            // School level → real-time (single school)
            $population = Population::where('school_id', $stationId)
                ->latest('sy_id')           // most recent school year
                ->first();

            if (!$population) {
                return $this->zeroPopulation();
            }

            $male = collect([
                $population->k_m,
                $population->g1_m,
                $population->g2_m,
                $population->g3_m,
                $population->g4_m,
                $population->g5_m,
                $population->g6_m,
                $population->g7_m,
                $population->g8_m,
                $population->g9_m,
                $population->g10_m,
                $population->g11_m,
                $population->g12_m,
            ])->sum();

            $female = collect([
                $population->k_f,
                $population->g1_f,
                $population->g2_f,
                $population->g3_f,
                $population->g4_f,
                $population->g5_f,
                $population->g6_f,
                $population->g7_f,
                $population->g8_f,
                $population->g9_f,
                $population->g10_f,
                $population->g11_f,
                $population->g12_f,
            ])->sum();

            $total = collect([
                $population->k_total,
                $population->g1_total,
                $population->g2_total,
                $population->g3_total,
                $population->g4_total,
                $population->g5_total,
                $population->g6_total,
                $population->g7_total,
                $population->g8_total,
                $population->g9_total,
                $population->g10_total,
                $population->g11_total,
                $population->g12_total,
            ])->sum();

            return [
                'total' => (int) $total,
                'male' => (int) $male,
                'female' => (int) $female,
            ];
        }

        // Level 2, 3, 4 → use materialized view (aggregated)
        $query = DB::table('mv_population_summary')
            ->select(['total_population', 'total_male', 'total_female']);

        // Match the correct grouping level and set higher levels to NULL
        switch ($userLevel) {
            case 4: // Region
                $query->where('region_id', $stationId)
                    ->whereNull('division_id')
                    ->whereNull('district_id')
                    ->whereNull('school_id');
                break;

            case 3: // Division
                $query->where('division_id', $stationId)
                    ->whereNull('district_id')
                    ->whereNull('school_id');
                break;

            case 2: // District
                $query->where('district_id', $stationId)
                    ->whereNull('school_id');
                break;

            default:
                return $this->zeroPopulation();
        }

        $row = $query->first();

        if (!$row) {
            return $this->zeroPopulation();
        }

        return [
            'total' => (int) $row->total_population,
            'male' => (int) $row->total_male,
            'female' => (int) $row->total_female,
        ];
    }

    private function zeroPopulation(): array
    {
        return [
            'total' => 0,
            'male' => 0,
            'female' => 0,
        ];
    }
}