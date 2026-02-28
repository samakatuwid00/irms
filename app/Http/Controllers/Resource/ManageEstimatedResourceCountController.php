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
        $request->validate([
            // min:0 prevents negative values that would break the fulfilment percentage
            'estimated_resource' => 'required|integer|min:0'
        ]);

        $user = Auth::user();

        // Cast to string — station_id is a UUID and the FK column is varchar,
        // type mismatch can silently return null on some drivers
        $stationId = (string) $user->station_id;

        $schoolLibrary = SchoolLibrary::where('school_id', $stationId)->first();

        if (!$schoolLibrary) {
            return redirect()->back()->with('error', 'School library not found.');
        }

        $schoolLibrary->update([
            'estimated_resource' => $request->estimated_resource
        ]);

        // Redirect to the school tab so they can immediately see the updated percentage
        return redirect()
                ->route('print-resources', ['tab' => 'school'])
                ->with('success', 'Estimated resource updated successfully.');
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
