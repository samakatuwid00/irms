<?php

namespace App\Http\Controllers\Resource;

use App\Models\School;
use App\Models\SchoolLibrary;
use Illuminate\Http\JsonResponse;
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

    public function updateSchoolPreInventory(Request $request, string $schoolId): JsonResponse
    {
        $validated = $request->validate([
            'estimated_resource' => 'required|integer|min:0',
        ]);

        $user = Auth::user();
        $userLevel = $this->determineUserLevel($user);

        if ($userLevel !== 3) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $divisionId = (string) $user->station_id;

        $school = School::query()
            ->where('id', $schoolId)
            ->whereHas('district', fn ($query) => $query->where('division_id', $divisionId))
            ->first();

        if (!$school) {
            return response()->json(['error' => 'School not found in your division.'], 404);
        }

        $schoolLibrary = SchoolLibrary::where('school_id', $schoolId)->first();

        if (!$schoolLibrary) {
            return response()->json(['error' => 'School library not found.'], 404);
        }

        $schoolLibrary->update([
            'estimated_resource' => $validated['estimated_resource'],
        ]);

        $estimatedPrint = (int) $schoolLibrary->estimated_resource;
        $estimatedNonprint = (int) ($schoolLibrary->estimated_resource_np ?? 0);
        $totalEstimated = $estimatedPrint + $estimatedNonprint;

        return response()->json([
            'success' => true,
            'message' => 'Pre-inventory updated successfully.',
            'estimated_print' => $estimatedPrint,
            'estimated_nonprint' => $estimatedNonprint,
            'estimated_resource' => $totalEstimated,
        ]);
    }

    private function determineUserLevel($user): int
    {
        $stationId = $user->station_id ?? null;
        if (!$stationId) {
            return 0;
        }

        if (School::where('id', $stationId)->exists()) {
            return 1;
        }

        if (\App\Models\District::where('id', $stationId)->exists()) {
            return 2;
        }

        if (\App\Models\Division::where('id', $stationId)->exists()) {
            return 3;
        }

        if (\App\Models\Region::where('id', $stationId)->exists()) {
            return 4;
        }

        return 0;
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
