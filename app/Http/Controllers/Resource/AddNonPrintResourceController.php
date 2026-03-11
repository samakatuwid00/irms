<?php

namespace App\Http\Controllers\Resource;

use App\Models\NonprintResource;
use App\Models\NonprintTitle;
use App\Models\NonPrintType;
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

    public function index()
    {
        return view('pages.add-nonprint-resource', $this->buildViewData());
    }

    public function store(Request $request)
    {
        $validated = $this->validateResourceRequest($request);

        $this->nonPrintResourceService->addNonPrintResource($validated);

        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        // Division users skip the approval queue, so just send them back to the add tab
        if ($level === 3) {
            return redirect()
                ->route('nonprint-resource.create')
                ->with('success', 'Non-print resource has been added successfully.')
                ->with('active_tab', 'tab-add');
        }

        // School users need to track their submission, so open My Requests
        return redirect()
            ->route('nonprint-resource.create')
            ->with('success', 'Your non-print resource request has been submitted and is pending approval.')
            ->with('active_tab', 'tab-requests');
    }

    public function edit(string $id)
    {
        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        if ($level == 1) {
            // Only let them edit their own pending records — not other users', not approved ones
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

        // subject_grade_level_ids is stored as CSV, so explode it for the checkbox loop
        $data['editingSglIds'] = $resource->subject_grade_level_ids
            ? explode(',', $resource->subject_grade_level_ids)
            : [];

        return view('pages.add-nonprint-resource', $data);
    }

    public function update(Request $request, string $id)
    {
        $user = Auth::user();

        // Re-check ownership here too — don't rely solely on the edit form guard
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

    public function destroy(string $id)
    {
        $user = Auth::user();

        $resource = NonprintResource::with('nonprintTitle')
            ->where('id', $id)
            ->where('station_type', 'school')
            ->where('station_id', $user->station_id)
            ->where('encoded_by', $user->id)
            ->where('status', 0)
            ->firstOrFail();

        DB::transaction(function () use ($resource) {
            // Grab this before deleting — the relationship won't be accessible after
            $titleId = $resource->nonprint_title_id;

            $resource->delete();

            // Clean up the title only if nothing else still points to it
            if (NonprintResource::where('nonprint_title_id', $titleId)->doesntExist()) {
                NonprintTitle::where('id', $titleId)->delete();
            }
        });

        return redirect()
            ->route('nonprint-resource.create')
            ->with('success', 'The resource request has been deleted.')
            ->with('active_tab', 'tab-requests');
    }

    // Shared between index() and edit() so dropdowns are always built the same way
    private function buildViewData(): array
    {
        $user      = Auth::user();
        $level     = $user->userType?->level ?? 0;
        $stationId = $user->station_id;

        // Single query to get the joined subject+grade data needed for the multi-select
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

        // Division users don't submit requests, so My Requests tab doesn't apply to them
        if ($level === 3) {
            $myRequests   = collect();
            $pendingCount = 0;
            $isDivision   = true;
        } else {
            // Only show this user's own submissions, not everyone at the school
            $myRequests = NonprintResource::with(['nonprintTitle', 'type'])
                ->where('station_type', 'school')
                ->where('station_id', $stationId)
                ->where('encoded_by', $user->id)
                ->latest()
                ->paginate(15);

            // Counts only the current page — fine for a badge indicator
            $pendingCount = $myRequests->where('status', 0)->count();
            $isDivision   = false;
        }

        return compact(
            'user',
            'subjectGradeLevels',
            'nonprintTypes',
            'myRequests',
            'pendingCount',
            'isDivision',
            'level'
        );
    }

    // Same rules for both store() and update() — keeps them from drifting apart
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

        // validate() strips the UploadedFile, so re-attach it manually
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image');
        }

        return $validated;
    }
}
