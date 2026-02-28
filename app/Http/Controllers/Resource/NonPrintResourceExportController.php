<?php

namespace App\Http\Controllers\Resource;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

use App\Services\Resource\Exports\ExportNonPrintResourceService;

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

    public function export(Request $request)
    {
        $user      = Auth::user();
        $level     = $user->userType?->level ?? 0;
        $stationId = (string) $user->station_id;

        // Service applies the same level-based scoping as the list view
        $resources = $this->exportService->getExportData($request, $level, $stationId);

        // Bail early rather than producing a confusingly empty spreadsheet
        if ($resources->isEmpty()) {
            return back()->with('error', 'No data available to export with the current filters.');
        }

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $spreadsheet->getProperties()
            ->setCreator(config('app.name'))
            ->setTitle('Non-Print Resources Export')
            ->setSubject('Library Non-Print Resources')
            ->setDescription('Export of library non-print resources');

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

        $sheet->getStyle('A1:P1')->applyFromArray($headerStyle);

        $columnWidths = [
            'A' => 35,
            'B' => 15,
            'C' => 20,
            'D' => 15,
            'E' => 12,
            'F' => 30,
            'G' => 12,
            'H' => 20,
            'I' => 25,
            'J' => 30,
            'K' => 10,
            'L' => 12,
            'M' => 10,
            'N' => 10,
            'O' => 12,
            'P' => 12
        ];

        foreach ($columnWidths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $row = 2;

        foreach ($resources as $resource) {
            // Non-print resources can have multiple acquisitions with different brands/codes —
            // using the first one is an approximation, but acceptable for the export format
            $acquisition = $resource->nonprintAcquisitions->first();

            $subjects = [];
            if ($resource->subjects()->count()) {
                foreach ($resource->subjects() as $sub) {
                    $subjects[] = $sub->subject->subject_name . ' - ' . $sub->gradeLevel->grade;
                }
            }
            $subjectsText = $subjects ? implode(', ', $subjects) : 'No assignment';

            // Library name lives at the acquisition level for non-print resources
            $libraryName = $resource->nonprintAcquisitions
                ->whereNotNull('library_name')
                ->value('library_name')
                ?? ($resource->nonprintAcquisitions->isNotEmpty() ? 'Unknown Library' : 'No Library Assigned');

            $qty   = $resource->quantities;
            $total = array_sum($qty);

            $sheet->setCellValue('A' . $row, $resource->nonprintTitle->title);
            $sheet->setCellValue('B' . $row, $resource->type->type_name);
            $sheet->setCellValue('C' . $row, $acquisition?->brand   ?? 'N/A');
            $sheet->setCellValue('D' . $row, $acquisition?->code    ?? 'N/A');
            $sheet->setCellValue('E' . $row, $acquisition?->version ?? 'N/A');
            $sheet->setCellValue('F' . $row, $acquisition?->url     ?? 'N/A');
            $sheet->setCellValue('G' . $row, $acquisition?->size    ?? 'N/A');
            $sheet->setCellValue('H' . $row, $acquisition?->model   ?? 'N/A');
            $sheet->setCellValue('I' . $row, $libraryName);
            $sheet->setCellValue('J' . $row, $subjectsText);
            $sheet->setCellValue('K' . $row, $qty['usable']);
            $sheet->setCellValue('L' . $row, $qty['partially_damaged']);
            $sheet->setCellValue('M' . $row, $qty['damaged']);
            $sheet->setCellValue('N' . $row, $qty['lost']);
            $sheet->setCellValue('O' . $row, $qty['condemnable']);
            $sheet->setCellValue('P' . $row, $total);

            // Wrap text on columns that can get long so they stay readable without manual resizing
            $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true);
            $sheet->getStyle('F' . $row)->getAlignment()->setWrapText(true);
            $sheet->getStyle('J' . $row)->getAlignment()->setWrapText(true);

            $sheet->getStyle('K' . $row . ':P' . $row)
                  ->getAlignment()
                  ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
        }

        $dataRange = 'A1:P' . ($row - 1);
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
            $sheet->mergeCells('A' . $totalRow . ':J' . $totalRow);

            // Use Excel formulas so the totals stay correct if the user edits the file
            $sheet->setCellValue('K' . $totalRow, '=SUM(K2:K' . ($row - 1) . ')');
            $sheet->setCellValue('L' . $totalRow, '=SUM(L2:L' . ($row - 1) . ')');
            $sheet->setCellValue('M' . $totalRow, '=SUM(M2:M' . ($row - 1) . ')');
            $sheet->setCellValue('N' . $totalRow, '=SUM(N2:N' . ($row - 1) . ')');
            $sheet->setCellValue('O' . $totalRow, '=SUM(O2:O' . ($row - 1) . ')');
            $sheet->setCellValue('P' . $totalRow, '=SUM(P2:P' . ($row - 1) . ')');

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
            $sheet->getStyle('A' . $totalRow . ':P' . $totalRow)->applyFromArray($totalStyle);
        }

        // Keep the header visible while scrolling through large exports
        $sheet->freezePane('A2');

        $timestamp = now()->format('Y-m-d_His');
        $levelName = $this->getLevelName($level);
        $filename  = "NonPrint_Resources_{$levelName}_{$timestamp}.xlsx";

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
            ExportNonPrintResourceService::LEVEL_SCHOOL   => 'School',
            ExportNonPrintResourceService::LEVEL_DISTRICT => 'District',
            ExportNonPrintResourceService::LEVEL_DIVISION => 'Division',
            ExportNonPrintResourceService::LEVEL_REGION   => 'Region',
            default => 'Unknown'
        };
    }
}
