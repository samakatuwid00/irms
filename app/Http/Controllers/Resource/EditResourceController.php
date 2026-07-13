<?php

namespace App\Http\Controllers\Resource;

use App\Models\DivisionLibrary;
use App\Models\NonprintResource;
use App\Models\NonPrintType;
use App\Models\PrintResource;
use App\Models\PrintType;
use App\Models\RegionLibrary;
use App\Models\SchoolLibrary;
use App\Models\SubjectGradeLevel;
use App\Services\Resource\Actions\EditNonPrintResourceService;
use App\Services\Resource\Actions\EditPrintResourceService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class EditResourceController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected EditPrintResourceService $printResourceService;
    protected EditNonPrintResourceService $nonPrintResourceService;

    public function __construct(
        EditPrintResourceService    $printResourceService,
        EditNonPrintResourceService $nonPrintResourceService
    ) {
        $this->middleware('auth');
        $this->printResourceService    = $printResourceService;
        $this->nonPrintResourceService = $nonPrintResourceService;
    }

    public function index($id)
    {
        $user       = Auth::user();
        $station_id = $user->station_id;
        $userLevel  = $user->userType?->level;

        $printResource    = null;
        $nonprintResource = null;

        $isPrint = PrintResource::where('id', $id)->exists();

        if ($isPrint) {
            $printResource = PrintResource::with([
                'printTitle.authors',
                'type',
                'printAcquisitions.printMasterlists',
            ])->find($id);

            abort_unless(
                $printResource && $this->canEditPrintResource($printResource),
                403,
                'Unauthorized access.'
            );
        } else {
            $nonprintResource = NonprintResource::with([
                'nonprintTitle',
                'type',
                'nonprintAcquisitions.nonprintMasterlists',
            ])->find($id);
        }

        $divisionLibraries = collect();
        $schoolLibrary     = null;
        $regionLibrary     = null;

        if ($userLevel === 3) {
            $divisionLibraries = DivisionLibrary::where('division_id', $station_id)
                ->orderBy('library_name')
                ->get();
        } elseif ($userLevel === 1) {
            $schoolLibrary = SchoolLibrary::where('school_id', $station_id)
                ->orderBy('library_name')
                ->first();
        } elseif ($userLevel === 4) {
            $regionLibrary = RegionLibrary::where('region_id', $station_id)
                ->orderBy('library_name')
                ->first();
        }

        $subjectGradeLevels = collect();

        if ($printResource) {
            $subjectGradeLevels = SubjectGradeLevel::query()
                ->select(
                    'subject_grade_levels.id as subject_grade_level_id',
                    'subject_grade_levels.subject_id',
                    'subject_grade_levels.grade_level_id',
                    'subjects.subject_name',
                    'grade_levels.grade as grade_level',
                    'key_stages.name as key_stage',
                    'grade_levels.sort_order'
                )
                ->join('subjects',     'subjects.id',     '=', 'subject_grade_levels.subject_id')
                ->join('grade_levels', 'grade_levels.id', '=', 'subject_grade_levels.grade_level_id')
                ->join('key_stages', 'key_stages.id', '=', 'grade_levels.key_stage_id')
                ->orderBy('grade_levels.sort_order')
                ->get();
        }

        $printTypes    = $printResource    ? PrintType::all()    : collect();
        $nonprintTypes = $nonprintResource ? NonPrintType::all() : collect();

        $selectedSubjectGradeLevels = $printResource
            ? ($printResource->subject_grade_level_ids
                ? explode(',', $printResource->subject_grade_level_ids)
                : [])
            : [];

        $selectedSubjectGradeLevelsNP = $nonprintResource
            ? ($nonprintResource->subject_grade_level_ids
                ? explode(',', $nonprintResource->subject_grade_level_ids)
                : [])
            : [];

        return view('pages.edit-resources', compact(
            'user',
            'printResource',
            'nonprintResource',
            'subjectGradeLevels',
            'printTypes',
            'nonprintTypes',
            'divisionLibraries',
            'regionLibrary',
            'schoolLibrary',
            'selectedSubjectGradeLevels',
            'selectedSubjectGradeLevelsNP'
        ));
    }

    public function updatePrintResource(Request $request, $id)
    {
        $printResource = PrintResource::with('printAcquisitions')->findOrFail($id);

        abort_unless(
            $this->canEditPrintResource($printResource),
            403,
            'Unauthorized access.'
        );

        $validated = $request->validate([
            // library_id is required — an acquisition must always be assigned somewhere
            'library_id'   => 'required|string|max:36',
            'acquisitions' => 'nullable|string',
        ]);

        // Default to empty array so the service always gets valid JSON
        $validated['acquisitions'] = $validated['acquisitions'] ?? '[]';

        $this->printResourceService->updatePrintResource($id, $validated);

        return redirect()
            ->route('print-resources')
            ->with('success', 'Acquisitions updated successfully.');
    }

    public function updateNonPrintResource(Request $request, $id)
    {
        $validated = $request->validate([
            'library_id'   => 'nullable|string|max:36',
            'acquisitions' => 'nullable|string',
        ]);

        // Default to empty array so the service always gets valid JSON
        $validated['acquisitions'] = $validated['acquisitions'] ?? '[]';

        $result = $this->nonPrintResourceService->updateNonPrintResource($id, $validated);

        // Service deletes the resource when all quantities hit zero — redirect to
        // the list instead of back to the edit page which would 404
        if ($result['deleted']) {
            return redirect()
                ->route('nonprint-resources')
                ->with('success', 'Non-print resource and all acquisitions have been deleted (no acquisitions with quantity > 0).');
        }

        return redirect()
            ->route('edit-resource', ['id' => $id, 'tab' => 'nonprint'])
            ->with('success', 'Acquisitions updated successfully.');
    }

    private function canEditPrintResource(PrintResource $printResource): bool
    {
        $libraryIds = $this->editablePrintLibraryIds();

        if (empty($libraryIds)) {
            return false;
        }

        $libraryIds = array_map('strval', $libraryIds);

        return $printResource->printAcquisitions
            ->contains(fn ($acquisition) => in_array((string) $acquisition->library_id, $libraryIds, true));
    }

    private function editablePrintLibraryIds(): array
    {
        $user = Auth::user();
        $level = (int) ($user->userType?->level ?? 0);

        if ($level === 1) {
            return SchoolLibrary::where('school_id', $user->station_id)
                ->pluck('id')
                ->all();
        }

        if ($level === 3) {
            return DivisionLibrary::where('division_id', $user->station_id)
                ->pluck('id')
                ->all();
        }

        return [];
    }
}
