<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;

use App\Models\PrintResource;
use App\Models\School;
use App\Models\District;
use App\Models\Division;
use App\Models\SchoolLibrary;
use App\Models\DivisionLibrary;
use App\Models\RegionLibrary;

class PrintResourceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $level = $user->userType?->level ?? 0;

        $stationId = (string) $user->station_id;

        $selectedDivision = $request->filled('division') ? (string) $request->input('division') : null;
        $selectedDistrict  = $request->filled('district')  ? (string) $request->input('district')  : null;
        $selectedSchool    = $request->filled('school')    ? (string) $request->input('school')    : null;

        $search = trim($request->input('search', ''));

        $divisions    = collect();
        $districts    = collect();
        $schools      = collect();
        $allDistricts = collect();
        $allSchools   = collect();

        $mainLibraryIds     = collect();
        $filteredLibraryIds = collect();

        // ── LEVEL 1: School Librarian ───────────────────────────────────────
        if ($level === 1) {
            $mainLibraryIds = SchoolLibrary::where('school_id', $stationId)
                ->pluck('id')
                ->map(fn($id) => (string) $id);
        }

        // ── LEVEL 2: District ───────────────────────────────────────────────
        elseif ($level === 2) {
            $districtId = $stationId;

            // Schools for the dropdown
            $schools = School::where('district_id', $districtId)
                ->orderBy('school_name')
                ->get();

            // NO automatic loading of school resources
            $mainLibraryIds = collect(); // ← empty → no auto table

            // Filtered school libraries – only when form submitted
            if ($request->has('school')) {
                if ($selectedSchool === '') {
                    // All Schools in this District
                    $schoolIds = $schools->pluck('id')->map(fn($id) => (string) $id);
                    $filteredLibraryIds = SchoolLibrary::whereIn('school_id', $schoolIds)
                        ->pluck('id')
                        ->map(fn($id) => (string) $id);
                } else {
                    // Specific school
                    $filteredLibraryIds = SchoolLibrary::where('school_id', $selectedSchool)
                        ->pluck('id')
                        ->map(fn($id) => (string) $id);
                }
            }
        }

        // Level 3: Division
        elseif ($level === 3) {
            $divisionId = $stationId;
            $districts = District::where('division_id', $divisionId)
                ->orderBy('district_name')
                ->get();

            $allSchools = collect(School::getSchools()->getData());

            $mainLibraryIds = DivisionLibrary::where('division_id', $divisionId)
                ->pluck('id')
                ->map(fn($id) => (string) $id);

            // Filtered school libraries
            if ($selectedSchool) {
                $filteredLibraryIds = SchoolLibrary::where('school_id', $selectedSchool)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);
            } elseif ($selectedDistrict) {
                $schoolIds = School::where('district_id', $selectedDistrict)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);
                $filteredLibraryIds = SchoolLibrary::whereIn('school_id', $schoolIds)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);
            } elseif ($request->filled('district')) { // All Districts
                $schoolIds = School::where('division_id', $divisionId)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);
                $filteredLibraryIds = SchoolLibrary::whereIn('school_id', $schoolIds)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);
            }
        }

        // Level 4: Region
        elseif ($level === 4) {
            $regionId = $stationId;
            $divisions = Division::where('region_id', $regionId)
                ->orderBy('division_name')
                ->get();

            $allDistricts = collect(District::getDistricts()->getData());
            $allSchools   = collect(School::getSchools()->getData());

            // Filtered libraries (only filtered)
            if ($selectedSchool) {
                $filteredLibraryIds = SchoolLibrary::where('school_id', $selectedSchool)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);
            } elseif ($selectedDistrict) {
                $schoolIds = School::where('district_id', $selectedDistrict)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);
                $filteredLibraryIds = SchoolLibrary::whereIn('school_id', $schoolIds)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);
            } elseif ($selectedDivision) {
                $filteredLibraryIds = DivisionLibrary::where('division_id', $selectedDivision)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);

                $districtIds = District::where('division_id', $selectedDivision)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);

                $schoolIds = School::whereIn('district_id', $districtIds)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);

                $filteredLibraryIds = $filteredLibraryIds->merge(
                    SchoolLibrary::whereIn('school_id', $schoolIds)
                        ->pluck('id')
                        ->map(fn($id) => (string) $id)
                );
            } elseif ($request->filled('division')) { // All Divisions
                $divisionIds = Division::where('region_id', $regionId)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);

                $filteredLibraryIds = DivisionLibrary::whereIn('division_id', $divisionIds)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);

                $districtIds = District::whereIn('division_id', $divisionIds)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);

                $schoolIds = School::whereIn('district_id', $districtIds)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);

                $filteredLibraryIds = $filteredLibraryIds->merge(
                    SchoolLibrary::whereIn('school_id', $schoolIds)
                        ->pluck('id')
                        ->map(fn($id) => (string) $id)
                );
            }
        }

        // ── Main (auto-loaded) resources ────────────────────────────────────
        $mainQuery = PrintResource::with([
            'printTitle.authors',
            'type',
            'printAcquisitions',
        ]);

        if ($mainLibraryIds->isNotEmpty()) {
            $mainQuery->whereHas('printAcquisitions', function ($q) use ($mainLibraryIds) {
                $q->whereIn('library_id', $mainLibraryIds->toArray());
            });
        }

        if ($search !== '') {
            $like = '%' . $search . '%';
            $mainQuery->where(function ($q) use ($like) {
                $q->where('isbn', 'like', $like)
                ->orWhere('publisher', 'like', $like)
                ->orWhere('copyright', 'like', $like)
                ->orWhereHas('printTitle', fn($qt) => $qt->where('title', 'like', $like))
                ->orWhereHas('printTitle.authors', fn($qa) => $qa->where('author_name', 'like', $like));
            });
        }

        $resources = $mainQuery->paginate(15)->withQueryString();

        // ── Filtered resources (only when requested) ────────────────────────
        $filteredResources = new LengthAwarePaginator([], 0, 15, 1, [
            'path'  => $request->url(),
            'query' => $request->query(),
        ]);

        $shouldShowFiltered =
            ($level === 2 && $request->has('school')) ||
            ($level === 3 && ($request->has('district') || $request->has('school'))) ||
            ($level === 4 && ($request->has('division') || $request->has('district') || $request->has('school')));

        if ($shouldShowFiltered && $filteredLibraryIds->isNotEmpty()) {
            $filteredQuery = PrintResource::with([
                'printTitle.authors',
                'type',
                'printAcquisitions',
            ])->whereHas('printAcquisitions', function ($q) use ($filteredLibraryIds) {
                $q->whereIn('library_id', $filteredLibraryIds->toArray());
            });

            if ($search !== '') {
                $like = '%' . $search . '%';
                $filteredQuery->where(function ($q) use ($like) {
                    $q->where('isbn', 'like', $like)
                    ->orWhere('publisher', 'like', $like)
                    ->orWhere('copyright', 'like', $like)
                    ->orWhereHas('printTitle', fn($qt) => $qt->where('title', 'like', $like))
                    ->orWhereHas('printTitle.authors', fn($qa) => $qa->where('author_name', 'like', $like));
                });
            }

            $filteredResources = $filteredQuery->paginate(15)->withQueryString();
        }

        return view('pages.print-resources', compact(
            'level',
            'resources',
            'filteredResources',
            'divisions',
            'districts',
            'schools',
            'allDistricts',
            'allSchools'
        ));
    }
}
