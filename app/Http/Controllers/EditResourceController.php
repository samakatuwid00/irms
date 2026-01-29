<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\{
    SubjectGradeLevel,
    PrintType,
    PrintTitle,
    Author,
    PrintResource,
    PrintAcquisition,
    PrintMasterlist,
    NonprintResource,
    NonprintAcquisition,
    NonprintMasterlist,
    DivisionLibrary,
    NonPrintType,
    NonprintTitle,
    RegionLibrary,
    SchoolLibrary
};
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
        $station_id = Auth::user()->station_id;

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
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120', // 5MB max
        ]);

        DB::transaction(function () use ($request, $id) {
            $printResource = PrintResource::findOrFail($id);

            // ==============================
            // STEP 1: UPDATE TITLE
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
            // STEP 2: UPDATE AUTHORS
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

            // Sync authors with title
            if (!empty($authorIds)) {
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

            $publisherName = $request->publisher ? ucwords(strtolower($request->publisher)) : null;

            $printResource->update([
                'print_title_id' => $title->id,
                'print_type_id' => $request->type,
                'publisher' => $publisherName,
                'volume' => $request->volume,
                'edition' => $request->edition,
                'copyright' => $request->copyright,
                'pages' => $request->pages,
                'isbn' => $request->isbn,
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

            $submittedAcquisitionIds = [];

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

                    // Update acquisition data
                    $acquisition->update([
                        'source' => $a['source'],
                        'date_acquired' => $a['date_acquired'],
                        'cost' => $a['cost'] ?: null,
                        'iar' => $a['iar'] ?: null,
                        'usable' => (int) ($a['usable'] ?? 0),
                        'partially_damaged' => (int) ($a['partially_damaged'] ?? 0),
                        'damaged' => (int) ($a['damaged'] ?? 0),
                        'lost' => (int) ($a['lost'] ?? 0),
                        'condemnable' => (int) ($a['condemnable'] ?? 0),
                        'total_qty' => (int) ($a['total_quantity'] ?? 0),
                        'remarks' => $a['remarks'] ?? null,
                    ]);

                    // Update masterlist based on quantity changes
                    $this->updatePrintMasterlist($acquisition, $oldQuantities, [
                        'usable' => (int) ($a['usable'] ?? 0),
                        'partially_damaged' => (int) ($a['partially_damaged'] ?? 0),
                        'damaged' => (int) ($a['damaged'] ?? 0),
                        'lost' => (int) ($a['lost'] ?? 0),
                        'condemnable' => (int) ($a['condemnable'] ?? 0),
                    ], $statusMap);

                    $submittedAcquisitionIds[] = $acquisition->id;
                } else {
                    // CREATE NEW ACQUISITION
                    $acquisition = PrintAcquisition::create([
                        'id' => (string) Str::uuid(),
                        'print_id' => $printResource->id,
                        'source' => $a['source'],
                        'date_acquired' => $a['date_acquired'],
                        'cost' => $a['cost'] ?: null,
                        'iar' => $a['iar'] ?: null,
                        'usable' => (int) ($a['usable'] ?? 0),
                        'partially_damaged' => (int) ($a['partially_damaged'] ?? 0),
                        'damaged' => (int) ($a['damaged'] ?? 0),
                        'lost' => (int) ($a['lost'] ?? 0),
                        'condemnable' => (int) ($a['condemnable'] ?? 0),
                        'total_qty' => (int) ($a['total_quantity'] ?? 0),
                        'remarks' => $a['remarks'] ?? null,
                        'encoded_by' => Auth::user()->id,
                        'date_encoded' => now(),
                    ]);

                    // Create masterlist entries for new acquisition
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

                    $submittedAcquisitionIds[] = $acquisition->id;
                }
            }

            // Delete acquisitions that were removed
            $existingAcquisitionIds = $printResource->printAcquisitions->pluck('id')->toArray();
            $acquisitionsToDelete = array_diff($existingAcquisitionIds, $submittedAcquisitionIds);

            foreach ($acquisitionsToDelete as $acquisitionId) {
                $acquisition = PrintAcquisition::find($acquisitionId);
                if ($acquisition) {
                    // Delete associated masterlist entries
                    PrintMasterlist::where('print_acquisition_id', $acquisitionId)->delete();
                    // Delete acquisition
                    $acquisition->delete();
                }
            }
        });

        return redirect()
            ->route('edit-resource', ['id' => $id])
            ->with('success', 'Print resource successfully updated.');
    }

    /**
     * Update print masterlist entries based on quantity changes
     */
    private function updatePrintMasterlist($acquisition, $oldQuantities, $newQuantities, $statusMap)
    {
        foreach ($statusMap as $field => $statusName) {
            $oldQty = (int) $oldQuantities[$field];
            $newQty = (int) $newQuantities[$field];
            $diff = $newQty - $oldQty;

            if ($diff > 0) {
                // ADD new masterlist entries
                for ($i = 0; $i < $diff; $i++) {
                    PrintMasterlist::create([
                        'id' => (string) Str::uuid(),
                        'print_acquisition_id' => $acquisition->id,
                        'status' => $statusName,
                    ]);
                }
            } elseif ($diff < 0) {
                // REMOVE masterlist entries
                $entriesToDelete = PrintMasterlist::where('print_acquisition_id', $acquisition->id)
                    ->where('status', $statusName)
                    ->limit(abs($diff))
                    ->get();

                foreach ($entriesToDelete as $entry) {
                    $entry->delete();
                }
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
            $title = NonprintTitle::where('title', $titleName)->first();

            if (!$title) {
                $title = NonprintTitle::create([
                    'id' => (string) Str::uuid(),
                    'title' => $titleName,
                ]);
            }

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

            $brandName = $request->brand ? ucwords(strtolower($request->brand)) : null;

            $nonprintResource->update([
                'nonprint_title_id' => $title->id,
                'nonprint_type_id' => $request->typeNP,
                'brand' => $brandName,
                'code' => $request->code,
                'version' => $request->version,
                'model' => $request->model,
                'url' => $request->url,
                'size' => $request->size,
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

            $submittedAcquisitionIds = [];

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

                    // Update acquisition data
                    $acquisition->update([
                        'source' => $a['source'],
                        'date_acquired' => $a['date_acquired'],
                        'cost' => $a['cost'] ?: null,
                        'iar' => $a['iar'] ?: null,
                        'usable' => (int) ($a['usable'] ?? 0),
                        'partially_damaged' => (int) ($a['partially_damaged'] ?? 0),
                        'damaged' => (int) ($a['damaged'] ?? 0),
                        'lost' => (int) ($a['lost'] ?? 0),
                        'condemnable' => (int) ($a['condemnable'] ?? 0),
                        'total_qty' => (int) ($a['total_quantity'] ?? 0),
                        'remarks' => $a['remarks'] ?? null,
                    ]);

                    // Update masterlist based on quantity changes
                    $this->updateNonprintMasterlist($acquisition, $oldQuantities, [
                        'usable' => (int) ($a['usable'] ?? 0),
                        'partially_damaged' => (int) ($a['partially_damaged'] ?? 0),
                        'damaged' => (int) ($a['damaged'] ?? 0),
                        'lost' => (int) ($a['lost'] ?? 0),
                        'condemnable' => (int) ($a['condemnable'] ?? 0),
                    ], $statusMap);

                    $submittedAcquisitionIds[] = $acquisition->id;
                } else {
                    // CREATE NEW ACQUISITION
                    $acquisition = NonprintAcquisition::create([
                        'id' => (string) Str::uuid(),
                        'nonprint_id' => $nonprintResource->id,
                        'source' => $a['source'],
                        'date_acquired' => $a['date_acquired'],
                        'cost' => $a['cost'] ?: null,
                        'iar' => $a['iar'] ?: null,
                        'usable' => (int) ($a['usable'] ?? 0),
                        'partially_damaged' => (int) ($a['partially_damaged'] ?? 0),
                        'damaged' => (int) ($a['damaged'] ?? 0),
                        'lost' => (int) ($a['lost'] ?? 0),
                        'condemnable' => (int) ($a['condemnable'] ?? 0),
                        'total_qty' => (int) ($a['total_quantity'] ?? 0),
                        'remarks' => $a['remarks'] ?? null,
                        'encoded_by' => Auth::user()->id,
                        'date_encoded' => now(),
                    ]);

                    // Create masterlist entries for new acquisition
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

                    $submittedAcquisitionIds[] = $acquisition->id;
                }
            }

            // Delete acquisitions that were removed
            $existingAcquisitionIds = $nonprintResource->nonprintAcquisitions->pluck('id')->toArray();
            $acquisitionsToDelete = array_diff($existingAcquisitionIds, $submittedAcquisitionIds);

            foreach ($acquisitionsToDelete as $acquisitionId) {
                $acquisition = NonprintAcquisition::find($acquisitionId);
                if ($acquisition) {
                    // Delete associated masterlist entries
                    NonprintMasterlist::where('nonprint_acquisition_id', $acquisitionId)->delete();
                    // Delete acquisition
                    $acquisition->delete();
                }
            }
        });

        return redirect()
                ->route('edit-resource', ['id' => $id, 'tab' => 'nonprint'])
                ->with('success', 'Non-print resource successfully updated.');
    }

    /**
     * Update nonprint masterlist entries based on quantity changes
     */
    private function updateNonprintMasterlist($acquisition, $oldQuantities, $newQuantities, $statusMap)
    {
        foreach ($statusMap as $field => $statusName) {
            $oldQty = (int) $oldQuantities[$field];
            $newQty = (int) $newQuantities[$field];
            $diff = $newQty - $oldQty;

            if ($diff > 0) {
                // ADD new masterlist entries
                for ($i = 0; $i < $diff; $i++) {
                    NonprintMasterlist::create([
                        'id' => (string) Str::uuid(),
                        'nonprint_acquisition_id' => $acquisition->id,
                        'status' => $statusName,
                    ]);
                }
            } elseif ($diff < 0) {
                // REMOVE masterlist entries
                $entriesToDelete = NonprintMasterlist::where('nonprint_acquisition_id', $acquisition->id)
                    ->where('status', $statusName)
                    ->limit(abs($diff))
                    ->get();

                foreach ($entriesToDelete as $entry) {
                    $entry->delete();
                }
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
