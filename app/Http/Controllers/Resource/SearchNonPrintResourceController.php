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

    /**
     * AJAX: Search non-print titles by keyword.
     * Returns one card per NonprintTitle with all its resource variants and a
     * deduplicated, comma-joined subject/grade-level string.
     */
    public function search(Request $request)
    {
        $query = trim($request->input('q', ''));

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $titleIds = NonprintTitle::where('title', 'ILIKE', '%' . $query . '%')
            ->pluck('id');

        $resources = NonprintResource::with(['nonprintTitle', 'type'])
            ->whereIn('nonprint_title_id', $titleIds)
            ->where('status', 1)
            ->get()
            ->groupBy('uniqueness_hash');

        $results = $resources->map(function ($group) {
            // Use the first resource in the group as the representative
            $resource = $group->first();
            $title    = $resource->nonprintTitle;

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

    /**
     * AJAX: Get full details of a NonprintTitle (all variants) for the view modal.
     */
    public function show(Request $request, string $id)
    {
        $title = NonprintTitle::with([
            'nonprintResources.type',
        ])->findOrFail($id);

        // Filter resources by uniqueness_hash so only the clicked group shows
        $hash      = $request->input('hash');
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

    /**
     * Show the "Add Acquisition" form for an existing non-print resource.
     * All resource fields are pre-filled and read-only; only acquisitions are editable.
     */
    public function addForm(string $id)
    {
        $user     = Auth::user();
        $resource = NonprintResource::with([
            'nonprintTitle',
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

        // Resolve library options based on user level
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

    /**
     * Store new acquisition batches for an existing non-print resource.
     * library_id and library_name are embedded inside each acquisition entry in the JSON.
     */
    public function store(Request $request, string $id)
    {
        $resource = NonprintResource::findOrFail($id);

        $validated = $request->validate([
            'acquisitions' => 'required|string',
        ]);

        $this->addAcquisitionService->addAcquisitions(
            $resource,
            $validated['acquisitions']
        );

        return redirect()
            ->route('nonprint-resource.create')
            ->with('success', 'Acquisition successfully added to "' . ($resource->nonprintTitle->title ?? 'resource') . '".')
            ->with('active_tab', 'tab-search');
    }
}
