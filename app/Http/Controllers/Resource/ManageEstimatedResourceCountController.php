<?php

namespace App\Http\Controllers\Resource;

use App\Models\SchoolLibrary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

class ManageEstimatedResourceCountController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function updateEstimatedResource(Request $request)
    {
        abort(403, 'School users can no longer update pre-inventory from Print Resources.');
    }

    public function updateEstimatedResourceNP(Request $request)
    {
        $request->validate([
            'estimated_resource_np' => 'required|integer|min:0'
        ]);

        $user = Auth::user();

        // Same UUID cast as above
        $stationId = (string) $user->station_id;

        $schoolLibrary = SchoolLibrary::where('school_id', $stationId)->first();

        if (!$schoolLibrary) {
            return redirect()->back()->with('error', 'School library not found.');
        }

        $schoolLibrary->update([
            'estimated_resource_np' => $request->estimated_resource_np
        ]);

        return redirect()
                ->route('nonprint-resources', ['tab' => 'school'])
                ->with('success', 'Estimated nonprint resource updated successfully.');
    }
}
