<?php

namespace App\Http\Controllers\Resource;

use App\Models\PrintResource;
use App\Models\PrintTitle;
use App\Models\SubjectGradeLevel;
use App\Models\PrintType;
use App\Services\PrintResourceVerificationService;
use App\Services\Resource\Actions\AddPrintResourceService;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MasterlistController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $printResourceService;

    public function __construct(
        AddPrintResourceService $printResourceService,
        private PrintResourceVerificationService $verificationService
    )
    {
        $this->middleware('auth');
        $this->printResourceService = $printResourceService;
    }

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

    public function editForm(Request $request, string $id)
    {
        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        abort_unless(in_array($level, [3, 4]), 403, 'Unauthorized access.');

        $resource = PrintResource::with(['printTitle.authors', 'type', 'verifiedBy.userType', 'verificationLogs.user.userType'])
            ->where('id', $id)
            ->where('status', 1)
            ->firstOrFail()
            ->fresh(['printTitle.authors', 'type', 'verifiedBy.userType', 'verificationLogs.user.userType']);

        $subjectGradeLevels = $this->getSubjectGradeLevels();
        $printTypes         = PrintType::all();

        $editingAuthors = $resource->printTitle->authors->pluck('author_name')->toArray();

        $editingSglIds = $resource->subject_grade_level_ids
            ? array_map('trim', explode(',', $resource->subject_grade_level_ids))
            : [];

        // Resolve full cover URL for the edit form image preview — no logic needed in blade
        $resource->cover_url = $resource->cover
            ? asset('storage/' . $resource->cover)
            : asset('assets/images/def.jpg');
        $verificationHistory = $this->verificationService->formatHistory($resource);

        [$masterlist, $requests] = $this->buildTabData($request, $user, $level);

        return response()
            ->view('pages.print-resource-masterlist', compact(
                'resource',
                'subjectGradeLevels',
                'printTypes',
                'editingAuthors',
                'editingSglIds',
                'verificationHistory',
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

        $resource = PrintResource::with(['printTitle.authors', 'type', 'verifiedBy.userType', 'verificationLogs.user.userType'])
            ->where('id', $id)
            ->where('status', 1)
            ->firstOrFail();

        $validated = $this->validateResourceRequest($request, $resource);

        $wasVerified = (bool) $resource->verified;
        $shouldVerify = ! $wasVerified && $request->boolean('verified');
        $comment = $validated['comment'] ?? null;
        $previousMetadata = $this->verificationService->snapshot($resource);

        DB::transaction(function () use ($resource, $validated, $user, $wasVerified, $shouldVerify, $comment, $previousMetadata) {
            if ($resource->printTitle) {
                $resource->printTitle->update(['title' => $validated['title']]);
            }

            if ($shouldVerify) {
                $resource->forceFill([
                    'verified' => true,
                    'verified_by' => $user->id,
                    'verified_at' => now(),
                ])->save();
            }

            $this->printResourceService->updatePrintResource($resource, $validated);

            $resource->refresh()->load(['printTitle.authors', 'type', 'verifiedBy.userType', 'verificationLogs.user.userType']);
            $newMetadata = $this->verificationService->snapshot($resource);

            if ($wasVerified) {
                $actionType = $resource->verified_by === $user->id
                    ? 'first_verifier_update'
                    : 'edit_after_verification';

                $this->verificationService->log(
                    $resource,
                    $user,
                    $actionType,
                    $comment,
                    $previousMetadata,
                    $newMetadata
                );
            } elseif ($shouldVerify) {
                $actionType = $resource->verificationLogs()->exists()
                    ? 're_verification'
                    : 'first_verification';

                $this->verificationService->log(
                    $resource,
                    $user,
                    $actionType,
                    null,
                    $previousMetadata,
                    $newMetadata
                );
            }
        });

        return redirect()
            ->route('masterlist.index', [
                'ml_page'    => $request->input('ml_page'),
                'ml_search'  => $request->input('ml_search'),
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

        $resource = $this->findScopedPendingRequest($id, $user);

        // Delete cover + thumbnail before the DB row to avoid orphaned files on disk
        if ($resource->cover) {
            if (Storage::disk('public')->exists($resource->cover)) {
                Storage::disk('public')->delete($resource->cover);
            }

            $thumbPath = $this->printResourceService->thumbnailPathFromCover($resource->cover);
            if ($thumbPath && Storage::disk('public')->exists($thumbPath)) {
                Storage::disk('public')->delete($thumbPath);
            }
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

        $query = PrintResource::with(['printTitle.authors', 'type', 'verifiedBy.userType', 'verificationLogs.user.userType'])
            ->where('status', 1);

        if (strlen($q) >= 2) {
            $query->whereRaw(
                "search_vector @@ plainto_tsquery('english', ?)",
                [$q]
            );
        }

        $results = $query
            ->orderByDesc('verified')
            ->orderByDesc('verified_at')
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

        $query = PrintResource::with(['printTitle.authors', 'type', 'encodedBy.schoolStation.district.division.region'])
            ->where('status', 0);

        if (strlen($q) >= 2) {
            $query->whereRaw(
                "search_vector @@ plainto_tsquery('english', ?)",
                [$q]
            );
        }

        $results = $query
            ->orderByDesc('verified')
            ->orderByDesc('verified_at')
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

    public function destroy(string $id)
    {
        $user  = Auth::user();
        $level = $user->userType?->level ?? 0;

        abort_unless(in_array($level, [3, 4]), 403, 'Unauthorized access.');

        $resource = PrintResource::where('id', $id)
            ->where('status', 1)
            ->firstOrFail();

        // Delete cover image + thumbnail — leave PrintTitle and Authors untouched
        if ($resource->cover) {
            if (Storage::disk('public')->exists($resource->cover)) {
                Storage::disk('public')->delete($resource->cover);
            }

            $thumbPath = $this->printResourceService->thumbnailPathFromCover($resource->cover);
            if ($thumbPath && Storage::disk('public')->exists($thumbPath)) {
                Storage::disk('public')->delete($thumbPath);
            }
        }

        $resource->delete();

        return redirect()
            ->route('masterlist.index', [
                'ml_page'   => request('ml_page'),
                'ml_search' => request('ml_search'),
                'active_tab' => 'tab-masterlist',
            ])
            ->with('success', 'Resource deleted successfully.');
    }

    // ── PRIVATE HELPERS ──────────────────────────────────────────────────

    // Appends two computed URL properties onto every item in a paginator — no extra queries.
    //   thumb_url → ≤20 KB thumbnail for table row <img> tags
    //   cover_url → full-size image for the view modal data-cover attribute
    // Falls back gracefully: thumbnail → full cover → default placeholder
    private function resolveCoverUrls($paginator, $user = null): void
    {
        $disk = Storage::disk('public');

        $paginator->through(function ($row) use ($disk, $user) {
            $thumbPath = $this->printResourceService->thumbnailPathFromCover($row->cover);

            $row->thumb_url = ($thumbPath && $disk->exists($thumbPath))
                ? asset('storage/' . $thumbPath)
                : ($row->cover ? asset('storage/' . $row->cover) : asset('assets/images/def.jpg'));

            $row->cover_url = $row->cover
                ? asset('storage/' . $row->cover)
                : asset('assets/images/def.jpg');

            $row->verification_history = $this->verificationService->formatHistory($row);
            $this->appendRequestScopeData($row, $user);

            return $row;
        });
    }

    private function findScopedPendingRequest(string $id, $user): PrintResource
    {
        $resource = PrintResource::where('id', $id)
            ->where('status', 0)
            ->firstOrFail();

        abort_unless($this->canManageRequest($resource, $user), 403, 'You can only approve or reject requests within your division.');

        return $resource;
    }

    private function canManageRequest(PrintResource $resource, $user): bool
    {
        return (int) ($user->userType?->level ?? 0) === 3
            && (string) $resource->approver_station === (string) $user->station_id;
    }

    private function appendRequestScopeData(PrintResource $row, $user = null): void
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

    // Shared between index() and editForm() to avoid duplicating the tab queries
    private function buildTabData(Request $request, $user, int $level): array
    {
        $allowedPerPage = [5, 10, 15, 20];

        $mlPerPage = (int) $request->input('ml_per_page', 10);
        if (!in_array($mlPerPage, $allowedPerPage)) {
            $mlPerPage = 10;
        }

        $mlSearch        = trim($request->input('ml_search', ''));
        $masterlistQuery = PrintResource::with(['printTitle.authors', 'type', 'verifiedBy.userType', 'verificationLogs.user.userType'])
            ->where('status', 1);

        if (strlen($mlSearch) >= 2) {
            $masterlistQuery->whereRaw(
                "search_vector @@ plainto_tsquery('english', ?)",
                [$mlSearch]
            );
        }

        $masterlist = $masterlistQuery
            ->orderByDesc('verified')
            ->orderByDesc('verified_at')
            ->orderBy(
                PrintTitle::select('title')
                    ->whereColumn('print_titles.id', 'print_resources.print_title_id')
                    ->limit(1)
            )
            ->paginate($mlPerPage, ['*'], 'ml_page');

        // Append thumb_url + cover_url to every masterlist row
        $this->resolveCoverUrls($masterlist);

        // Region users don't have an approval queue, so $requests stays null
        $requests = null;
        if ($level === 3) {
            $rqPerPage = (int) $request->input('rq_per_page', 10);
            if (!in_array($rqPerPage, $allowedPerPage)) {
                $rqPerPage = 10;
            }

            $rqSearch      = trim($request->input('rq_search', ''));
            $requestsQuery = PrintResource::with(['printTitle.authors', 'type', 'encodedBy.schoolStation.district.division.region'])
                ->where('status', 0);

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
                ->paginate($rqPerPage, ['*'], 'rq_page');

            // Append thumb_url + cover_url to every request row
            $this->resolveCoverUrls($requests, $user);
        }

        return [$masterlist, $requests];
    }

    // Shared JSON shape for both search() and requestSearch()
    private function formatResource(PrintResource $r): array
    {
        $thumbPath = $this->printResourceService->thumbnailPathFromCover($r->cover);
        $thumbUrl  = ($thumbPath && Storage::disk('public')->exists($thumbPath))
            ? asset('storage/' . $thumbPath)
            : ($r->cover ? asset('storage/' . $r->cover) : asset('assets/images/def.jpg'));

        return [
            'id'        => $r->id,
            'cover'     => $r->cover ? asset('storage/' . $r->cover) : asset('assets/images/def.jpg'),
            'thumb'     => $thumbUrl,
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
            'verified'  => (bool) $r->verified,
            'verification_history' => $this->verificationService->formatHistory($r),
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
                'key_stages.code as key_stage',
                'grade_levels.sort_order'
            )
            ->join('subjects',     'subjects.id',     '=', 'subject_grade_levels.subject_id')
            ->join('grade_levels', 'grade_levels.id', '=', 'subject_grade_levels.grade_level_id')
            ->join('key_stages', 'key_stages.id', '=', 'grade_levels.key_stage_id')
            ->orderBy('key_stages.sort_order')
            ->orderBy('grade_levels.sort_order')
            ->orderBy('subjects.subject_name')
            ->get();
    }

    private function validateResourceRequest(Request $request, ?PrintResource $resource = null): array
    {
        $validated = $request->validate($this->resourceValidationRules($resource));

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image');
        }

        return $validated;
    }

    private function resourceValidationRules(?PrintResource $resource = null): array
    {
        return [
            'title'                => 'required|string|max:255',
            'authors'              => 'nullable|string',
            'type'                 => 'required|exists:print_types,id',
            'publisher'            => 'nullable|string|max:255',
            'volume'               => 'nullable|string|max:50',
            'edition'              => 'nullable|string|max:50',
            'copyright'            => 'nullable|integer',
            'isbn'                 => 'nullable|string|max:50',
            'pages'                => 'nullable|integer',
            'subject_grade_levels' => 'required|array|min:1',
            'subject_grade_levels.*' => [
                'required',
                'uuid',
                'distinct',
                Rule::exists('subject_grade_levels', 'id'),
            ],
            'image'                => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'verified'             => 'nullable|boolean',
            'comment'              => [
                $resource?->verified ? 'required' : 'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
