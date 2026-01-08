<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;

use App\Models\PrintResource;
use App\Models\School;
use App\Models\District;
use App\Models\Division;
use App\Models\SchoolLibrary;
use App\Models\DivisionLibrary;

class PrintResourceController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $level = $user->userType?->level ?? 0;
        $stationId = (string) $user->station_id;

        $selectedDivision = $request->input('division');
        $selectedDistrict  = $request->input('district');
        $selectedSchool    = $request->input('school');

        $search = trim($request->input('search', ''));

        $divisions    = collect();
        $districts    = collect();
        $schools      = collect();
        $allDistricts = collect();
        $allSchools   = collect();

        $mainLibraryIds     = collect();
        $filteredLibraryIds = collect();

        // ── Determine which libraries to load ────────────────────────────────
        // LEVEL 1: School Librarian
        if ($level === 1) {
            $mainLibraryIds = SchoolLibrary::where('school_id', $stationId)
                ->pluck('id')
                ->map(fn($id) => (string) $id);
        }

        // LEVEL 2: District
        elseif ($level === 2) {
            $districtId = $stationId;
            $schools = School::where('district_id', $districtId)
                ->orderBy('school_name')
                ->get();

            if ($request->has('school')) {
                if ($selectedSchool === 'all' || !$selectedSchool) {
                    $schoolIds = $schools->pluck('id')->map(fn($id) => (string) $id);
                    $filteredLibraryIds = SchoolLibrary::whereIn('school_id', $schoolIds)
                        ->pluck('id')
                        ->map(fn($id) => (string) $id);
                } elseif ($selectedSchool) {
                    $filteredLibraryIds = SchoolLibrary::where('school_id', $selectedSchool)
                        ->pluck('id')
                        ->map(fn($id) => (string) $id);
                }
            }
        }

        // LEVEL 3: Division
        elseif ($level === 3) {
            $divisionId = $stationId;

            // Dropdown data
            $districts = District::where('division_id', $divisionId)
                ->orderBy('district_name')
                ->get();
            $allSchools = School::whereIn('district_id', $districts->pluck('id'))
                ->orderBy('school_name')
                ->get(['id', 'school_name', 'district_id']);
            // Always load Division Library IDs
            $mainLibraryIds = DivisionLibrary::where('division_id', $divisionId)
                ->pluck('id')
                ->map(fn($id) => (string)$id);

            // ── Division Library Resources ───────────────────────────────────────
            $divisionQuery = PrintResource::with([
                'printTitle.authors',
                'type',
                'printAcquisitions'
            ])
            ->whereHas('printAcquisitions', fn($q) =>
                $q->whereIn('library_id', $mainLibraryIds->toArray())
            );

            // Apply Division-specific search
            $divisionSearch = trim($request->input('division_search', ''));
            if ($divisionSearch !== '') {
                $searchLower = '%' . strtolower($divisionSearch) . '%';

                $divisionQuery->where(function ($q) use ($searchLower) {
                    $q->whereRaw('LOWER(isbn) LIKE ?', [$searchLower])
                    ->orWhereRaw('LOWER(publisher) LIKE ?', [$searchLower])
                    ->orWhereRaw('LOWER(copyright) LIKE ?', [$searchLower])

                    // Title
                    ->orWhereHas('printTitle', fn($qt) =>
                        $qt->whereRaw('LOWER(title) LIKE ?', [$searchLower])
                    )

                    // Authors
                    ->orWhereHas('printTitle.authors', fn($qa) =>
                        $qa->whereRaw('LOWER(author_name) LIKE ?', [$searchLower])
                    )

                    // Subject + Grade (PostgreSQL string_to_array)
                    ->orWhereExists(function ($exists) use ($searchLower) {
                        $exists->select(DB::raw(1))
                                ->from('subject_grade_levels as sgl')
                                ->join('subjects as subj', 'sgl.subject_id', '=', 'subj.id')
                                ->join('grade_levels as gl', 'sgl.grade_level_id', '=', 'gl.id')
                                ->whereRaw("sgl.id::text = ANY(string_to_array(print_resources.subject_grade_level_ids, ','))")
                                ->where(function ($match) use ($searchLower) {
                                    $match->whereRaw('LOWER(subj.subject_name) LIKE ?', [$searchLower])
                                        ->orWhereRaw('LOWER(gl.grade) LIKE ?', [$searchLower]);
                                });
                    });
                });
            }

            $resources = $divisionQuery->paginate(15)->withQueryString();

            // ── Filtered School Library Resources ─────────────────────────────────
            $filteredResources = new LengthAwarePaginator([], 0, 15, 1, [
                'path'  => $request->url(),
                'query' => $request->query(),
            ]);

            $filteredLibraryIds = collect();

            if ($selectedSchool && $selectedSchool !== 'all') {
                $filteredLibraryIds = SchoolLibrary::where('school_id', $selectedSchool)
                    ->pluck('id')
                    ->map(fn($id) => (string)$id);
            } elseif ($selectedDistrict && $selectedDistrict !== 'all') {
                $schoolIds = School::where('district_id', $selectedDistrict)
                    ->pluck('id')
                    ->map(fn($id) => (string)$id);
                $filteredLibraryIds = SchoolLibrary::whereIn('school_id', $schoolIds)
                    ->pluck('id')
                    ->map(fn($id) => (string)$id);
            } elseif ($selectedDistrict === 'all') {
                $districtIds = $districts->pluck('id');
                $schoolIds = School::whereIn('district_id', $districtIds)
                    ->pluck('id')
                    ->map(fn($id) => (string)$id);
                $filteredLibraryIds = SchoolLibrary::whereIn('school_id', $schoolIds)
                    ->pluck('id')
                    ->map(fn($id) => (string)$id);
            }

            if ($filteredLibraryIds->isNotEmpty()) {
                $schoolQuery = PrintResource::with([
                    'printTitle.authors',
                    'type',
                    'printAcquisitions'
                ])
                ->whereHas('printAcquisitions', fn($q) =>
                    $q->whereIn('library_id', $filteredLibraryIds->toArray())
                );

                // Apply School-specific search
                $schoolSearch = trim($request->input('school_search', ''));
                if ($schoolSearch !== '') {
                    $searchLower = '%' . strtolower($schoolSearch) . '%';

                    $schoolQuery->where(function ($q) use ($searchLower) {
                        $q->whereRaw('LOWER(isbn) LIKE ?', [$searchLower])
                        ->orWhereRaw('LOWER(publisher) LIKE ?', [$searchLower])
                        ->orWhereRaw('LOWER(copyright) LIKE ?', [$searchLower])

                        // Title
                        ->orWhereHas('printTitle', fn($qt) =>
                            $qt->whereRaw('LOWER(title) LIKE ?', [$searchLower])
                        )

                        // Authors
                        ->orWhereHas('printTitle.authors', fn($qa) =>
                            $qa->whereRaw('LOWER(author_name) LIKE ?', [$searchLower])
                        )

                        // Subject + Grade
                        ->orWhereExists(function ($exists) use ($searchLower) {
                            $exists->select(DB::raw(1))
                                    ->from('subject_grade_levels as sgl')
                                    ->join('subjects as subj', 'sgl.subject_id', '=', 'subj.id')
                                    ->join('grade_levels as gl', 'sgl.grade_level_id', '=', 'gl.id')
                                    ->whereRaw("sgl.id::text = ANY(string_to_array(print_resources.subject_grade_level_ids, ','))")
                                    ->where(function ($match) use ($searchLower) {
                                        $match->whereRaw('LOWER(subj.subject_name) LIKE ?', [$searchLower])
                                            ->orWhereRaw('LOWER(gl.grade) LIKE ?', [$searchLower]);
                                    });
                        });
                    });
                }

                $filteredResources = $schoolQuery->paginate(15)->withQueryString();
            }
        }
        // LEVEL 4: Region
        elseif ($level === 4) {
            $regionId = $stationId;
            $divisions = Division::where('region_id', $regionId)
                ->orderBy('division_name')
                ->get();

            $allDistricts = collect(District::getDistricts()->getData() ?? []);
            $allSchools   = collect(School::getSchools()->getData() ?? []);

            if ($request->has('school') && $selectedSchool !== 'all' && $selectedSchool) {
                $filteredLibraryIds = SchoolLibrary::where('school_id', $selectedSchool)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);
            } elseif ($request->has('district') && $selectedDistrict !== 'all' && $selectedDistrict) {
                $schoolIds = School::where('district_id', $selectedDistrict)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);
                $filteredLibraryIds = SchoolLibrary::whereIn('school_id', $schoolIds)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);
            } elseif ($request->has('division') && $selectedDivision !== 'all' && $selectedDivision) {
                $divisionLibs = DivisionLibrary::where('division_id', $selectedDivision)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);

                $districtIds = District::where('division_id', $selectedDivision)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);

                $schoolIds = School::whereIn('district_id', $districtIds)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);

                $schoolLibs = SchoolLibrary::whereIn('school_id', $schoolIds)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);

                $filteredLibraryIds = $divisionLibs->merge($schoolLibs);
            } elseif ($request->has('division') || $request->has('district') || $request->has('school')) {
                $divisionIds = Division::where('region_id', $regionId)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);

                $divisionLibs = DivisionLibrary::whereIn('division_id', $divisionIds)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);

                $districtIds = District::whereIn('division_id', $divisionIds)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);

                $schoolIds = School::whereIn('district_id', $districtIds)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);

                $schoolLibs = SchoolLibrary::whereIn('school_id', $schoolIds)
                    ->pluck('id')
                    ->map(fn($id) => (string) $id);

                $filteredLibraryIds = $divisionLibs->merge($schoolLibs);
            }
        }

        // ── Search logic (PostgreSQL-compatible) ─────────────────────────────
        $applySearch = function ($query) use ($search) {
            if (trim($search) === '') {
                return $query;
            }

            $searchLower = '%' . strtolower($search) . '%';

            return $query->where(function ($q) use ($searchLower) {
                $q->whereRaw('LOWER(isbn) LIKE ?', [$searchLower])
                  ->orWhereRaw('LOWER(publisher) LIKE ?', [$searchLower])
                  ->orWhereRaw('LOWER(copyright) LIKE ?', [$searchLower])

                  // Title
                  ->orWhereHas('printTitle', function ($qt) use ($searchLower) {
                      $qt->whereRaw('LOWER(title) LIKE ?', [$searchLower]);
                  })

                  // Authors
                  ->orWhereHas('printTitle.authors', function ($qa) use ($searchLower) {
                      $qa->whereRaw('LOWER(author_name) LIKE ?', [$searchLower]);
                  })

                  // Subjects & Grade Levels – PostgreSQL string_to_array
                  ->orWhereExists(function ($exists) use ($searchLower) {
                      $exists->select(DB::raw(1))
                             ->from('subject_grade_levels as sgl')
                             ->join('subjects as subj', 'sgl.subject_id', '=', 'subj.id')
                             ->join('grade_levels as gl', 'sgl.grade_level_id', '=', 'gl.id')
                             ->whereRaw("sgl.id::text = ANY(string_to_array(print_resources.subject_grade_level_ids, ','))")
                             ->where(function ($match) use ($searchLower) {
                                 $match->whereRaw('LOWER(subj.subject_name) LIKE ?', [$searchLower])
                                       ->orWhereRaw('LOWER(gl.grade) LIKE ?', [$searchLower]);
                             });
                  });
            });
        };

        // ── Main (auto-loaded) resources ─────────────────────────────────────
        $mainQuery = PrintResource::with([
            'printTitle.authors',
            'type',
            'printAcquisitions',
        ]);

        if ($mainLibraryIds->isNotEmpty()) {
            $mainQuery->whereHas('printAcquisitions', fn($q) =>
                $q->whereIn('library_id', $mainLibraryIds->toArray())
            );
        }

        $mainQuery = $applySearch($mainQuery);
        $resources = $mainQuery->paginate(15)->withQueryString();

        // ── Filtered resources (school/district/division selection) ──────────
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
            ])->whereHas('printAcquisitions', fn($q) =>
                $q->whereIn('library_id', $filteredLibraryIds->toArray())
            );

            $filteredQuery = $applySearch($filteredQuery);
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
