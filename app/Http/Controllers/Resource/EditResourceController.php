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

    // -------------------------------------------------------------------------
    // index — load the edit page
    // -------------------------------------------------------------------------

    public function index($id)
    {
        $user       = Auth::user();
        $station_id = $user->station_id;

        $printResource = PrintResource::with([
            'printTitle.authors',
            'type',
            'printAcquisitions.printMasterlists',
        ])->find($id);

        $nonprintResource = NonprintResource::with([
            'nonprintTitle',
            'type',
            'nonprintAcquisitions.nonprintMasterlists',
        ])->find($id);

        $divisionLibraries = DivisionLibrary::where('division_id', $station_id)
            ->orderBy('library_name')
            ->get();

        $schoolLibrary = SchoolLibrary::where('school_id', $station_id)
            ->orderBy('library_name')
            ->first();

        $regionLibrary = RegionLibrary::where('region_id', $station_id)
            ->orderBy('library_name')
            ->first();

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

        $printTypes    = PrintType::all();
        $nonprintTypes = NonPrintType::all();

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

    // -------------------------------------------------------------------------
    // updatePrintResource — acquisitions only
    // -------------------------------------------------------------------------

    /**
     * Only the acquisition list is editable from the revised form.
     * Resource metadata (title, authors, cover, type, etc.) is read-only in
     * the blade and is NOT updated here.
     */
    public function updatePrintResource(Request $request, $id)
    {
        $validated = $request->validate([
            'acquisitions' => 'required|string',
        ]);

        $result = $this->printResourceService->updatePrintResource($id, $validated);

        if ($result['deleted']) {
            return redirect()
                ->route('print-resources')
                ->with('success', 'All acquisitions were removed so the print resource has been deleted.');
        }

        return redirect()
            ->route('edit-resource', ['id' => $id])
            ->with('success', 'Acquisitions updated successfully.');
    }

    // -------------------------------------------------------------------------
    // updateNonPrintResource — unchanged
    // -------------------------------------------------------------------------

    public function updateNonPrintResource(Request $request, $id)
    {
        $validated = $request->validate([
            'nonprintTitle'        => 'required|string|max:255',
            'typeNP'               => 'required|exists:nonprint_types,id',
            'brand'                => 'nullable|string|max:255',
            'code'                 => 'nullable|string|max:255',
            'version'              => 'nullable|string|max:255',
            'model'                => 'nullable|string|max:255',
            'url'                  => 'nullable|string|max:255',
            'size'                 => 'nullable|string|max:255',
            'library_idNP'         => 'required|string|max:36',
            'subject_grade_levels' => 'nullable|array',
            'acquisitions'         => 'required|string',
            'imageNP'              => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $serviceData = [
            'title'                => $validated['nonprintTitle'],
            'type'                 => $validated['typeNP'],
            'brand'                => $validated['brand']    ?? 'Brand not specified.',
            'code'                 => $validated['code']     ?? 'Code not specified.',
            'version'              => $validated['version']  ?? 'Version not specified.',
            'model'                => $validated['model']    ?? 'Model not specified.',
            'url'                  => $validated['url']      ?? 'URL not specified.',
            'size'                 => $validated['size']     ?? '',
            'library_id'           => $validated['library_idNP'],
            'subject_grade_levels' => $validated['subject_grade_levels'] ?? null,
            'acquisitions'         => $validated['acquisitions'],
        ];

        if ($request->hasFile('imageNP')) {
            $serviceData['image'] = $request->file('imageNP');
        }

        $result = $this->nonPrintResourceService->updateNonPrintResource($id, $serviceData);

        if ($result['deleted']) {
            return redirect()
                ->route('nonprint-resources')
                ->with('success', 'Non-print resource and all acquisitions have been deleted (no acquisitions with quantity > 0).');
        }

        return redirect()
            ->route('edit-resource', ['id' => $id, 'tab' => 'nonprint'])
            ->with('success', 'Non-print resource successfully updated.');
    }
}
