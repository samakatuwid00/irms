<?php

namespace App\Http\Controllers;

use App\Models\BosySetting;
use App\Models\District;
use App\Models\Division;
use App\Models\Region;
use App\Models\School;
use App\Services\LrAvailabilityService;
use App\Services\LrNeedsService;
use App\Services\LrRatioService;
use App\Services\LrSubjectGradeHeatmapService;
use App\Services\LrSufficiencyService;
use App\Services\TotalLearningResourcesService;
use App\Services\BosyStatusService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    private LrAvailabilityService $lrAvailabilityService;
    private LrRatioService $lrRatioService;
    private LrSufficiencyService $lrSufficiencyService;
    private LrSubjectGradeHeatmapService $lrHeatmapService;
    private TotalLearningResourcesService $totalLearningResourcesService;
    private TotalLearningResourcesService $totalPopulationService;
    private LrNeedsService $lrNeedsService;
    private BosyStatusService $bosyStatusService;


    public function __construct(
        LrAvailabilityService $lrAvailabilityService,
        LrRatioService $lrRatioService,
        LrSufficiencyService $lrSufficiencyService,
        LrSubjectGradeHeatmapService $lrHeatmapService,
        TotalLearningResourcesService $totalLearningResourcesService,
        TotalLearningResourcesService $totalPopulationService,
        LrNeedsService $lrNeedsService,
        BosyStatusService $bosyStatusService
    ) {
        $this->middleware('auth');
        $this->lrAvailabilityService          = $lrAvailabilityService;
        $this->lrRatioService                 = $lrRatioService;
        $this->lrSufficiencyService           = $lrSufficiencyService;
        $this->lrHeatmapService               = $lrHeatmapService;
        $this->totalLearningResourcesService  = $totalLearningResourcesService;
        $this->totalPopulationService         = $totalPopulationService;
        $this->lrNeedsService                 = $lrNeedsService;
        $this->bosyStatusService              = $bosyStatusService;
    }

    // =========================================================================
    // Main dashboard view
    // =========================================================================

    public function index()
    {
        $user      = Auth::user();
        $userLevel = $this->determineUserLevel($user);
        $stationId = $this->determineStationId($user, $userLevel);
        $userTypeId = $user->usertype_id ?? '';
        $totalLrData    = $this->totalLearningResourcesService->getTotalResourcesData(null, $userLevel, $stationId);
        $populationData = $this->totalPopulationService->getPopulationData(null, $userLevel, $stationId);
        $lrNeedsData    = $this->lrNeedsService->getLrNeeds(null, $userLevel, $stationId, 5); // top 5 needs

        $totalLr  = (int) ($totalLrData['total']    ?? 0);
        $totalPop = (int) ($populationData['total'] ?? 0);

        $ratioDisplay = 'N/A';

        if ($totalLr > 0 && $totalPop > 0) {
            $learnersPerResource = $totalPop / $totalLr;
            $rounded             = round($learnersPerResource);

            if ($rounded >= 1) {
                $ratioDisplay = '1 : ' . number_format($rounded);
            } else {
                $resourcesPerLearner = round($totalLr / $totalPop);
                $ratioDisplay        = number_format($resourcesPerLearner) . ' : 1';
            }
        }

        // ------------------------------------------------------------------
        // BOSY Settings — loaded from DB so all users see the same values
        // ------------------------------------------------------------------
        $bosySettings = BosySetting::current();

        // Prepare dropdown data
        $regionOptions = [
            ['value' => '',             'label' => 'All'],
            ['value' => 'division-hub', 'label' => 'Division Library Hub'],
            ['value' => 'school-hub',   'label' => 'School LRs'],
        ];

        if ($userLevel < 4) {
            $regionOptions = array_filter($regionOptions, function ($opt) use ($userLevel) {
                if ($opt['value'] === 'all-library')  return false;
                if ($opt['value'] === 'region-hub' && $userLevel < 3) return false;
                return true;
            });
        }

        $divisions = [];
        if ($userLevel >= 3) {
            if ($userLevel === 3) {
                $query = District::select('id', 'district_name as name')
                    ->where('division_id', $stationId)
                    ->where('id', '!=', '39b30b89-89e5-48fb-a879-6551aad12121')
                    ->orderBy('district_name');
            } else {
                $query = Division::select('id', 'division_name as name')
                    ->orderBy('division_name');

                if ($userLevel === 4) {
                    $query->where('region_id', $stationId);
                }
            }

            $divisions = $query->get()->toArray();
        }

        $overallRatioData = [
            'ratio_display'    => $ratioDisplay,
            'total_lr'         => $totalLr,
            'total_population' => $totalPop,
        ];

        $printTypes = \App\Models\PrintType::select('id', 'type_name')
            ->orderBy('type_name')
            ->get()
            ->map(fn ($t) => ['value' => $t->id, 'label' => $t->type_name])
            ->toArray();

        $printTypeOptions = array_merge(
            [['value' => '', 'label' => 'All Print Types']],
            $printTypes
        );

        return view('pages.dashboard', compact(
            'user',
            'totalLrData',
            'populationData',
            'overallRatioData',
            'lrNeedsData',
            'regionOptions',
            'userLevel',
            'stationId',
            'divisions',
            'printTypeOptions',
            'bosySettings',
            'userTypeId'
        ));
    }

    // =========================================================================
    // BOSY Settings — read (all users) & write (region level only)
    // =========================================================================

    /**
     * GET /dashboard/bosy-settings
     * Returns current BOSY period + calendar year as JSON.
     * Called by the blade on page load and after a successful update so every
     * tab / user sees the latest values without a full page refresh.
     */
    public function getBosySettings()
    {
        $s = BosySetting::current();

        return response()->json([
            'calendar_year'  => $s->calendar_year,
            'period_start'   => $s->period_start ? $s->period_start->format('Y-m-d') : null,
            'period_end'     => $s->period_end   ? $s->period_end->format('Y-m-d')   : null,
            'period_display' => $s->period_display, // uses getPeriodDisplayAttribute()
        ]);
    }

    /**
     * POST /dashboard/bosy-settings
     * Updates calendar year, period start, and period end.
     * Restricted to regional accounts (userLevel === 4 or above).
     */
    public function updateBosySettings(Request $request)
    {
        $user      = Auth::user();
        $userLevel = $this->determineUserLevel($user);

        // ── Authorization: only regional accounts (level 4+) may update ──────
        if ($userLevel < 4) {
            return response()->json([
                'error' => 'Unauthorized. Only Regional Accounts may update BOSY settings.',
            ], 403);
        }

        // ── Validation ────────────────────────────────────────────────────────
        $validated = $request->validate([
            'calendar_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'period_start'  => ['required', 'date_format:Y-m-d'],
            'period_end'    => ['required', 'date_format:Y-m-d', 'after_or_equal:period_start'],
            'period_label'  => ['nullable', 'string', 'max:60'],
        ]);

        // ── Persist ───────────────────────────────────────────────────────────
        $setting = BosySetting::current();
        $setting->update([
            'calendar_year'      => $validated['calendar_year'],
            'period_start'       => $validated['period_start'],
            'period_end'         => $validated['period_end'],
            // If a custom label is supplied use it, otherwise null triggers auto-format
            'period_label'       => $validated['period_label'] ?? null,
            'updated_by_user_id' => (string) $user->id,
        ]);

        // Bust any cached BOSY data so the next load reflects the new period
        Cache::forget('bosy_settings_current');

        Log::info('BOSY settings updated', [
            'by_user_id'    => $user->id,
            'user_level'    => $userLevel,
            'calendar_year' => $setting->calendar_year,
            'period_start'  => $setting->period_start->format('Y-m-d'),
            'period_end'    => $setting->period_end->format('Y-m-d'),
        ]);

        return response()->json([
            'success'        => true,
            'calendar_year'  => $setting->calendar_year,
            'period_start'   => $setting->period_start->format('Y-m-d'),
            'period_end'     => $setting->period_end->format('Y-m-d'),
            'period_display' => $setting->period_display,
            'message'        => 'BOSY settings updated successfully.',
        ]);
    }

    // =========================================================================
    // Chart data endpoints (unchanged)
    // =========================================================================

    public function getLrAvailabilityData(Request $request)
    {
        $explicitLibraryId = $request->query('library_id');
        $printTypeId       = $request->query('print_type_id') ?: null;
        $user              = Auth::user();
        $userLevel         = $this->determineUserLevel($user);
        $stationId         = $this->determineStationId($user, $userLevel);

        Log::info('LR Availability chart requested', [
            'explicit_library_id' => $explicitLibraryId ?: 'none (auto-scope)',
            'print_type_id'       => $printTypeId ?: 'all',
            'user_level'          => $userLevel,
            'station_id'          => $stationId,
            'user_id'             => Auth::id(),
            'ip'                  => $request->ip(),
        ]);

        try {
            $data = $this->lrAvailabilityService->getChartData(
                $explicitLibraryId, $userLevel, $stationId, $printTypeId
            );
            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('LR Availability chart failed', [
                'message'    => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
                'user_level' => $userLevel,
                'station_id' => $stationId,
            ]);
            return response()->json(['error' => 'Failed to generate chart data', 'message' => $e->getMessage()], 500);
        }
    }

    public function getLrRatioData(Request $request)
    {
        $explicitLibraryId = $request->query('library_id');
        $printTypeId       = $request->query('print_type_id') ?: null;
        $user              = Auth::user();
        $userLevel         = $this->determineUserLevel($user);
        $stationId         = $this->determineStationId($user, $userLevel);

        Log::info('LR Ratio chart requested', [
            'explicit_library_id' => $explicitLibraryId ?: 'none (auto-scope)',
            'print_type_id'       => $printTypeId ?: 'all',
            'user_level'          => $userLevel,
            'station_id'          => $stationId,
            'user_id'             => Auth::id(),
            'ip'                  => $request->ip(),
        ]);

        try {
            $data = $this->lrRatioService->getChartDataCached(
                $explicitLibraryId, $userLevel, $stationId, $printTypeId
            );
            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('LR Ratio chart failed', [
                'message'    => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
                'user_level' => $userLevel,
                'station_id' => $stationId,
            ]);
            return response()->json(['error' => 'Failed to generate chart data', 'message' => $e->getMessage()], 500);
        }
    }

    public function getLrSufficiencyData(Request $request)
    {
        $explicitLibraryId = $request->query('library_id');
        $printTypeId       = $request->query('print_type_id') ?: null;
        $user              = Auth::user();
        $userLevel         = $this->determineUserLevel($user);
        $stationId         = $this->determineStationId($user, $userLevel);

        try {
            $data = $this->lrSufficiencyService->getSufficiencyData(
                $explicitLibraryId, $userLevel, $stationId, $printTypeId
            );
            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('LR Sufficiency chart failed', [
                'message'       => $e->getMessage(),
                'user_level'    => $userLevel,
                'station_id'    => $stationId,
                'print_type_id' => $printTypeId ?: 'all',
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getLrHeatmapData(Request $request)
    {
        $explicitLibraryId = $request->query('library_id');
        $printTypeId       = $request->query('print_type_id') ?: null;
        $user              = Auth::user();
        $userLevel         = $this->determineUserLevel($user);
        $stationId         = $this->determineStationId($user, $userLevel);

        Log::info('LR Heatmap data requested', [
            'explicit_library_id' => $explicitLibraryId ?: 'none (auto-scope)',
            'print_type_id'       => $printTypeId ?: 'all',
            'user_level'          => $userLevel,
            'station_id'          => $stationId,
        ]);

        try {
            $data = $this->lrHeatmapService->getHeatmapData(
                $explicitLibraryId, $userLevel, $stationId, $printTypeId
            );
            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('LR Heatmap data failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getBosyStatusData(Request $request)
    {
        $result = $this->bosyStatusService->getBosyStatusData($request);

        if (isset($result['error'])) {
            $status = $result['status'] ?? 500;
            unset($result['status']);
            return response()->json($result, $status);
        }

        return response()->json($result);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function determineUserLevel($user): int
    {
        $stationId = $user->station_id;

        if (!$stationId) {
            return 0;
        }

        $cacheKey = "user_org_level_{$user->id}";
        $ttl      = 86400;

        return Cache::remember($cacheKey, $ttl, function () use ($stationId) {
            if (School::where('id', $stationId)->exists())    return 1;
            if (District::where('id', $stationId)->exists())  return 2;
            if (Division::where('id', $stationId)->exists())  return 3;
            if (Region::where('id', $stationId)->exists())    return 4;
            return 0;
        });
    }

    private function determineStationId($user, int $level): ?string
    {
        return $user->station_id ?: null;
    }
}