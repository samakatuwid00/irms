<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use Illuminate\Support\Facades\Log;

use App\Models\PrintType;
use App\Models\PrintTitle;
use App\Models\Author;
use App\Models\PrintResource;
use App\Models\PrintAcquisition;
use App\Models\PrintMasterlist;

class ImportController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }

    public function index()
    {
        return view('pages.import-resources');
    }

    public function importPrintResources(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt|max:10240',
        ]);

        try {
            DB::beginTransaction();

            $file = $request->file('csv_file');

            // Read CSV file using PhpSpreadsheet
            $reader = new Csv();
            $spreadsheet = $reader->load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Skip header row
            array_shift($rows);

            $typeMapping = [
                'Textbook' => 'a4039a19-40e9-4ca9-ac0e-525c9d299dd3',
                'SLR' => 'd805aca1-aa26-4943-9d18-d691ab75308f',
                'textbook' => 'a4039a19-40e9-4ca9-ac0e-525c9d299dd3',
                'slr' => 'd805aca1-aa26-4943-9d18-d691ab75308f',
                'Bigbook' => '91ea54b2-dec7-4b9f-b3d9-aea96af5b0f2',
                'Modules' => 'a2f1742c-59f4-4c49-804f-38b203f6fd65',
                'Smallbook' =>'5a0b40d4-789f-4ceb-9fad-d027bb6c8382',
                'PDM' => 'd7019867-4c2b-4ea3-bc89-abc31c82a511',
                'GeneralRef' => '988d9d86-96d4-44cc-bfde-90507af0a10e',
                'Activitysheets' => '14b2cd05-c72e-49d6-8236-86cca87a5b09',
                'TM' => '72e65a86-ba3a-4f98-98c8-dabb637fc829',
                'TG' => 'f8494a4d-4051-4895-80c0-bfe04f2d051d',
                'LM' => '1402d3a3-8a74-4a92-9bf7-f5a5cb11fa6e'
            ];

            // $libraryId = 'e95defae-d9c2-40bf-9f95-f841eb7338a7'; //Camarines Norte
            // $libraryId = 'f33f03de-188e-4dce-a4e6-5add7ba1cae5'; // Camarines Sur
            // $libraryId = '9ef7447d-1f56-4673-aa5b-eb1465da078b'; // Naga City
            // $libraryId = 'dc80ab07-1687-40a0-9a40-b4743a616abb'; // Albay
            // $libraryId = 'e59bba3a-5461-4df6-8f34-b417d9ecd892'; // Legazpi City
            // $libraryId = '3a59290c-c2b8-46dd-ad54-32c30de9783c'; // Masbate City
            //$libraryId = '2324a262-5356-4a20-a714-59730d52d3ca'; // Catanduanes
            //$libraryId = '743821b5-5bce-4f6e-ab2d-767d644cf562'; // Tabaco City
            //$libraryId = 'a49627d6-c44c-4275-b00d-3084b0c85994'; //CAM-HIGH
            //$libraryId = 'b0b9c127-8423-4ff3-a680-a4a0914982e5'; // Balatas Elementary School
            //$libraryId = 'd6b805f0-2041-4175-a4a7-8c567a5a41b4'; // Naga Central 1
            //$libraryId = '6ebeadd0-13e3-4834-8793-cf153e4a9352'; // Naga Central 2
            //$libraryId = '8e90358e-28db-4bd6-88d1-39ea316b2431'; // Tinago Central School
            //$libraryId = 'ed5aede5-7945-4288-98a8-80795a0245e2'; // Tinago High School
            //$libraryId = '16b2fde1-9d74-491a-9ec6-a1f01f1b8296'; // Jose Rizal Elementary School
            //$libraryId = 'f0eeb551-ffcc-497b-b9ec-1018479fc89e'; // NCSAT
            //$libraryId = '7f722be7-b58b-4cde-ba14-44b35d7951cc'; // Sta Cruz Elem
            //$libraryId = '5d824b30-8827-47cd-bf98-ee0a11e75381'; // Sta Cruz High School
            //$libraryId = 'cb1de5aa-c420-4389-b1f7-359aa6ca8383'; // Mabolo Elementary School
            //$libraryId = 'd8d08044-9b46-45df-b932-500b87a04c64'; // Naga Science High School
            //$libraryId = '03b7c84b-591c-4849-9a09-bab45d38e7c0'; // Tabuco Elementary School
            //$libraryId = '8750a21f-56c5-4dd8-b849-21278fcbb427'; // Triangulo Elementary School
            //$libraryId = '80be7b26-5e0c-4dcf-b6e6-f9e91a89f7d9'; // Concepcion Elem School
            //$libraryId = 'aa5ab0e4-539c-4da5-b09f-073ea268a9b4'; // Concepcion Pequeña National High School
            //$libraryId = 'c4f83c2b-22a8-46ec-af3f-f1497dec6b11'; // JBMES
            //$libraryId = '79c38f6f-91d5-4420-a8b3-d6389d806d88'; // Villa Grande Homes Elementary School
            //$libraryId = '8202c5aa-eff4-4b83-a34e-4cc400460322'; // Calauag Elem School
            //$libraryId = '557e9dff-1b21-4892-9366-112ce1929906'; // Del Rosario Elem School
            //$libraryId = '6bf3abb9-cd2a-47fb-9b00-1de50cf44003'; // Dr. Domingo G. Abcede Elem School
            //$libraryId = '3b7d9b38-472c-4301-9f82-16f9d540a6d2'; // Mac Mariano ES
            //$libraryId = '713e8887-73bb-4216-8e29-9c2b11728639'; // Cararayan National High School
            //$libraryId = '8671378d-526f-43ef-bb0b-79ed119a0089'; //Don Manuel I. Abella Central School
            //$libraryId = '51f81eb3-01b6-437f-bf0d-1a83eff71c5e'; // SAN RAFAEL ELEM SCHOOL SPED CENTER
            //$libraryId = 'c6b47918-56a6-4a29-acac-86ba7617104a'; // Villa Corazon ES
            //$libraryId = '0145e364-06f2-4dac-8c42-1b40fbc98d9a'; // Grand View Elem School
            //$libraryId = 'c12ce96c-de6e-4539-8b57-60ed40c9f7fb'; // LEON MHS
            //$libraryId = '31888d63-2092-440f-9cab-bd2c797b7017'; // Pacol Elem School
            //$libraryId = 'f405d8ab-9f4b-4137-bb34-a5fa5a40437e'; // Rosario V. Maramba Elem School
            //$libraryId = 'aadfad63-1792-4a08-81db-bf1531a71b43'; // Carolina Elem School
            //$libraryId = 'e79ec817-9fa4-463a-81be-e0c43edcbd80'; // Carolina National High School
            //$libraryId = '1e719eba-2358-4f65-905c-1313a57765a9'; // Morada-Ramos Elem. School
            //$libraryId = '75cdaa8a-5c5c-4f33-a51a-20abdfbf2728'; // Panicuason Elem School
            //$libraryId = '95dc7193-fa9a-4b48-a660-311e3a23db76'; // San Isidro Elem School
           //$libraryId = 'c8da8355-5a8f-451c-a0d1-982cfbf29600'; // San Isidro National High School
            //$libraryId = 'c7d6df06-0ccb-4eac-b01b-e27c75f0d722'; // teodora moscoso elem school
            //$libraryId = '889d706e-ca2e-4659-bb73-b0a97f0fb56b'; // Yabu Elementary School
            //$libraryId = '7af2a623-28ad-4fe0-a6de-acd122cec69d'; // Balatas High School
            //$libraryId = '35cfb49d-5749-4864-aa34-81d99a544e96'; // Del Rosario High School
            //$libraryId = '6a4dabb1-6d21-47ab-bf70-059caeee331e'; // Sabang Elementary School
            // $userId = Auth::id();
            $now = now();
            $importedCount = 0;
            $skippedCount = 0;

            foreach ($rows as $row) {
                try {
                    $title = $row[0] ?? null; // title
                    $type = $row[1] ?? null; // type
                    $author = $row[2] ?? null; // author
                    $publisher = $row[3] ?? null; // publisher
                    $volume = $row[4] ?? null; // volume
                    $copyrightYear = $row[5] ?? null; // copyright_year
                    $pages = $row[6] ?? null; // pages
                    $source = $row[7] ?? null; // source
                    $status = $row[8] ?? null; // status
                    $remarks = $row[9] ?? null; // remarks
                    $quantity = $row[11] ?? 0; // quantity
                    $subjectGradeLevelIds = $row[13] ?? null;

                    // Skip if no title
                    if (empty($title)) {
                        $skippedCount++;
                        continue;
                    }

                    // Process title
                    $titleName = ucwords(strtolower(trim($title)));
                    $printTitle = PrintTitle::firstOrCreate(
                        ['title' => $titleName],
                        ['id' => (string) Str::uuid()]
                    );

                    // Process author(s)
                    $authorIds = [];
                    if (!empty($author)) {
                        // Split multiple authors by comma
                        $authorNames = array_map('trim', explode(',', $author));

                        foreach ($authorNames as $authorName) {
                            if (!empty($authorName)) {
                                $normalizedName = ucwords(strtolower($authorName));
                                $authorModel = Author::firstOrCreate(
                                    ['author_name' => $normalizedName],
                                    ['id' => (string) Str::uuid()]
                                );
                                $authorIds[] = $authorModel->id;
                            }
                        }

                        // Attach authors to title
                        if (!empty($authorIds)) {
                            $printTitle->authors()->syncWithoutDetaching($authorIds);
                        }
                    }

                    // Map print type
                    $printTypeId = $typeMapping[$type] ?? $typeMapping['SLR'];

                    // Process subject grade level IDs
                    // PostgreSQL array format: {uuid1,uuid2,uuid3}
                    $gradeLevelIds = null;
                    if (!empty($subjectGradeLevelIds)) {
                        // Remove curly braces and split by comma
                        $subjectGradeLevelIds = str_replace(['{', '}'], '', $subjectGradeLevelIds);
                        $idsArray = array_map('trim', explode(',', $subjectGradeLevelIds));
                        // Join with comma for storage (matching the format in addPrintResource)
                        $gradeLevelIds = implode(',', $idsArray);
                    }

                    // Create print resource
                    $printResource = PrintResource::create([
                        'id' => (string) Str::uuid(),
                        'print_title_id' => $printTitle->id,
                        'print_type_id' => $printTypeId,
                        'publisher' => !empty($publisher) ? ucwords(strtolower($publisher)) : 'publisher',
                        'volume' => !empty($volume) ? $volume : 'volume',
                        'edition' => 'edition', // Not in source data
                        'copyright' => !empty($copyrightYear) ? (int)$copyrightYear : 0,
                        'pages' => !empty($pages) ? (int)$pages : 0,
                        'isbn' => 'isbn', // Not in source data
                        'subject_grade_level_ids' => $gradeLevelIds,
                        'library_id' => $libraryId,
                        'cover' => null, // No image in import
                    ]);

                    // Create acquisition record
                    $acquisitionId = (string) Str::uuid();
                    $totalQty = !empty($quantity) ? (int)$quantity : 0;

                    // Determine status quantities based on the status field
                    $usable = 0;
                    $partiallyDamaged = 0;
                    $damaged = 0;
                    $lost = 0;
                    $condemnable = 0;

                    $statusLower = strtolower(trim($status ?? 'usable'));
                    switch ($statusLower) {
                        case 'usable':
                            $usable = $totalQty;
                            break;
                        case 'partially damaged':
                        case 'partially_damaged':
                            $partiallyDamaged = $totalQty;
                            break;
                        case 'damaged':
                            $damaged = $totalQty;
                            break;
                        case 'lost':
                            $lost = $totalQty;
                            break;
                        case 'condemnable':
                            $condemnable = $totalQty;
                            break;
                        default:
                            $usable = $totalQty; // Default to usable
                    }

                    PrintAcquisition::create([
                        'id' => $acquisitionId,
                        'print_id' => $printResource->id,
                        'source' => !empty($source) ? $source : 'CO',
                        'date_acquired' => $now->format('Y-m-d'),
                        'cost' => 0,
                        'iar' => 'iar',
                        'usable' => $usable,
                        'partially_damaged' => $partiallyDamaged,
                        'damaged' => $damaged,
                        'lost' => $lost,
                        'condemnable' => $condemnable,
                        'total_qty' => $totalQty,
                        'remarks' => !empty($remarks) ? $remarks : 'Imported from Excel',
                        'encoded_by' => NULL,
                        'date_encoded' => $now,
                    ]);

                    // Create masterlist records
                    $statusMap = [
                        'usable' => 'USABLE',
                        'partially_damaged' => 'PARTIALLY DAMAGED',
                        'damaged' => 'DAMAGED',
                        'lost' => 'LOST',
                        'condemnable' => 'CONDEMNABLE',
                    ];

                    $masterlistInserts = [];
                    $statusQuantities = [
                        'usable' => $usable,
                        'partially_damaged' => $partiallyDamaged,
                        'damaged' => $damaged,
                        'lost' => $lost,
                        'condemnable' => $condemnable,
                    ];

                    foreach ($statusQuantities as $field => $qty) {
                        for ($i = 0; $i < $qty; $i++) {
                            $masterlistInserts[] = [
                                'id' => (string) Str::uuid(),
                                'print_acquisition_id' => $acquisitionId,
                                'status' => $statusMap[$field],
                            ];
                        }
                    }

                    // Bulk insert masterlist records
                    if (!empty($masterlistInserts)) {
                        foreach (array_chunk($masterlistInserts, 500) as $chunk) {
                            PrintMasterlist::insert($chunk);
                        }
                    }

                    // Update search vector
                    DB::statement('
                        UPDATE print_resources
                        SET search_vector = build_print_resource_search_vector(id)
                        WHERE id = ?
                    ', [$printResource->id]);

                    $importedCount++;

                } catch (\Exception $e) {
                    Log::error('Error importing row: ' . $e->getMessage(), ['row' => $row]);
                    $skippedCount++;
                    continue;
                }
            }

            DB::commit();

            return redirect()
                ->back()
                ->with('success', "Successfully imported {$importedCount} resources. Skipped {$skippedCount} rows.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import failed: ' . $e->getMessage());

            return redirect()
                ->back()
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
