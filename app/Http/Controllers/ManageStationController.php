<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

use App\Models\{
    Division,
    District,
    School
};

class ManageStationController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user   = Auth::user();
        $level  = $user->userType?->level;
        $stationId = $user->station_id;
        $search = strtolower($request->query('search'));

        $divisions = null;
        $districts = null;
        $schools   = null;

        /* ================= REGION ================= */
        if ($level === 4) {
            $divisions = $this->getDivisionsByRegion($stationId, $search);
        }

        // Level 3 search values.
        $districtSearch = strtolower($request->query('district_search', ''));
        $schoolSearch   = strtolower($request->query('school_search', ''));

        /* ================= DIVISION LEVEL (level 3) ================= */
        if ($level === 3) {
            $districts = $this->getDistrictsByDivision($stationId, $districtSearch);
            $schools   = $this->getSchoolsByDivision($stationId, $schoolSearch);
        }

        /* ================= DISTRICT ================= */
        if ($level === 2) {
            $schools = $this->getSchoolsByDistrict($stationId, $search);
        }

        return view('pages.stations', compact(
            'divisions',
            'districts',
            'schools'
        ));
    }


    public function addDivision(Request $request)
    {

        $request->validate([
            'division_name' => 'required|string|max:255',
            'shortname' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'date_establish' => 'nullable|date',
            'legislative_district' => 'nullable|string|max:255',
        ]);

        Division::create([
            'id' => Str::uuid(),
            'division_name' => $request->division_name,
            'shortname' => $request->shortname,
            'address' => $request->address,
            'contact_number' => $request->contact_number,
            'email' => $request->email,
            'date_establish' => $request->date_establish,
            'legislative_district' => $request->legislative_district,
            'region_id' => Auth::user()->station_id,
        ]);

        return redirect()->route('stations')->with('success', 'Division added successfully!');
    }

    public function updateDivision(Request $request, Division $division)
    {
        $request->validate([
            'division_name' => 'required|string|max:255',
            'shortname' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'date_establish' => 'nullable|date',
            'legislative_district' => 'nullable|string|max:50',
        ]);

        $division->update($request->all());

        return redirect()->route('stations')->with('success', 'Division updated successfully!');
    }

    public function destroyDivision(Division $division)
    {
        $division->delete();

        return redirect()->route('stations')->with('success', 'Division deleted successfully!');
    }

    /* =====================================================
     | REGION → DIVISIONS
     ===================================================== */
    private function getDivisionsByRegion(string $regionId, ?string $search)
    {
        $query = Division::query()
            ->with('region')
            ->where('region_id', $regionId)
            ->orderBy('division_name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(division_name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(shortname) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(address) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
            });
        }

        return $query->paginate(15);
    }

    /* =====================================================
     | DIVISION → DISTRICTS
     ===================================================== */
    private function getDistrictsByDivision(string $divisionId, ?string $search)
    {
        $query = District::query()
            ->with('division')
            ->where('division_id', $divisionId)
            ->orderBy('district_name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(district_name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(shortname) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(address) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
            });
        }

        return $query->paginate(15);
    }

    /* =====================================================
     | DIVISION → SCHOOLS
     ===================================================== */
    private function getSchoolsByDivision(string $divisionId, ?string $search)
    {
        $query = School::query()
            ->with('district.division')
            ->whereHas('district', function ($q) use ($divisionId) {
                $q->where('division_id', $divisionId);
            })
            ->orderBy('school_name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(school_name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(shortname) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(school_type) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(address) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
            });
        }

        return $query->paginate(15);
    }

    /* =====================================================
     | DISTRICT → SCHOOLS
     ===================================================== */
    private function getSchoolsByDistrict(string $districtId, ?string $search)
    {
        $query = School::query()
            ->with('district.division')
            ->where('district_id', $districtId)
            ->orderBy('school_name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(school_name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(shortname) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(school_type) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(address) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
            });
        }

        return $query->paginate(15);
    }
}
