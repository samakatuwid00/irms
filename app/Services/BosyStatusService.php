<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class BosyStatusService
{
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

        try {
            return match ($userLevel) {
                4 => $this->getRegionData($stationId, $hubFilter, $printTypeId),
                3 => $this->getDivisionData($stationId, $districtFilter, $printTypeId),
                2 => $this->getDistrictData($stationId, $printTypeId),
                1 => $this->getSchoolData($stationId, $printTypeId),
            };
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
            ->leftJoin('lrmis.school_libraries as sl', 'sl.school_id', '=', 's.id')
            ->where('s.id', $schoolId)
            ->select(
                's.id as school_id',
                's.school_name',
                's.shortname',
                's.logo',
                DB::raw('COALESCE(SUM(sl.estimated_resource), 0) as estimated_print'),
                DB::raw('COALESCE(SUM(sl.estimated_resource), 0) as total_estimated_resource')
            )
            ->groupBy('s.id', 's.school_name', 's.shortname', 's.logo')
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

        // 4. Build items — one row per user
        $items = $users->map(function ($user) use ($printPerUser, $nonprintPerUser, $school) {
            $userPrint    = (int) ($printPerUser[$user->user_id]    ?? 0);
            $userNonprint = (int) ($nonprintPerUser[$user->user_id] ?? 0);
            $userTotal    = $userPrint + $userNonprint;

            $estimated    = (int) $school->total_estimated_resource;
            $percentage   = $this->calculatePercentage($userTotal, $estimated);

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
                'estimated_resource' => $estimated,
                'percentage'         => $percentage,
                'status'             => $this->determineBosyStatus($percentage),
                'color'              => $this->determineBosyColor($percentage),
                'last_updated'       => null,
                'libraries'          => ['total' => 1],
            ];
        });

        // 5. Summary — total across all users vs school estimated
        $totalActualLr      = $items->sum('total_lr');
        $totalEstimated     = (int) $school->total_estimated_resource;
        $overallPercentage  = $this->calculatePercentage($totalActualLr, $totalEstimated);

        return [
            'level'          => 'school',
            'items'          => $items->toArray(),
            'station_id'     => $schoolId,
            'station_name'   => $school->school_name,
            'school_info'    => [
                'name'               => $school->school_name,
                'shortname'          => $school->shortname ?? $school->school_name,
                'logo'               => $school->logo ?? null,
                'estimated_print'    => (int) $school->estimated_print,
                'estimated_nonprint' => (int) $school->estimated_nonprint,
                'total_estimated'    => $totalEstimated,
            ],
            'summary' => [
                'total_items'        => $items->count(),
                'item_label'         => 'Users',
                'total_libraries'    => 1,
                'total_lr'           => $totalActualLr,
                'total_estimated'    => $totalEstimated,
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
        $schools = DB::table('lrmis.mv_bosy_district_schools_status')
            ->where('district_id', $districtId)
            ->orderBy('school_name')
            ->get();

        // When print type is selected, overlay real-time total_lr from schema
        $realTimeLr = [];
        if ($printTypeId && $schools->isNotEmpty()) {
            $schoolIds = $schools->pluck('school_id')->toArray();
            $realTimeLr = $this->getRealTimeLrBySchool($schoolIds, $printTypeId);
        }

        if ($schools->isEmpty()) {
            return $this->emptyResponse('district', $districtId);
        }

        $items = $schools->map(function ($school) use ($printTypeId, $realTimeLr) {
            $totalLr = $printTypeId
                ? (int) ($realTimeLr[$school->school_id] ?? 0)
                : (int) $school->total_actual_lr;
            $estimated = (int) $school->total_estimated_resource;
            $percentage = $printTypeId
                ? $this->calculatePercentage($totalLr, $estimated)
                : (int) $school->percentage;

            return [
                'id' => $school->school_id,
                'name' => $school->school_name,
                'shortname' => $school->school_name,
                'logo' => $school->logo
                    ? asset('storage/' . $school->logo)
                    : asset('assets/images/no_image.jpg'),
                'total_lr' => $totalLr,
                'estimated_resource' => $estimated,
                'estimated_print' => (int) ($school->estimated_print ?? 0),
                'estimated_nonprint' => (int) ($school->estimated_nonprint ?? 0),
                'percentage' => $percentage,
                'status' => $printTypeId ? $this->determineBosyStatus($percentage) : $school->status,
                'color' => $printTypeId ? $this->determineBosyColor($percentage) : $school->color,
                'last_updated' => $school->last_updated_formatted,
                'libraries' => [
                    'total' => (int) $school->total_libraries,
                ],
                'parent' => [
                    'id' => $school->division_id,
                    'name' => $school->division_name,
                ],
                'district' => [
                    'id' => $school->district_id,
                    'name' => $school->district_name,
                ],
            ];
        });

        $totals = [
            'total_lr' => $items->sum('total_lr'),
            'total_estimated' => $items->sum('estimated_resource'),
            'total_items' => $items->count(),
            'total_libraries' => $items->sum('libraries.total'),
        ];

        $overallPercentage = $this->calculatePercentage($totals['total_lr'], $totals['total_estimated']);

        $districtName = DB::table('lrmis.districts')
            ->where('id', $districtId)
            ->value('district_name') ?? 'District';

        return [
            'level' => 'district',
            'items' => $items->toArray(),
            'station_id' => $districtId,
            'station_name' => $districtName,
            'district_name' => $districtName,
            'summary' => [
                'total_items' => $totals['total_items'],
                'item_label' => 'Schools',
                'total_libraries' => $totals['total_libraries'],
                'total_lr' => $totals['total_lr'],
                'total_estimated' => $totals['total_estimated'],
                'overall_percentage' => $overallPercentage,
                'status' => $this->determineBosyStatus($overallPercentage),
                'color' => $this->determineBosyColor($overallPercentage),
            ],
            'period' => [
                'start' => '05 June',
                'end' => '25 Dec',
                'year' => '2026'
            ],
            'mv_refreshed_at' => $schools->first()->mv_refreshed_at ?? now()->format('Y-m-d H:i:s')
        ];
    }

    private function getRegionData(string $regionId, string $hubFilter = '', ?string $printTypeId = null): array
    {
        $divisions = DB::table('lrmis.mv_bosy_division_status')
            ->where('region_id', $regionId)
            ->orderBy('division_name')
            ->get();

        // When print type is selected, overlay real-time total_lr from schema
        $realTimeLr = [];
        if ($printTypeId && $divisions->isNotEmpty()) {
            $divisionIds = $divisions->pluck('division_id')->toArray();
            $realTimeLr = $this->getRealTimeLrByDivision($divisionIds, $printTypeId, $hubFilter);
        }

        if ($divisions->isEmpty()) {
            return $this->emptyResponse('region', $regionId);
        }

        $items = $divisions->map(function ($division) use ($hubFilter, $printTypeId, $realTimeLr) {
            // Choose estimated resource based on hub filter (always from MV)
            switch ($hubFilter) {
                case 'division-hub':
                    $mvActualLr = (int) $division->division_actual_lr;
                    $estimatedResource = (int) $division->division_estimated_resource;
                    break;
                case 'school-hub':
                    $mvActualLr = (int) $division->school_actual_lr;
                    $estimatedResource = (int) $division->school_estimated_resource;
                    break;
                default:
                    $mvActualLr = (int) $division->total_actual_lr;
                    $estimatedResource = (int) $division->total_estimated_resource;
                    break;
            }

            // Use real-time LR when print type is selected, otherwise MV data
            $actualLr = $printTypeId
                ? (int) ($realTimeLr[$division->division_id] ?? 0)
                : $mvActualLr;

            $percentage = $this->calculatePercentage($actualLr, $estimatedResource);

            return [
                'id' => $division->division_id,
                'name' => $division->division_name,
                'shortname' => $division->division_name,
                'logo' => $division->logo
                    ? asset('storage/' . $division->logo)
                    : asset('assets/images/no_image.jpg'),
                'total_lr' => $actualLr,
                'estimated_resource' => $estimatedResource,
                'percentage' => $percentage,
                'status' => $this->determineBosyStatus($percentage),
                'color' => $this->determineBosyColor($percentage),
                'last_updated' => $division->last_updated_formatted,
                'libraries' => [
                    'total' => (int) $division->total_libraries,
                    'schools' => (int) $division->school_libraries,
                    'divisions' => (int) $division->division_libraries,
                ]
            ];
        });

        $totals = [
            'total_lr' => $items->sum('total_lr'),
            'total_estimated' => $items->sum('estimated_resource'),
            'total_items' => $items->count(),
            'total_libraries' => $items->sum('libraries.total'),
            'school_libraries' => $items->sum('libraries.schools'),
            'division_libraries' => $items->sum('libraries.divisions'),
        ];

        $overallPercentage = $this->calculatePercentage($totals['total_lr'], $totals['total_estimated']);

        return [
            'level' => 'region',
            'hub_filter' => $hubFilter, // pass back so frontend knows
            'items' => $items->toArray(),
            'station_id' => $regionId,
            'station_name' => $this->getRegionName($regionId),
            'summary' => [
                'total_items' => $totals['total_items'],
                'item_label' => 'Divisions',
                'total_libraries' => $totals['total_libraries'],
                'school_libraries' => $totals['school_libraries'],
                'division_libraries' => $totals['division_libraries'],
                'total_lr' => $totals['total_lr'],
                'total_estimated' => $totals['total_estimated'],
                'overall_percentage' => $overallPercentage,
                'status' => $this->determineBosyStatus($overallPercentage),
                'color' => $this->determineBosyColor($overallPercentage),
            ],
            'period' => [
                'start' => '05 June',
                'end' => '25 Dec',
                'year' => '2026'
            ],
            'mv_refreshed_at' => $divisions->first()->mv_refreshed_at ?? now()->format('Y-m-d H:i:s')
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
                DB::raw('COALESCE(SUM(sl.estimated_resource), 0) as estimated_print'),
                DB::raw('COALESCE(SUM(sl.estimated_resource), 0) as total_estimated_resource'),
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

        // Real-time LR calculation (print + nonprint or filtered)
        $realTimeLr = $this->getRealTimeTotalLrBySchool($schoolIds, $printTypeId);

        $items = $schools->map(function ($school) use ($realTimeLr) {
            $totalLr = (int) ($realTimeLr[$school->school_id] ?? 0);
            $estimated = (int) $school->total_estimated_resource;
            $percentage = $this->calculatePercentage($totalLr, $estimated);

            return [
                'id' => $school->school_id,
                'name' => $school->school_name,
                'shortname' => $school->school_name,
                'logo' => $school->logo
                    ? asset('storage/' . $school->logo)
                    : asset('assets/images/no_image.jpg'),
                'total_lr' => $totalLr,
                'estimated_resource' => $estimated,
                'estimated_print' => (int) ($school->estimated_print ?? 0),
                'estimated_nonprint' => (int) ($school->estimated_nonprint ?? 0),
                'percentage' => $percentage,
                'status' => $this->determineBosyStatus($percentage),
                'color' => $this->determineBosyColor($percentage),
                'last_updated' => null,
                'libraries' => ['total' => (int) $school->total_libraries],
                'parent' => [
                    'id' => $school->division_id,
                    'name' => $this->getDivisionName($school->division_id),
                ],
                'district' => [
                    'id' => $school->district_id,
                    'name' => $school->district_name,
                ],
            ];
        });

        $totals = [
            'total_lr' => $items->sum('total_lr'),
            'total_estimated' => $items->sum('estimated_resource'),
            'total_items' => $items->count(),
            'total_libraries' => $items->sum('libraries.total'),
        ];

        $overallPercentage = $this->calculatePercentage($totals['total_lr'], $totals['total_estimated']);

        $districtName = null;
        if (!empty($districtFilter)) {
            $districtName = DB::table('lrmis.districts')
                ->where('id', $districtFilter)
                ->value('district_name');
        }

        return [
            'level' => 'division',
            'can_edit_pre_inventory' => true,
            'items' => $items->toArray(),
            'station_id' => $divisionId,
            'station_name' => $this->getDivisionName($divisionId),
            'district_id' => $districtFilter ?: null,
            'district_name' => $districtName,
            'summary' => [
                'total_items' => $totals['total_items'],
                'item_label' => 'Schools',
                'total_libraries' => $totals['total_libraries'],
                'total_lr' => $totals['total_lr'],
                'total_estimated' => $totals['total_estimated'],
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

    private function calculatePercentage($total, $estimated): int
    {
        if ($estimated > 0) {
            return min(100, round(($total / $estimated) * 100));
        }
        return $total > 0 ? 100 : 0;
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

        return [
            'level'          => $level,
            'items'          => [],
            'station_id'     => $stationId,
            'summary'        => [
                'total_items'        => 0,
                'item_label'         => $labelMap[$level] ?? 'Items',
                'total_libraries'    => 0,
                'total_lr'           => 0,
                'total_estimated'    => 0,
                'overall_percentage' => 0,
                'status'             => 'Not Started',
                'color'              => 'bg-gray-400',
            ],
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

    private function determineBosyStatus(int $percentage): string
    {
        return match (true) {
            $percentage === 0 => 'Not Started',
            $percentage < 25 => 'Partial',
            $percentage < 50 => 'In-progress',
            $percentage < 75 => 'Advanced',
            $percentage < 100 => 'In-review',
            default => 'Complete',
        };
    }

    private function determineBosyColor(int $percentage): string
    {
        return match (true) {
            $percentage === 0 => 'bg-gray-400',
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
}
