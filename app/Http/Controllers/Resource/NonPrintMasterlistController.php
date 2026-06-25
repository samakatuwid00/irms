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

        $resource = $this->findScopedPendingRequest($id, $user);

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

        $resource = $this->findScopedPendingRequest($id, $user);

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

        $query = NonprintResource::with(['nonprintTitle', 'type', 'encodedBy.schoolStation.district.division.region'])
            ->where('status', 0);

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
            $requestsQuery = NonprintResource::with(['nonprintTitle', 'type', 'encodedBy.schoolStation.district.division.region'])
                ->where('status', 0);

            if (strlen($rqSearch) >= 2) {
                $requestsQuery->whereRaw(
                    "search_vector @@ plainto_tsquery('english', ?)",
                    [$rqSearch]
                );
            }

            $requests = $requestsQuery->orderByDesc('created_at')->paginate($rqPerPage, ['*'], 'rq_page');

            // Append thumb_url + cover_url to every request row
            $this->resolveCoverUrls($requests, $user);
        }

        return [$masterlist, $requests];
    }

    // Appends two computed URL properties onto every item in a paginator — no extra queries.
    //   thumb_url → ≤20 KB thumbnail for table row <img> tags
    //   cover_url → full-size image for the view modal data-cover attribute
    // Falls back gracefully: thumbnail → full cover → default placeholder
    private function resolveCoverUrls($paginator, $user = null): void
    {
        $disk = Storage::disk('public');

        $paginator->through(function ($row) use ($disk, $user) {
            $thumbPath = $this->nonPrintResourceService->thumbnailPathFromCover($row->cover);

            $row->thumb_url = ($thumbPath && $disk->exists($thumbPath))
                ? asset('storage/' . $thumbPath)
                : ($row->cover ? asset('storage/' . $row->cover) : asset('assets/images/def.jpg'));

            $row->cover_url = $row->cover
                ? asset('storage/' . $row->cover)
                : asset('assets/images/def.jpg');

            $this->appendRequestScopeData($row, $user);

            return $row;
        });
    }

    private function findScopedPendingRequest(string $id, $user): NonprintResource
    {
        $resource = NonprintResource::where('id', $id)
            ->where('status', 0)
            ->firstOrFail();

        abort_unless($this->canManageRequest($resource, $user), 403, 'You can only approve or reject requests within your division.');

        return $resource;
    }

    private function canManageRequest(NonprintResource $resource, $user): bool
    {
        return (int) ($user->userType?->level ?? 0) === 3
            && (string) $resource->approver_station === (string) $user->station_id;
    }

    private function appendRequestScopeData(NonprintResource $row, $user = null): void
    {
        if ((int) $row->status !== 0) {
            return;
        }

        $school = $row->encodedBy?->schoolStation;
        $district = $school?->district;
        $division = $district?->division;
        $region = $division?->region;

        $row->request_region_name = $region?->region_name ?? '-';
        $row->request_division_name = $division?->division_name ?? '-';
        $row->request_district_name = $district?->district_name ?? '-';
        $row->request_school_name = $school?->school_name ?? '-';
        $row->can_manage_request = $user ? $this->canManageRequest($row, $user) : false;
        $row->request_scope_label = $row->can_manage_request ? 'Within Your Division' : 'Outside Your Division';
        $row->request_scope_class = $row->can_manage_request
            ? 'bg-green-50 text-green-700 border-green-200'
            : 'bg-gray-100 text-gray-600 border-gray-200';
        $row->request_scope_tooltip = $row->can_manage_request
            ? 'You can approve or reject this request.'
            : 'You can view this request but cannot approve or reject it because it belongs to another division.';
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
