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

        try {
            return match ($userLevel) {
                4 => $this->getRegionData($stationId, $hubFilter),
                3 => $this->getDivisionData($stationId, $districtFilter),
                2 => $this->getDistrictData($stationId),
                1 => $this->getSchoolData($stationId),
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

    private function getSchoolData(string $schoolId): array
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
                DB::raw('COALESCE(SUM(sl.estimated_resource_np), 0) as estimated_nonprint'),
                DB::raw('COALESCE(SUM(sl.estimated_resource), 0) + COALESCE(SUM(sl.estimated_resource_np), 0) as total_estimated_resource')
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
        $printPerUser = DB::table('lrmis.print_acquisitions as pa')
            ->join('lrmis.print_resources as pr', 'pr.id', '=', 'pa.print_id')
            ->whereIn('pa.encoded_by', $userIds)
            ->where('pa.library_id', function ($q) use ($schoolId) {
                $q->select('id')
                    ->from('lrmis.school_libraries')
                    ->where('school_id', $schoolId);
            })
            ->select(
                'pa.encoded_by',
                DB::raw('COALESCE(SUM(pa.usable + pa.partially_damaged + pa.damaged + pa.lost + pa.condemnable), 0) as total_print')
            )
            ->groupBy('pa.encoded_by')
            ->pluck('total_print', 'encoded_by');

        // Nonprint LR per user
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

    private function getDistrictData(string $districtId): array
    {
        $schools = DB::table('lrmis.mv_bosy_district_schools_status')
            ->where('district_id', $districtId)
            ->orderBy('school_name')
            ->get();

        if ($schools->isEmpty()) {
            return $this->emptyResponse('district', $districtId);
        }

        $items = $schools->map(fn($school) => [
            'id' => $school->school_id,
            'name' => $school->school_name,
            'shortname' => $school->school_name,
            'logo' => $school->logo
                ? asset('storage/' . $school->logo)
                : asset('assets/images/no_image.jpg'),
            'total_lr' => (int) $school->total_actual_lr,
            'estimated_resource' => (int) $school->total_estimated_resource,
            'percentage' => (int) $school->percentage,
            'status' => $school->status,
            'color' => $school->color,
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
        ]);

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

    private function getRegionData(string $regionId, string $hubFilter = ''): array
    {
        $divisions = DB::table('lrmis.mv_bosy_division_status')
            ->where('region_id', $regionId)
            ->orderBy('division_name')
            ->get();

        if ($divisions->isEmpty()) {
            return $this->emptyResponse('region', $regionId);
        }

        $items = $divisions->map(function ($division) use ($hubFilter) {
            // Choose columns based on hub filter
            switch ($hubFilter) {
                case 'division-hub':
                    $actualLr = (int) $division->division_actual_lr;
                    $estimatedResource = (int) $division->division_estimated_resource;
                    break;
                case 'school-hub':
                    $actualLr = (int) $division->school_actual_lr;
                    $estimatedResource = (int) $division->school_estimated_resource;
                    break;
                default: // '' = All Library Hubs
                    $actualLr = (int) $division->total_actual_lr;
                    $estimatedResource = (int) $division->total_estimated_resource;
                    break;
            }

            $percentage = $this->calculatePercentage($actualLr, $estimatedResource);

            return [
                'id' => $division->division_id,
                'name' => $division->division_name,  // always the same
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

    private function getDivisionData(string $divisionId, string $districtFilter = ''): array
    {
        $query = DB::table('lrmis.mv_bosy_division_schools_status')
            ->where('division_id', $divisionId);

        // If a specific district is selected, filter by it
        if (!empty($districtFilter)) {
            $query->where('district_id', $districtFilter);
        }

        $schools = $query->orderBy('school_name')->get();

        if ($schools->isEmpty()) {
            return $this->emptyResponse('division', $divisionId);
        }

        $items = $schools->map(fn($school) => [
            'id' => $school->school_id,
            'name' => $school->school_name,
            'shortname' => $school->school_name,
            'logo' => $school->logo
                ? asset('storage/' . $school->logo)
                : asset('assets/images/no_image.jpg'),
            'total_lr' => (int) $school->total_actual_lr,
            'estimated_resource' => (int) $school->total_estimated_resource,
            'percentage' => (int) $school->percentage,
            'status' => $school->status,
            'color' => $school->color,
            'last_updated' => $school->last_updated_formatted,
            'libraries' => [
                'total' => (int) $school->total_libraries,
            ],
            'parent' => [
                'id' => $school->division_id,
                'name' => $school->division_name,
            ],
            // expose district info per item (useful for display)
            'district' => [
                'id' => $school->district_id,
                'name' => $school->district_name,
            ],
        ]);

        $totals = [
            'total_lr' => $items->sum('total_lr'),
            'total_estimated' => $items->sum('estimated_resource'),
            'total_items' => $items->count(),
            'total_libraries' => $items->sum('libraries.total'),
        ];

        $overallPercentage = $this->calculatePercentage($totals['total_lr'], $totals['total_estimated']);

        // Resolve district name for the response (used by frontend title)
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
            'station_name' => $this->getDivisionName($divisionId),
            'district_id' => $districtFilter ?: null,
            'district_name' => $districtName,           // sent to frontend for title
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

    private function calculatePercentage($total, $estimated): int
    {
        if ($estimated > 0) {
            return min(100, round(($total / $estimated) * 100));
        }
        return $total > 0 ? 100 : 0;
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
