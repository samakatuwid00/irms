<?php

namespace App\Http\Controllers\User;
use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Division;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ManageUserController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct( private UserManagementService $userManagementService)
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $authUser = Auth::user();
        $level    = $authUser->userType?->level;
        $filters  = $this->extractFilters($request);
        $users    = $this->userManagementService->getHierarchicalUsers($authUser, $filters);

        if ($request->header('HX-Request')) {
            $activeTab = $request->input('active_tab', 'main');

            [$tableUsers, $emptyMessage] = match($activeTab) {
                'sub', 'division' => [$users['subUsers'],    'No users found.'],
                'subsub' => [$users['subSubUsers'], 'No users found.'],
                default  => [$users['mainUsers'],   'No users found.'],
            };

            return view('pages.partials.users-table', [
                'users'            => $tableUsers,
                'emptyMessage'     => $emptyMessage,
                'activeTab'        => $activeTab,
                'allowStationEdit' => in_array($activeTab, ['sub', 'division']) && $level === 3, // ← ADD
            ]);
        }

        return view('pages.users', [
            'user'        => $authUser,
            'mainUsers'   => $users['mainUsers'],
            'subUsers'    => $users['subUsers'],
            'subSubUsers' => $users['subSubUsers'],
            'authLevel'   => $level, // ← ADD so blade components can use it
        ]);
    }

    /**
     * Returns districts scoped to a division — for the inline station edit dropdown.
     * Division-level users (level 3) can only query their own division.
     */
    public function districtsByDivision(Division $division)
    {
        $authUser  = Auth::user();
        $authLevel = $authUser->userType?->level;

        // Level 3: enforce they can only fetch their own division's districts
        if ($authLevel === 3 && $authUser->station_id !== $division->id) {
            abort(403, 'Unauthorized.');
        }

        $districts = District::select('id', 'district_name', 'shortname')
            ->where('division_id', $division->id)
            ->orderBy('district_name')
            ->get();

        return response()->json($districts);
    }

    public function updateStatus(Request $request, User $user)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,pending,deactivated'
        ]);

        $this->userManagementService->updateUserStatus($user, $validated['status']);

        if ($request->header('HX-Request')) {
            return view('pages.partials.user-row', ['user' => $user->fresh()]);
        }

        return redirect()->back()->with('success', "User status updated to {$validated['status']}.");
    }

    private function extractFilters(Request $request): array
    {
        return [
            'main' => [
                'search'   => $request->input('search_main'),
                'usertype' => $request->input('usertype_main'),
                'status'   => $request->input('status_main'),
            ],
            'sub' => [
                'search'   => $request->input('search_sub'),
                'usertype' => $request->input('usertype_sub'),
                'status'   => $request->input('status_sub'),
            ],
            'subsub' => [
                'search'   => $request->input('search_subsub'),
                'usertype' => $request->input('usertype_subsub'),
                'status'   => $request->input('status_subsub'),
            ],
        ];
    }

    public function changePassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        try {
            $user->update([
                'password' => Hash::make($request->password)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update password. Please try again.'
            ], 500);
        }
    }

    public function updateStation(Request $request, User $user)
    {
        $validated = $request->validate([
            // station_id is the actual FK on the users table (see User.$fillable and districtStation relationship)
            'station_id' => ['nullable', 'string', 'exists:districts,id'],
        ]);

        $user->update([
            'station_id' => $validated['station_id'] ?? null,
        ]);

        // Unset cached relation so districtStation re-queries with the new station_id
        $user->unsetRelation('districtStation');
        $stationName = $user->districtStation?->district_name ?? null;

        return response()->json([
            'success'      => true,
            'station_name' => $stationName,
        ]);
    }
}