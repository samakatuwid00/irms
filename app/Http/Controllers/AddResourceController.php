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
        $station_id = $user->station_id;

        // Optimize: Fetch all libraries in parallel queries (eager load if needed)
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
        // Input Validations
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $printResourceId = null;

        DB::transaction(function () use ($request, &$printResourceId) {
            // Title
            $titleName = ucwords(strtolower($request->title));

            // OPTIMIZATION: Use firstOrCreate to reduce queries
            $title = PrintTitle::firstOrCreate(
                ['title' => $titleName],
                ['id' => (string) Str::uuid()]
            );

            // Author/s
            $authorNames = json_decode($request->authors, true) ?? [];
            $authorIds = [];

            if (!empty($authorNames)) {
                // OPTIMIZATION: Batch fetch existing authors to reduce queries
                $normalizedNames = array_map(fn($name) => ucwords(strtolower($name)), $authorNames);

                $existingAuthors = Author::whereIn('author_name', $normalizedNames)
                    ->get()
                    ->keyBy('author_name');

                foreach ($normalizedNames as $name) {
                    if ($existingAuthors->has($name)) {
                        $authorIds[] = $existingAuthors->get($name)->id;
                    } else {
                        // Create new author
                        $author = Author::create([
                            'id' => (string) Str::uuid(),
                            'author_name' => $name,
                        ]);
                        $authorIds[] = $author->id;
                    }
                }

                // Author + Pivot Table
                $title->authors()->syncWithoutDetaching($authorIds);
            }

            // Image Upload
            $coverPath = null;
            if ($request->hasFile('image')) {
                $coverPath = $this->handleImageUpload($request->file('image'), $titleName);
            }

            // Print Resource
            $gradeLevelIds = $request->subject_grade_levels
                ? implode(',', $request->subject_grade_levels)
                : null;

            $publisherName = $request->publisher ? ucwords(strtolower($request->publisher)) : 'publisher';

            $printResource = PrintResource::create([
                'id' => (string) Str::uuid(),
                'print_title_id' => $title->id,
                'print_type_id' => $request->type,
                'publisher' => $publisherName,
                'volume' => $request->volume ?: 'volume',
                'edition' => $request->edition ?: 'edition',
                'copyright' => $request->copyright ?: 0,
                'pages' => $request->pages ?: 0,
                'isbn' => $request->isbn ?: 'isbn',
                'subject_grade_level_ids' => $gradeLevelIds,
                'library_id' => $request->library_id,
                'cover' => $coverPath,
            ]);

            $printResourceId = $printResource->id;

            // Acquisitions
            $acquisitions = json_decode($request->acquisitions, true);

            $statusMap = [
                'usable' => 'USABLE',
                'partially_damaged' => 'PARTIALLY DAMAGED',
                'damaged' => 'DAMAGED',
                'lost' => 'LOST',
                'condemnable' => 'CONDEMNABLE',
            ];

            // OPTIMIZATION: Prepare bulk inserts
            $userId = Auth::id();
            $now = now();
            $masterlistInserts = [];

            foreach ($acquisitions as $a) {
                $acquisitionId = (string) Str::uuid();

                PrintAcquisition::create([
                    'id' => $acquisitionId,
                    'print_id' => $printResource->id,
                    'source' => $a['source'],
                    'date_acquired' => $a['date_acquired'],
                    'cost' => $a['cost'] !== '' ? $a['cost'] : 0,
                    'iar' => $a['iar'] !== '' ? $a['iar'] : 'iar',
                    'usable' => $a['usable'] !== '' ? (int)$a['usable'] : 0,
                    'partially_damaged' => $a['partially_damaged'] !== '' ? (int)$a['partially_damaged'] : 0,
                    'damaged' => $a['damaged'] !== '' ? (int)$a['damaged'] : 0,
                    'lost' => $a['lost'] !== '' ? (int)$a['lost'] : 0,
                    'condemnable' => $a['condemnable'] !== '' ? (int)$a['condemnable'] : 0,
                    'total_qty' => $a['total_quantity'] !== '' ? (int)$a['total_quantity'] : 0,
                    'remarks' => $a['remarks'],
                    'encoded_by' => $userId,
                    'date_encoded' => $now,
                ]);

                // OPTIMIZATION: Prepare masterlist records for bulk insert
                foreach ($statusMap as $field => $statusName) {
                    $qty = (int) ($a[$field] ?? 0);

                    for ($i = 0; $i < $qty; $i++) {
                        $masterlistInserts[] = [
                            'id' => (string) Str::uuid(),
                            'print_acquisition_id' => $acquisitionId,
                            'status' => $statusName
                        ];
                    }
                }
            }

            // OPTIMIZATION: Bulk insert masterlist records (chunks to avoid max query size)
            if (!empty($masterlistInserts)) {
                foreach (array_chunk($masterlistInserts, 500) as $chunk) {
                    PrintMasterlist::insert($chunk);
                }
            }
        });

        // FIX: Update search vector AFTER transaction commits
        // This ensures all related data (title, authors, library) exists
        if ($printResourceId) {
            DB::statement('
                UPDATE print_resources
                SET search_vector = build_print_resource_search_vector(id)
                WHERE id = ?
            ', [$printResourceId]);
        }

        return redirect()
            ->route('add-resources')
            ->with('success', 'Print resource successfully added.');
    }

    public function addNonPrintResource(Request $request)
    {
        // Input Validations
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $nonprintResourceId = null;

        DB::transaction(function () use ($request, &$nonprintResourceId) {
            // Title
            $titleName = ucwords(strtolower($request->title));

            // OPTIMIZATION: Use firstOrCreate to reduce queries
            $title = NonprintTitle::firstOrCreate(
                ['title' => $titleName],
                ['id' => (string) Str::uuid()]
            );

            // Image Upload
            $coverPath = null;
            if ($request->hasFile('image')) {
                $coverPath = $this->handleNonprintImageUpload($request->file('image'), $titleName);
            }

            // Non-Print Resource
            $gradeLevelIds = $request->subject_grade_levels
                ? implode(',', $request->subject_grade_levels)
                : null;

            $brandName = $request->brand ? ucwords(strtolower($request->brand)) : 'brand';

            $nonprintResource = NonprintResource::create([
                'id' => (string) Str::uuid(),
                'nonprint_title_id' => $title->id,
                'nonprint_type_id' => $request->type,
                'brand' => $brandName,
                'code' => $request->code ?: 'code',
                'version' => $request->version ?: 'version',
                'url' => $request->url ?: 'url',
                'size' => $request->size ?: 'size',
                'model' => $request->model ?: 'model',
                'subject_grade_level_ids' => $gradeLevelIds,
                'library_id' => $request->library_id,
                'cover' => $coverPath,
            ]);

            $nonprintResourceId = $nonprintResource->id;

            // Acquisitions
            $acquisitions = json_decode($request->acquisitions, true);

            $statusMap = [
                'usable' => 'USABLE',
                'partially_damaged' => 'PARTIALLY DAMAGED',
                'damaged' => 'DAMAGED',
                'lost' => 'LOST',
                'condemnable' => 'CONDEMNABLE',
            ];

            // OPTIMIZATION: Prepare bulk inserts
            $userId = Auth::id();
            $now = now();
            $masterlistInserts = [];

            foreach ($acquisitions as $a) {
                $acquisitionId = (string) Str::uuid();

                NonprintAcquisition::create([
                    'id' => $acquisitionId,
                    'nonprint_id' => $nonprintResource->id,
                    'source' => $a['source'],
                    'date_acquired' => $a['date_acquired'],
                    'cost' => $a['cost'] !== '' ? $a['cost'] : 0,
                    'iar' => $a['iar'] !== '' ? $a['iar'] : 'iar',
                    'usable' => $a['usable'] !== '' ? (int)$a['usable'] : 0,
                    'partially_damaged' => $a['partially_damaged'] !== '' ? (int)$a['partially_damaged'] : 0,
                    'damaged' => $a['damaged'] !== '' ? (int)$a['damaged'] : 0,
                    'lost' => $a['lost'] !== '' ? (int)$a['lost'] : 0,
                    'condemnable' => $a['condemnable'] !== '' ? (int)$a['condemnable'] : 0,
                    'total_qty' => $a['total_quantity'] !== '' ? (int)$a['total_quantity'] : 0,
                    'remarks' => $a['remarks'] ?? 'remarks',
                    'encoded_by' => $userId,
                    'date_encoded' => $now,
                ]);

                // OPTIMIZATION: Prepare masterlist records for bulk insert
                foreach ($statusMap as $field => $statusName) {
                    $qty = (int) ($a[$field] ?? 0);

                    for ($i = 0; $i < $qty; $i++) {
                        $masterlistInserts[] = [
                            'id' => (string) Str::uuid(),
                            'nonprint_acquisition_id' => $acquisitionId,
                            'status' => $statusName,
                        ];
                    }
                }
            }

            // OPTIMIZATION: Bulk insert masterlist records (chunks to avoid max query size)
            if (!empty($masterlistInserts)) {
                foreach (array_chunk($masterlistInserts, 500) as $chunk) {
                    NonprintMasterlist::insert($chunk);
                }
            }
        });

        // FIX: Update search vector AFTER transaction commits
        // This ensures all related data (title, library) exists
        if ($nonprintResourceId) {
            DB::statement('
                UPDATE nonprint_resources
                SET search_vector = build_nonprint_resource_search_vector(id)
                WHERE id = ?
            ', [$nonprintResourceId]);
        }

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
