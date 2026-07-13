<?php

namespace App\Http\Controllers\Resource;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

use App\Services\Resource\Exports\ExportPrintResourceService;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PrintResourceExportController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $exportService;

    public function __construct(ExportPrintResourceService $exportService)
    {
        $this->middleware('auth');
        $this->exportService = $exportService;
    }

    public function export(Request $request)
    {
        $user      = Auth::user();
        $level     = $user->userType?->level ?? 0;
        $stationId = (string) $user->station_id;

        // Service applies the same level-based scoping as the list view.
        // Returns both the resources and the scoped library IDs so we can call
        // scopedQuantities() and show only counts from the relevant libraries.
        $result     = $this->exportService->getExportData($request, $level, $stationId);
        $resources  = $result['resources'];
        $libraryIds = $result['libraryIds']->values()->all();

        // Bail early rather than producing a confusingly empty spreadsheet
        if ($resources->isEmpty()) {
            return back()->with('error', 'No data available to export with the current filters.');
        }

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $spreadsheet->getProperties()
            ->setCreator(config('app.name'))
            ->setTitle('Print Resources Export')
            ->setSubject('Library Resources')
            ->setDescription('Export of library print resources');

        $headers = [
            'A1' => 'Title',
            'B1' => 'Author(s)',
            'C1' => 'Publisher',
            'D1' => 'Type',
            'E1' => 'Subject & Grade',
            'F1' => 'ISBN',
            'G1' => 'Copyright',
            'H1' => 'Date Acquired',
            'I1' => 'Usable',
            'J1' => 'Partially Damaged',
            'K1' => 'Damaged',
            'L1' => 'Lost',
            'M1' => 'Condemnable',
            'N1' => 'Total Quantity'
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        $headerStyle = [
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 12
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => '000000']
                ]
            ]
        ];

        $sheet->getStyle('A1:N1')->applyFromArray($headerStyle);

        $columnWidths = [
            'A' => 35,
            'B' => 25,
            'C' => 20,
            'D' => 15,
            'E' => 30,
            'F' => 15,
            'G' => 12,
            'H' => 16,
            'I' => 10,
            'J' => 12,
            'K' => 10,
            'L' => 10,
            'M' => 12,
            'N' => 12
        ];

        foreach ($columnWidths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $row = 2;

        foreach ($resources as $resource) {
            $authors = $resource->printTitle->authors->pluck('author_name')->join(', ');

            $subjects = [];
            if ($resource->subjects()->count()) {
                foreach ($resource->subjects() as $sub) {
                    $subjects[] = $sub->subject->subject_name . ' - ' . $sub->gradeLevel->grade;
                }
            }
            $subjectsText = $subjects ? implode(', ', $subjects) : 'No assignment';

            // scopedQuantities() sums only acquisitions belonging to the filtered
            // library IDs — prevents double-counting across schools/divisions.
            $qty   = $resource->scopedQuantities($libraryIds);
            $total = array_sum($qty);

            $sheet->setCellValue('A' . $row, $resource->printTitle->title);
            $sheet->setCellValue('B' . $row, $authors);
            $sheet->setCellValue('C' . $row, $resource->publisher);
            $sheet->setCellValue('D' . $row, $resource->type->type_name);
            $sheet->setCellValue('E' . $row, $subjectsText);
            $sheet->setCellValue('F' . $row, $resource->isbn);
            $sheet->setCellValue('G' . $row, $resource->copyright);

            $latestDate = $resource->printAcquisitions->pluck('date_acquired')->filter()->sortDesc()->first();
            $sheet->setCellValue('H' . $row, $latestDate ? date('M d, Y', strtotime($latestDate)) : '');

            $sheet->setCellValue('I' . $row, $qty['usable']);
            $sheet->setCellValue('J' . $row, $qty['partially_damaged']);
            $sheet->setCellValue('K' . $row, $qty['damaged']);
            $sheet->setCellValue('L' . $row, $qty['lost']);
            $sheet->setCellValue('M' . $row, $qty['condemnable']);
            $sheet->setCellValue('N' . $row, $total);

            // Wrap text on columns that can get long so they stay readable without manual resizing
            $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true);
            $sheet->getStyle('E' . $row)->getAlignment()->setWrapText(true);

            $sheet->getStyle('I' . $row . ':N' . $row)
                  ->getAlignment()
                  ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
        }

        $dataRange = 'A1:N' . ($row - 1);
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'CCCCCC']
                ]
            ]
        ]);

        if ($row > 2) {
            $totalRow = $row;
            $sheet->setCellValue('A' . $totalRow, 'TOTAL');
            $sheet->mergeCells('A' . $totalRow . ':G' . $totalRow);

            // Use Excel formulas so the totals stay correct if the user edits the file
            $sheet->setCellValue('I' . $totalRow, '=SUM(I2:I' . ($row - 1) . ')');
            $sheet->setCellValue('J' . $totalRow, '=SUM(J2:J' . ($row - 1) . ')');
            $sheet->setCellValue('K' . $totalRow, '=SUM(K2:K' . ($row - 1) . ')');
            $sheet->setCellValue('L' . $totalRow, '=SUM(L2:L' . ($row - 1) . ')');
            $sheet->setCellValue('M' . $totalRow, '=SUM(M2:M' . ($row - 1) . ')');
            $sheet->setCellValue('N' . $totalRow, '=SUM(N2:N' . ($row - 1) . ')');

            $totalStyle = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E5E7EB']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER
                ]
            ];
            $sheet->getStyle('A' . $totalRow . ':N' . $totalRow)->applyFromArray($totalStyle);
        }

        // Keep the header visible while scrolling through large exports
        $sheet->freezePane('A2');

        $timestamp = now()->format('Y-m-d_His');
        $levelName = $this->getLevelName($level);
        $filename  = "Print_Resources_{$levelName}_{$timestamp}.xlsx";

        $writer = new Xlsx($spreadsheet);

        // Stream to php://output to avoid writing a temp file to disk
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit; // Stop here — any buffered output after this would corrupt the binary stream
    }

    private function getLevelName(int $level): string
    {
        return match($level) {
            ExportPrintResourceService::LEVEL_SCHOOL   => 'School',
            ExportPrintResourceService::LEVEL_DISTRICT => 'District',
            ExportPrintResourceService::LEVEL_DIVISION => 'Division',
            ExportPrintResourceService::LEVEL_REGION   => 'Region',
            default => 'Unknown'
        };
    }
}