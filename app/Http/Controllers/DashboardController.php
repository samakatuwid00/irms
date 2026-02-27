<?php

namespace App\Http\Controllers;

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
        $this->lrAvailabilityService = $lrAvailabilityService;
        $this->lrRatioService = $lrRatioService;
        $this->lrSufficiencyService = $lrSufficiencyService;
        $this->lrHeatmapService = $lrHeatmapService;
        $this->totalLearningResourcesService = $totalLearningResourcesService;
        $this->totalPopulationService = $totalPopulationService;
        $this->lrNeedsService = $lrNeedsService;
        $this->bosyStatusService = $bosyStatusService;
    }

    public function index()
    {
        $user = Auth::user();
        $userLevel = $this->determineUserLevel($user);
        $stationId = $this->determineStationId($user, $userLevel);

        $totalLrData = $this->totalLearningResourcesService->getTotalResourcesData(null, $userLevel, $stationId);
        $populationData = $this->totalPopulationService->getPopulationData(null, $userLevel, $stationId);
        $lrNeedsData = $this->lrNeedsService->getLrNeeds(null, $userLevel, $stationId, 5); // top 5 needs

        $totalLr = (int) ($totalLrData['total'] ?? 0);
        $totalPop = (int) ($populationData['total'] ?? 0);

        $ratioDisplay = 'N/A';

        if ($totalLr > 0 && $totalPop > 0) {
            $learnersPerResource = $totalPop / $totalLr;

            // Always round to nearest whole number
            $rounded = round($learnersPerResource);

            // Most dashboards prefer "1 : X" format even when X < 1
            // but if you really want to show the reverse when < 1:
            if ($rounded >= 1) {
                $ratioDisplay = "1 : " . number_format($rounded);
            } else {
                // Rare case: more resources than learners
                $resourcesPerLearner = round($totalLr / $totalPop);
                $ratioDisplay = number_format($resourcesPerLearner) . " : 1";
            }
        }

        // Prepare dropdown data
        $regionOptions = [
            ['value' => '', 'label' => 'All Library Hubs'],
            ['value' => 'division-hub', 'label' => 'Division Library Hub'],
            ['value' => 'school-hub', 'label' => 'School Library Hub'],
        ];

        // For most users → only show their own level + lower
        if ($userLevel < 4) {
            $regionOptions = array_filter($regionOptions, function ($opt) use ($userLevel) {
                if ($opt['value'] === 'all-library')
                    return false;
                if ($opt['value'] === 'region-hub' && $userLevel < 3)
                    return false;
                return true;
            });
        }

        // Divisions/Districts - different for level 3 vs level 4
        $divisions = [];
        if ($userLevel >= 3) {
            if ($userLevel === 3) {
                // For division users (level 3) - show their districts
                $query = District::select('id', 'district_name as name')
                    ->where('division_id', $stationId) // Assuming District has division_id
                    ->where('id', '!=', '39b30b89-89e5-48fb-a879-6551aad12121') // Exclude specific district
                    ->orderBy('district_name');
            } else {
                // For region users (level 4) and above - show all divisions
                $query = Division::select('id', 'division_name as name')
                    ->orderBy('division_name');

                if ($userLevel === 4) {
                    // If it's a region user, scope to their region
                    $query->where('region_id', $stationId);
                }
                // level >4 sees all divisions
            }

            $divisions = $query->get()->toArray();
        }

        $overallRatioData = [
            'ratio_display' => $ratioDisplay,
            'total_lr' => $totalLr,
            'total_population' => $totalPop,
        ];

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
        ));
    }

    public function getLrAvailabilityData(Request $request)
    {
        $explicitLibraryId = $request->query('library_id');
        $user = Auth::user();

        $userLevel = $this->determineUserLevel($user);
        $stationId = $this->determineStationId($user, $userLevel);

        Log::info('LR Availability chart requested', [
            'explicit_library_id' => $explicitLibraryId ?: 'none (auto-scope)',
            'user_level' => $userLevel,
            'station_id' => $stationId,
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
        ]);

        try {
            $data = $this->lrAvailabilityService->getChartData(
                $explicitLibraryId,
                $userLevel,
                $stationId
            );

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('LR Availability chart failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_level' => $userLevel,
                'station_id' => $stationId,
            ]);

            return response()->json([
                'error' => 'Failed to generate chart data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getLrRatioData(Request $request)
    {
        $explicitLibraryId = $request->query('library_id');
        $user = Auth::user();
        $userLevel = $this->determineUserLevel($user);
        $stationId = $this->determineStationId($user, $userLevel);

        Log::info('LR Ratio chart requested', [
            'explicit_library_id' => $explicitLibraryId ?: 'none (auto-scope)',
            'user_level' => $userLevel,
            'station_id' => $stationId,
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
        ]);

        try {
            $data = $this->lrRatioService->getChartDataCached(
                $explicitLibraryId,
                $userLevel,
                $stationId
            );

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('LR Ratio chart failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_level' => $userLevel,
                'station_id' => $stationId,
            ]);

            return response()->json([
                'error' => 'Failed to generate chart data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getLrSufficiencyData(Request $request)
    {
        $explicitLibraryId = $request->query('library_id');
        $user = Auth::user();

        $userLevel = $this->determineUserLevel($user);
        $stationId = $this->determineStationId($user, $userLevel);

        try {
            $data = $this->lrSufficiencyService->getSufficiencyData(
                $explicitLibraryId,
                $userLevel,
                $stationId
            );

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('LR Sufficiency chart failed', [
                'message' => $e->getMessage(),
                'user_level' => $userLevel,
                'station_id' => $stationId,
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getLrHeatmapData(Request $request)
    {
        $explicitLibraryId = $request->query('library_id');
        $user = Auth::user();

        $userLevel = $this->determineUserLevel($user);
        $stationId = $this->determineStationId($user, $userLevel);

        try {
            $data = $this->lrHeatmapService->getHeatmapData(
                $explicitLibraryId,
                $userLevel,
                $stationId
            );

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('LR Heatmap data failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function determineUserLevel($user): int
    {
        $stationId = $user->station_id;

        if (!$stationId) {
            return 0;
        }

        $cacheKey = "user_org_level_{$user->id}";
        $ttl = 86400;

        return Cache::remember($cacheKey, $ttl, function () use ($stationId) {
            if (School::where('id', $stationId)->exists())
                return 1;
            if (District::where('id', $stationId)->exists())
                return 2;
            if (Division::where('id', $stationId)->exists())
                return 3;
            if (Region::where('id', $stationId)->exists())
                return 4;
            return 0;
        });
    }

    private function determineStationId($user, int $level): ?string
    {
        return $user->station_id ?: null;
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
}
