<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

use App\Models\SubjectGradeLevel;
use App\Models\PrintType;
use App\Models\PrintTitle;
use App\Models\Author;
use App\Models\PrintResource;
use App\Models\PrintAcquisition;
use App\Models\PrintMasterlist;
use App\Models\DivisionLibrary;
use App\Models\RegionLibrary;
use App\Models\SchoolLibrary;
use App\Models\NonPrintType;
use App\Models\NonprintTitle;
use App\Models\NonprintResource;
use App\Models\NonprintAcquisition;
use App\Models\NonprintMasterlist;
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
        $nonprintTypes = NonPrintType::all();

        return view('pages.add-resources',
                        compact(
                            'user',
                            'subjectGradeLevels',
                            'printTypes',
                            'nonprintTypes',
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120', // 5MB max
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
            // STEP 3.5: HANDLE IMAGE UPLOAD
            // ==============================
            $coverPath = null;
            if ($request->hasFile('image')) {
                $coverPath = $this->handleImageUpload($request->file('image'), $titleName);
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
                'publisher' => $publisherName ?: null,
                'volume' => $request->volume ?: null,
                'edition' => $request->edition ?: null,
                'copyright' => $request->copyright ?: null,
                'pages' => $request->pages ?: null,
                'isbn' => $request->isbn ?: null,
                'subject_grade_level_ids' => $gradeLevelIds,
                'library_id'       => $request->library_id,
                'cover' => $coverPath,
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
                    'cost' => $a['cost'] !== '' ? $a['cost'] : null,
                    'iar' => $a['iar'] !== '' ? $a['iar'] : null,

                    'usable' => $a['usable'] !== '' ? (int)$a['usable'] : 0,
                    'partially_damaged' => $a['partially_damaged'] !== '' ? (int)$a['partially_damaged'] : 0,
                    'damaged' => $a['damaged'] !== '' ? (int)$a['damaged'] : 0,
                    'lost' => $a['lost'] !== '' ? (int)$a['lost'] : 0,
                    'condemnable' => $a['condemnable'] !== '' ? (int)$a['condemnable'] : 0,
                    'total_qty' => $a['total_quantity'] !== '' ? (int)$a['total_quantity'] : 0,

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

    public function addNonPrintResource(Request $request)
    {

        // ==============================
        // STEP 0: VALIDATION
        // ==============================
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|exists:nonprint_types,id',
            'brand' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:255',
            'version' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'url' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:255',
            'library_id' => 'required|string|max:36',
            'subject_grade_levels' => 'nullable|array',
            'acquisitions' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120', // 5MB max
        ]);

        // ==============================
        // STEP 5: TRANSACTION START
        // ==============================
        DB::transaction(function () use ($request) {

            // ==============================
            // STEP 1: TITLE
            // ==============================
            $titleName = ucwords(strtolower($request->title));

            $title = NonprintTitle::where('title', $titleName)->first();

            if (!$title) {
                $title = NonprintTitle::create([
                    'id' => (string) Str::uuid(),
                    'title' => $titleName,
                ]);
            }

            // ==============================
            // STEP 1.5: HANDLE IMAGE UPLOAD
            // ==============================
            $coverPath = null;
            if ($request->hasFile('image')) {
                $coverPath = $this->handleNonprintImageUpload($request->file('image'), $titleName);
            }

            // ==============================
            // STEP 2: NONPRINT RESOURCE
            // ==============================
            $gradeLevelIds = $request->subject_grade_levels
                ? implode(',', $request->subject_grade_levels)
                : null;

            $brandName = $request->brand ? ucwords(strtolower($request->brand)) : null;

            $nonprintResource = NonprintResource::create([
                'id' => (string) Str::uuid(),
                'nonprint_title_id' => $title->id,
                'nonprint_type_id' => $request->type,

                'brand' => $brandName,
                'code' => $request->code,
                'version' => $request->version,
                'url' => $request->url,
                'size' => $request->size,
                'model' => $request->model,

                'subject_grade_level_ids' => $gradeLevelIds,
                'library_id'       => $request->library_id,
                'cover' => $coverPath,
            ]);

            // ==============================
            // STEP 3: ACQUISITIONS
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

                $acquisition = NonprintAcquisition::create([
                    'id' => (string) Str::uuid(),
                    'nonprint_id' => $nonprintResource->id,
                    'source' => $a['source'],
                    'date_acquired' => $a['date_acquired'],
                    'cost' => $a['cost'] !== '' ? $a['cost'] : null,
                    'iar' => $a['iar'] !== '' ? $a['iar'] : null,

                    'usable' => $a['usable'] !== '' ? (int)$a['usable'] : 0,
                    'partially_damaged' => $a['partially_damaged'] !== '' ? (int)$a['partially_damaged'] : 0,
                    'damaged' => $a['damaged'] !== '' ? (int)$a['damaged'] : 0,
                    'lost' => $a['lost'] !== '' ? (int)$a['lost'] : 0,
                    'condemnable' => $a['condemnable'] !== '' ? (int)$a['condemnable'] : 0,
                    'total_qty' => $a['total_quantity'] !== '' ? (int)$a['total_quantity'] : 0,

                    'remarks' => $a['remarks'] ?? null,
                    'encoded_by' => Auth::user()->id,
                    'date_encoded' => now(),
                ]);

                // ==============================
                // STEP 4: MASTERLIST POPULATION
                // ==============================
                foreach ($statusMap as $field => $statusName) {
                    $qty = (int) ($a[$field] ?? 0);

                    for ($i = 0; $i < $qty; $i++) {
                        NonprintMasterlist::create([
                            'id' => (string) Str::uuid(),
                            'nonprint_acquisition_id' => $acquisition->id,
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
            ->with('success', 'Non-Print resource successfully added.');
    }

    private function handleImageUpload($image, $title)
    {
        // Create a safe filename from the title
        $baseFileName = Str::slug($title);
        $extension = $image->getClientOriginalExtension();
        $fileName = $baseFileName . '.' . $extension;

        // Define the storage path
        $storagePath = 'print_cover';
        $fullPath = $storagePath . '/' . $fileName;

        // Check if file already exists, if so, append a counter
        $counter = 1;
        while (Storage::disk('public')->exists($fullPath)) {
            $fileName = $baseFileName . '_' . $counter . '.' . $extension;
            $fullPath = $storagePath . '/' . $fileName;
            $counter++;
        }

        // Store the image
        $image->storeAs($storagePath, $fileName, 'public');

        return $fullPath;
    }

    private function handleNonprintImageUpload($image, $title)
    {
        // Create a safe filename from the title
        $baseFileName = Str::slug($title);
        $extension = $image->getClientOriginalExtension();
        $fileName = $baseFileName . '.' . $extension;

        // Define the storage path
        $storagePath = 'nonprint_cover';
        $fullPath = $storagePath . '/' . $fileName;

        // Check if file already exists, if so, append a counter
        $counter = 1;
        while (Storage::disk('public')->exists($fullPath)) {
            $fileName = $baseFileName . '_' . $counter . '.' . $extension;
            $fullPath = $storagePath . '/' . $fileName;
            $counter++;
        }

        // Store the image
        $image->storeAs($storagePath, $fileName, 'public');

        return $fullPath;
    }
}
