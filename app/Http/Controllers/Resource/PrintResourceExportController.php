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

    /**
     * Export filtered resources to Excel
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $level = $user->userType?->level ?? 0;
        $stationId = (string) $user->station_id;

        // Get export data
        $resources = $this->exportService->getExportData($request, $level, $stationId);

        if ($resources->isEmpty()) {
            return back()->with('error', 'No data available to export with the current filters.');
        }

        // Create spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator(config('app.name'))
            ->setTitle('Print Resources Export')
            ->setSubject('Library Resources')
            ->setDescription('Export of library print resources');

        // Define headers
        $headers = [
            'A1' => 'Title',
            'B1' => 'Author(s)',
            'C1' => 'Publisher',
            'D1' => 'Type',
            'E1' => 'Subject & Grade',
            'F1' => 'ISBN',
            'G1' => 'Copyright',
            'H1' => 'Library/Station',
            'I1' => 'Usable',
            'J1' => 'Partially Damaged',
            'K1' => 'Damaged',
            'L1' => 'Lost',
            'M1' => 'Condemnable',
            'N1' => 'Total Quantity'
        ];

        // Apply headers
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style header row
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'] // Blue
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ];

        $sheet->getStyle('A1:N1')->applyFromArray($headerStyle);

        // Set column widths
        $columnWidths = [
            'A' => 35, // Title
            'B' => 25, // Authors
            'C' => 20, // Publisher
            'D' => 15, // Type
            'E' => 30, // Subject & Grade
            'F' => 15, // ISBN
            'G' => 12, // Copyright
            'H' => 25, // Library/Station
            'I' => 10, // Usable
            'J' => 12, // Partially Damaged
            'K' => 10, // Damaged
            'L' => 10, // Lost
            'M' => 12, // Condemnable
            'N' => 12  // Total
        ];

        foreach ($columnWidths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        // Populate data
        $row = 2;
        foreach ($resources as $resource) {
            // Get authors
            $authors = $resource->printTitle->authors->pluck('author_name')->join(', ');

            // Get subjects and grades
            $subjects = [];
            if ($resource->subjects()->count()) {
                foreach ($resource->subjects() as $sub) {
                    $subjects[] = $sub->subject->subject_name . ' - ' . $sub->gradeLevel->grade;
                }
            }
            $subjectsText = $subjects ? implode(', ', $subjects) : 'No assignment';

            // Get quantities
            $qty = $resource->quantities;
            $total = array_sum($qty);

            // Set cell values
            $sheet->setCellValue('A' . $row, $resource->printTitle->title);
            $sheet->setCellValue('B' . $row, $authors);
            $sheet->setCellValue('C' . $row, $resource->publisher);
            $sheet->setCellValue('D' . $row, $resource->type->type_name);
            $sheet->setCellValue('E' . $row, $subjectsText);
            $sheet->setCellValue('F' . $row, $resource->isbn);
            $sheet->setCellValue('G' . $row, $resource->copyright);
            $sheet->setCellValue('H' . $row, $resource->library_name ?? 'N/A');
            $sheet->setCellValue('I' . $row, $qty['usable']);
            $sheet->setCellValue('J' . $row, $qty['partially_damaged']);
            $sheet->setCellValue('K' . $row, $qty['damaged']);
            $sheet->setCellValue('L' . $row, $qty['lost']);
            $sheet->setCellValue('M' . $row, $qty['condemnable']);
            $sheet->setCellValue('N' . $row, $total);

            // Apply text wrapping for long text columns
            $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true);
            $sheet->getStyle('E' . $row)->getAlignment()->setWrapText(true);

            // Center align quantity columns
            $sheet->getStyle('I' . $row . ':N' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
        }

        // Apply borders to all data
        $dataRange = 'A1:N' . ($row - 1);
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ]
        ]);

        // Add totals row if there are resources
        if ($row > 2) {
            $totalRow = $row;
            $sheet->setCellValue('A' . $totalRow, 'TOTAL');
            $sheet->mergeCells('A' . $totalRow . ':H' . $totalRow);

            // Sum formulas for quantity columns
            $sheet->setCellValue('I' . $totalRow, '=SUM(I2:I' . ($row - 1) . ')');
            $sheet->setCellValue('J' . $totalRow, '=SUM(J2:J' . ($row - 1) . ')');
            $sheet->setCellValue('K' . $totalRow, '=SUM(K2:K' . ($row - 1) . ')');
            $sheet->setCellValue('L' . $totalRow, '=SUM(L2:L' . ($row - 1) . ')');
            $sheet->setCellValue('M' . $totalRow, '=SUM(M2:M' . ($row - 1) . ')');
            $sheet->setCellValue('N' . $totalRow, '=SUM(N2:N' . ($row - 1) . ')');

            // Style totals row
            $totalStyle = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E5E7EB'] // Gray
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ];
            $sheet->getStyle('A' . $totalRow . ':N' . $totalRow)->applyFromArray($totalStyle);
        }

        // Freeze header row
        $sheet->freezePane('A2');

        // Generate filename with timestamp
        $timestamp = now()->format('Y-m-d_His');
        $levelName = $this->getLevelName($level);
        $filename = "Print_Resources_{$levelName}_{$timestamp}.xlsx";

        // Create writer and save to output
        $writer = new Xlsx($spreadsheet);

        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    /**
     * Get level name for filename
     */
    private function getLevelName(int $level): string
    {
        return match($level) {
            ExportPrintResourceService::LEVEL_SCHOOL => 'School',
            ExportPrintResourceService::LEVEL_DISTRICT => 'District',
            ExportPrintResourceService::LEVEL_DIVISION => 'Division',
            ExportPrintResourceService::LEVEL_REGION => 'Region',
            default => 'Unknown'
        };
    }
}
