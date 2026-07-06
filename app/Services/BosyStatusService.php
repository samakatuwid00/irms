<?php

namespace App\Services;

use App\Support\GradeColumnMap;
use App\Support\GradeOfferingMap;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BosyStatusService
{
    public function __construct(
        private readonly RegionNecCalculator $regionNecCalculator
    ) {}

    public function getBosyStatusData(Request $request): array
    {
        $user      = Auth::user();
        $userLevel = $this->determineUserLevel($user);
        $stationId = $this->determineStationId($user, $userLevel);

        if (!in_array($userLevel, [1, 2, 3, 4])) {
            return ['error' => 'Unauthorized', 'status' => 403];
        }

        $hubFilter      = $request->query('hub_filter', '');
        $districtFilter = $request->query('district_filter', '');
        $printTypeId    = $request->query('print_type_id') ?: null;
        $cacheKey       = 'bosy_status_' . sha1(json_encode([
            $userLevel,
            $stationId,
            $hubFilter,
            $districtFilter,
            $printTypeId,
            session('dashboard_chart_cache_version'),
        ]));

        try {
            return Cache::remember($cacheKey, now()->addHour(), function () use ($userLevel, $stationId, $hubFilter, $districtFilter, $printTypeId) {
                return match ($userLevel) {
                    4 => $this->getRegionData($stationId, $hubFilter, $printTypeId),
                    3 => $this->getDivisionData($stationId, $districtFilter, $printTypeId),
                    2 => $this->getDistrictData($stationId, $printTypeId),
                    1 => $this->getSchoolData($stationId, $printTypeId),
                };
            });
        } catch (\Exception $e) {
            Log::error('BOSY Status data failed', [
                'message'    => $e->getMessage(),
                'user_level' => $userLevel,
                'station_id' => $stationId,
                'trace'      => $e->getTraceAsString()
            ]);

            return ['error' => $e->getMessage(), 'status' => 500];
        }
    }

    private function getSchoolData(string $schoolId, ?string $printTypeId = null): array
    {
        // 1. Get school info + library estimated resources
        $school = DB::table('lrmis.schools as s')
            ->where('s.id', $schoolId)
            ->select(
                's.id as school_id',
                's.school_name',
                's.shortname',
                's.logo'
            )
            ->first();

        if (!$school) {
            return $this->emptyResponse('school', $schoolId);
        }

        // 2. Get all users under this school station
        $users = DB::table('lrmis.users as u')
            ->join('lrmis.usertypes as ut', 'ut.id', '=', 'u.usertype_id')
            ->where('u.station_id', $schoolId)
            ->where('u.status', 'active')
            ->select(
                'u.id as user_id',
                DB::raw("TRIM(CONCAT(u.firstname, ' ', COALESCE(u.middlename, ''), ' ', u.lastname)) as full_name"),
                'u.photo',
                'u.email',
                'ut.type_name'
            )
            ->orderBy('u.lastname')
            ->get();

        // 3. For each user, sum their print + nonprint acquisitions
        //    via print_resources → print_acquisitions (encoded_by)
        //    and nonprint_resources → nonprint_acquisitions (encoded_by)
        $userIds = $users->pluck('user_id')->toArray();

        // Print LR per user
        $printQuery = DB::table('lrmis.print_acquisitions as pa')
            ->join('lrmis.print_resources as pr', 'pr.id', '=', 'pa.print_id')
            ->whereIn('pa.encoded_by', $userIds)
            ->where('pa.library_id', function ($q) use ($schoolId) {
                $q->select('id')
                    ->from('lrmis.school_libraries')
                    ->where('school_id', $schoolId);
            });

        if ($printTypeId) {
            $printQuery->where('pr.print_type_id', $printTypeId);
        }

        $printPerUser = $printQuery->select(
                'pa.encoded_by',
                DB::raw('COALESCE(SUM(pa.usable + pa.partially_damaged + pa.damaged + pa.lost + pa.condemnable), 0) as total_print')
            )
            ->groupBy('pa.encoded_by')
            ->pluck('total_print', 'encoded_by');

        // Nonprint LR per user (skip when filtering by print type)
        $nonprintPerUser = collect();
        if (!$printTypeId) {
            $nonprintPerUser = DB::table('lrmis.nonprint_acquisitions as na')
                ->join('lrmis.nonprint_resources as nr', 'nr.id', '=', 'na.nonprint_id')
                ->whereIn('na.encoded_by', $userIds)
                ->where('na.library_id', function ($q) use ($schoolId) {
                    $q->select('id')
                        ->from('lrmis.school_libraries')
                        ->where('school_id', $schoolId);
                })
                ->select(
                    'na.encoded_by',
                    DB::raw('COALESCE(SUM(na.total_qty), 0) as total_nonprint')
                )
                ->groupBy('na.encoded_by')
                ->pluck('total_nonprint', 'encoded_by');
        }

        // 4. Compute NEC for the school
        $nec = $this->calculateNec([$schoolId]);

        // 5. Build items — one row per user
        $items = $users->map(function ($user) use ($printPerUser, $nonprintPerUser, $nec) {
            $userPrint    = (int) ($printPerUser[$user->user_id]    ?? 0);
            $userNonprint = (int) ($nonprintPerUser[$user->user_id] ?? 0);
            $userTotal    = $userPrint + $userNonprint;

            $percentage = $this->calculatePercentage($userTotal, $nec);

            return [
                'id'                 => $user->user_id,
                'name'               => $user->full_name,
                'shortname'          => $user->full_name,
                'logo'               => $user->photo
                    ? asset('storage/' . $user->photo)
                    : asset('assets/images/default.jpg'),
                'role'               => $user->type_name,
                'total_lr'           => $userTotal,
                'total_print'        => $userPrint,
                'total_nonprint'     => $userNonprint,
                'percentage'         => $percentage,
                'status'             => $this->determineBosyStatus($percentage),
                'color'              => $this->determineBosyColor($percentage),
                'last_updated'       => null,
                'libraries'          => ['total' => 1],
            ];
        });

        // 6. Summary — total across all users vs school estimated
        $totalActualLr = $items->sum('total_lr');
        $overallPercentage = $this->calculatePercentage($totalActualLr, $nec);

        return [
            'level'          => 'school',
            'items'          => $items->toArray(),
            'station_id'     => $schoolId,
            'station_name'   => $school->school_name,
            'school_info'    => [
                'name'          => $school->school_name,
                'shortname'     => $school->shortname ?? $school->school_name,
                'logo'          => $school->logo ?? null,
            ],
            'summary' => [
                'total_items'        => $items->count(),
                'item_label'         => 'Users',
                'total_libraries'    => 1,
                'total_lr'           => $totalActualLr,
                'overall_percentage' => $overallPercentage,
                'status'             => $this->determineBosyStatus($overallPercentage),
                'color'              => $this->determineBosyColor($overallPercentage),
            ],
            'period' => [
                'start' => '05 June',
                'end'   => '25 Dec',
                'year'  => '2026'
            ],
            'mv_refreshed_at' => now()->format('Y-m-d H:i:s'),
            'is_realtime'     => true,
        ];
    }

    private function getDistrictData(string $districtId, ?string $printTypeId = null): array
    {
        // Real-time query — no MV, mirrors getDivisionData pattern
        $schools = DB::table('lrmis.schools as s')
            ->join('lrmis.districts as d', 'd.id', '=', 's.district_id')
            ->leftJoin('lrmis.school_libraries as sl', 'sl.school_id', '=', 's.id')
            ->where('d.id', $districtId)
            ->select(
                's.id as school_id',
                's.school_name',
                's.logo',
                DB::raw('COUNT(DISTINCT sl.id) as total_libraries'),
                'd.id as district_id',
                'd.district_name',
                'd.division_id'
            )
            ->groupBy('s.id', 's.school_name', 's.logo', 'd.id', 'd.district_name', 'd.division_id')
            ->orderBy('s.school_name')
            ->get();

        if ($schools->isEmpty()) {
            return $this->emptyResponse('district', $districtId);
        }

        $schoolIds = $schools->pluck('school_id')->toArray();

        // Real-time LR calculation (print + nonprint or filtered)
        $realTimeLr = $this->getRealTimeTotalLrBySchool($schoolIds, $printTypeId);

        // Compute NEC once for both the district summary and displayed schools.
        $necBySchool = $this->calculateNecBySchool($schoolIds);
        $nec = (int) $necBySchool->sum();
        $divisionName = $this->getDivisionName((string) $schools->first()->division_id);

        $items = $schools->map(function ($school) use ($realTimeLr, $necBySchool, $divisionName) {
            $totalLr = (int) ($realTimeLr[$school->school_id] ?? 0);
            $schoolNec = (int) ($necBySchool[$school->school_id] ?? 0);
            $percentage = $this->calculatePercentage($totalLr, $schoolNec);

            return [
                'id'                 => $school->school_id,
                'name'               => $school->school_name,
                'shortname'          => $school->school_name,
                'logo'               => $school->logo
                    ? asset('storage/' . $school->logo)
                    : asset('assets/images/no_image.jpg'),
                'total_lr'           => $totalLr,
                'net_expected_count' => $schoolNec,
                'percentage'         => $percentage,
                'status'             => $this->determineBosyStatus($percentage),
                'color'              => $this->determineBosyColor($percentage),
                'last_updated'       => null,
                'libraries'          => ['total' => (int) $school->total_libraries],
                'parent'             => [
                    'id'   => $school->division_id,
                    'name' => $divisionName,
                ],
                'district'           => [
                    'id'   => $school->district_id,
                    'name' => $school->district_name,
                ],
            ];
        });

        $totals = [
            'total_lr'        => $items->sum('total_lr'),
            'total_items'     => $items->count(),
            'total_libraries' => $items->sum('libraries.total'),
        ];

        $overallPercentage = $this->calculatePercentage($totals['total_lr'], $nec);

        $districtName = $schools->first()->district_name ?? 'District';

        return [
            'level'        => 'district',
            'items'        => $items->toArray(),
            'station_id'   => $districtId,
            'station_name' => $districtName,
            'district_name' => $districtName,
            'summary'      => [
                'total_items'        => $totals['total_items'],
                'item_label'         => 'Schools',
                'total_libraries'    => $totals['total_libraries'],
                'total_lr'           => $totals['total_lr'],
                'net_expected_count' => $nec,
                'overall_percentage' => $overallPercentage,
                'status'             => $this->determineBosyStatus($overallPercentage),
                'color'              => $this->determineBosyColor($overallPercentage),
            ],
            'period'          => [
                'start' => '05 June',
                'end'   => '25 Dec',
                'year'  => '2026'
            ],
            'mv_refreshed_at' => now()->format('Y-m-d H:i:s'),
            'is_realtime'     => true,
        ];
    }

    private function getRegionData(string $regionId, string $hubFilter = '', ?string $printTypeId = null): array
    {
        // Real-time base query — schools connect via district_id to districts,
        // districts connect via division_id to divisions
        $divisionLibraryStats = DB::table('lrmis.division_libraries')
            ->select('division_id')
            ->selectRaw('COUNT(*) as library_count')
            ->selectRaw('COALESCE(SUM(net_expected_count), 0) as net_expected_count')
            ->groupBy('division_id');

        $schoolLibraryStats = DB::table('lrmis.school_libraries as sl')
            ->join('lrmis.schools as s', 's.id', '=', 'sl.school_id')
            ->join('lrmis.districts as dt', 'dt.id', '=', 's.district_id')
            ->select('dt.division_id')
            ->selectRaw('COUNT(*) as library_count')
            ->groupBy('dt.division_id');

        $divisions = DB::table('lrmis.divisions as dv')
            ->where('dv.region_id', $regionId)
            ->leftJoinSub($divisionLibraryStats, 'dls', 'dls.division_id', '=', 'dv.id')
            ->leftJoinSub($schoolLibraryStats, 'sls', 'sls.division_id', '=', 'dv.id')
            ->select(
                'dv.id as division_id',
                'dv.division_name',
                'dv.logo',
                DB::raw('COALESCE(dls.library_count, 0) as division_libraries'),
                DB::raw('COALESCE(sls.library_count, 0) as school_libraries'),
                DB::raw('(COALESCE(dls.library_count, 0) + COALESCE(sls.library_count, 0)) as total_libraries'),
                DB::raw('COALESCE(dls.net_expected_count, 0) as division_net_expected_count')
            )
            ->orderBy('dv.division_name')
            ->get();

        if ($divisions->isEmpty()) {
            return $this->emptyResponse('region', $regionId);
        }

        $divisionIds = $divisions->pluck('division_id')->toArray();

        $scopedProgress = match ($hubFilter) {
            'school-hub' => $this->getRegionSchoolLrProgressByDivision($divisionIds, $printTypeId),
            'division-hub' => $this->getRegionDivisionHubProgressByDivision($divisionIds, $printTypeId),
            default => null,
        };

        // The unfiltered Region view retains the existing aggregate LR / aggregate NEC formula.
        $schoolNecByDivision = $scopedProgress === null
            ? $this->calculateSchoolNecByDivision($divisionIds)
            : collect();
        $realTimeLr = $scopedProgress === null
            ? $this->getRealTimeTotalLrByDivision($divisionIds, $hubFilter, $printTypeId)
            : [];

        $items = $divisions->map(function ($division) use (
            $hubFilter,
            $realTimeLr,
            $schoolNecByDivision,
            $scopedProgress
        ) {
            if ($scopedProgress !== null) {
                $progress = $scopedProgress['divisions'][$division->division_id]
                    ?? $this->calculateAverageCompletionSummary([], [], []);
                $actualLr = $progress['total_lr'];
                $filteredNec = $progress['net_expected_count'];
                $percentage = $progress['percentage'];
            } else {
                $actualLr = (int) ($realTimeLr[$division->division_id] ?? 0);
                $divisionInputNec = (int) $division->division_net_expected_count;
                $schoolNec = (int) ($schoolNecByDivision[$division->division_id] ?? 0);
                $filteredNec = $this->regionNecCalculator->forFilter(
                    $hubFilter,
                    $divisionInputNec,
                    $schoolNec
                );
                $percentage = $this->calculatePercentage($actualLr, $filteredNec);
            }

            return [
                'id'                 => $division->division_id,
                'name'               => $division->division_name,
                'shortname'          => $division->division_name,
                'logo'               => $division->logo
                    ? asset('storage/' . $division->logo)
                    : asset('assets/images/no_image.jpg'),
                'total_lr'           => $actualLr,
                'net_expected_count' => $filteredNec,
                'percentage'         => $percentage,
                'status'             => $this->determineBosyStatus($percentage),
                'color'              => $this->determineBosyColor($percentage),
                'last_updated'       => null,
                'libraries'          => [
                    'total'     => (int) $division->total_libraries,
                    'schools'   => (int) $division->school_libraries,
                    'divisions' => (int) $division->division_libraries,
                ],
            ];
        });

        $totals = [
            'total_lr'          => $items->sum('total_lr'),
            'total_items'       => $items->count(),
            'total_libraries'   => $items->sum('libraries.total'),
            'school_libraries'  => $items->sum('libraries.schools'),
            'division_libraries'=> $items->sum('libraries.divisions'),
        ];

        $nec = (int) $items->sum('net_expected_count');
        $overallPercentage = $scopedProgress !== null
            ? $scopedProgress['overall']['percentage']
            : $this->calculatePercentage($totals['total_lr'], $nec);
        return [
            'level'      => 'region',
            'hub_filter' => $hubFilter,
            'items'      => $items->toArray(),
            'station_id' => $regionId,
            'station_name' => $this->getRegionName($regionId),
            'summary'    => [
                'total_items'        => $totals['total_items'],
                'item_label'         => 'Divisions',
                'total_libraries'    => $totals['total_libraries'],
                'school_libraries'   => $totals['school_libraries'],
                'division_libraries' => $totals['division_libraries'],
                'total_lr'           => $totals['total_lr'],
                'net_expected_count' => $nec,
                'overall_percentage' => $overallPercentage,
                'status'             => $this->determineBosyStatus($overallPercentage),
                'color'              => $this->determineBosyColor($overallPercentage),
            ],
            'period'          => [
                'start' => '05 June',
                'end'   => '25 Dec',
                'year'  => '2026'
            ],
            'mv_refreshed_at' => now()->format('Y-m-d H:i:s'),
            'is_realtime'     => true,
        ];
    }

    private function getDivisionData(string $divisionId, string $districtFilter = '', ?string $printTypeId = null): array
    {
        // Build schools list with estimated resources (real-time)
        $schoolsQuery = DB::table('lrmis.schools as s')
            ->join('lrmis.districts as d', 'd.id', '=', 's.district_id')
            ->leftJoin('lrmis.school_libraries as sl', 'sl.school_id', '=', 's.id')
            ->where('d.division_id', $divisionId);

        if (!empty($districtFilter)) {
            $schoolsQuery->where('d.id', $districtFilter);
        }

        $schools = $schoolsQuery->select(
                's.id as school_id',
                's.school_name',
                's.logo',
                DB::raw('COUNT(DISTINCT sl.id) as total_libraries'),
                'd.id as district_id',
                'd.district_name',
                'd.division_id'
            )
            ->groupBy('s.id', 's.school_name', 's.logo', 'd.id', 'd.district_name', 'd.division_id')
            ->orderBy('s.school_name')
            ->get();

        if ($schools->isEmpty()) {
            return $this->emptyResponse('division', $divisionId);
        }

        $schoolIds = $schools->pluck('school_id')->toArray();

        // Compute NEC once for both the division summary and displayed schools.
        $necBySchool = $this->calculateNecBySchool($schoolIds);
        $nec = (int) $necBySchool->sum();
        $divisionName = $this->getDivisionName($divisionId);

        // Real-time LR calculation (print + nonprint or filtered)
        $realTimeLr = $this->getRealTimeTotalLrBySchool($schoolIds, $printTypeId);

        $items = $schools->map(function ($school) use ($realTimeLr, $necBySchool, $divisionName) {
            $totalLr = (int) ($realTimeLr[$school->school_id] ?? 0);
            $schoolNec = (int) ($necBySchool[$school->school_id] ?? 0);
            $percentage = $this->calculatePercentage($totalLr, $schoolNec);
            $status = $this->determineDivisionSchoolStatus($schoolNec, $percentage);
            $color = $this->determineDivisionSchoolColor($schoolNec, $percentage);

            return [
                'id' => $school->school_id,
                'name' => $school->school_name,
                'shortname' => $school->school_name,
                'logo' => $school->logo
                    ? asset('storage/' . $school->logo)
                    : asset('assets/images/no_image.jpg'),
                'total_lr' => $totalLr,
                'net_expected_count' => $schoolNec,
                'percentage' => $percentage,
                'status' => $status,
                'color' => $color,
                'last_updated' => null,
                'libraries' => ['total' => (int) $school->total_libraries],
                'parent' => [
                    'id' => $school->division_id,
                    'name' => $divisionName,
                ],
                'district' => [
                    'id' => $school->district_id,
                    'name' => $school->district_name,
                ],
            ];
        });

        $totals = [
            'total_lr' => $items->sum('total_lr'),
            'total_items' => $items->count(),
            'total_libraries' => $items->sum('libraries.total'),
        ];

        $overallPercentage = $this->calculatePercentage($totals['total_lr'], $nec);

        $districtName = null;
        if (!empty($districtFilter)) {
            $districtName = DB::table('lrmis.districts')
                ->where('id', $districtFilter)
                ->value('district_name');
        }

        return [
            'level' => 'division',
            'items' => $items->toArray(),
            'station_id' => $divisionId,
            'station_name' => $divisionName,
            'district_id' => $districtFilter ?: null,
            'district_name' => $districtName,
            'summary' => [
                'total_items' => $totals['total_items'],
                'item_label' => 'Schools',
                'total_libraries' => $totals['total_libraries'],
                'total_lr' => $totals['total_lr'],
                'net_expected_count' => $nec,
                'overall_percentage' => $overallPercentage,
                'status' => $this->determineBosyStatus($overallPercentage),
                'color' => $this->determineBosyColor($overallPercentage),
            ],
            'period' => [
                'start' => '05 June',
                'end' => '25 Dec',
                'year' => '2026'
            ],
            'mv_refreshed_at' => now()->format('Y-m-d H:i:s'),
            'is_realtime' => true,
        ];
    }

    private function calculatePercentage($total, $nec): int
    {
        if ($nec > 0) {
            return min(100, round(($total / $nec) * 100));
        }

        return 0;
    }

    /**
     * Region "School LRs" completion treats every school as one equal unit.
     * Each eligible school's progress is capped at 100 before division and
     * Region averages are calculated, so surplus in one school cannot hide a
     * shortfall in another school.
     */
    private function getRegionSchoolLrProgressByDivision(
        array $divisionIds,
        ?string $printTypeId = null
    ): array
    {
        $emptySummary = $this->calculateAverageCompletionSummary([], [], []);

        if (empty($divisionIds)) {
            return ['divisions' => [], 'overall' => $emptySummary];
        }

        $schools = DB::table('lrmis.schools as s')
            ->join('lrmis.districts as d', 'd.id', '=', 's.district_id')
            ->whereIn('d.division_id', $divisionIds)
            ->select('s.id as school_id', 'd.division_id')
            ->get();

        if ($schools->isEmpty()) {
            return [
                'divisions' => array_fill_keys($divisionIds, $emptySummary),
                'overall' => $emptySummary,
            ];
        }

        $schoolIds = $schools->pluck('school_id')->all();
        $necBySchool = $this->calculateNecBySchool($schoolIds)->all();
        $actualLrBySchool = $this->getRealTimeTotalLrBySchool($schoolIds, $printTypeId);

        $progressByDivision = [];

        foreach ($schools->groupBy('division_id') as $divisionId => $divisionSchools) {
            $progressByDivision[$divisionId] = $this->calculateAverageCompletionSummary(
                $divisionSchools->pluck('school_id')->all(),
                $actualLrBySchool,
                $necBySchool
            );
        }

        foreach ($divisionIds as $divisionId) {
            $progressByDivision[$divisionId] ??= $emptySummary;
        }

        return [
            'divisions' => $progressByDivision,
            'overall' => $this->calculateAverageCompletionSummary(
                $schoolIds,
                $actualLrBySchool,
                $necBySchool
            ),
        ];
    }

    /**
     * Region "Division Library Hub" completion treats every hub as one equal
     * unit. NEC comes from the division user's input for each hub.
     */
    private function getRegionDivisionHubProgressByDivision(
        array $divisionIds,
        ?string $printTypeId = null
    ): array
    {
        $emptySummary = $this->calculateAverageCompletionSummary([], [], []);

        if (empty($divisionIds)) {
            return ['divisions' => [], 'overall' => $emptySummary];
        }

        $hubs = DB::table('lrmis.division_libraries as dl')
            ->whereIn('dl.division_id', $divisionIds)
            ->select('dl.id as library_id', 'dl.division_id', 'dl.net_expected_count')
            ->get();

        if ($hubs->isEmpty()) {
            return [
                'divisions' => array_fill_keys($divisionIds, $emptySummary),
                'overall' => $emptySummary,
            ];
        }

        $hubIds = $hubs->pluck('library_id')->all();
        $necByHub = $hubs->pluck('net_expected_count', 'library_id')
            ->map(fn ($nec): int => (int) $nec)
            ->all();
        $actualLrByHub = $this->getRealTimeTotalLrByDivisionLibrary($hubIds, $printTypeId);

        $progressByDivision = [];

        foreach ($hubs->groupBy('division_id') as $divisionId => $divisionHubs) {
            $progressByDivision[$divisionId] = $this->calculateAverageCompletionSummary(
                $divisionHubs->pluck('library_id')->all(),
                $actualLrByHub,
                $necByHub
            );
        }

        foreach ($divisionIds as $divisionId) {
            $progressByDivision[$divisionId] ??= $emptySummary;
        }

        return [
            'divisions' => $progressByDivision,
            'overall' => $this->calculateAverageCompletionSummary(
                $hubIds,
                $actualLrByHub,
                $necByHub
            ),
        ];
    }

    /**
     * Calculate the average of capped completion percentages for schools or
     * division library hubs. Items without a positive NEC are excluded.
     */
    private function calculateAverageCompletionSummary(
        array $itemIds,
        array $actualLrByItem,
        array $necByItem
    ): array
    {
        $totalLr = 0;
        $totalNec = 0;
        $completionSum = 0.0;
        $eligibleItems = 0;
        $completedItems = 0;

        foreach ($itemIds as $itemId) {
            $actualLr = max(0, (int) ($actualLrByItem[$itemId] ?? 0));
            $itemNec = max(0, (int) ($necByItem[$itemId] ?? 0));

            $totalLr += $actualLr;
            $totalNec += $itemNec;

            if ($itemNec === 0) {
                continue;
            }

            $eligibleItems++;

            if ($actualLr >= $itemNec) {
                $completionSum += 100;
                $completedItems++;
            } else {
                $completionSum += ($actualLr / $itemNec) * 100;
            }
        }

        return [
            'total_lr' => $totalLr,
            'net_expected_count' => $totalNec,
            'eligible_items' => $eligibleItems,
            'completed_items' => $completedItems,
            'percentage' => $this->finalizeAverageCompletion(
                $completionSum,
                $eligibleItems,
                $completedItems
            ),
        ];
    }

    private function finalizeAverageCompletion(
        float $completionSum,
        int $eligibleItems,
        int $completedItems
    ): float
    {
        if ($eligibleItems === 0) {
            return 0.0;
        }

        if ($completedItems === $eligibleItems) {
            return 100.0;
        }

        return min(99.99, round($completionSum / $eligibleItems, 2));
    }

    /**
     * Real-time total LR per school filtered by print_type_id.
     * Queries the schema directly (not the MV) for accurate per-type counts.
     */
    private function getRealTimeLrBySchool(array $schoolIds, string $printTypeId): array
    {
        if (empty($schoolIds)) return [];

        return DB::table('lrmis.school_libraries as sl')
            ->join('lrmis.print_acquisitions as pa', 'pa.library_id', '=', 'sl.id')
            ->join('lrmis.print_resources as pr', 'pr.id', '=', 'pa.print_id')
            ->where('pr.print_type_id', $printTypeId)
            ->whereIn('sl.school_id', $schoolIds)
            ->select('sl.school_id', DB::raw('COALESCE(SUM(pa.total_qty), 0)::integer as total_lr'))
            ->groupBy('sl.school_id')
            ->pluck('total_lr', 'school_id')
            ->all();
    }

    /**
     * Optimized real-time total LR per school (print + nonprint or print-only).
     * Fixed PostgreSQL strict GROUP BY + union alias issues.
     */
    private function getRealTimeTotalLrBySchool(array $schoolIds, ?string $printTypeId = null): array
    {
        if (empty($schoolIds)) {
            return [];
        }

        if ($printTypeId) {
            // Print-only with type filter
            return DB::table('lrmis.school_libraries as sl')
                ->join('lrmis.print_acquisitions as pa', 'pa.library_id', '=', 'sl.id')
                ->join('lrmis.print_resources as pr', 'pr.id', '=', 'pa.print_id')
                ->where('pr.print_type_id', $printTypeId)
                ->whereIn('sl.school_id', $schoolIds)
                ->select('sl.school_id', DB::raw('COALESCE(SUM(pa.total_qty), 0)::integer as total_lr'))
                ->groupBy('sl.school_id')
                ->pluck('total_lr', 'school_id')
                ->all();
        }

        // === FULL LR (Print + Nonprint) - Fixed version ===
        $combined = DB::table('lrmis.print_acquisitions as pa')
            ->join('lrmis.school_libraries as sl', 'sl.id', '=', 'pa.library_id')
            ->whereIn('sl.school_id', $schoolIds)
            ->select([
                'sl.school_id',
                DB::raw('COALESCE(SUM(pa.total_qty), 0) as qty')
            ])
            ->groupBy('sl.school_id')
            ->unionAll(
                DB::table('lrmis.nonprint_acquisitions as na')
                    ->join('lrmis.school_libraries as sl', 'sl.id', '=', 'na.library_id')
                    ->whereIn('sl.school_id', $schoolIds)
                    ->select([
                        'sl.school_id',
                        DB::raw('COALESCE(SUM(na.total_qty), 0) as qty')
                    ])
                    ->groupBy('sl.school_id')
            );

        return DB::query()
            ->fromSub($combined, 'combined')
            ->select('school_id', DB::raw('SUM(qty)::integer as total_lr'))
            ->groupBy('school_id')
            ->pluck('total_lr', 'school_id')
            ->all();
    }

    /**
     * Real-time total LR per division library hub (print + nonprint, or
     * print-only when a print type is selected).
     */
    private function getRealTimeTotalLrByDivisionLibrary(
        array $libraryIds,
        ?string $printTypeId = null
    ): array
    {
        if (empty($libraryIds)) {
            return [];
        }

        if ($printTypeId) {
            return DB::table('lrmis.division_libraries as dl')
                ->join('lrmis.print_acquisitions as pa', 'pa.library_id', '=', 'dl.id')
                ->join('lrmis.print_resources as pr', 'pr.id', '=', 'pa.print_id')
                ->where('pr.print_type_id', $printTypeId)
                ->whereIn('dl.id', $libraryIds)
                ->select('dl.id as library_id', DB::raw('COALESCE(SUM(pa.total_qty), 0)::integer as total_lr'))
                ->groupBy('dl.id')
                ->pluck('total_lr', 'library_id')
                ->all();
        }

        $combined = DB::table('lrmis.print_acquisitions as pa')
            ->join('lrmis.division_libraries as dl', 'dl.id', '=', 'pa.library_id')
            ->whereIn('dl.id', $libraryIds)
            ->select([
                'dl.id as library_id',
                DB::raw('COALESCE(SUM(pa.total_qty), 0) as qty'),
            ])
            ->groupBy('dl.id')
            ->unionAll(
                DB::table('lrmis.nonprint_acquisitions as na')
                    ->join('lrmis.division_libraries as dl', 'dl.id', '=', 'na.library_id')
                    ->whereIn('dl.id', $libraryIds)
                    ->select([
                        'dl.id as library_id',
                        DB::raw('COALESCE(SUM(na.total_qty), 0) as qty'),
                    ])
                    ->groupBy('dl.id')
            );

        return DB::query()
            ->fromSub($combined, 'combined')
            ->select('library_id', DB::raw('SUM(qty)::integer as total_lr'))
            ->groupBy('library_id')
            ->pluck('total_lr', 'library_id')
            ->all();
    }

    /**
     * Real-time total LR per division (print + nonprint, or print-only when filtered).
     * Respects hub_filter to scope to school-only or division-only libraries.
     * Used by getRegionData (no MV dependency).
     */
    private function getRealTimeTotalLrByDivision(array $divisionIds, string $hubFilter = '', ?string $printTypeId = null): array
    {
        if (empty($divisionIds)) return [];

        $result = array_fill_keys($divisionIds, 0);

        // ── School-hub component ──────────────────────────────────────────────
        // schools.district_id → districts.division_id (no direct schools.division_id column)
        if ($hubFilter !== 'division-hub') {
            if ($printTypeId) {
                // Print-only with type filter
                $rows = DB::table('lrmis.school_libraries as sl')
                    ->join('lrmis.schools as s', 's.id', '=', 'sl.school_id')
                    ->join('lrmis.districts as dt', 'dt.id', '=', 's.district_id')
                    ->join('lrmis.print_acquisitions as pa', 'pa.library_id', '=', 'sl.id')
                    ->join('lrmis.print_resources as pr', 'pr.id', '=', 'pa.print_id')
                    ->where('pr.print_type_id', $printTypeId)
                    ->whereIn('dt.division_id', $divisionIds)
                    ->select('dt.division_id', DB::raw('COALESCE(SUM(pa.total_qty), 0)::integer as total_lr'))
                    ->groupBy('dt.division_id')
                    ->pluck('total_lr', 'division_id');
            } else {
                // Full LR (print + nonprint) from school libraries
                $printRows = DB::table('lrmis.school_libraries as sl')
                    ->join('lrmis.schools as s', 's.id', '=', 'sl.school_id')
                    ->join('lrmis.districts as dt', 'dt.id', '=', 's.district_id')
                    ->join('lrmis.print_acquisitions as pa', 'pa.library_id', '=', 'sl.id')
                    ->whereIn('dt.division_id', $divisionIds)
                    ->select('dt.division_id', DB::raw('COALESCE(SUM(pa.total_qty), 0) as qty'))
                    ->groupBy('dt.division_id');

                $nonprintRows = DB::table('lrmis.school_libraries as sl')
                    ->join('lrmis.schools as s', 's.id', '=', 'sl.school_id')
                    ->join('lrmis.districts as dt', 'dt.id', '=', 's.district_id')
                    ->join('lrmis.nonprint_acquisitions as na', 'na.library_id', '=', 'sl.id')
                    ->whereIn('dt.division_id', $divisionIds)
                    ->select('dt.division_id', DB::raw('COALESCE(SUM(na.total_qty), 0) as qty'))
                    ->groupBy('dt.division_id');

                $rows = DB::query()
                    ->fromSub($printRows->unionAll($nonprintRows), 'combined')
                    ->select('division_id', DB::raw('SUM(qty)::integer as total_lr'))
                    ->groupBy('division_id')
                    ->pluck('total_lr', 'division_id');
            }

            foreach ($rows as $divId => $lr) {
                $result[$divId] = ($result[$divId] ?? 0) + (int) $lr;
            }
        }

        // ── Division-hub component ────────────────────────────────────────────
        if ($hubFilter !== 'school-hub') {
            if ($printTypeId) {
                $rows = DB::table('lrmis.division_libraries as dl')
                    ->join('lrmis.print_acquisitions as pa', 'pa.library_id', '=', 'dl.id')
                    ->join('lrmis.print_resources as pr', 'pr.id', '=', 'pa.print_id')
                    ->where('pr.print_type_id', $printTypeId)
                    ->whereIn('dl.division_id', $divisionIds)
                    ->select('dl.division_id', DB::raw('COALESCE(SUM(pa.total_qty), 0)::integer as total_lr'))
                    ->groupBy('dl.division_id')
                    ->pluck('total_lr', 'division_id');
            } else {
                $printRows = DB::table('lrmis.division_libraries as dl')
                    ->join('lrmis.print_acquisitions as pa', 'pa.library_id', '=', 'dl.id')
                    ->whereIn('dl.division_id', $divisionIds)
                    ->select('dl.division_id', DB::raw('COALESCE(SUM(pa.total_qty), 0) as qty'))
                    ->groupBy('dl.division_id');

                $nonprintRows = DB::table('lrmis.division_libraries as dl')
                    ->join('lrmis.nonprint_acquisitions as na', 'na.library_id', '=', 'dl.id')
                    ->whereIn('dl.division_id', $divisionIds)
                    ->select('dl.division_id', DB::raw('COALESCE(SUM(na.total_qty), 0) as qty'))
                    ->groupBy('dl.division_id');

                $rows = DB::query()
                    ->fromSub($printRows->unionAll($nonprintRows), 'combined')
                    ->select('division_id', DB::raw('SUM(qty)::integer as total_lr'))
                    ->groupBy('division_id')
                    ->pluck('total_lr', 'division_id');
            }

            foreach ($rows as $divId => $lr) {
                $result[$divId] = ($result[$divId] ?? 0) + (int) $lr;
            }
        }

        return $result;
    }

    /**
     * Real-time total LR per division filtered by print_type_id.
     * Respects hub_filter to scope to school-only or division-only libraries.
     */
    private function getRealTimeLrByDivision(array $divisionIds, string $printTypeId, string $hubFilter = ''): array
    {
        if (empty($divisionIds)) return [];

        $result = array_fill_keys($divisionIds, 0);

        // School libraries component
        if ($hubFilter !== 'division-hub') {
            $schoolLr = DB::table('lrmis.school_libraries as sl')
                ->join('lrmis.schools as s', 's.id', '=', 'sl.school_id')
                ->join('lrmis.districts as dt', 'dt.id', '=', 's.district_id')
                ->join('lrmis.print_acquisitions as pa', 'pa.library_id', '=', 'sl.id')
                ->join('lrmis.print_resources as pr', 'pr.id', '=', 'pa.print_id')
                ->where('pr.print_type_id', $printTypeId)
                ->whereIn('dt.division_id', $divisionIds)
                ->select('dt.division_id', DB::raw('COALESCE(SUM(pa.total_qty), 0)::integer as total_lr'))
                ->groupBy('dt.division_id')
                ->pluck('total_lr', 'division_id');

            foreach ($schoolLr as $divId => $lr) {
                $result[$divId] = ($result[$divId] ?? 0) + (int) $lr;
            }
        }

        // Division libraries component
        if ($hubFilter !== 'school-hub') {
            $divisionLr = DB::table('lrmis.division_libraries as dl')
                ->join('lrmis.print_acquisitions as pa', 'pa.library_id', '=', 'dl.id')
                ->join('lrmis.print_resources as pr', 'pr.id', '=', 'pa.print_id')
                ->where('pr.print_type_id', $printTypeId)
                ->whereIn('dl.division_id', $divisionIds)
                ->select('dl.division_id', DB::raw('COALESCE(SUM(pa.total_qty), 0)::integer as total_lr'))
                ->groupBy('dl.division_id')
                ->pluck('total_lr', 'division_id');

            foreach ($divisionLr as $divId => $lr) {
                $result[$divId] = ($result[$divId] ?? 0) + (int) $lr;
            }
        }

        return $result;
    }

    private function emptyResponse(string $level, string $stationId): array
    {
        $labelMap = [
            'region'   => 'Divisions',
            'division' => 'Schools',
            'district' => 'Schools',
            'school'   => 'Users',
        ];

        $summary = [
            'total_items'        => 0,
            'item_label'         => $labelMap[$level] ?? 'Items',
            'total_libraries'    => 0,
            'total_lr'           => 0,
            'overall_percentage' => 0,
            'status'             => 'Not Started',
            'color'              => 'bg-gray-400',
        ];

        if ($level !== 'school') {
            $summary['net_expected_count'] = 0;
        }

        return [
            'level'          => $level,
            'items'          => [],
            'station_id'     => $stationId,
            'summary'        => $summary,
            'period' => [
                'start' => '05 June',
                'end'   => '25 Dec',
                'year'  => '2026'
            ],
            'mv_refreshed_at' => now()->format('Y-m-d H:i:s')
        ];
    }

    private function getRegionName(string $regionId): string
    {
        return DB::table('lrmis.regions')
            ->where('id', $regionId)
            ->value('region_name') ?? 'Region';
    }

    private function getDivisionName(string $divisionId): string
    {
        return DB::table('lrmis.divisions')
            ->where('id', $divisionId)
            ->value('division_name') ?? 'Division';
    }

    private function determineBosyStatus(int|float $percentage): string
    {
        return match (true) {
            $percentage <= 0 => 'Not Started',
            $percentage < 25 => 'Partial',
            $percentage < 50 => 'In-progress',
            $percentage < 75 => 'Advanced',
            $percentage < 100 => 'In-review',
            default => 'Complete',
        };
    }

    private function determineDivisionSchoolStatus(int $nec, int $percentage): string
    {
        return $nec > 0 ? $this->determineBosyStatus($percentage) : 'No Population';
    }

    private function determineDivisionSchoolColor(int $nec, int $percentage): string
    {
        return $nec > 0 ? $this->determineBosyColor($percentage) : 'bg-gray-400';
    }

    private function determineBosyColor(int|float $percentage): string
    {
        return match (true) {
            $percentage <= 0 => 'bg-gray-400',
            $percentage < 25 => 'bg-red-600',
            $percentage < 50 => 'bg-green-600',
            $percentage < 75 => 'bg-purple-500',
            $percentage < 100 => 'bg-yellow-500',
            default => 'bg-emerald-500',
        };
    }

    private function determineUserLevel($user): int
    {
        $stationId = $user->station_id ?? null;
        if (!$stationId)
            return 0;

        return cache()->remember("user_org_level_{$user->id}", 86400, function () use ($stationId) {
            if (\App\Models\School::where('id', $stationId)->exists())
                return 1;
            if (\App\Models\District::where('id', $stationId)->exists())
                return 2;
            if (\App\Models\Division::where('id', $stationId)->exists())
                return 3;
            if (\App\Models\Region::where('id', $stationId)->exists())
                return 4;
            return 0;
        });
    }

    private function determineStationId($user, int $level): ?string
    {
        return $user->station_id ?: null;
    }

    /**
     * NEC = latest population x offered grade count x available subject-area count.
     */
    private function calculateNec(array $schoolIds): int
    {
        return (int) $this->calculateNecBySchool($schoolIds)->sum();
    }

    /**
     * Calculate the effective NEC per school. A positive SDO-validated value in
     * school_libraries replaces the projected formula; zero keeps the formula.
     */
    private function calculateNecBySchool(array $schoolIds)
    {
        $gradeOfferings = $this->getNecGradeOfferingsBySchool($schoolIds);
        $gradeOfferingCounts = $gradeOfferings->map(fn (array $grades): int => count($grades));
        $subjectAreaCounts = $this->getSubjectAreaCountsBySchool($gradeOfferings);

        $populationTotals = $this->getPopulationTotalsBySchool($schoolIds);
        $computedNec = collect($schoolIds)
            ->filter()
            ->unique()
            ->mapWithKeys(fn (string $schoolId): array => [
                $schoolId => (int) ($populationTotals[$schoolId] ?? 0)
                    * (int) ($gradeOfferingCounts[$schoolId] ?? 0)
                    * (int) ($subjectAreaCounts[$schoolId] ?? 0),
            ]);

        $validatedNec = $this->getValidatedNecBySchool($schoolIds);

        return $computedNec->map(
            fn (int $computed, string $schoolId): int => (int) ($validatedNec[$schoolId] ?? $computed)
        );
    }

    /**
     * Return the canonical positive school-library NEC overrides keyed by school.
     */
    private function getValidatedNecBySchool(array $schoolIds): Collection
    {
        $schoolIds = array_values(array_filter(array_unique($schoolIds)));

        if (empty($schoolIds)) {
            return collect();
        }

        return DB::table('school_libraries')
            ->whereIn('school_id', $schoolIds)
            ->orderBy('id')
            ->get(['school_id', 'estimated_resource'])
            ->unique('school_id')
            ->filter(fn ($library): bool => (int) $library->estimated_resource > 0)
            ->mapWithKeys(fn ($library): array => [
                (string) $library->school_id => (int) $library->estimated_resource,
            ]);
    }

    /**
     * Count distinct NEC-eligible grade levels offered by each school.
     */
    private function getGradeOfferingCountsBySchool(array $schoolIds)
    {
        return $this->getNecGradeOfferingsBySchool($schoolIds)
            ->map(fn (array $grades): int => count($grades));
    }

    /**
     * Get the formal grade-level names offered by each school. Non-Graded is
     * intentionally excluded from the NEC grade-offering factor.
     */
    private function getNecGradeOfferingsBySchool(array $schoolIds): Collection
    {
        $schoolIds = array_values(array_filter(array_unique($schoolIds)));

        if (empty($schoolIds)) {
            return collect();
        }

        $columns = GradeOfferingMap::necEligible();
        $offerings = DB::table('grade_offerings')
            ->whereIn('school_id', $schoolIds)
            ->select(array_merge(['school_id'], $columns))
            ->get()
            ->groupBy('school_id');

        return collect($schoolIds)->mapWithKeys(function (string $schoolId) use ($columns, $offerings): array {
            $schoolOfferings = $offerings->get($schoolId, collect());

            $grades = collect($columns)
                ->filter(fn (string $column): bool => $schoolOfferings->contains(
                    fn ($offering) => strtolower((string) ($offering->{$column} ?? 'no')) === 'yes'
                ))
                ->map(fn (string $column) => GradeOfferingMap::gradeLevel($column))
                ->filter()
                ->values()
                ->all();

            return [$schoolId => $grades];
        });
    }

    /**
     * Count distinct subject areas available across each school's offered
     * grades using the curated subject_grade_levels matrix.
     */
    private function getSubjectAreaCountsBySchool(Collection $gradeOfferings): Collection
    {
        $offeredGrades = $gradeOfferings->flatten()->unique()->values();

        if ($offeredGrades->isEmpty()) {
            return $gradeOfferings->map(fn (): int => 0);
        }

        $subjectsByGrade = DB::table('grade_levels as grade_level')
            ->join('subject_grade_levels as subject_grade_level', 'subject_grade_level.grade_level_id', '=', 'grade_level.id')
            ->whereIn('grade_level.grade', $offeredGrades->all())
            ->select(['grade_level.grade', 'subject_grade_level.subject_id'])
            ->distinct()
            ->get()
            ->groupBy('grade')
            ->map(fn (Collection $rows) => $rows->pluck('subject_id')->unique()->values());

        return $gradeOfferings->map(function (array $grades) use ($subjectsByGrade): int {
            return collect($grades)
                ->flatMap(fn (string $grade) => $subjectsByGrade->get($grade, collect()))
                ->unique()
                ->count();
        });
    }

    /**
     * Calculate automated school NEC totals grouped by division.
     */
    private function calculateSchoolNecByDivision(array $divisionIds)
    {
        if (empty($divisionIds)) {
            return collect();
        }

        $schools = DB::table('lrmis.schools as s')
            ->join('lrmis.districts as d', 'd.id', '=', 's.district_id')
            ->whereIn('d.division_id', $divisionIds)
            ->select('s.id as school_id', 'd.division_id')
            ->get();

        if ($schools->isEmpty()) {
            return collect();
        }

        $necBySchool = $this->calculateNecBySchool($schools->pluck('school_id')->all());
        $necByDivision = collect();

        foreach ($schools as $school) {
            $current = (int) ($necByDivision[$school->division_id] ?? 0);
            $necByDivision[$school->division_id] = $current + (int) ($necBySchool[$school->school_id] ?? 0);
        }

        return $necByDivision;
    }

    /**
     * Sum all grade-level population totals per school for the latest school year.
     *
     * The populations table stores a pre-computed total column for each grade level
     * (e.g. k_total, g1_total … g12_total, ng_total). We sum those columns so that
     * each school's population figure is the grand total of all enrolled learners
     * across every grade level in the most-recent school year on record.
     */
    private function getPopulationTotalsBySchool(array $schoolIds)
    {
        $schoolIds = array_values(array_filter(array_unique($schoolIds)));

        if (empty($schoolIds)) {
            return collect();
        }

        $latestPopulations = DB::table('populations as p')
            ->join('school_years as sy', 'sy.id', '=', 'p.sy_id')
            ->whereIn('p.school_id', $schoolIds)
            ->select('p.*')
            ->selectRaw(
                'ROW_NUMBER() OVER (PARTITION BY p.school_id ORDER BY sy.year_end DESC, sy.year_start DESC) as population_rank'
            );

        $populationColumns = array_merge(array_values(GradeColumnMap::all()), ['ng_total']);
        $sumExpr = implode(' + ', array_map(
            fn (string $col) => "COALESCE(latest_population.{$col}, 0)",
            $populationColumns
        ));

        $rows = DB::query()
            ->fromSub($latestPopulations, 'latest_population')
            ->where('population_rank', 1)
            ->selectRaw("latest_population.school_id, ({$sumExpr}) AS population_total")
            ->get();

        return $rows->mapWithKeys(fn ($row) => [
            $row->school_id => (int) $row->population_total,
        ]);
    }

}
