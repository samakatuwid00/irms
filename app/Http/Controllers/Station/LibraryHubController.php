<?php

namespace App\Http\Controllers\Station;

use App\Services\LibraryHubService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LibraryHubController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct(private LibraryHubService $libraryHubService)
    {
        $this->middleware('auth');
    }

    public function store(Request $request)
    {
        $divisionId = (string) Auth::user()->station_id;
        $validated = $this->validateLibraryHub($request, $divisionId);

        $this->libraryHubService->createDivisionHub($divisionId, $validated);

        return redirect()
            ->route('division-profile', ['active_tab' => 'library_hubs'])
            ->with('success', 'Library hub added successfully.');
    }

    public function update(Request $request, string $libraryHub)
    {
        $divisionId = (string) Auth::user()->station_id;
        $hub = $this->libraryHubService->findDivisionHub($divisionId, $libraryHub);
        $validated = $this->validateLibraryHub($request, $divisionId);

        $this->libraryHubService->updateDivisionHub($hub, $validated);

        return redirect()
            ->route('division-profile', ['active_tab' => 'library_hubs'])
            ->with('success', 'Library hub updated successfully.');
    }

    private function validateLibraryHub(Request $request, string $divisionId): array
    {
        return $request->validate([
            'library_name' => ['required', 'string', 'max:255'],
            'librarian' => [
                'required',
                'uuid',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('station_id', $divisionId)),
            ],
            'net_expected_count' => ['required', 'integer', 'min:1'],
        ]);
    }
}
