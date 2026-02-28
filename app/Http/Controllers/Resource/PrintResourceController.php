<?php

namespace App\Http\Controllers\Resource;

use App\Models\SchoolLibrary;
use App\Models\EstimatedResourcePrecentage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Services\Resource\Tables\PrintResourceService;

class PrintResourceController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $printResourceService;

    public function __construct(PrintResourceService $printResourceService)
    {
        $this->middleware('auth');
        $this->printResourceService = $printResourceService;
    }

    public function index(Request $request)
    {
        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        // Cast to string — station_id is a UUID and the FK column is varchar,
        // type mismatch can silently return null on some drivers
        $stationId = (string) $user->station_id;

        // Only relevant for school-level users — will be null for everyone else
        $schoolLibrary          = SchoolLibrary::where('school_id', $stationId)->first();
        $schoolEstimatedPercent = EstimatedResourcePrecentage::where('school_id', $stationId)->first();

        // Service handles all level-based filtering and table building
        $data = $this->printResourceService->getResourcesData($request, $level, $stationId);

        // Merge the school extras — they're outside the service's core concern
        $data = array_merge($data, [
            'schoolLibrary' => $schoolLibrary,
            'countPercent'  => $schoolEstimatedPercent,
        ]);

        // AJAX request: the JS sends X-Requested-With: XMLHttpRequest when using fetch().
        // Return only the relevant component partial so the sidebar is never re-rendered.
        if ($request->ajax() || $request->boolean('_ajax')) {
            $partial = match ($level) {
                1 => 'pages.components.print-resource-school-account',
                2 => 'pages.components.print-resource-district-account',
                3 => 'pages.components.print-resource-division-account',
                4 => 'pages.components.print-resource-region-account',
                default => null,
            };

            if ($partial) {
                return response()->view($partial, $data);
            }
        }

        return view('pages.print-resources', $data);
        }
}
