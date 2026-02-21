<?php

namespace App\Http\Controllers\Resource;

use App\Models\Author;
use App\Models\PrintResource;
use App\Models\PrintTitle;
use App\Models\PrintType;
use App\Models\SubjectGradeLevel;
use App\Services\Resource\Actions\AddPrintResourceService;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AddPrintResourceController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $printResourceService;

    public function __construct(AddPrintResourceService $printResourceService)
    {
        $this->middleware('auth');
        $this->printResourceService = $printResourceService;
    }

    // -----------------------------------------------------------------------
    // index — show the page (Search Existing / Manual Add / My Requests tabs)
    // -----------------------------------------------------------------------

    public function index()
    {
        return view('pages.add-print-resource', $this->buildViewData());
    }

    // -----------------------------------------------------------------------
    // store — save a new pending print-resource request
    // -----------------------------------------------------------------------

    public function store(Request $request)
    {
        $validated = $this->validateResourceRequest($request);

        $this->printResourceService->addPrintResource($validated);

        return redirect()
            ->route('print-resource.create')
            ->with('success', 'Your resource request has been submitted and is pending approval.')
            ->with('active_tab', 'tab-requests');
    }

    // -----------------------------------------------------------------------
    // edit — reuse the same page/form, just pass the resource to pre-fill
    // -----------------------------------------------------------------------

    public function edit(string $id)
    {
        $user     = Auth::user();
        $resource = PrintResource::with(['printTitle.authors', 'type'])
            ->where('id',           $id)
            ->where('station_type', 'school')
            ->where('station_id',   $user->station_id)
            ->where('encoded_by',   $user->id)
            ->where('status',       0)   // only pending requests can be edited
            ->firstOrFail();

        $data = $this->buildViewData();

        // Extra variables the blade uses to pre-fill the form
        $data['editResource']  = $resource;
        $data['editingAuthors'] = $resource->printTitle->authors->pluck('author_name')->toArray();
        $data['editingSglIds']  = $resource->subject_grade_level_ids
            ? explode(',', $resource->subject_grade_level_ids)
            : [];

        return view('pages.add-print-resource', $data);
    }

    // -----------------------------------------------------------------------
    // update — save edits to a pending resource request
    // -----------------------------------------------------------------------

    public function update(Request $request, string $id)
    {
        $user     = Auth::user();
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

    // -----------------------------------------------------------------------
    // destroy — delete a pending request and clean up orphaned title/authors
    // -----------------------------------------------------------------------

    public function destroy(string $id)
    {
        $user     = Auth::user();
        $resource = PrintResource::with('printTitle.authors')
            ->where('id',           $id)
            ->where('station_type', 'school')
            ->where('station_id',   $user->station_id)
            ->where('encoded_by',   $user->id)
            ->firstOrFail();

        DB::transaction(function () use ($resource) {
            $titleId = $resource->print_title_id;

            $resource->delete();

            // Clean up title + authors only if nothing else references the title
            if (PrintResource::where('print_title_id', $titleId)->doesntExist()) {
                $title = PrintTitle::with('authors')->find($titleId);

                if ($title) {
                    $authorIds = $title->authors->pluck('id')->toArray();
                    $title->authors()->detach();

                    foreach ($authorIds as $authorId) {
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

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Shared data needed by both index() and edit().
     * Returns the array passed to the view.
     */
    private function buildViewData(): array
    {
        $user      = Auth::user();
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
            ->join('grade_levels',  'grade_levels.id',  '=', 'subject_grade_levels.grade_level_id')
            ->orderBy('grade_levels.sort_order')
            ->get();

        $printTypes = PrintType::all();

        $myRequests = PrintResource::with(['printTitle.authors', 'type'])
            ->where('station_type', 'school')
            ->where('station_id',   $stationId)
            ->where('encoded_by',   $user->id)
            ->latest()
            ->paginate(15);

        $pendingCount = $myRequests->where('status', 0)->count();

        return compact('user', 'subjectGradeLevels', 'printTypes', 'myRequests', 'pendingCount');
    }

    /**
     * Shared validation rules for store() and update().
     */
    private function validateResourceRequest(Request $request): array
    {
        $validated = $request->validate([
            'title'                => 'required|string|max:255',
            'authors'              => 'nullable|string',
            'type'                 => 'required|exists:print_types,id',
            'publisher'            => 'nullable|string|max:255',
            'volume'               => 'nullable|string|max:50',
            'edition'              => 'nullable|string|max:50',
            'copyright'            => 'nullable|integer',
            'isbn'                 => 'nullable|string|max:50',
            'pages'                => 'nullable|integer',
            'subject_grade_levels' => 'nullable|array',
            'image'                => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image');
        }

        return $validated;
    }
}
