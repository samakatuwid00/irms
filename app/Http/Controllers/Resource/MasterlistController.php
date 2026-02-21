<?php

namespace App\Http\Controllers\Resource;

use App\Models\District;
use App\Models\PrintResource;
use App\Models\PrintTitle;
use App\Models\School;
use App\Models\SubjectGradeLevel;
use App\Models\PrintType;
use App\Services\Resource\Actions\AddPrintResourceService;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MasterlistController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $printResourceService;

    public function __construct(AddPrintResourceService $printResourceService)
    {
        $this->middleware('auth');
        $this->printResourceService = $printResourceService;
    }

    // -----------------------------------------------------------------------
    // index — show the masterlist page (level 3: masterlist + requests tabs;
    //         level 4: masterlist tab only)
    // -----------------------------------------------------------------------

    public function index(Request $request)
    {
        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        abort_unless(in_array($level, [3, 4]), 403, 'Unauthorized access.');

        [$masterlist, $requests] = $this->buildTabData($request, $user, $level);

        return view('pages.print-resource-masterlist', compact(
            'masterlist',
            'requests',
            'level',
            'user'
        ));
    }

    // -----------------------------------------------------------------------
    // editForm — show the edit form for a single approved resource
    // -----------------------------------------------------------------------

    public function editForm(Request $request, string $id)
    {
        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        abort_unless(in_array($level, [3, 4]), 403, 'Unauthorized access.');

        $resource = PrintResource::with(['printTitle.authors', 'type'])
            ->where('id', $id)
            ->where('status', 1)
            ->firstOrFail();

        $subjectGradeLevels = $this->getSubjectGradeLevels();
        $printTypes         = PrintType::all();

        $editingAuthors = $resource->printTitle->authors->pluck('author_name')->toArray();
        $editingSglIds  = $resource->subject_grade_level_ids
            ? explode(',', $resource->subject_grade_level_ids)
            : [];

        // Build tab data so the blade has $masterlist and $requests
        [$masterlist, $requests] = $this->buildTabData($request, $user, $level);

        return view('pages.print-resource-masterlist', compact(
            'resource',
            'subjectGradeLevels',
            'printTypes',
            'editingAuthors',
            'editingSglIds',
            'masterlist',
            'requests',
            'level',
            'user'
        ));
    }

    // -----------------------------------------------------------------------
    // update — save edits to an approved resource (division or region)
    // -----------------------------------------------------------------------

    public function update(Request $request, string $id)
    {
        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        abort_unless(in_array($level, [3, 4]), 403, 'Unauthorized access.');

        $resource = PrintResource::where('id', $id)
            ->where('status', 1)
            ->firstOrFail();

        $validated = $this->validateResourceRequest($request);

        $this->printResourceService->updatePrintResource($resource, $validated);

        return redirect()
            ->route('masterlist.index')
            ->with('success', 'Resource updated successfully.')
            ->with('active_tab', 'tab-masterlist');
    }

    // -----------------------------------------------------------------------
    // approve — approve a school request (division only, level 3)
    // -----------------------------------------------------------------------

    public function approve(string $id)
    {
        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        abort_unless($level === 3, 403, 'Only division accounts can approve requests.');

        // Only approve requests directed to this division
        $resource = PrintResource::where('id', $id)
            ->where('status', 0)
            ->where('approver_station', $user->station_id)
            ->firstOrFail();

        $resource->update(['status' => 1]);

        // Rebuild search vector after approval
        DB::statement('
            UPDATE print_resources
            SET search_vector = build_print_resource_search_vector(id)
            WHERE id = ?
        ', [$resource->id]);

        return redirect()
            ->route('masterlist.index')
            ->with('success', 'Request approved and added to the masterlist.')
            ->with('active_tab', 'tab-requests');
    }

    // -----------------------------------------------------------------------
    // reject — reject (and delete) a school request (division only, level 3)
    //          NOTE: title and authors are intentionally NOT deleted here,
    //          as they may be referenced by other resources.
    // -----------------------------------------------------------------------

    public function reject(string $id)
    {
        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        abort_unless($level === 3, 403, 'Only division accounts can reject requests.');

        $resource = PrintResource::where('id', $id)
            ->where('status', 0)
            ->where('approver_station', $user->station_id)
            ->firstOrFail();

        // Delete cover image if it exists
        if ($resource->cover && \Illuminate\Support\Facades\Storage::disk('public')->exists($resource->cover)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($resource->cover);
        }

        // Delete the resource only — do NOT touch title/authors
        $resource->delete();

        return redirect()
            ->route('masterlist.index')
            ->with('success', 'Request rejected and removed.')
            ->with('active_tab', 'tab-requests');
    }

    // -----------------------------------------------------------------------
    // search (AJAX) — full-text search on masterlist
    // -----------------------------------------------------------------------

    public function search(Request $request)
    {
        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        abort_unless(in_array($level, [3, 4]), 403);

        $q = trim($request->input('q', ''));

        $query = PrintResource::with(['printTitle.authors', 'type'])
            ->where('status', 1);

        if (strlen($q) >= 2) {
            $query->whereRaw(
                "search_vector @@ plainto_tsquery('english', ?)",
                [$q]
            );
        }

        $results = $query->orderByDesc('created_at')->paginate(15);

        return response()->json([
            'data' => $results->map(fn($r) => $this->formatResource($r)),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page'    => $results->lastPage(),
                'total'        => $results->total(),
            ],
        ]);
    }

    // -----------------------------------------------------------------------
    // requestSearch (AJAX) — search pending requests for division
    // -----------------------------------------------------------------------

    public function requestSearch(Request $request)
    {
        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        abort_unless($level === 3, 403);

        $q = trim($request->input('q', ''));

        $query = PrintResource::with(['printTitle.authors', 'type'])
            ->where('status', 0)
            ->where('approver_station', $user->station_id);

        if (strlen($q) >= 2) {
            $query->whereRaw(
                "search_vector @@ plainto_tsquery('english', ?)",
                [$q]
            );
        }

        $results = $query->orderByDesc('created_at')->paginate(15);

        return response()->json([
            'data' => $results->map(fn($r) => $this->formatResource($r)),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page'    => $results->lastPage(),
                'total'        => $results->total(),
            ],
        ]);
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function buildTabData(Request $request, $user, int $level): array
    {
        // Masterlist: all approved resources
        $mlSearch = trim($request->input('ml_search', ''));
        $masterlistQuery = PrintResource::with(['printTitle.authors', 'type'])
            ->where('status', 1);

        if (strlen($mlSearch) >= 2) {
            $masterlistQuery->whereRaw(
                "search_vector @@ plainto_tsquery('english', ?)",
                [$mlSearch]
            );
        }

        $masterlist = $masterlistQuery->orderByDesc('created_at')->paginate(15, ['*'], 'ml_page');

        // Requests: only for division (level 3)
        $requests = null;
        if ($level === 3) {
            $rqSearch = trim($request->input('rq_search', ''));
            $requestsQuery = PrintResource::with(['printTitle.authors', 'type'])
                ->where('status', 0)
                ->where('approver_station', $user->station_id);

            if (strlen($rqSearch) >= 2) {
                $requestsQuery->whereRaw(
                    "search_vector @@ plainto_tsquery('english', ?)",
                    [$rqSearch]
                );
            }

            $requests = $requestsQuery->orderByDesc('created_at')->paginate(15, ['*'], 'rq_page');
        }

        return [$masterlist, $requests];
    }

    private function formatResource(PrintResource $r): array
    {
        return [
            'id'        => $r->id,
            'cover'     => $r->cover ? asset('storage/' . $r->cover) : asset('assets/images/def.jpg'),
            'title'     => $r->printTitle->title ?? '-',
            'authors'   => $r->printTitle->authors->pluck('author_name')->join(', ') ?: '-',
            'type'      => $r->type->type_name ?? '-',
            'publisher' => $r->publisher ?? '-',
            'edition'   => $r->edition ?? '-',
            'copyright' => $r->copyright ?? '-',
            'isbn'      => $r->isbn ?? '-',
            'status'    => $r->status,
            'submitted' => $r->created_at?->format('M d, Y'),
            'subjects'  => $this->formatSubjects($r),
        ];
    }

    private function formatSubjects(PrintResource $r): string
    {
        if (! $r->subject_grade_level_ids) {
            return '-';
        }

        $ids = explode(',', $r->subject_grade_level_ids);

        return SubjectGradeLevel::with(['subject', 'gradeLevel'])
            ->whereIn('id', $ids)
            ->get()
            ->map(fn($s) => ($s->subject->subject_name ?? '') . ' - Grade ' . ($s->gradeLevel->grade ?? ''))
            ->join('; ') ?: '-';
    }

    private function getSubjectGradeLevels()
    {
        return SubjectGradeLevel::query()
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
    }

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
