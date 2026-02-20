<?php

namespace App\Http\Controllers\Resource;

use App\Models\DivisionLibrary;
use App\Models\NonPrintType;
use App\Models\RegionLibrary;
use App\Models\SchoolLibrary;
use App\Models\SubjectGradeLevel;
use App\Services\Resource\Actions\AddNonPrintResourceService;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class AddNonPrintResourceController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $nonPrintResourceService;

    public function __construct(AddNonPrintResourceService $nonPrintResourceService)
    {
        $this->middleware('auth');
        $this->nonPrintResourceService = $nonPrintResourceService;
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

        $nonprintTypes = NonPrintType::all();

        return view('pages.add-nonprint-resource',
            compact(
                'user',
                'subjectGradeLevels',
                'nonprintTypes',
                'divisionLibraries',
                'regionLibrary',
                'schoolLibrary'
            )
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'                => 'required|string|max:255',
            'type'                 => 'required|exists:nonprint_types,id',
            'brand'                => 'nullable|string|max:255',
            'code'                 => 'nullable|string|max:255',
            'version'              => 'nullable|string|max:255',
            'model'                => 'nullable|string|max:255',
            'url'                  => 'nullable|string|max:255',
            'size'                 => 'nullable|string|max:255',
            'library_id'           => 'required|string|max:36',
            'subject_grade_levels' => 'nullable|array',
            'acquisitions'         => 'required|string',
            'image'                => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image');
        }

        $this->nonPrintResourceService->addNonPrintResource($validated);

        return redirect()
            ->route('nonprint-resource.create')
            ->with('success', 'Non-Print resource successfully added.');
    }
}
