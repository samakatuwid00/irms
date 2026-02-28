<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MvViewRefresher extends Command
{
    protected $signature = 'summaries:refresh
                            {--tier=all : Which tier to refresh: all, region, division, district, charts, population}
                            {--concurrent : Force concurrent refresh for all views}';

    protected $description = 'Refresh materialized views by tier: region (6hr), division (4hr), district (2hr), charts & population (every run)';

    /**
     * Region-level views → refresh every 6 hours
     * mv_bosy_division_status is the region status view
     */
    private array $regionViews = [
        'lrmis.mv_bosy_division_status'          => true,   // true = supports CONCURRENTLY
        'lrmis.mv_learning_resources_region'     => true,
        'lrmis.mv_lr_charts_by_region'           => true,
    ];

    /**
     * Division-level views → refresh every 4 hours
     * mv_bosy_division_schools_status is the division status view
     */
    private array $divisionViews = [
        'lrmis.mv_bosy_division_schools_status'  => true,
        'lrmis.mv_learning_resources_division'   => true,
        'lrmis.mv_lr_charts_by_division'         => true,
    ];

    /**
     * District-level views → refresh every 2 hours
     * mv_bosy_district_schools_status is the district status view
     * district_schools_status is the school-level real-time view (schema-connected)
     */
    private array $districtViews = [
        'lrmis.mv_bosy_district_schools_status'             => true,
        'lrmis.mv_district_learning_resources_summary'      => true,
        'lrmis.mv_lr_charts_by_district'                    => true,
    ];

    /**
     * Population summary → no CONCURRENTLY support (no unique index)
     * Runs on every scheduled call alongside charts
     */
    private array $populationViews = [
        'lrmis.mv_population_summary'            => false,  // false = no CONCURRENTLY
    ];

    public function handle(): int
    {
        $tier      = $this->option('tier');
        $forceConcurrent = $this->option('concurrent');
        $startAll  = now();

        $this->info("===========================================");
        $this->info(" Materialized View Refresh | Tier: {$tier}");
        $this->info(" Started: " . $startAll->toDateTimeString());
        $this->info("===========================================");

        $viewsToRefresh = match ($tier) {
            'region'     => $this->regionViews,
            'division'   => $this->divisionViews,
            'district'   => $this->districtViews,
            'population' => $this->populationViews,
            'all'        => array_merge(
                                $this->populationViews,
                                $this->regionViews,
                                $this->divisionViews,
                                $this->districtViews
                            ),
            default      => $this->populationViews,
        };

        $failed  = 0;
        $success = 0;

        foreach ($viewsToRefresh as $view => $supportsConcurrent) {
            $start = now();

            // Use CONCURRENTLY only if the view supports it (has a unique index)
            // --concurrent flag forces it; otherwise use the per-view flag
            $useConcurrent = $forceConcurrent || $supportsConcurrent;

            $sql = $useConcurrent
                ? "REFRESH MATERIALIZED VIEW CONCURRENTLY {$view}"
                : "REFRESH MATERIALIZED VIEW {$view}";

            try {
                DB::statement($sql);

                $duration = now()->diffInMilliseconds($start);
                $this->info(sprintf(
                    "  ✓ %-60s %s  [%s ms]",
                    $view,
                    $useConcurrent ? '(concurrent)' : '(exclusive) ',
                    number_format($duration)
                ));

                Log::info("MV refreshed", [
                    'view'       => $view,
                    'tier'       => $tier,
                    'concurrent' => $useConcurrent,
                    'ms'         => $duration,
                ]);

                $success++;

            } catch (\Exception $e) {
                $this->error(sprintf(
                    "  ✗ %-60s FAILED: %s",
                    $view,
                    $e->getMessage()
                ));

                Log::error("MV refresh failed", [
                    'view'       => $view,
                    'tier'       => $tier,
                    'concurrent' => $useConcurrent,
                    'error'      => $e->getMessage(),
                ]);

                $failed++;
            }
        }

        $total = now()->diffInSeconds($startAll);

        $this->info("===========================================");
        $this->info(" Done in {$total}s — ✓ {$success} succeeded, ✗ {$failed} failed");
        $this->info("===========================================");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}