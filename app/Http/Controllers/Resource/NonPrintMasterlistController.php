<?php

namespace App\Http\Controllers\Resource;

use App\Models\NonprintResource;
use App\Models\NonprintTitle;
use App\Models\NonprintType;
use App\Models\SubjectGradeLevel;
use App\Services\Resource\Actions\AddNonPrintResourceService;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class NonPrintMasterlistController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $nonPrintResourceService;

    public function __construct(AddNonPrintResourceService $nonPrintResourceService)
    {
        $this->middleware('auth');
        $this->nonPrintResourceService = $nonPrintResourceService;
    }

    public function index(Request $request)
    {
        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        // Only division (3) and region (4) manage the masterlist
        abort_unless(in_array($level, [3, 4]), 403, 'Unauthorized access.');

        [$masterlist, $requests] = $this->buildTabData($request, $user, $level);

        return view('pages.nonprint-resource-masterlist', compact(
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

        $resource = NonprintResource::with(['nonprintTitle', 'type'])
            ->where('id', $id)
            ->where('status', 1)
            ->firstOrFail();

        $nonprintTypes      = NonprintType::all();
        $subjectGradeLevels = $this->getSubjectGradeLevels();

        // subject_grade_level_ids is stored as CSV, so explode it for the checkbox loop
        $editingSglIds = $resource->subject_grade_level_ids
            ? explode(',', $resource->subject_grade_level_ids)
            : [];

        [$masterlist, $requests] = $this->buildTabData($request, $user, $level);

        return view('pages.nonprint-resource-masterlist', compact(
            'resource',
            'nonprintTypes',
            'subjectGradeLevels',
            'editingSglIds',
            'masterlist',
            'requests',
            'level',
            'user'
        ));
    }

    public function update(Request $request, string $id)
    {
        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        abort_unless(in_array($level, [3, 4]), 403, 'Unauthorized access.');

        $resource = NonprintResource::where('id', $id)
            ->where('status', 1)
            ->firstOrFail();

        $validated = $this->validateResourceRequest($request);

        $this->nonPrintResourceService->updateNonPrintResource($resource, $validated);

        return redirect()
                ->route('nonprint-masterlist.index', [
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

        // approver_station scopes this to the division's own queue
        $resource = NonprintResource::where('id', $id)
            ->where('status', 0)
            ->where('approver_station', $user->station_id)
            ->firstOrFail();

        $resource->update(['status' => 1]);

        // Rebuild the search vector immediately so the record is searchable right away
        DB::statement('
            UPDATE nonprint_resources
            SET search_vector = build_nonprint_resource_search_vector(id)
            WHERE id = ?
        ', [$resource->id]);

        return redirect()
            ->route('nonprint-masterlist.index')
            ->with('success', 'Request approved and added to the masterlist.')
            ->with('active_tab', 'tab-requests');
    }

    public function reject(string $id)
    {
        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        abort_unless($level === 3, 403, 'Only division accounts can reject requests.');

        $resource = NonprintResource::where('id', $id)
            ->where('status', 0)
            ->where('approver_station', $user->station_id)
            ->firstOrFail();

        // Delete the cover file + thumbnail before the DB row — otherwise they're orphaned on disk
        if ($resource->cover) {
            if (Storage::disk('public')->exists($resource->cover)) {
                Storage::disk('public')->delete($resource->cover);
            }

            $thumbPath = $this->nonPrintResourceService->thumbnailPathFromCover($resource->cover);
            if ($thumbPath && Storage::disk('public')->exists($thumbPath)) {
                Storage::disk('public')->delete($thumbPath);
            }
        }

        // Only delete the resource — intentionally leaving the title intact because
        // it may still be referenced by other approved resources
        $resource->delete();

        return redirect()
            ->route('nonprint-masterlist.index')
            ->with('success', 'Request rejected and removed.')
            ->with('active_tab', 'tab-requests');
    }

    public function search(Request $request)
    {
        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        abort_unless(in_array($level, [3, 4]), 403);

        $q = trim($request->input('q', ''));

        $query = NonprintResource::with(['nonprintTitle', 'type'])
            ->where('status', 1);

        // Skip FTS filter for short queries — a single char matches almost everything
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

    public function requestSearch(Request $request)
    {
        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        abort_unless($level === 3, 403);

        $q = trim($request->input('q', ''));

        // Scope to this division's queue only
        $query = NonprintResource::with(['nonprintTitle', 'type'])
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

    // Shared between index() and editForm() to avoid duplicating the tab queries
    private function buildTabData(Request $request, $user, int $level): array
    {
        $allowedPerPage = [10, 25, 50, 100];

        $mlPerPage = (int) $request->input('ml_per_page', 10);
        if (!in_array($mlPerPage, $allowedPerPage)) {
            $mlPerPage = 10;
        }

        $mlSearch = trim($request->input('ml_search', ''));
        $masterlistQuery = NonprintResource::with(['nonprintTitle', 'type'])
            ->where('status', 1);

        if (strlen($mlSearch) >= 2) {
            $masterlistQuery->whereRaw(
                "search_vector @@ plainto_tsquery('english', ?)",
                [$mlSearch]
            );
        }

        // Named paginator 'ml_page' avoids colliding with 'rq_page' on the same page
        $masterlist = $masterlistQuery->orderByDesc('created_at')->paginate($mlPerPage, ['*'], 'ml_page');

        // Append thumb_url + cover_url to every masterlist row
        $this->resolveCoverUrls($masterlist);

        // Region users don't have a pending queue, so $requests stays null
        $requests = null;
        if ($level === 3) {
            $rqPerPage = (int) $request->input('rq_per_page', 10);
            if (!in_array($rqPerPage, $allowedPerPage)) {
                $rqPerPage = 10;
            }

            $rqSearch = trim($request->input('rq_search', ''));
            $requestsQuery = NonprintResource::with(['nonprintTitle', 'type'])
                ->where('status', 0)
                ->where('approver_station', $user->station_id);

            if (strlen($rqSearch) >= 2) {
                $requestsQuery->whereRaw(
                    "search_vector @@ plainto_tsquery('english', ?)",
                    [$rqSearch]
                );
            }

            $requests = $requestsQuery->orderByDesc('created_at')->paginate($rqPerPage, ['*'], 'rq_page');

            // Append thumb_url + cover_url to every request row
            $this->resolveCoverUrls($requests);
        }

        return [$masterlist, $requests];
    }

    // Appends two computed URL properties onto every item in a paginator — no extra queries.
    //   thumb_url → ≤20 KB thumbnail for table row <img> tags
    //   cover_url → full-size image for the view modal data-cover attribute
    // Falls back gracefully: thumbnail → full cover → default placeholder
    private function resolveCoverUrls($paginator): void
    {
        $disk = Storage::disk('public');

        $paginator->through(function ($row) use ($disk) {
            $thumbPath = $this->nonPrintResourceService->thumbnailPathFromCover($row->cover);

            $row->thumb_url = ($thumbPath && $disk->exists($thumbPath))
                ? asset('storage/' . $thumbPath)
                : ($row->cover ? asset('storage/' . $row->cover) : asset('assets/images/def.jpg'));

            $row->cover_url = $row->cover
                ? asset('storage/' . $row->cover)
                : asset('assets/images/def.jpg');

            return $row;
        });
    }

    // Shared JSON shape for both search() and requestSearch()
    private function formatResource(NonprintResource $r): array
    {
        $thumbPath = $this->nonPrintResourceService->thumbnailPathFromCover($r->cover);
        $thumbUrl  = ($thumbPath && Storage::disk('public')->exists($thumbPath))
            ? asset('storage/' . $thumbPath)
            : ($r->cover ? asset('storage/' . $r->cover) : asset('assets/images/def.jpg'));

        return [
            'id'        => $r->id,
            'cover'     => $r->cover ? asset('storage/' . $r->cover) : asset('assets/images/def.jpg'),
            'thumb'     => $thumbUrl,
            'title'     => $r->nonprintTitle->title ?? '-',
            'type'      => $r->type->type_name ?? '-',
            'brand'     => $r->brand ?? '-',
            'code'      => $r->code ?? '-',
            'version'   => $r->version ?? '-',
            'model'     => $r->model ?? '-',
            'url'       => $r->url ?? '-',
            'size'      => $r->size ?? '-',
            'status'    => $r->status,
            'submitted' => $r->created_at?->format('M d, Y'),
            'subjects'  => $this->formatSubjects($r),
        ];
    }

    private function formatSubjects(NonprintResource $r): string
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
            'type'                 => 'required|exists:nonprint_types,id',
            'brand'                => 'nullable|string|max:255',
            'code'                 => 'nullable|string|max:255',
            'version'              => 'nullable|string|max:255',
            'model'                => 'nullable|string|max:255',
            'url'                  => 'nullable|string|max:500',
            'size'                 => 'nullable|string|max:255',
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