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

        Log::info('Total Learning Resources Data Request', [
            'user_level' => $userLevel,
            'station_id' => $stationId,
            'explicit_library' => $explicitLibraryId,
            'library_count' => $allowedLibraryIds?->count() ?? 0,
        ]);

        if ($allowedLibraryIds->isEmpty()) {
            Log::warning('No libraries in scope for total resources', [
                'user_level' => $userLevel,
                'station_id' => $stationId
            ]);
            return [
                'total' => 0,
                'print' => 0,
                'non_print' => 0
            ];
        }

        // MODIFIED: All user levels now use real-time calculations
        // Materialized views are no longer used for testing purposes
        
        $libraryIds = $allowedLibraryIds->values()->toArray();
        
        // Get print resources total
        $printTotal = PrintResource::whereIn('library_id', $libraryIds)
            ->join('print_acquisitions', 'print_resources.id', '=', 'print_acquisitions.print_id')
            ->sum('print_acquisitions.total_qty');

        // Get non-print resources total
        $nonPrintTotal = NonprintResource::whereIn('library_id', $libraryIds)
            ->join('nonprint_acquisitions', 'nonprint_resources.id', '=', 'nonprint_acquisitions.nonprint_id')
            ->sum('nonprint_acquisitions.total_qty');

        $total = $printTotal + $nonPrintTotal;

        Log::info('Total resources calculated', [
            'user_level' => $userLevel,
            'station_id' => $stationId,
            'library_count' => count($libraryIds),
            'print_total' => (int) $printTotal,
            'non_print_total' => (int) $nonPrintTotal,
            'total' => (int) $total,
        ]);

        return [
            'total' => (int) $total,
            'print' => (int) $printTotal,
            'non_print' => (int) $nonPrintTotal,
            'source' => 'live_query_direct_schema',
            'user_level' => $userLevel,
            'station_id' => $stationId,
        ];
    }

    /**
     * Get population summary for all user levels using real-time queries.
     * Now connects directly to the schema just like school level.
     */
    public function getPopulationData(?string $explicitLibraryId, int $userLevel, ?string $stationId): array
    {
        if (!$stationId) {
            Log::warning('No station ID provided for population data', [
                'user_level' => $userLevel
            ]);
            return $this->zeroPopulation();
        }

        Log::info('Population Data Request', [
            'user_level' => $userLevel,
            'station_id' => $stationId,
            'explicit_library' => $explicitLibraryId,
        ]);

        // MODIFIED: All user levels now use real-time calculations
        // Get the school IDs based on the user level and scope
        
        $schoolIds = $this->getSchoolIdsInScope($explicitLibraryId, $userLevel, $stationId);
        
        if (empty($schoolIds)) {
            Log::warning('No schools found in scope for population data', [
                'user_level' => $userLevel,
                'station_id' => $stationId
            ]);
            return $this->zeroPopulation();
        }

        // Get the most recent school year population data
        $latestSy = DB::table('populations')
            ->whereIn('school_id', $schoolIds)
            ->select('sy_id')
            ->orderBy('sy_id', 'asc')
            ->first();
            
        if (!$latestSy) {
            Log::warning('No population records found for schools', [
                'school_count' => count($schoolIds),
                'user_level' => $userLevel
            ]);
            return $this->zeroPopulation();
        }
        
        // Aggregate population data for all schools in scope for the latest school year
        $population = DB::table('populations')
            ->whereIn('school_id', $schoolIds)
            ->where('sy_id', $latestSy->sy_id)
            ->select([
                DB::raw('SUM(k_m) as k_m'),
                DB::raw('SUM(g1_m) as g1_m'),
                DB::raw('SUM(g2_m) as g2_m'),
                DB::raw('SUM(g3_m) as g3_m'),
                DB::raw('SUM(g4_m) as g4_m'),
                DB::raw('SUM(g5_m) as g5_m'),
                DB::raw('SUM(g6_m) as g6_m'),
                DB::raw('SUM(g7_m) as g7_m'),
                DB::raw('SUM(g8_m) as g8_m'),
                DB::raw('SUM(g9_m) as g9_m'),
                DB::raw('SUM(g10_m) as g10_m'),
                DB::raw('SUM(g11_m) as g11_m'),
                DB::raw('SUM(g12_m) as g12_m'),
                DB::raw('SUM(k_f) as k_f'),
                DB::raw('SUM(g1_f) as g1_f'),
                DB::raw('SUM(g2_f) as g2_f'),
                DB::raw('SUM(g3_f) as g3_f'),
                DB::raw('SUM(g4_f) as g4_f'),
                DB::raw('SUM(g5_f) as g5_f'),
                DB::raw('SUM(g6_f) as g6_f'),
                DB::raw('SUM(g7_f) as g7_f'),
                DB::raw('SUM(g8_f) as g8_f'),
                DB::raw('SUM(g9_f) as g9_f'),
                DB::raw('SUM(g10_f) as g10_f'),
                DB::raw('SUM(g11_f) as g11_f'),
                DB::raw('SUM(g12_f) as g12_f'),
                DB::raw('SUM(k_total) as k_total'),
                DB::raw('SUM(g1_total) as g1_total'),
                DB::raw('SUM(g2_total) as g2_total'),
                DB::raw('SUM(g3_total) as g3_total'),
                DB::raw('SUM(g4_total) as g4_total'),
                DB::raw('SUM(g5_total) as g5_total'),
                DB::raw('SUM(g6_total) as g6_total'),
                DB::raw('SUM(g7_total) as g7_total'),
                DB::raw('SUM(g8_total) as g8_total'),
                DB::raw('SUM(g9_total) as g9_total'),
                DB::raw('SUM(g10_total) as g10_total'),
                DB::raw('SUM(g11_total) as g11_total'),
                DB::raw('SUM(g12_total) as g12_total'),
            ])
            ->first();

        if (!$population) {
            Log::warning('No aggregated population data found', [
                'school_count' => count($schoolIds),
                'sy_id' => $latestSy->sy_id
            ]);
            return $this->zeroPopulation();
        }

        // Calculate totals
        $male = collect([
            $population->k_m ?? 0,
            $population->g1_m ?? 0,
            $population->g2_m ?? 0,
            $population->g3_m ?? 0,
            $population->g4_m ?? 0,
            $population->g5_m ?? 0,
            $population->g6_m ?? 0,
            $population->g7_m ?? 0,
            $population->g8_m ?? 0,
            $population->g9_m ?? 0,
            $population->g10_m ?? 0,
            $population->g11_m ?? 0,
            $population->g12_m ?? 0,
        ])->sum();

        $female = collect([
            $population->k_f ?? 0,
            $population->g1_f ?? 0,
            $population->g2_f ?? 0,
            $population->g3_f ?? 0,
            $population->g4_f ?? 0,
            $population->g5_f ?? 0,
            $population->g6_f ?? 0,
            $population->g7_f ?? 0,
            $population->g8_f ?? 0,
            $population->g9_f ?? 0,
            $population->g10_f ?? 0,
            $population->g11_f ?? 0,
            $population->g12_f ?? 0,
        ])->sum();

        $total = collect([
            $population->k_total ?? 0,
            $population->g1_total ?? 0,
            $population->g2_total ?? 0,
            $population->g3_total ?? 0,
            $population->g4_total ?? 0,
            $population->g5_total ?? 0,
            $population->g6_total ?? 0,
            $population->g7_total ?? 0,
            $population->g8_total ?? 0,
            $population->g9_total ?? 0,
            $population->g10_total ?? 0,
            $population->g11_total ?? 0,
            $population->g12_total ?? 0,
        ])->sum();

        Log::info('Population data calculated', [
            'user_level' => $userLevel,
            'station_id' => $stationId,
            'school_count' => count($schoolIds),
            'sy_id' => $latestSy->sy_id,
            'total_population' => (int) $total,
            'male' => (int) $male,
            'female' => (int) $female,
        ]);

        return [
            'total' => (int) $total,
            'male' => (int) $male,
            'female' => (int) $female,
            'source' => 'live_query_direct_schema',
            'user_level' => $userLevel,
            'station_id' => $stationId,
            'school_year' => $latestSy->sy_id,
        ];
    }

    /**
     * Get school IDs based on user level and scope
     * 
     * @param string|null $explicitLibraryId
     * @param int $userLevel
     * @param string|null $stationId
     * @return array
     */
    private function getSchoolIdsInScope(?string $explicitLibraryId, int $userLevel, ?string $stationId): array
    {
        $allowedLibraryIds = $this->libraryScopeService->getAllowedLibraryIds(
            $explicitLibraryId,
            $userLevel,
            $stationId
        );

        if ($allowedLibraryIds->isEmpty()) {
            return [];
        }

        $schoolIds = DB::table('school_libraries')
            ->whereIn('id', $allowedLibraryIds->values()->toArray())
            ->pluck('school_id')
            ->unique()
            ->values()
            ->toArray();

        Log::debug('School IDs retrieved', [
            'user_level' => $userLevel,
            'station_id' => $stationId,
            'library_count' => $allowedLibraryIds->count(),
            'school_count' => count($schoolIds),
        ]);

        return $schoolIds;
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