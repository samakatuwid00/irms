<?php

namespace App\Http\Controllers\Station;

use App\Http\Controllers\Controller;
use App\Models\GradeOffering;
use App\Models\Population;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class ImportSF6Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index()
    {
        $schoolYears = SchoolYear::orderBy('year_end', 'desc')->get();
        return view('pages.import-sf6', compact('schoolYears'));
    }

    public function preview(Request $request)
    {
        $request->validate([
            'sf6_file' => 'required|file|mimes:xls,xlsx|max:5120',
            'sy_id'    => 'required|exists:school_years,id',
        ]);

        try {
            $data = $this->extractFromFile($request->file('sf6_file'));
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not read the file: ' . $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data'    => $data,
            'sy_id'   => $request->sy_id,
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'sf6_file' => 'required|file|mimes:xls,xlsx|max:5120',
            'sy_id'    => 'required|exists:school_years,id',
        ]);

        $schoolId = Auth::user()->station_id;
        $syId     = $request->sy_id;

        try {
            $data = $this->extractFromFile($request->file('sf6_file'));
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withErrors(['sf6_file' => 'Could not read the file: ' . $e->getMessage()]);
        }

        $gradeOffering = GradeOffering::where('school_id', $schoolId)->first();
        if (!$gradeOffering) {
            return redirect()->back()
                ->withErrors(['sf6_file' => 'Please configure grade offerings before importing.']);
        }

        $populationData = [
            'school_id'  => $schoolId,
            'sy_id'      => $syId,
            'encoded_by' => Auth::id(),
        ];

        $gradeMap = [
            'K'   => ['k_m',   'k_f',   'k_total'],
            'g1'  => ['g1_m',  'g1_f',  'g1_total'],
            'g2'  => ['g2_m',  'g2_f',  'g2_total'],
            'g3'  => ['g3_m',  'g3_f',  'g3_total'],
            'g4'  => ['g4_m',  'g4_f',  'g4_total'],
            'g5'  => ['g5_m',  'g5_f',  'g5_total'],
            'g6'  => ['g6_m',  'g6_f',  'g6_total'],
            'g7'  => ['g7_m',  'g7_f',  'g7_total'],
            'g8'  => ['g8_m',  'g8_f',  'g8_total'],
            'g9'  => ['g9_m',  'g9_f',  'g9_total'],
            'g10' => ['g10_m', 'g10_f', 'g10_total'],
            'g11' => ['g11_m', 'g11_f', 'g11_total'],
            'g12' => ['g12_m', 'g12_f', 'g12_total'],
        ];

        foreach ($gradeMap as $gradeKey => [$mField, $fField, $tField]) {
            if ($gradeOffering->{$gradeKey} === 'yes' && isset($data[$gradeKey])) {
                $populationData[$mField] = $data[$gradeKey]['male']   ?? 0;
                $populationData[$fField] = $data[$gradeKey]['female'] ?? 0;
                $populationData[$tField] = $data[$gradeKey]['total']  ?? 0;
            }
        }

        $existing = Population::where('school_id', $schoolId)->where('sy_id', $syId)->first();

        if ($existing) {
            $existing->update($populationData);
            $message = 'SF6 data imported and population updated successfully!';
        } else {
            $populationData['id'] = (string) Str::uuid();
            Population::create($populationData);
            $message = 'SF6 data imported and population created successfully!';
        }

        return redirect()
            ->route('school-profile', ['sy_id' => $syId])
            ->with('success', $message);
    }

    private const GRADE_LABEL_MAP = [
        'KINDERGARTEN' => 'K', 'KINDER' => 'K', 'KG' => 'K', 'K' => 'K',
        'GRADE 1' => 'g1', 'GRADE 2' => 'g2', 'GRADE 3' => 'g3', 'GRADE 4' => 'g4',
        'GRADE 5' => 'g5', 'GRADE 6' => 'g6', 'GRADE 7' => 'g7', 'GRADE 8' => 'g8',
        'GRADE 9' => 'g9', 'GRADE 10' => 'g10', 'GRADE 11' => 'g11', 'GRADE 12' => 'g12',
        'GRADE1' => 'g1', 'GRADE2' => 'g2', 'GRADE3' => 'g3', 'GRADE4' => 'g4',
        'GRADE5' => 'g5', 'GRADE6' => 'g6', 'GRADE7' => 'g7', 'GRADE8' => 'g8',
        'GRADE9' => 'g9', 'GRADE10' => 'g10', 'GRADE11' => 'g11', 'GRADE12' => 'g12',
    ];


    private function extractFromFile($file): array
    {
        $filePath = $file->getRealPath();

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        
        $spreadsheet = $reader->load($filePath);
        $sheet       = $spreadsheet->getActiveSheet();

        $rows = [];
        foreach ($sheet->getRowIterator() as $row) {
            $cells = [];
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); 
            foreach ($cellIterator as $cell) {
                $cells[] = $cell->getValue();
            }
            $rows[] = $cells;
        }

        $gradeHeaderRowIndex = null;
        $gradeCols           = [];

        foreach ($rows as $rowIdx => $row) {
            $found = [];
            foreach ($row as $colIdx => $cell) {
                $normalized = strtoupper(trim((string) $cell));
                $normalized = preg_replace('/\s+/', ' ', $normalized);

                if (isset(self::GRADE_LABEL_MAP[$normalized])) {
                    $found[self::GRADE_LABEL_MAP[$normalized]] = $colIdx;
                }
            }
            if (count($found) >= 1) {
                $gradeHeaderRowIndex = $rowIdx;
                $gradeCols           = $found;
                break;
            }
        }

        if ($gradeHeaderRowIndex === null || empty($gradeCols)) {
            throw new \RuntimeException('Could not find any grade headers in the SF6 file.');
        }

        $subHeaderRowIndex = null;
        $subHeaderRow      = [];

        for ($i = $gradeHeaderRowIndex + 1; $i <= $gradeHeaderRowIndex + 5; $i++) {
            if (!isset($rows[$i])) break;
            $rowUpperCase = array_map(fn($v) => strtoupper(trim((string) $v)), $rows[$i]);
            if (in_array('MALE', $rowUpperCase) || in_array('FEMALE', $rowUpperCase)) {
                $subHeaderRowIndex = $i;
                $subHeaderRow      = $rowUpperCase;
                break;
            }
        }

        if ($subHeaderRowIndex === null) {
            throw new \RuntimeException('Could not find the Male / Female sub-header row.');
        }

        asort($gradeCols);
        $gradeKeys     = array_keys($gradeCols);
        $gradeColStart = array_values($gradeCols);
        $gradeCount    = count($gradeKeys);
        $gradeColMap   = [];

        for ($g = 0; $g < $gradeCount; $g++) {
            $startCol = $gradeColStart[$g];
            $endCol = ($g + 1 < $gradeCount) ? $gradeColStart[$g + 1] - 1 : count($subHeaderRow) - 1;

            $maleCol = $femaleCol = $totalCol = null;

            for ($col = $startCol; $col <= $endCol; $col++) {
                $label = $subHeaderRow[$col] ?? '';
                if ($label === 'MALE'   && $maleCol   === null) $maleCol   = $col;
                if ($label === 'FEMALE' && $femaleCol === null) $femaleCol = $col;
                if ($label === 'TOTAL'  && $totalCol  === null) $totalCol  = $col;
            }

            if ($maleCol !== null && $femaleCol !== null) {
                $gradeColMap[$gradeKeys[$g]] = [
                    'male'   => $maleCol,
                    'female' => $femaleCol,
                    'total'  => $totalCol,
                ];
            }
        }

        $totalRowIndex = null;
        foreach ($rows as $i => $row) {
            if (isset($row[0]) && strtoupper(trim((string) $row[0])) === 'TOTAL') {
                $totalRowIndex = $i;
                break;
            }
        }

        if ($totalRowIndex === null) {
            throw new \RuntimeException('Could not find the TOTAL row in the SF6 file.');
        }

        $totalRow = $rows[$totalRowIndex];
        $int      = fn($v) => (int) ($v ?? 0);
        $result   = [];

        foreach ($gradeColMap as $gradeKey => $cols) {
            $male   = $int($totalRow[$cols['male']]   ?? null);
            $female = $int($totalRow[$cols['female']] ?? null);
            $total  = ($cols['total'] !== null) ? $int($totalRow[$cols['total']] ?? null) : $male + $female;
            $result[$gradeKey] = compact('male', 'female', 'total');
        }

        return $result;
    }
}