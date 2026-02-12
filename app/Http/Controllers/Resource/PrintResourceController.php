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
        $user = Auth::user();
        $level = $user->userType?->level ?? 0;
        $stationId = (string) $user->station_id;

        $schoolLibrary = SchoolLibrary::where('school_id', $stationId)->first();
        $schoolEstimatedPercent = EstimatedResourcePrecentage::where('school_id', $stationId)->first();

        $data = $this->printResourceService->getResourcesData($request, $level, $stationId);

        // Merge additional variables into $data
        $data = array_merge($data, [
            'schoolLibrary' => $schoolLibrary,
            'countPercent' => $schoolEstimatedPercent,
        ]);

        return view('pages.print-resources', $data);
    }

}
