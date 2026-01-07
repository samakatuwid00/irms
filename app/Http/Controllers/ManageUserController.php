<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\{
    User
};

class ManageUserController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $authUser = Auth::user();
        $level = $authUser->userType?->level;

        $mainUsersQuery = User::query()->with('userType');
        $subUsersQuery = User::query()->with('userType');
        $subSubUsersQuery = User::query()->with('userType');

        // ================= HIERARCHICAL USER QUERIES =================
        switch ($level) {
            case 4: // Region
                $mainUsersQuery->where('station_id', $authUser->station_id)
                    ->whereHas('userType', fn($q) => $q->where('level', 4));

                $subUsersQuery->whereHas('userType', fn($q) => $q->where('level', 3))
                    ->join('divisions', 'users.station_id', '=', 'divisions.id')
                    ->where('divisions.region_id', $authUser->station_id)
                    ->select('users.*');

                $subSubUsersQuery->whereHas('userType', fn($q) => $q->where('level', 2))
                    ->join('districts', 'users.station_id', '=', 'districts.id')
                    ->whereIn('districts.division_id', function($query) use ($authUser) {
                        $query->select('id')->from('divisions')->where('region_id', $authUser->station_id);
                    })
                    ->select('users.*');
                break;

            case 3: // Division
                $mainUsersQuery->where('station_id', $authUser->station_id)
                    ->whereHas('userType', fn($q) => $q->where('level', 3));

                $subUsersQuery->whereHas('userType', fn($q) => $q->where('level', 2))
                    ->join('districts', 'users.station_id', '=', 'districts.id')
                    ->where('districts.division_id', $authUser->station_id)
                    ->select('users.*');

                $subSubUsersQuery->whereHas('userType', fn($q) => $q->where('level', 1))
                    ->join('schools', 'users.station_id', '=', 'schools.id')
                    ->whereIn('schools.district_id', function($query) use ($authUser) {
                        $query->select('id')->from('districts')->where('division_id', $authUser->station_id);
                    })
                    ->select('users.*');
                break;

            case 2: // District
                $mainUsersQuery->where('station_id', $authUser->station_id)
                    ->whereHas('userType', fn($q) => $q->where('level', 2));

                $subUsersQuery->whereHas('userType', fn($q) => $q->where('level', 1))
                    ->join('schools', 'users.station_id', '=', 'schools.id')
                    ->where('schools.district_id', $authUser->station_id)
                    ->select('users.*');

                $subSubUsersQuery = collect();
                break;

            case 1: // School
                $mainUsersQuery->where('station_id', $authUser->station_id)
                    ->whereHas('userType', fn($q) => $q->where('level', 1));

                $subUsersQuery = collect();
                $subSubUsersQuery = collect();
                break;
        }

        // ================= FILTERS =================
        $filters = [
            'mainUsersQuery' => ['search' => 'search_main', 'usertype' => 'usertype_main', 'status' => 'status_main'],
            'subUsersQuery'  => ['search' => 'search_sub',  'usertype' => 'usertype_sub',  'status' => 'status_sub'],
            'subSubUsersQuery' => ['search' => 'search_subsub', 'usertype' => 'usertype_subsub', 'status' => 'status_subsub'],
        ];

        $pageNames = [
            'mainUsersQuery' => 'main_page',
            'subUsersQuery' => 'sub_page',
            'subSubUsersQuery' => 'subsub_page',
        ];

        foreach ($filters as $queryVar => $keys) {
            if ($$queryVar instanceof \Illuminate\Database\Eloquent\Builder) {

                // Apply Search Filter
                if ($request->filled($keys['search'])) {
                    $search = strtolower($request->input($keys['search']));
                    $$queryVar->where(function ($q) use ($search) {
                        $q->whereRaw('LOWER(users.firstname) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(users.lastname) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(users.username) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(users.email) LIKE ?', ["%{$search}%"]);
                    });
                }

                // Apply User Type Filter
                if ($request->filled($keys['usertype'])) {
                    $$queryVar->where('usertype_id', $request->input($keys['usertype']));
                }

                // Apply Status Filter
                if ($request->filled($keys['status'])) {
                    $$queryVar->where('status', $request->input($keys['status']));
                }

                // Paginate after all filters applied
                $$queryVar = $$queryVar->paginate(10, ['*'], $pageNames[$queryVar])->withQueryString();
            } else {
                $$queryVar = collect();
            }
        }

        return view('pages.users', [
            'user' => $authUser,
            'mainUsers' => $mainUsersQuery,
            'subUsers' => $subUsersQuery,
            'subSubUsers' => $subSubUsersQuery,
        ]);
    }

    public function updateStatus(Request $request, User $user)
    {
        $request->validate([
            'status' => 'required|in:active,pending,deactivated'
        ]);

        $user->update([
            'status' => $request->status
        ]);

        return redirect()->back()->with('success', "User status updated to {$request->status}.");
    }
}
