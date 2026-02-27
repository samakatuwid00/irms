<?php

namespace App\Http\Controllers\Resource;

use App\Models\DivisionLibrary;
use App\Models\PrintResource;
use App\Models\PrintTitle;
use App\Models\RegionLibrary;
use App\Models\SchoolLibrary;
use App\Models\SubjectGradeLevel;
use App\Services\Resource\Actions\AddAcquisitionToExistingResourceService;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class SearchPrintResourceController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $addAcquisitionService;

    public function __construct(AddAcquisitionToExistingResourceService $addAcquisitionService)
    {
        $this->middleware('auth');
        $this->addAcquisitionService = $addAcquisitionService;
    }

    /**
     * Show the search page for finding existing print resources.
     */
    public function index()
    {
        $user = Auth::user();

        return view('pages.add-print-resource', compact('user'));
    }

    /**
     * AJAX: Search print titles by keyword.
     * Returns one card per PrintTitle with all its resource editions and a
     * deduplicated, comma-joined subject/grade-level string.
     */
    public function search(Request $request)
    {
        $query = trim($request->input('q', ''));

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        // Search PrintTitles matching the query, get their IDs
        $titleIds = PrintTitle::with('authors')
            ->where('title', 'ILIKE', '%' . $query . '%')
            ->orWhereHas('authors', fn($q) => $q->where('author_name', 'ILIKE', '%' . $query . '%'))
            ->pluck('id');

        // Get PrintResources grouped by uniqueness_hash
        $resources = PrintResource::with(['printTitle.authors', 'type'])
            ->whereIn('print_title_id', $titleIds)
            ->where('status', 1)
            ->get()
            ->groupBy('uniqueness_hash'); // one card per unique hash

        $results = $resources->map(function ($group) {
            // Use the first resource in the group as the representative
            $resource = $group->first();
            $title    = $resource->printTitle;

            // Aggregate SGL ids across all resources in this hash group
            $allSglIds = $group
                ->pluck('subject_grade_level_ids')
                ->filter()
                ->flatMap(fn($csv) => explode(',', $csv))
                ->unique()
                ->values()
                ->all();

            $subjectString = '';
            if (!empty($allSglIds)) {
                $sgls = SubjectGradeLevel::with(['subject', 'gradeLevel'])
                    ->whereIn('id', $allSglIds)
                    ->get();

                $subjectString = $sgls
                    ->map(fn($sgl) =>
                        ($sgl->subject->subject_name ?? 'N/A') . ' (' . ($sgl->gradeLevel->grade ?? 'N/A') . ')'
                    )
                    ->unique()
                    ->join(', ');
            }

            $cover = $group
                ->map(fn($r) => $r->cover ? asset('storage/' . $r->cover) : null)
                ->filter()
                ->first() ?? asset('assets/images/def.jpg');

            return [
                'id'             => $title->id,
                'resource_id'    => $resource->id,
                'uniqueness_hash'=> $resource->uniqueness_hash,
                'title'          => $title->title,
                'authors'        => $title->authors->pluck('author_name')->join(', ') ?: 'No Author',
                'subjects'       => $subjectString ?: 'No subjects assigned',
                'cover'          => $cover,
                'editions'       => $group->map(fn($r) => [
                    'id'        => $r->id,
                    'type'      => $r->type->type_name ?? '-',
                    'publisher' => $r->publisher ?? '-',
                    'edition'   => $r->edition ?? '-',
                    'copyright' => $r->copyright ?? '-',
                ])->values(),
            ];
        })->values();

        return response()->json($results);
    }

    /**
     * AJAX: Get full details of a PrintTitle (all editions) for the view modal.
     * Accepts a PrintTitle ID. Subject/grade levels are aggregated and
     * deduplicated across every resource edition under this title.
     */
    public function show(Request $request, string $id)
    {
        $title = PrintTitle::with([
            'authors',
            'printResources.type',
        ])->findOrFail($id);

        // Filter resources by uniqueness_hash so only the clicked group shows
        $hash      = $request->input('hash');
        $resources = $hash
            ? $title->printResources->where('uniqueness_hash', $hash)
            : $title->printResources;

        // Collect all SGL ids across filtered resources
        $allSglIds = $resources
            ->pluck('subject_grade_level_ids')
            ->filter()
            ->flatMap(fn($csv) => explode(',', $csv))
            ->unique()
            ->values()
            ->all();

        $subjectString = '';
        if (!empty($allSglIds)) {
            $sgls = SubjectGradeLevel::with(['subject', 'gradeLevel'])
                ->whereIn('id', $allSglIds)
                ->get();

            $subjectString = $sgls
                ->map(fn($sgl) =>
                    ($sgl->subject->subject_name ?? 'N/A') . ' (' . ($sgl->gradeLevel->grade ?? 'N/A') . ')'
                )
                ->unique()
                ->join(', ');
        }

        $cover = $resources
            ->map(fn($r) => $r->cover ? asset('storage/' . $r->cover) : null)
            ->filter()
            ->first() ?? asset('assets/images/def.jpg');

        $editions = $resources->map(fn($r) => [
            'id'        => $r->id,
            'type'      => $r->type->type_name ?? '-',
            'publisher' => $r->publisher ?? '-',
            'volume'    => $r->volume ?? '-',
            'edition'   => $r->edition ?? '-',
            'copyright' => $r->copyright ?? '-',
            'pages'     => $r->pages ?? '-',
            'isbn'      => $r->isbn ?? '-',
            'cover'     => $r->cover
                ? asset('storage/' . $r->cover)
                : asset('assets/images/def.jpg'),
            'add_url'   => route('search-print-resource.add-form', $r->id),
        ])->values();

        return response()->json([
            'id'       => $title->id,
            'title'    => $title->title,
            'authors'  => $title->authors->pluck('author_name')->join(', ') ?: '-',
            'subjects' => $subjectString ?: 'No subjects assigned',
            'cover'    => $cover,
            'editions' => $editions,
        ]);
    }

    /**
     * Show the "Add Acquisition" form for an existing print resource.
     * All resource fields are pre-filled and read-only; only acquisitions are editable.
     * Library selection has moved inside each acquisition entry.
     */
    public function addForm(string $id)
    {
        $user     = Auth::user();
        $resource = PrintResource::with([
            'printTitle.authors',
            'type',
        ])->findOrFail($id);

        // Resolve subjects for display
        $subjects = collect();
        if ($resource->subject_grade_level_ids) {
            $ids      = explode(',', $resource->subject_grade_level_ids);
            $subjects = SubjectGradeLevel::with(['subject', 'gradeLevel'])
                ->whereIn('id', $ids)
                ->get();
        }

        // Resolve library options based on user level (passed to view for
        // use inside the per-acquisition library selector)
        $divisionLibraries = collect();
        $regionLibrary     = null;
        $schoolLibrary     = null;
        $stationId         = $user->station_id;

        if ($user->userType?->level === 3) {
            $divisionLibraries = DivisionLibrary::where('division_id', $stationId)
                ->orderBy('library_name')->get();
        } elseif ($user->userType?->level === 4) {
            $regionLibrary = RegionLibrary::where('region_id', $stationId)->first();
        } elseif ($user->userType?->level === 1) {
            $schoolLibrary = SchoolLibrary::where('school_id', $stationId)->first();
        }

        return view('pages.components.add-acquisition-to-resource', compact(
            'user',
            'resource',
            'subjects',
            'divisionLibraries',
            'regionLibrary',
            'schoolLibrary'
        ));
    }

    /**
     * Store new acquisition batches for an existing print resource.
     * library_id and library_name are now embedded inside each acquisition entry in the JSON.
     */
    public function store(Request $request, string $id)
    {
        $resource = PrintResource::findOrFail($id);

        $validated = $request->validate([
            'acquisitions' => 'required|string',
        ]);

        $this->addAcquisitionService->addAcquisitions(
            $resource,
            $validated['acquisitions']
        );
        return redirect()
            ->route('print-resource.create')
            ->with('success', 'Acquisition successfully added to "' . ($resource->printTitle->title ?? 'resource') . '".')
            ->with('active_tab', 'tab-search');
            }
}
