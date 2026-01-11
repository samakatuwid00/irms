<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use Illuminate\Http\Request;
use App\Models\{
    SubjectGradeLevel,
    PrintType,
    PrintTitle,
    Author,
    PrintResource,
    PrintAcquisition,
    PrintMasterlist,
    DivisionLibrary,
    RegionLibrary,
    SchoolLibrary
};
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
class AddResourceController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $station_id = Auth::user()->station_id;

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

        return view('pages.add-resources',
                        compact(
                            'user',
                            'subjectGradeLevels',
                            'printTypes',
                            'divisionLibraries',
                            'regionLibrary',
                            'schoolLibrary'
                        )
                    );
    }

    public function addPrintResource(Request $request)
    {

        // ==============================
        // STEP 0: VALIDATION
        // ==============================
        $request->validate([
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
        ]);

        // ==============================
        // STEP 7: TRANSACTION START
        // ==============================
        DB::transaction(function () use ($request) {

            // ==============================
            // STEP 1: TITLE
            // ==============================
            $titleName = ucwords(strtolower($request->title));

            $title = PrintTitle::where('title', $titleName)->first();

            if (!$title) {
                $title = PrintTitle::create([
                    'id' => (string) Str::uuid(),
                    'title' => $titleName,
                ]);
            }

            // ==============================
            // STEP 2: AUTHORS
            // ==============================
            $authorNames = json_decode($request->authors, true) ?? [];
            $authorIds = [];

            foreach ($authorNames as $name) {
                $name = ucwords(strtolower($name));
                $author = Author::where('author_name', $name)->first();

                if (!$author) {
                    $author = Author::create([
                        'id' => (string) Str::uuid(),
                        'author_name' => $name,
                    ]);
                }

                $authorIds[] = $author->id;
            }

            // ==============================
            // STEP 3: AUTHOR ↔ TITLE PIVOT
            // ==============================
            if (!empty($authorIds)) {
                $title->authors()->syncWithoutDetaching($authorIds);
            }

            // ==============================
            // STEP 4: PRINT RESOURCE
            // ==============================
            $gradeLevelIds = $request->subject_grade_levels
                ? implode(',', $request->subject_grade_levels)
                : null;

            $publisherName = $request->publisher ? ucwords(strtolower($request->publisher)) : null;

            $printResource = PrintResource::create([
                'id' => (string) Str::uuid(),
                'print_title_id' => $title->id,
                'print_type_id' => $request->type,
                'publisher' => $publisherName,
                'volume' => $request->volume,
                'edition' => $request->edition,
                'copyright' => $request->copyright,
                'pages' => $request->pages,
                'isbn' => $request->isbn,
                'subject_grade_level_ids' => $gradeLevelIds,
                'library_id'       => $request->library_id,
            ]);

            // ==============================
            // STEP 5: ACQUISITIONS (MULTIPLE)
            // ==============================
            $acquisitions = json_decode($request->acquisitions, true);

            $statusMap = [
                'usable' => 'USABLE',
                'partially_damaged' => 'PARTIALLY DAMAGED',
                'damaged' => 'DAMAGED',
                'lost' => 'LOST',
                'condemnable' => 'CONDEMNABLE',
            ];

            foreach ($acquisitions as $a) {

                $acquisition = PrintAcquisition::create([
                    'id' => (string) Str::uuid(),
                    'print_id' => $printResource->id,
                    'source' => $a['source'],
                    'date_acquired' => $a['date_acquired'],
                    'cost' => $a['cost'],
                    'iar' => $a['iar'],

                    'usable' => $a['usable'],
                    'partially_damaged' => $a['partially_damaged'],
                    'damaged' => $a['damaged'],
                    'lost' => $a['lost'],
                    'condemnable' => $a['condemnable'],
                    'total_qty' => $a['total_quantity'],

                    'remarks' => $a['remarks'],
                    'encoded_by' => Auth::user()->id,
                    'date_encoded' => now(),
                ]);

                // ==============================
                // STEP 6: MASTERLIST POPULATION
                // ==============================
                foreach ($statusMap as $field => $statusName) {
                    $qty = (int) ($a[$field] ?? 0);

                    for ($i = 0; $i < $qty; $i++) {
                        PrintMasterlist::create([
                            'id' => (string) Str::uuid(),
                            'print_acquisition_id' => $acquisition->id,
                            'status' => $statusName,
                        ]);
                    }
                }
            }
        });

        // ==============================
        // SUCCESS RESPONSE
        // ==============================
        return redirect()
            ->route('add-resources')
            ->with('success', 'Print resource successfully added.');
    }

}
