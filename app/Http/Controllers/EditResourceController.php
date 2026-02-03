<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\SubjectGradeLevel;
use App\Models\PrintType;
use App\Models\PrintTitle;
use App\Models\Author;
use App\Models\PrintResource;
use App\Models\PrintAcquisition;
use App\Models\PrintMasterlist;
use App\Models\NonprintResource;
use App\Models\NonprintAcquisition;
use App\Models\NonprintMasterlist;
use App\Models\DivisionLibrary;
use App\Models\NonPrintType;
use App\Models\NonprintTitle;
use App\Models\RegionLibrary;
use App\Models\SchoolLibrary;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class EditResourceController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct()
    {
        $this->middleware('auth');
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

        DB::transaction(function () use ($request, $id) {
            $printResource = PrintResource::findOrFail($id);

            // ==============================
            // STEP 1: UPDATE TITLE
            // ==============================
            $titleName = ucwords(strtolower($request->title));

            // OPTIMIZATION: Use firstOrCreate to reduce queries
            $title = PrintTitle::firstOrCreate(
                ['title' => $titleName],
                ['id' => (string) Str::uuid()]
            );

            // ==============================
            // STEP 2: UPDATE AUTHORS
            // ==============================
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

                // Sync authors with title
                $title->authors()->sync($authorIds);
            } else {
                $title->authors()->detach();
            }

            // ==============================
            // STEP 2.5: HANDLE IMAGE UPLOAD
            // ==============================
            $coverPath = $printResource->cover; // Keep existing cover by default

            if ($request->hasFile('image')) {
                // Delete old cover if it exists
                if ($printResource->cover && Storage::disk('public')->exists($printResource->cover)) {
                    Storage::disk('public')->delete($printResource->cover);
                }

                // Upload new cover
                $coverPath = $this->handleImageUpload($request->file('image'), $titleName);
            }

            // ==============================
            // STEP 3: UPDATE PRINT RESOURCE
            // ==============================
            $gradeLevelIds = $request->subject_grade_levels
                ? implode(',', $request->subject_grade_levels)
                : null;

            $publisherName = $request->publisher ? ucwords(strtolower($request->publisher)) : 'publisher';

            $printResource->update([
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

            // ==============================
            // STEP 4: UPDATE ACQUISITIONS
            // ==============================
            $acquisitions = json_decode($request->acquisitions, true);
            $statusMap = [
                'usable' => 'USABLE',
                'partially_damaged' => 'PARTIALLY DAMAGED',
                'damaged' => 'DAMAGED',
                'lost' => 'LOST',
                'condemnable' => 'CONDEMNABLE',
            ];

            // OPTIMIZATION: Prepare variables for bulk operations
            $userId = Auth::id();
            $now = now();
            $submittedAcquisitionIds = [];
            $masterlistInserts = [];
            $masterlistDeletes = [];

            foreach ($acquisitions as $a) {
                // Check if this is an existing acquisition (has 'id' field) or new one
                if (!empty($a['id'])) {
                    // UPDATE EXISTING ACQUISITION
                    $acquisition = PrintAcquisition::findOrFail($a['id']);

                    // Store old quantities for comparison
                    $oldQuantities = [
                        'usable' => $acquisition->usable,
                        'partially_damaged' => $acquisition->partially_damaged,
                        'damaged' => $acquisition->damaged,
                        'lost' => $acquisition->lost,
                        'condemnable' => $acquisition->condemnable,
                    ];

                    $newQuantities = [
                        'usable' => (int) ($a['usable'] ?? 0),
                        'partially_damaged' => (int) ($a['partially_damaged'] ?? 0),
                        'damaged' => (int) ($a['damaged'] ?? 0),
                        'lost' => (int) ($a['lost'] ?? 0),
                        'condemnable' => (int) ($a['condemnable'] ?? 0),
                    ];

                    // Update acquisition data
                    $acquisition->update([
                        'source' => $a['source'],
                        'date_acquired' => $a['date_acquired'],
                        'cost' => $a['cost'] ?: 0,
                        'iar' => $a['iar'] ?: 'iar',
                        'usable' => $newQuantities['usable'],
                        'partially_damaged' => $newQuantities['partially_damaged'],
                        'damaged' => $newQuantities['damaged'],
                        'lost' => $newQuantities['lost'],
                        'condemnable' => $newQuantities['condemnable'],
                        'total_qty' => (int) ($a['total_quantity'] ?? 0),
                        'remarks' => $a['remarks'] ?? 'remarks',
                    ]);

                    // OPTIMIZATION: Collect masterlist changes for batch processing
                    $this->preparePrintMasterlistChanges(
                        $acquisition,
                        $oldQuantities,
                        $newQuantities,
                        $statusMap,
                        $masterlistInserts,
                        $masterlistDeletes,
                        $now
                    );

                    $submittedAcquisitionIds[] = $acquisition->id;
                } else {
                    // CREATE NEW ACQUISITION
                    $acquisitionId = (string) Str::uuid();

                    PrintAcquisition::create([
                        'id' => $acquisitionId,
                        'print_id' => $printResource->id,
                        'source' => $a['source'],
                        'date_acquired' => $a['date_acquired'],
                        'cost' => $a['cost'] ?: 0,
                        'iar' => $a['iar'] ?: 'iar',
                        'usable' => (int) ($a['usable'] ?? 0),
                        'partially_damaged' => (int) ($a['partially_damaged'] ?? 0),
                        'damaged' => (int) ($a['damaged'] ?? 0),
                        'lost' => (int) ($a['lost'] ?? 0),
                        'condemnable' => (int) ($a['condemnable'] ?? 0),
                        'total_qty' => (int) ($a['total_quantity'] ?? 0),
                        'remarks' => $a['remarks'] ?? 'remarks',
                        'encoded_by' => $userId,
                        'date_encoded' => $now,
                    ]);

                    // OPTIMIZATION: Prepare masterlist entries for bulk insert
                    foreach ($statusMap as $field => $statusName) {
                        $qty = (int) ($a[$field] ?? 0);

                        for ($i = 0; $i < $qty; $i++) {
                            $masterlistInserts[] = [
                                'id' => (string) Str::uuid(),
                                'print_acquisition_id' => $acquisitionId,
                                'status' => $statusName,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }

                    $submittedAcquisitionIds[] = $acquisitionId;
                }
            }

            // OPTIMIZATION: Process bulk inserts
            if (!empty($masterlistInserts)) {
                foreach (array_chunk($masterlistInserts, 500) as $chunk) {
                    PrintMasterlist::insert($chunk);
                }
            }

            // OPTIMIZATION: Process bulk deletes
            if (!empty($masterlistDeletes)) {
                PrintMasterlist::whereIn('id', $masterlistDeletes)->delete();
            }

            // Delete acquisitions that were removed
            $existingAcquisitionIds = $printResource->printAcquisitions->pluck('id')->toArray();
            $acquisitionsToDelete = array_diff($existingAcquisitionIds, $submittedAcquisitionIds);

            if (!empty($acquisitionsToDelete)) {
                // OPTIMIZATION: Bulk delete masterlist entries
                PrintMasterlist::whereIn('print_acquisition_id', $acquisitionsToDelete)->delete();
                // OPTIMIZATION: Bulk delete acquisitions
                PrintAcquisition::whereIn('id', $acquisitionsToDelete)->delete();
            }
        });

        // FIX: Update search vector AFTER transaction commits
        DB::statement('
            UPDATE print_resources
            SET search_vector = build_print_resource_search_vector(id)
            WHERE id = ?
        ', [$id]);

        return redirect()
            ->route('edit-resource', ['id' => $id])
            ->with('success', 'Print resource successfully updated.');
    }

    /**
     * OPTIMIZATION: Prepare print masterlist changes for batch processing
     */
    private function preparePrintMasterlistChanges($acquisition, $oldQuantities, $newQuantities, $statusMap, &$masterlistInserts, &$masterlistDeletes, $now)
    {
        foreach ($statusMap as $field => $statusName) {
            $oldQty = (int) $oldQuantities[$field];
            $newQty = (int) $newQuantities[$field];
            $diff = $newQty - $oldQty;

            if ($diff > 0) {
                // PREPARE new masterlist entries for bulk insert
                for ($i = 0; $i < $diff; $i++) {
                    $masterlistInserts[] = [
                        'id' => (string) Str::uuid(),
                        'print_acquisition_id' => $acquisition->id,
                        'status' => $statusName,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            } elseif ($diff < 0) {
                // PREPARE masterlist entries for bulk delete
                $entriesToDelete = PrintMasterlist::where('print_acquisition_id', $acquisition->id)
                    ->where('status', $statusName)
                    ->limit(abs($diff))
                    ->pluck('id')
                    ->toArray();

                $masterlistDeletes = array_merge($masterlistDeletes, $entriesToDelete);
            }
            // If diff == 0, no changes needed for this status
        }
    }

    public function updateNonPrintResource(Request $request, $id)
    {
        $request->validate([
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

        DB::transaction(function () use ($request, $id) {
            $nonprintResource = NonprintResource::findOrFail($id);

            // ==============================
            // STEP 1: UPDATE TITLE
            // ==============================
            $titleName = ucwords(strtolower($request->nonprintTitle));

            // OPTIMIZATION: Use firstOrCreate to reduce queries
            $title = NonprintTitle::firstOrCreate(
                ['title' => $titleName],
                ['id' => (string) Str::uuid()]
            );

            // ==============================
            // STEP 2: HANDLE IMAGE UPLOAD
            // ==============================
            $coverPath = $nonprintResource->cover; // Keep existing cover by default

            if ($request->hasFile('imageNP')) {
                // Delete old cover if it exists
                if ($nonprintResource->cover && Storage::disk('public')->exists($nonprintResource->cover)) {
                    Storage::disk('public')->delete($nonprintResource->cover);
                }

                // Upload new cover
                $coverPath = $this->handleNonprintImageUpload($request->file('imageNP'), $titleName);
            }

            // ==============================
            // STEP 3: UPDATE NONPRINT RESOURCE
            // ==============================
            $gradeLevelIds = $request->subject_grade_levels
                ? implode(',', $request->subject_grade_levels)
                : null;

            $brandName = $request->brand ? ucwords(strtolower($request->brand)) : 'brand';

            $nonprintResource->update([
                'nonprint_title_id' => $title->id,
                'nonprint_type_id' => $request->typeNP,
                'brand' => $brandName,
                'code' => $request->code ?: 'code',
                'version' => $request->version ?: 'version',
                'model' => $request->model ?: 'model',
                'url' => $request->url ?: 'url',
                'size' => $request->size ?: 'size',
                'subject_grade_level_ids' => $gradeLevelIds,
                'library_id' => $request->library_idNP,
                'cover' => $coverPath,
            ]);

            // ==============================
            // STEP 4: UPDATE ACQUISITIONS
            // ==============================
            $acquisitions = json_decode($request->acquisitions, true);
            $statusMap = [
                'usable' => 'USABLE',
                'partially_damaged' => 'PARTIALLY DAMAGED',
                'damaged' => 'DAMAGED',
                'lost' => 'LOST',
                'condemnable' => 'CONDEMNABLE',
            ];

            // OPTIMIZATION: Prepare variables for bulk operations
            $userId = Auth::id();
            $now = now();
            $submittedAcquisitionIds = [];
            $masterlistInserts = [];
            $masterlistDeletes = [];

            foreach ($acquisitions as $a) {
                // Check if this is an existing acquisition (has 'id' field) or new one
                if (!empty($a['id'])) {
                    // UPDATE EXISTING ACQUISITION
                    $acquisition = NonprintAcquisition::findOrFail($a['id']);

                    // Store old quantities for comparison
                    $oldQuantities = [
                        'usable' => $acquisition->usable,
                        'partially_damaged' => $acquisition->partially_damaged,
                        'damaged' => $acquisition->damaged,
                        'lost' => $acquisition->lost,
                        'condemnable' => $acquisition->condemnable,
                    ];

                    $newQuantities = [
                        'usable' => (int) ($a['usable'] ?? 0),
                        'partially_damaged' => (int) ($a['partially_damaged'] ?? 0),
                        'damaged' => (int) ($a['damaged'] ?? 0),
                        'lost' => (int) ($a['lost'] ?? 0),
                        'condemnable' => (int) ($a['condemnable'] ?? 0),
                    ];

                    // Update acquisition data
                    $acquisition->update([
                        'source' => $a['source'],
                        'date_acquired' => $a['date_acquired'],
                        'cost' => $a['cost'] ?: 0,
                        'iar' => $a['iar'] ?: 'iar',
                        'usable' => $newQuantities['usable'],
                        'partially_damaged' => $newQuantities['partially_damaged'],
                        'damaged' => $newQuantities['damaged'],
                        'lost' => $newQuantities['lost'],
                        'condemnable' => $newQuantities['condemnable'],
                        'total_qty' => (int) ($a['total_quantity'] ?? 0),
                        'remarks' => $a['remarks'] ?? 'remarks',
                    ]);

                    // OPTIMIZATION: Collect masterlist changes for batch processing
                    $this->prepareNonprintMasterlistChanges(
                        $acquisition,
                        $oldQuantities,
                        $newQuantities,
                        $statusMap,
                        $masterlistInserts,
                        $masterlistDeletes,
                        $now
                    );

                    $submittedAcquisitionIds[] = $acquisition->id;
                } else {
                    // CREATE NEW ACQUISITION
                    $acquisitionId = (string) Str::uuid();

                    NonprintAcquisition::create([
                        'id' => $acquisitionId,
                        'nonprint_id' => $nonprintResource->id,
                        'source' => $a['source'],
                        'date_acquired' => $a['date_acquired'],
                        'cost' => $a['cost'] ?: 0,
                        'iar' => $a['iar'] ?: 'iar',
                        'usable' => (int) ($a['usable'] ?? 0),
                        'partially_damaged' => (int) ($a['partially_damaged'] ?? 0),
                        'damaged' => (int) ($a['damaged'] ?? 0),
                        'lost' => (int) ($a['lost'] ?? 0),
                        'condemnable' => (int) ($a['condemnable'] ?? 0),
                        'total_qty' => (int) ($a['total_quantity'] ?? 0),
                        'remarks' => $a['remarks'] ?? 'remarks',
                        'encoded_by' => $userId,
                        'date_encoded' => $now,
                    ]);

                    // OPTIMIZATION: Prepare masterlist entries for bulk insert
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

                    $submittedAcquisitionIds[] = $acquisitionId;
                }
            }

            // OPTIMIZATION: Process bulk inserts
            if (!empty($masterlistInserts)) {
                foreach (array_chunk($masterlistInserts, 500) as $chunk) {
                    NonprintMasterlist::insert($chunk);
                }
            }

            // OPTIMIZATION: Process bulk deletes
            if (!empty($masterlistDeletes)) {
                NonprintMasterlist::whereIn('id', $masterlistDeletes)->delete();
            }

            // Delete acquisitions that were removed
            $existingAcquisitionIds = $nonprintResource->nonprintAcquisitions->pluck('id')->toArray();
            $acquisitionsToDelete = array_diff($existingAcquisitionIds, $submittedAcquisitionIds);

            if (!empty($acquisitionsToDelete)) {
                // OPTIMIZATION: Bulk delete masterlist entries
                NonprintMasterlist::whereIn('nonprint_acquisition_id', $acquisitionsToDelete)->delete();
                // OPTIMIZATION: Bulk delete acquisitions
                NonprintAcquisition::whereIn('id', $acquisitionsToDelete)->delete();
            }
        });

        // FIX: Update search vector AFTER transaction commits
        DB::statement('
            UPDATE nonprint_resources
            SET search_vector = build_nonprint_resource_search_vector(id)
            WHERE id = ?
        ', [$id]);

        return redirect()
                ->route('edit-resource', ['id' => $id, 'tab' => 'nonprint'])
                ->with('success', 'Non-print resource successfully updated.');
    }

    /**
     * OPTIMIZATION: Prepare nonprint masterlist changes for batch processing
     */
    private function prepareNonprintMasterlistChanges($acquisition, $oldQuantities, $newQuantities, $statusMap, &$masterlistInserts, &$masterlistDeletes, $now)
    {
        foreach ($statusMap as $field => $statusName) {
            $oldQty = (int) $oldQuantities[$field];
            $newQty = (int) $newQuantities[$field];
            $diff = $newQty - $oldQty;

            if ($diff > 0) {
                // PREPARE new masterlist entries for bulk insert
                for ($i = 0; $i < $diff; $i++) {
                    $masterlistInserts[] = [
                        'id' => (string) Str::uuid(),
                        'nonprint_acquisition_id' => $acquisition->id,
                        'status' => $statusName,
                    ];
                }
            } elseif ($diff < 0) {
                // PREPARE masterlist entries for bulk delete
                $entriesToDelete = NonprintMasterlist::where('nonprint_acquisition_id', $acquisition->id)
                    ->where('status', $statusName)
                    ->limit(abs($diff))
                    ->pluck('id')
                    ->toArray();

                $masterlistDeletes = array_merge($masterlistDeletes, $entriesToDelete);
            }
            // If diff == 0, no changes needed for this status
        }
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
