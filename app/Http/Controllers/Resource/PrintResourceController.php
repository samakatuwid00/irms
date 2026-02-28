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

        return view('pages.print-resources', $data);
    }
}
