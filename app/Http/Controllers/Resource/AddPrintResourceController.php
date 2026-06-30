<?php

namespace App\Http\Controllers\Resource;

use App\Models\Author;
use App\Models\PrintResource;
use App\Models\PrintTitle;
use App\Models\PrintType;
use App\Models\SubjectGradeLevel;
use App\Services\Resource\Actions\AddPrintResourceService;
use App\Services\SchoolDashboardCurriculumScopeService;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AddPrintResourceController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $printResourceService;

    public function __construct(
        AddPrintResourceService $printResourceService,
        private readonly SchoolDashboardCurriculumScopeService $curriculumScopeService
    ) {
        $this->middleware('auth');
        $this->printResourceService = $printResourceService;
    }

    public function index()
    {
        return view('pages.add-print-resource', $this->buildViewData());
    }

    public function store(Request $request)
    {
        $validated = $this->validateResourceRequest($request);

        $this->printResourceService->addPrintResource($validated);

        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        if ($level === 3) {
            // Division entries are auto-approved and land on the masterlist immediately
            return redirect()
                ->route('masterlist.index')
                ->with('success', 'Resource has been added to the masterlist.')
                ->with('active_tab', 'tab-masterlist');
        }

        // School users go to My Requests so they can track the pending approval
        return redirect()
            ->route('print-resource.create')
            ->with('success', 'Your resource request has been submitted and is pending approval.')
            ->with('active_tab', 'tab-requests');
    }

    public function edit(string $id)
    {
        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        if ($level == 1) {
            // Only their own pending records — not approved ones, not other users'
            $resource = PrintResource::with(['printTitle.authors', 'type'])
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

        // Pull author names out of the pivot so the blade can render them in the input field
        $data['editingAuthors'] = $resource->printTitle->authors
            ->pluck('author_name')
            ->toArray();

        // subject_grade_level_ids is stored as CSV, so explode it for the checkbox loop
        $data['editingSglIds'] = $resource->subject_grade_level_ids
            ? explode(',', $resource->subject_grade_level_ids)
            : [];

        return view('pages.add-print-resource', $data);
    }

    public function update(Request $request, string $id)
    {
        $user = Auth::user();

        // Re-check ownership here too — don't rely solely on the edit form guard
        $resource = PrintResource::where('id',           $id)
            ->where('station_type', 'school')
            ->where('station_id',   $user->station_id)
            ->where('encoded_by',   $user->id)
            ->where('status',       0)
            ->firstOrFail();

        $validated = $this->validateResourceRequest($request);

        $this->printResourceService->updatePrintResource($resource, $validated);

        return redirect()
            ->route('print-resource.create')
            ->with('success', 'Your request has been updated successfully.')
            ->with('active_tab', 'tab-requests');
    }

    public function destroy(string $id)
    {
        $user = Auth::user();

        $resource = PrintResource::with('printTitle.authors')
            ->where('id',           $id)
            ->where('station_type', 'school')
            ->where('station_id',   $user->station_id)
            ->where('encoded_by',   $user->id)
            ->where('status',       0)
            ->firstOrFail();

        DB::transaction(function () use ($resource) {
            // Save title ID before deleting — relationship is gone after delete()
            $titleId = $resource->print_title_id;

            $resource->delete();

            // Only clean up the title if no other resource still references it
            if (PrintResource::where('print_title_id', $titleId)->doesntExist()) {
                $title = PrintTitle::with('authors')->find($titleId);

                if ($title) {
                    $authorIds = $title->authors->pluck('id')->toArray();

                    // Detach pivot rows first so the author deletes don't hit FK constraints
                    $title->authors()->detach();

                    foreach ($authorIds as $authorId) {
                        // Don't delete the author if they're still linked to other titles
                        $stillUsed = DB::table('author_print_title')
                            ->where('author_id', $authorId)
                            ->exists();

                        if (! $stillUsed) {
                            Author::where('id', $authorId)->delete();
                        }
                    }

                    $title->delete();
                }
            }
        });

        return redirect()
            ->route('print-resource.create')
            ->with('success', 'The resource request has been deleted.')
            ->with('active_tab', 'tab-requests');
    }

    // Shared between index() and edit() so dropdowns are always built the same way
    private function buildViewData(): array
    {
        $user      = Auth::user();
        $level     = $user->userType?->level ?? 0;
        $stationId = $user->station_id;

        $curriculumScope = $this->curriculumScopeService->resolve($level, $stationId);
        $allowedGradeLevelIds = $curriculumScope['grade_levels']->pluck('id')->all();
        $subjectGradeLevels = $this->getSubjectGradeLevels($allowedGradeLevelIds);
        $curriculumMessage = $curriculumScope['message'];

        $printTypes = PrintType::all();

        // Division users add directly — no pending queue, no My Requests tab
        if ($level === 3) {
            $myRequests   = collect();
            $pendingCount = 0;
            $isDivision   = true;
        } else {
            // Only show this user's own submissions, not everyone at the school
            $myRequests = PrintResource::with(['printTitle.authors', 'type'])
                ->where('station_type', 'school')
                ->where('station_id',   $stationId)
                ->where('encoded_by',   $user->id)
                ->latest()
                ->paginate(15);

            // Counts only the current page — fine for a badge indicator
            $pendingCount = $myRequests->where('status', 0)->count();
            $isDivision   = false;
        }

        return compact(
            'user',
            'subjectGradeLevels',
            'printTypes',
            'myRequests',
            'pendingCount',
            'isDivision',
            'level',
            'curriculumMessage'
        );
    }

    private function getSubjectGradeLevels(?array $gradeLevelIds = null)
    {
        return SubjectGradeLevel::query()
            ->select(
                'subject_grade_levels.id as subject_grade_level_id',
                'subject_grade_levels.subject_id',
                'subject_grade_levels.grade_level_id',
                'subjects.subject_name',
                'grade_levels.grade as grade_level',
                'key_stages.code as key_stage',
                'grade_levels.sort_order'
            )
            ->join('subjects', 'subjects.id', '=', 'subject_grade_levels.subject_id')
            ->join('grade_levels', 'grade_levels.id', '=', 'subject_grade_levels.grade_level_id')
            ->join('key_stages', 'key_stages.id', '=', 'grade_levels.key_stage_id')
            ->when($gradeLevelIds !== null, fn ($query) => $query->whereIn('grade_levels.id', $gradeLevelIds))
            ->orderBy('key_stages.sort_order')
            ->orderBy('grade_levels.sort_order')
            ->orderBy('subjects.subject_name')
            ->get();
    }

    // Same rules for both store() and update() — keeps them from drifting apart
    private function validateResourceRequest(Request $request): array
    {
        $user = Auth::user();
        $level = $user->userType?->level ?? 0;
        $curriculumScope = $this->curriculumScopeService->resolve($level, $user->station_id);
        $allowedGradeLevelIds = $curriculumScope['grade_levels']->pluck('id')->all();

        $validated = $request->validate($this->resourceValidationRules($allowedGradeLevelIds));

        // validate() strips the UploadedFile, so re-attach it manually
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image');
        }

        return $validated;
    }

    private function resourceValidationRules(?array $allowedGradeLevelIds = null): array
    {
        $subjectGradeLevelExists = Rule::exists('subject_grade_levels', 'id');

        if ($allowedGradeLevelIds !== null) {
            $subjectGradeLevelExists->where(
                fn ($query) => $query->whereIn('grade_level_id', $allowedGradeLevelIds)
            );
        }

        return [
            'title'                => 'required|string|max:255',
            'authors'              => 'nullable|string',
            'type'                 => 'required|exists:print_types,id',
            'publisher'            => 'nullable|string|max:255',
            'volume'               => 'nullable|string|max:255',
            'edition'              => 'nullable|string|max:255',
            'copyright'            => 'nullable|string|max:255',
            'isbn'                 => 'nullable|string|max:255',
            'pages'                => 'nullable|integer',
            'subject_grade_levels' => 'required|array|min:1',
            'subject_grade_levels.*' => [
                'required',
                'uuid',
                'distinct',
                $subjectGradeLevelExists,
            ],
            'image'                => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ];
    }
}
