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
        $user = Auth::user();
        $level = $user->userType?->level ?? 0;
        $stationId = (string) $user->station_id;

        $schoolLibrary = SchoolLibrary::where('school_id', $stationId)->first();
        $schoolEstimatedPercent = EstimatedResourcePrecentageNP::where('school_id', $stationId)->first();

        $data = $this->nonprintResourceService->getResourcesData($request, $level, $stationId);

        $data = array_merge($data, [
            'schoolLibrary' => $schoolLibrary,
            'countPercent' => $schoolEstimatedPercent,
        ]);

        return view('pages.nonprint-resources', $data);
    }
}
