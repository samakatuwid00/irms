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

    public function index()
    {
        $user = Auth::user();

        return view('pages.add-print-resource', compact('user'));
    }

    public function search(Request $request)
    {
        $query = trim($request->input('q', ''));

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        // Tokenize: lowercase, split on spaces, strip trailing 's'
        $tokens = collect(preg_split('/\s+/', mb_strtolower($query)))
            ->filter(fn($t) => strlen($t) >= 1)
            ->map(fn($t) => rtrim($t, 's'))
            ->unique()
            ->values();

        // Space-stripped version for concatenated searches e.g. "grammaressential4"
        $nospaceQuery = preg_replace('/\s+/', '', mb_strtolower($query));

        $titleIds = PrintTitle::with('authors')
            ->where(function ($q) use ($tokens, $nospaceQuery) {

                // Each token must match either the title or an author
                foreach ($tokens as $token) {
                    $q->where(function ($inner) use ($token) {
                        $inner->where('title', 'ILIKE', '%' . $token . '%')
                            ->orWhereHas('authors', fn($a) => $a->where('author_name', 'ILIKE', '%' . $token . '%'));
                    });
                }

                // Also match when spaces are stripped from both query and title
                if ($nospaceQuery) {
                    $q->orWhereRaw(
                        "regexp_replace(lower(title), '\\s+', '', 'g') ILIKE ?",
                        ['%' . $nospaceQuery . '%']
                    );
                }
            })
            ->pluck('id');

        // One card per uniqueness_hash, not per title
        $resources = PrintResource::with(['printTitle.authors', 'type'])
            ->whereIn('print_title_id', $titleIds)
            ->where('status', 1)
            ->get()
            ->groupBy('uniqueness_hash');

        $results = $resources->map(function ($group) {
            $resource = $group->first();
            $title    = $resource->printTitle;

            // Union of all SGL IDs across editions in the group
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

            // Use the first available cover in the group
            $cover = $group
                ->map(fn($r) => $r->cover ? asset('storage/' . $r->cover) : null)
                ->filter()
                ->first() ?? asset('assets/images/def.jpg');

            return [
                'id'              => $title->id,
                'resource_id'     => $resource->id,
                'uniqueness_hash' => $resource->uniqueness_hash,
                'title'           => $title->title,
                'authors'         => $title->authors->pluck('author_name')->join(', ') ?: 'No Author',
                'subjects'        => $subjectString ?: 'No subjects assigned',
                'cover'           => $cover,
                'editions'        => $group->map(fn($r) => [
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

    public function show(Request $request, string $id)
    {
        $title = PrintTitle::with([
            'authors',
            'printResources.type',
        ])->findOrFail($id);

        $hash = $request->input('hash');

        // Filter to the clicked hash group so the modal doesn't show unrelated editions
        $resources = $hash
            ? $title->printResources->where('uniqueness_hash', $hash)
            : $title->printResources;

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

    public function addForm(string $id)
    {
        $user     = Auth::user();
        $resource = PrintResource::with([
            'printTitle.authors',
            'type',
        ])->findOrFail($id);

        // Resolve the SGL objects so the blade can display subject/grade labels
        $subjects = collect();
        if ($resource->subject_grade_level_ids) {
            $ids      = explode(',', $resource->subject_grade_level_ids);
            $subjects = SubjectGradeLevel::with(['subject', 'gradeLevel'])
                ->whereIn('id', $ids)
                ->get();
        }

        // Library options vary by user level — default everything to empty/null
        // so the blade doesn't need isset() guards everywhere
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

    public function store(Request $request, string $id)
    {
        $resource = PrintResource::findOrFail($id);

        $validated = $request->validate([
            // JSON string because the number of acquisition entries is dynamic
            // and encoded by the JS layer — named array fields wouldn't work here
            'acquisitions' => 'required|string',
        ]);

        $this->addAcquisitionService->addAcquisitions(
            $resource,
            $validated['acquisitions']
        );

        // Back to the search tab so they can add acquisitions to another resource right away
        return redirect()
            ->route('print-resource.create')
            ->with('success', 'Acquisition successfully added to "' . ($resource->printTitle->title ?? 'resource') . '".')
            ->with('active_tab', 'tab-search');
    }
}
