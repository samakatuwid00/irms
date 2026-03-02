<?php

namespace App\Http\Controllers\Station;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

use App\Models\{Division, District, School};
use App\Services\StationManagementService;

class ManageStationController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $stationService;

    public function __construct(StationManagementService $stationService)
    {
        $this->middleware('auth');
        $this->stationService = $stationService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $level = $user->userType?->level;
        $stationId = $user->station_id;
        $search = strtolower($request->query('search'));

        $divisions = null;
        $districts = null;
        $schools = null;
        $districtsWithSchools = null;

        // Region Level (level 4)
        if ($level === 4) {
            $divisions = $this->stationService->getDivisionsByRegion($stationId, $search);
        }

        // Division Level (level 3)
        if ($level === 3) {
            $districtSearch = strtolower($request->query('district_search', ''));
            $schoolSearch = strtolower($request->query('school_search', ''));

            $districts = $this->stationService->getDistrictsByDivision($stationId, $districtSearch);

            // Get districts with their schools
            $districtsWithSchools = $this->stationService->getDistrictsWithSchools($stationId, $schoolSearch);
        }

        // District Level (level 2)
        if ($level === 2) {
            $schools = $this->stationService->getSchoolsByDistrict($stationId, $search);
        }

        return view('pages.stations', compact('divisions', 'districts', 'schools', 'districtsWithSchools'));
    }

    public function addDivision(Request $request)
    {
        $validated = $request->validate([
            'division_name' => 'required|string|max:255',
            'shortname' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'date_establish' => 'nullable|date',
            'legislative_district' => 'nullable|string|max:255',
        ]);

        $this->stationService->createDivision($validated, Auth::user()->station_id);

        return redirect()->route('stations')->with('success', 'Division added successfully!');
    }

    public function updateDivision(Request $request, Division $division)
    {
        $validated = $request->validate([
            'division_name' => 'required|string|max:255',
            'shortname' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'date_establish' => 'nullable|date',
            'legislative_district' => 'nullable|string|max:50',
        ]);

        $this->stationService->updateDivision($division, $validated);

        return redirect()->route('stations')->with('success', 'Division updated successfully!');
    }

    public function destroyDivision(Division $division)
    {
        $this->stationService->deleteDivision($division);

        return redirect()->route('stations')->with('success', 'Division deleted successfully!');
    }

    public function addDistrict(Request $request)
    {
        $validated = $request->validate([
            'district_name' => 'required|string|max:255',
            'shortname' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'date_establish' => 'nullable|date',
            'legislative_district' => 'nullable|string|max:255',
        ]);

        $this->stationService->createDistrict($validated, Auth::user()->station_id);

        return redirect()->route('stations')->with('success', 'District added successfully!');
    }

    public function updateDistrict(Request $request, District $district)
    {
        $validated = $request->validate([
            'district_name' => 'required|string|max:255',
            'shortname' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'date_establish' => 'nullable|date',
            'legislative_district' => 'nullable|string|max:50',
        ]);

        $this->stationService->updateDistrict($district, $validated);

        return redirect()->route('stations')->with('success', 'District updated successfully!');
    }

    public function destroyDistrict(District $district)
    {
        $this->stationService->deleteDistrict($district);

        return redirect()->route('stations')->with('success', 'District deleted successfully!');
    }

    public function addSchool(Request $request)
    {
        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'shortname' => 'nullable|string|max:50',
            'school_id' => 'required|string|max:50',
            'address' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'date_establish' => 'nullable|date',
            'legislative_school' => 'nullable|string|max:255',
            'school_type' => 'nullable',
            'district_id' => 'required|exists:districts,id',
        ]);

        $this->stationService->createSchool($validated, $validated['district_id']);

        return redirect()->route('stations')->with('success', 'School added successfully!');
    }

    public function updateSchool(Request $request, School $school)
    {
        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'shortname' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'date_establish' => 'nullable|date',
            'legislative_school' => 'nullable|string|max:50',
            'school_type' => 'nullable',
        ]);

        $this->stationService->updateSchool($school, $validated);

        return redirect()->route('stations')->with('success', 'School updated successfully!');
    }

    public function destroySchool(School $school)
    {
        $this->stationService->deleteSchool($school);

        return redirect()->route('stations')->with('success', 'School deleted successfully!');
    }
}
