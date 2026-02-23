<?php

namespace App\Http\Controllers\Resource;

use App\Models\DivisionLibrary;
use App\Models\NonprintResource;
use App\Models\NonprintTitle;
use App\Models\NonPrintType;
use App\Models\RegionLibrary;
use App\Models\SchoolLibrary;
use App\Models\SubjectGradeLevel;
use App\Services\Resource\Actions\AddNonPrintResourceService;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AddNonPrintResourceController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $nonPrintResourceService;

    public function __construct(AddNonPrintResourceService $nonPrintResourceService)
    {
        $this->middleware('auth');
        $this->nonPrintResourceService = $nonPrintResourceService;
    }

    // -----------------------------------------------------------------------
    // index — show the page (Search Existing / Manual Add / My Requests tabs)
    //         Division users (level 3) do not see the My Requests tab.
    // -----------------------------------------------------------------------

    public function index()
    {
        return view('pages.add-nonprint-resource', $this->buildViewData());
    }

    // -----------------------------------------------------------------------
    // store — save a new non-print resource
    // -----------------------------------------------------------------------

    public function store(Request $request)
    {
        $validated = $this->validateResourceRequest($request);

        $this->nonPrintResourceService->addNonPrintResource($validated);

        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        if ($level === 3) {
            return redirect()
                ->route('nonprint-resource.create')
                ->with('success', 'Non-print resource has been added successfully.')
                ->with('active_tab', 'tab-add');
        }

        return redirect()
            ->route('nonprint-resource.create')
            ->with('success', 'Your non-print resource request has been submitted and is pending approval.')
            ->with('active_tab', 'tab-requests');
    }

    // -----------------------------------------------------------------------
    // edit — reuse the same page/form, pre-filled with the resource data.
    //        Only level 1 (school) users may edit their own pending requests.
    // -----------------------------------------------------------------------

    public function edit(string $id)
    {
        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        if ($level == 1) {
            $resource = NonprintResource::with(['nonprintTitle', 'type'])
                ->where('id', $id)
                ->where('station_type', 'school')
                ->where('station_id', $user->station_id)
                ->where('encoded_by', $user->id)
                ->where('status', 0)
                ->firstOrFail();
        } else {
            abort(403, 'Unauthorized access.');
        }

        $data = $this->buildViewData();

        $data['editResource'] = $resource;

        $data['editingSglIds'] = $resource->subject_grade_level_ids
            ? explode(',', $resource->subject_grade_level_ids)
            : [];

        return view('pages.add-nonprint-resource', $data);
    }

    // -----------------------------------------------------------------------
    // update — save edits to a pending resource request (school only)
    // -----------------------------------------------------------------------

    public function update(Request $request, string $id)
    {
        $user     = Auth::user();
        $resource = NonprintResource::where('id', $id)
            ->where('station_type', 'school')
            ->where('station_id', $user->station_id)
            ->where('encoded_by', $user->id)
            ->where('status', 0)
            ->firstOrFail();

        $validated = $this->validateResourceRequest($request);

        $this->nonPrintResourceService->updateNonPrintResource($resource, $validated);

        return redirect()
            ->route('nonprint-resource.create')
            ->with('success', 'Your request has been updated successfully.')
            ->with('active_tab', 'tab-requests');
    }

    // -----------------------------------------------------------------------
    // destroy — delete a pending request (school only).
    //           Title is cleaned up only if nothing else references it.
    // -----------------------------------------------------------------------

    public function destroy(string $id)
    {
        $user     = Auth::user();
        $resource = NonprintResource::with('nonprintTitle')
            ->where('id', $id)
            ->where('station_type', 'school')
            ->where('station_id', $user->station_id)
            ->where('encoded_by', $user->id)
            ->where('status', 0)
            ->firstOrFail();

        DB::transaction(function () use ($resource) {
            $titleId = $resource->nonprint_title_id;

            $resource->delete();

            // Clean up title only if nothing else references it
            if (NonprintResource::where('nonprint_title_id', $titleId)->doesntExist()) {
                NonprintTitle::where('id', $titleId)->delete();
            }
        });

        return redirect()
            ->route('nonprint-resource.create')
            ->with('success', 'The resource request has been deleted.')
            ->with('active_tab', 'tab-requests');
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Shared data needed by both index() and edit().
     * For division users (level 3), myRequests and pendingCount are omitted.
     */
    private function buildViewData(): array
    {
        $user      = Auth::user();
        $level     = $user->userType?->level ?? 0;
        $stationId = $user->station_id;

        $subjectGradeLevels = SubjectGradeLevel::query()
            ->select(
                'subject_grade_levels.id as subject_grade_level_id',
                'subject_grade_levels.subject_id',
                'subject_grade_levels.grade_level_id',
                'subjects.subject_name',
                'grade_levels.grade as grade_level',
                'subject_grade_levels.key_stage',
                'grade_levels.sort_order'
            )
            ->join('subjects',     'subjects.id',     '=', 'subject_grade_levels.subject_id')
            ->join('grade_levels', 'grade_levels.id', '=', 'subject_grade_levels.grade_level_id')
            ->orderBy('grade_levels.sort_order')
            ->get();

        $nonprintTypes = NonPrintType::all();

        // Resolve library options based on user level
        $divisionLibraries = collect();
        $regionLibrary     = null;
        $schoolLibrary     = null;

        if ($level === 3) {
            $divisionLibraries = DivisionLibrary::where('division_id', $stationId)
                ->orderBy('library_name')->get();
        } elseif ($level === 4) {
            $regionLibrary = RegionLibrary::where('region_id', $stationId)->first();
        } elseif ($level === 1) {
            $schoolLibrary = SchoolLibrary::where('school_id', $stationId)->first();
        }

        // Division users (level 3) don't have a "My Requests" tab
        if ($level === 3) {
            $myRequests   = collect();
            $pendingCount = 0;
            $isDivision   = true;
        } else {
            $myRequests = NonprintResource::with(['nonprintTitle', 'type'])
                ->where('station_type', 'school')
                ->where('station_id', $stationId)
                ->where('encoded_by', $user->id)
                ->latest()
                ->paginate(15);

            $pendingCount = $myRequests->where('status', 0)->count();
            $isDivision   = false;
        }

        return compact(
            'user',
            'subjectGradeLevels',
            'nonprintTypes',
            'divisionLibraries',
            'regionLibrary',
            'schoolLibrary',
            'myRequests',
            'pendingCount',
            'isDivision',
            'level'
        );
    }

    /**
     * Shared validation rules for store() and update().
     */
    private function validateResourceRequest(Request $request): array
    {
        $validated = $request->validate([
            'title'                => 'required|string|max:255',
            'type'                 => 'required|exists:nonprint_types,id',
            'brand'                => 'nullable|string|max:255',
            'code'                 => 'nullable|string|max:255',
            'version'              => 'nullable|string|max:255',
            'model'                => 'nullable|string|max:255',
            'url'                  => 'nullable|string|max:500',
            'size'                 => 'nullable|string|max:255',
            'library_id'           => 'nullable|string|max:36',
            'subject_grade_levels' => 'nullable|array',
            'image'                => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image');
        }

        return $validated;
    }
}
