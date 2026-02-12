<?php

namespace App\Http\Controllers\Resource;

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

        $data = $this->printResourceService->getResourcesData($request, $level, $stationId);

        return view('pages.print-resources', $data);
    }
}
