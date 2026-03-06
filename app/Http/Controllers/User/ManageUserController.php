<?php

namespace App\Http\Controllers\User;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\User;
use App\Services\UserManagementService;

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
    $filters  = $this->extractFilters($request);
    $users    = $this->userManagementService->getHierarchicalUsers($authUser, $filters);

    if ($request->header('HX-Request')) {
        $activeTab = $request->input('active_tab', 'main');

        [$tableUsers, $emptyMessage] = match($activeTab) {
            'sub'    => [$users['subUsers'],    'No users found.'],
            'subsub' => [$users['subSubUsers'], 'No users found.'],
            default  => [$users['mainUsers'],   'No users found.'],
        };

        return view('pages.partials.users-table', [
            'users'        => $tableUsers,
            'emptyMessage' => $emptyMessage,
        ]);
    }

    return view('pages.users', [
        'user'        => $authUser,
        'mainUsers'   => $users['mainUsers'],
        'subUsers'    => $users['subUsers'],
        'subSubUsers' => $users['subSubUsers'],
    ]);
}
public function updateStatus(Request $request, User $user)
{
    $validated = $request->validate([
        'status' => 'required|in:active,pending,deactivated'
    ]);

    $this->userManagementService->updateUserStatus($user, $validated['status']);

    // HTMX — return updated row HTML
    if ($request->header('HX-Request')) {
        return view('pages.partials.user-row', ['user' => $user->fresh()]);
    }

    return redirect()->back()->with('success', "User status updated to {$validated['status']}.");
}

    private function extractFilters(Request $request): array
    {
        return [
            'main' => [
                'search' => $request->input('search_main'),
                'usertype' => $request->input('usertype_main'),
                'status' => $request->input('status_main'),
            ],
            'sub' => [
                'search' => $request->input('search_sub'),
                'usertype' => $request->input('usertype_sub'),
                'status' => $request->input('status_sub'),
            ],
            'subsub' => [
                'search' => $request->input('search_subsub'),
                'usertype' => $request->input('usertype_subsub'),
                'status' => $request->input('status_subsub'),
            ],
        ];
    }
}
