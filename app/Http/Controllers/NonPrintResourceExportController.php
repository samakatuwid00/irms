<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Services\ExportNonPrintResourceService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class NonPrintResourceExportController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $exportService;

    public function __construct(ExportNonPrintResourceService $exportService)
    {
        $this->middleware('auth');
        $this->exportService = $exportService;
    }

    /**
     * Export filtered non-print resources to Excel
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
            ->setTitle('Non-Print Resources Export')
            ->setSubject('Library Non-Print Resources')
            ->setDescription('Export of library non-print resources');

        // Define headers
        $headers = [
            'A1' => 'Title',
            'B1' => 'Type',
            'C1' => 'Brand',
            'D1' => 'Code',
            'E1' => 'Version',
            'F1' => 'URL',
            'G1' => 'Size',
            'H1' => 'Model',
            'I1' => 'Library/Station',
            'J1' => 'Subject & Grade',
            'K1' => 'Usable',
            'L1' => 'Partially Damaged',
            'M1' => 'Damaged',
            'N1' => 'Lost',
            'O1' => 'Condemnable',
            'P1' => 'Total Quantity'
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

        $sheet->getStyle('A1:P1')->applyFromArray($headerStyle);

        // Set column widths
        $columnWidths = [
            'A' => 35, // Title
            'B' => 15, // Type
            'C' => 20, // Brand
            'D' => 15, // Code
            'E' => 12, // Version
            'F' => 30, // URL
            'G' => 12, // Size
            'H' => 20, // Model
            'I' => 25, // Library/Station
            'J' => 30, // Subject & Grade
            'K' => 10, // Usable
            'L' => 12, // Partially Damaged
            'M' => 10, // Damaged
            'N' => 10, // Lost
            'O' => 12, // Condemnable
            'P' => 12  // Total
        ];

        foreach ($columnWidths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        // Populate data
        $row = 2;
        foreach ($resources as $resource) {
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
            $sheet->setCellValue('A' . $row, $resource->nonprintTitle->title);
            $sheet->setCellValue('B' . $row, $resource->type->type_name);
            $sheet->setCellValue('C' . $row, $resource->brand ?? 'N/A');
            $sheet->setCellValue('D' . $row, $resource->code ?? 'N/A');
            $sheet->setCellValue('E' . $row, $resource->version ?? 'N/A');
            $sheet->setCellValue('F' . $row, $resource->url ?? 'N/A');
            $sheet->setCellValue('G' . $row, $resource->size ?? 'N/A');
            $sheet->setCellValue('H' . $row, $resource->model ?? 'N/A');
            $sheet->setCellValue('I' . $row, $resource->library_name ?? 'N/A');
            $sheet->setCellValue('J' . $row, $subjectsText);
            $sheet->setCellValue('K' . $row, $qty['usable']);
            $sheet->setCellValue('L' . $row, $qty['partially_damaged']);
            $sheet->setCellValue('M' . $row, $qty['damaged']);
            $sheet->setCellValue('N' . $row, $qty['lost']);
            $sheet->setCellValue('O' . $row, $qty['condemnable']);
            $sheet->setCellValue('P' . $row, $total);

            // Apply text wrapping for long text columns
            $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true);
            $sheet->getStyle('F' . $row)->getAlignment()->setWrapText(true);
            $sheet->getStyle('J' . $row)->getAlignment()->setWrapText(true);

            // Center align quantity columns
            $sheet->getStyle('K' . $row . ':P' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
        }

        // Apply borders to all data
        $dataRange = 'A1:P' . ($row - 1);
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
            $sheet->mergeCells('A' . $totalRow . ':J' . $totalRow);

            // Sum formulas for quantity columns
            $sheet->setCellValue('K' . $totalRow, '=SUM(K2:K' . ($row - 1) . ')');
            $sheet->setCellValue('L' . $totalRow, '=SUM(L2:L' . ($row - 1) . ')');
            $sheet->setCellValue('M' . $totalRow, '=SUM(M2:M' . ($row - 1) . ')');
            $sheet->setCellValue('N' . $totalRow, '=SUM(N2:N' . ($row - 1) . ')');
            $sheet->setCellValue('O' . $totalRow, '=SUM(O2:O' . ($row - 1) . ')');
            $sheet->setCellValue('P' . $totalRow, '=SUM(P2:P' . ($row - 1) . ')');

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
            $sheet->getStyle('A' . $totalRow . ':P' . $totalRow)->applyFromArray($totalStyle);
        }

        // Freeze header row
        $sheet->freezePane('A2');

        // Generate filename with timestamp
        $timestamp = now()->format('Y-m-d_His');
        $levelName = $this->getLevelName($level);
        $filename = "NonPrint_Resources_{$levelName}_{$timestamp}.xlsx";

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
            ExportNonPrintResourceService::LEVEL_SCHOOL => 'School',
            ExportNonPrintResourceService::LEVEL_DISTRICT => 'District',
            ExportNonPrintResourceService::LEVEL_DIVISION => 'Division',
            ExportNonPrintResourceService::LEVEL_REGION => 'Region',
            default => 'Unknown'
        };
    }
}
