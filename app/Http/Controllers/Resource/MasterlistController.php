<?php

namespace App\Http\Controllers\Resource;

use App\Models\PrintResource;
use App\Models\PrintTitle;
use App\Models\SubjectGradeLevel;
use App\Models\PrintType;
use App\Services\Resource\Actions\AddPrintResourceService;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

        $resource = PrintResource::with(['printTitle.authors', 'type'])
            ->where('id', $id)
            ->where('status', 1)
            ->firstOrFail()
            ->fresh(['printTitle.authors', 'type']);

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

        [$masterlist, $requests] = $this->buildTabData($request, $user, $level);

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

        if ($resource->printTitle) {
            $resource->printTitle->update(['title' => $validated['title']]);
        }

        $this->printResourceService->updatePrintResource($resource, $validated);

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

        $resource = PrintResource::where('id', $id)
            ->where('status', 0)
            ->where('approver_station', $user->station_id)
            ->firstOrFail();

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

        $resource = PrintResource::where('id', $id)
            ->where('status', 0)
            ->where('approver_station', $user->station_id)
            ->firstOrFail();

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

        $query = PrintResource::with(['printTitle.authors', 'type'])
            ->where('status', 1);

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
    private function resolveCoverUrls($paginator): void
    {
        $disk = Storage::disk('public');

        $paginator->through(function ($row) use ($disk) {
            $thumbPath = $this->printResourceService->thumbnailPathFromCover($row->cover);

            $row->thumb_url = ($thumbPath && $disk->exists($thumbPath))
                ? asset('storage/' . $thumbPath)
                : ($row->cover ? asset('storage/' . $row->cover) : asset('assets/images/def.jpg'));

            $row->cover_url = $row->cover
                ? asset('storage/' . $row->cover)
                : asset('assets/images/def.jpg');

            return $row;
        });
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

        $masterlist = $masterlistQuery
            ->orderBy(
                PrintTitle::select('title')
                    ->whereColumn('print_titles.id', 'print_resources.print_title_id')
                    ->limit(1)
            )
            ->paginate(15, ['*'], 'ml_page');

        // Append thumb_url + cover_url to every masterlist row
        $this->resolveCoverUrls($masterlist);

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

            // Append thumb_url + cover_url to every request row
            $this->resolveCoverUrls($requests);
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