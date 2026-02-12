<?php

namespace App\Http\Controllers;

use App\Models\DivisionLibrary;
use App\Models\NonprintResource;
use App\Models\NonPrintType;
use App\Models\PrintResource;
use App\Models\PrintType;
use App\Models\RegionLibrary;
use App\Models\SchoolLibrary;
use App\Models\SubjectGradeLevel;
use App\Services\EditNonPrintResourceService;
use App\Services\EditPrintResourceService;
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
        EditPrintResourceService $printResourceService,
        EditNonPrintResourceService $nonPrintResourceService
    ) {
        $this->middleware('auth');
        $this->printResourceService = $printResourceService;
        $this->nonPrintResourceService = $nonPrintResourceService;
    }

    public function index($id)
    {
        $user = Auth::user();
        $station_id = $user->station_id;

        // Get the print resource with relationships
        $printResource = PrintResource::with([
            'printTitle.authors',
            'type',
            'printAcquisitions.printMasterlists'
        ])->find($id);

        // Get the nonprint resource with relationships
        $nonprintResource = NonprintResource::with([
            'nonprintTitle',
            'type',
            'nonprintAcquisitions.nonprintMasterlists'
        ])->find($id);

        // Get libraries based on user level
        $divisionLibraries = DivisionLibrary::where('division_id', $station_id)
            ->orderBy('library_name')
            ->get();

        $schoolLibrary = SchoolLibrary::where('school_id', $station_id)
            ->orderBy('library_name')
            ->first();

        $regionLibrary = RegionLibrary::where('region_id', $station_id)
            ->orderBy('library_name')
            ->first();

        // Get subject grade levels
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
            ->join('subjects', 'subjects.id', '=', 'subject_grade_levels.subject_id')
            ->join('grade_levels', 'grade_levels.id', '=', 'subject_grade_levels.grade_level_id')
            ->orderBy('grade_levels.sort_order')
            ->get();

        $printTypes = PrintType::all();
        $nonprintTypes = NonPrintType::all();

        // Parse selected subject grade levels for print resource
        $selectedSubjectGradeLevels = [];
        if ($printResource) {
            $selectedSubjectGradeLevels = $printResource->subject_grade_level_ids
                ? explode(',', $printResource->subject_grade_level_ids)
                : [];
        }

        // Parse selected subject grade levels for nonprint resource
        $selectedSubjectGradeLevelsNP = [];
        if ($nonprintResource) {
            $selectedSubjectGradeLevelsNP = $nonprintResource->subject_grade_level_ids
                ? explode(',', $nonprintResource->subject_grade_level_ids)
                : [];
        }

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
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'authors' => 'nullable|string',
            'type' => 'required|exists:print_types,id',
            'publisher' => 'nullable|string|max:255',
            'volume' => 'nullable|string|max:50',
            'edition' => 'nullable|string|max:50',
            'copyright' => 'nullable|integer',
            'isbn' => 'nullable|string|max:50',
            'pages' => 'nullable|integer',
            'library_id' => 'required|string|max:36',
            'subject_grade_levels' => 'nullable|array',
            'acquisitions' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        // Add the image file to the validated data if present
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image');
        }

        // Update the resource using the service
        $result = $this->printResourceService->updatePrintResource($id, $validated);

        // Handle redirect based on whether resource was deleted
        if ($result['deleted']) {
            return redirect()
                ->route('print-resources')
                ->with('success', 'Print resource and all acquisitions have been deleted (no acquisitions with quantity > 0).');
        }

        return redirect()
            ->route('edit-resource', ['id' => $id])
            ->with('success', 'Print resource successfully updated.');
    }

    public function updateNonPrintResource(Request $request, $id)
    {
        $validated = $request->validate([
            'nonprintTitle' => 'required|string|max:255',
            'typeNP' => 'required|exists:nonprint_types,id',
            'brand' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:255',
            'version' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'url' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:255',
            'library_idNP' => 'required|string|max:36',
            'subject_grade_levels' => 'nullable|array',
            'acquisitions' => 'required|string',
            'imageNP' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        // Normalize the validated data to match service expectations
        $serviceData = [
            'title' => $validated['nonprintTitle'],
            'type' => $validated['typeNP'],
            'brand' => $validated['brand'] ?? null,
            'code' => $validated['code'] ?? null,
            'version' => $validated['version'] ?? null,
            'model' => $validated['model'] ?? null,
            'url' => $validated['url'] ?? null,
            'size' => $validated['size'] ?? null,
            'library_id' => $validated['library_idNP'],
            'subject_grade_levels' => $validated['subject_grade_levels'] ?? null,
            'acquisitions' => $validated['acquisitions'],
        ];

        // Add the image file to the service data if present
        if ($request->hasFile('imageNP')) {
            $serviceData['image'] = $request->file('imageNP');
        }

        // Update the resource using the service
        $result = $this->nonPrintResourceService->updateNonPrintResource($id, $serviceData);

        // Handle redirect based on whether resource was deleted
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
