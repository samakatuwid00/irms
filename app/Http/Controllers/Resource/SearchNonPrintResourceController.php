<?php

namespace App\Http\Controllers\Resource;

use App\Models\DivisionLibrary;
use App\Models\NonprintResource;
use App\Models\NonprintTitle;
use App\Models\RegionLibrary;
use App\Models\SchoolLibrary;
use App\Models\SubjectGradeLevel;
use App\Services\Resource\Actions\AddAcquisitionToExistingNonPrintResourceService;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class SearchNonPrintResourceController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $addAcquisitionService;

    public function __construct(AddAcquisitionToExistingNonPrintResourceService $addAcquisitionService)
    {
        $this->middleware('auth');
        $this->addAcquisitionService = $addAcquisitionService;
    }

    public function search(Request $request)
    {
        $query = trim($request->input('q', ''));

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        // Same FTS approach as the non-print masterlist — searches title, brand,
        // model, code, subjects, type, and other indexed fields together.
        $resources = NonprintResource::with(['nonprintTitle', 'type'])
            ->where('status', 1)
            ->whereRaw(
                "search_vector @@ plainto_tsquery('english', ?)",
                [$query]
            )
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('uniqueness_hash');

        $results = $resources->map(function ($group) {
            $resource = $group->first();
            $title    = $resource->nonprintTitle;

            // Aggregate SGL IDs across all resources in the group — different variants
            // may cover different subjects, so the card should show the union of all of them
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

            // Use the first uploaded cover in the group — not all variants may have one
            $cover = $group
                ->map(fn($r) => $r->cover ? asset('storage/' . $r->cover) : null)
                ->filter()
                ->first() ?? asset('assets/images/def.jpg');

            return [
                'id'              => $title->id,
                'resource_id'     => $resource->id,
                'uniqueness_hash' => $resource->uniqueness_hash,
                'title'           => $title->title,
                'subjects'        => $subjectString ?: 'No subjects assigned',
                'cover'           => $cover,
                'editions'        => $group->map(fn($r) => [
                    'id'      => $r->id,
                    'type'    => $r->type->type_name ?? '-',
                    'brand'   => $r->brand ?? '-',
                    'model'   => $r->model ?? '-',
                    'version' => $r->version ?? '-',
                    'add_url' => route('search-nonprint-resource.add-form', $r->id),
                ])->values(),
            ];
        })->values();

        return response()->json($results);
    }

    public function show(Request $request, string $id)
    {
        $title = NonprintTitle::with([
            'nonprintResources.type',
        ])->findOrFail($id);

        $hash = $request->input('hash');

        // Filter to the clicked hash group so the modal doesn't show unrelated variants
        $resources = $hash
            ? $title->nonprintResources->where('uniqueness_hash', $hash)
            : $title->nonprintResources;

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
            'id'      => $r->id,
            'type'    => $r->type->type_name ?? '-',
            'brand'   => $r->brand ?? '-',
            'code'    => $r->code ?? '-',
            'version' => $r->version ?? '-',
            'model'   => $r->model ?? '-',
            'url'     => $r->url ?? '-',
            'size'    => $r->size ?? '-',
            'cover'   => $r->cover ? asset('storage/' . $r->cover) : asset('assets/images/def.jpg'),
            'add_url' => route('search-nonprint-resource.add-form', $r->id),
        ])->values();

        return response()->json([
            'id'       => $title->id,
            'title'    => $title->title,
            'subjects' => $subjectString ?: 'No subjects assigned',
            'cover'    => $cover,
            'editions' => $editions,
        ]);
    }

    public function addForm(string $id)
    {
        $user     = Auth::user();
        $resource = NonprintResource::with([
            'nonprintTitle',
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

        return view('pages.components.add-acquisition-to-nonprint-resource', compact(
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
        $resource = NonprintResource::findOrFail($id);

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
            ->route('nonprint-resource.create')
            ->with('success', 'Acquisition successfully added to "' . ($resource->nonprintTitle->title ?? 'resource') . '".')
            ->with('active_tab', 'tab-search');
    }
}
