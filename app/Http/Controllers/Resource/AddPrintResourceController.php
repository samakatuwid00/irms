<?php

namespace App\Http\Controllers\Resource;

use App\Models\DivisionLibrary;
use App\Models\PrintType;
use App\Models\RegionLibrary;
use App\Models\SchoolLibrary;
use App\Models\SubjectGradeLevel;
use App\Services\Resource\Actions\AddPrintResourceService;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class AddPrintResourceController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $printResourceService;

    public function __construct(AddPrintResourceService $printResourceService)
    {
        $this->middleware('auth');
        $this->printResourceService = $printResourceService;
    }

    public function index()
    {
        $user       = Auth::user();
        $station_id = $user->station_id;

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
            ->join('subjects', 'subjects.id', '=', 'subject_grade_levels.subject_id')
            ->join('grade_levels', 'grade_levels.id', '=', 'subject_grade_levels.grade_level_id')
            ->orderBy('grade_levels.sort_order')
            ->get();

        $printTypes = PrintType::all();

        return view('pages.add-print-resource', compact(
            'user',
            'subjectGradeLevels',
            'printTypes',
            'divisionLibraries',
            'regionLibrary',
            'schoolLibrary'
        ));
    }

    public function store(Request $request)
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
            'acquisitions'         => 'required|string',
            'image'                => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image');
        }

        $this->printResourceService->addPrintResource($validated);

        return redirect()
            ->route('print-resource.create')
            ->with('success', 'Print resource successfully added.');
    }
}
