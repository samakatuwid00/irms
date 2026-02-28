<?php

namespace App\Http\Controllers\Resource;

use App\Models\SchoolLibrary;
use App\Models\EstimatedResourcePrecentageNP;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

use App\Services\Resource\Tables\NonPrintResourceService;

class NonPrintResourceController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $nonprintResourceService;

    public function __construct(NonPrintResourceService $nonprintResourceService)
    {
        $this->middleware('auth');
        $this->nonprintResourceService = $nonprintResourceService;
    }

    public function index(Request $request)
    {
        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        // Cast to string — station_id is a UUID and the FK column is varchar,
        // type mismatch can silently return null on some drivers
        $stationId = (string) $user->station_id;

        // Only needed for school-level users — will be null for division/region
        $schoolLibrary          = SchoolLibrary::where('school_id', $stationId)->first();
        $schoolEstimatedPercent = EstimatedResourcePrecentageNP::where('school_id', $stationId)->first();

        // Service handles all level-based filtering and table building
        $data = $this->nonprintResourceService->getResourcesData($request, $level, $stationId);

        // Merge the school extras — they're separate from the service's core concern
        $data = array_merge($data, [
            'schoolLibrary' => $schoolLibrary,
            'countPercent'  => $schoolEstimatedPercent,
        ]);

        // AJAX request: the JS sends X-Requested-With: XMLHttpRequest when using fetch().
        // Return only the relevant component partial so the sidebar is never re-rendered.
        if ($request->ajax()) {
            $partial = match ($level) {
                1 => 'pages.components.nonprint-resource-school-account',
                2 => 'pages.components.nonprint-resource-district-account',
                3 => 'pages.components.nonprint-resource-division-account',
                4 => 'pages.components.nonprint-resource-region-account',
                default => null,
            };

            if ($partial) {
                return response()->view($partial, $data);
            }
        }

        // Normal full-page request — return the full layout as before
        return view('pages.nonprint-resources', $data);
    }
}
