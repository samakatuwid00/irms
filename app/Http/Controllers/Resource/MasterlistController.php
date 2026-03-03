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

    public function index(Request $request)
    {
        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        // Only division (3) and region (4) manage the masterlist
        abort_unless(in_array($level, [3, 4]), 403, 'Unauthorized access.');

        [$masterlist, $requests] = $this->buildTabData($request, $user, $level);

        return view('pages.print-resource-masterlist', compact(
            'masterlist',
            'requests',
            'level',
            'user'
        ));
    }

    public function editForm(Request $request, string $id)
    {
        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        abort_unless(in_array($level, [3, 4]), 403, 'Unauthorized access.');

        // fresh() re-fetches after find() to avoid working with stale in-memory data,
        // especially important when the user navigates quickly between edit forms
        $resource = PrintResource::with(['printTitle.authors', 'type'])
            ->where('id', $id)
            ->where('status', 1)
            ->firstOrFail()
            ->fresh(['printTitle.authors', 'type']);

        $subjectGradeLevels = $this->getSubjectGradeLevels();
        $printTypes         = PrintType::all();

        $editingAuthors = $resource->printTitle->authors->pluck('author_name')->toArray();

        // trim() each ID — trailing spaces from the CSV can cause checkbox matching to fail
        $editingSglIds  = $resource->subject_grade_level_ids
            ? array_map('trim', explode(',', $resource->subject_grade_level_ids))
            : [];

        [$masterlist, $requests] = $this->buildTabData($request, $user, $level);

        // no-store prevents bfcache from restoring a stale edit form when the user
        // navigates back — without this, the previous resource's data bleeds through
        return response()
            ->view('pages.print-resource-masterlist', compact(
                'resource',
                'subjectGradeLevels',
                'printTypes',
                'editingAuthors',
                'editingSglIds',
                'masterlist',
                'requests',
                'level',
                'user'
            ))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    public function update(Request $request, string $id)
    {
        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        abort_unless(in_array($level, [3, 4]), 403, 'Unauthorized access.');

        $resource = PrintResource::with(['printTitle.authors'])
            ->where('id', $id)
            ->where('status', 1)
            ->firstOrFail();

        $validated = $this->validateResourceRequest($request);

        // Update the title row separately — the service only handles PrintResource fields,
        // so skipping this would leave the title text out of sync
        if ($resource->printTitle) {
            $resource->printTitle->update(['title' => $validated['title']]);
        }

        $this->printResourceService->updatePrintResource($resource, $validated);

        return redirect()
                ->route('masterlist.index', [
                    'ml_page' => $request->input('ml_page'),
                    'ml_search' => $request->input('ml_search'),
                    'active_tab' => 'tab-masterlist',
                ])
                ->with('success', 'Resource updated successfully.');
            }

    public function approve(string $id)
    {
        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        abort_unless($level === 3, 403, 'Only division accounts can approve requests.');

        // approver_station ensures a division can only approve its own queue
        $resource = PrintResource::where('id', $id)
            ->where('status', 0)
            ->where('approver_station', $user->station_id)
            ->firstOrFail();

        $resource->update(['status' => 1]);

        // Rebuild the search vector immediately so the record is searchable right away
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

    public function reject(string $id)
    {
        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        abort_unless($level === 3, 403, 'Only division accounts can reject requests.');

        $resource = PrintResource::where('id', $id)
            ->where('status', 0)
            ->where('approver_station', $user->station_id)
            ->firstOrFail();

        // Delete the cover file before the DB row — otherwise the file is orphaned on disk
        if ($resource->cover && \Illuminate\Support\Facades\Storage::disk('public')->exists($resource->cover)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($resource->cover);
        }

        $resource->delete();

        return redirect()
            ->route('masterlist.index')
            ->with('success', 'Request rejected and removed.')
            ->with('active_tab', 'tab-requests');
    }

    public function search(Request $request)
    {
        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        abort_unless(in_array($level, [3, 4]), 403);

        $q = trim($request->input('q', ''));

        $query = PrintResource::with(['printTitle.authors', 'type'])
            ->where('status', 1);

        // Skip the FTS filter for short queries — a single char would match almost everything
        if (strlen($q) >= 2) {
            $query->whereRaw(
                "search_vector @@ plainto_tsquery('english', ?)",
                [$q]
            );
        }

        // Correlated subquery for ordering avoids a JOIN on every search request
        $results = $query
            ->orderBy(
                PrintTitle::select('title')
                    ->whereColumn('print_titles.id', 'print_resources.print_title_id')
                    ->limit(1)
            )
            ->paginate(15);

        return response()->json([
            'data' => $results->map(fn($r) => $this->formatResource($r)),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page'    => $results->lastPage(),
                'total'        => $results->total(),
            ],
        ]);
    }

    public function requestSearch(Request $request)
    {
        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        abort_unless($level === 3, 403);

        $q = trim($request->input('q', ''));

        // Scope to this division's queue only — don't let them see other divisions' requests
        $query = PrintResource::with(['printTitle.authors', 'type'])
            ->where('status', 0)
            ->where('approver_station', $user->station_id);

        if (strlen($q) >= 2) {
            $query->whereRaw(
                "search_vector @@ plainto_tsquery('english', ?)",
                [$q]
            );
        }

        $results = $query
            ->orderBy(
                PrintTitle::select('title')
                    ->whereColumn('print_titles.id', 'print_resources.print_title_id')
                    ->limit(1)
            )
            ->paginate(15);

        return response()->json([
            'data' => $results->map(fn($r) => $this->formatResource($r)),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page'    => $results->lastPage(),
                'total'        => $results->total(),
            ],
        ]);
    }

    // Shared between index() and editForm() to avoid duplicating the tab queries
    private function buildTabData(Request $request, $user, int $level): array
    {
        $mlSearch        = trim($request->input('ml_search', ''));
        $masterlistQuery = PrintResource::with(['printTitle.authors', 'type'])
            ->where('status', 1);

        if (strlen($mlSearch) >= 2) {
            $masterlistQuery->whereRaw(
                "search_vector @@ plainto_tsquery('english', ?)",
                [$mlSearch]
            );
        }

        // Named paginator 'ml_page' avoids colliding with 'rq_page' on the same page
        $masterlist = $masterlistQuery
            ->orderBy(
                PrintTitle::select('title')
                    ->whereColumn('print_titles.id', 'print_resources.print_title_id')
                    ->limit(1)
            )
            ->paginate(15, ['*'], 'ml_page');

        // Region users don't have an approval queue, so $requests stays null
        $requests = null;
        if ($level === 3) {
            $rqSearch      = trim($request->input('rq_search', ''));
            $requestsQuery = PrintResource::with(['printTitle.authors', 'type'])
                ->where('status', 0)
                ->where('approver_station', $user->station_id);

            if (strlen($rqSearch) >= 2) {
                $requestsQuery->whereRaw(
                    "search_vector @@ plainto_tsquery('english', ?)",
                    [$rqSearch]
                );
            }

            $requests = $requestsQuery
                ->orderBy(
                    PrintTitle::select('title')
                        ->whereColumn('print_titles.id', 'print_resources.print_title_id')
                        ->limit(1)
                )
                ->paginate(15, ['*'], 'rq_page');
        }

        return [$masterlist, $requests];
    }

    // Shared JSON shape for both search() and requestSearch()
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

    // Same rules for both store() and update() — keeps them from drifting apart
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

        // validate() strips the UploadedFile, so re-attach it manually
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image');
        }

        return $validated;
    }
}
