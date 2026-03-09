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
            'H1' => 'Usable',
            'I1' => 'Partially Damaged',
            'J1' => 'Damaged',
            'K1' => 'Lost',
            'L1' => 'Condemnable',
            'M1' => 'Total Quantity'
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

        $sheet->getStyle('A1:M1')->applyFromArray($headerStyle);

        $columnWidths = [
            'A' => 35,
            'B' => 25,
            'C' => 20,
            'D' => 15,
            'E' => 30,
            'F' => 15,
            'G' => 12,
            'H' => 10,
            'I' => 12,
            'J' => 10,
            'K' => 10,
            'L' => 12,
            'M' => 12
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

            $qty   = $resource->quantities;
            $total = array_sum($qty);

            $sheet->setCellValue('A' . $row, $resource->printTitle->title);
            $sheet->setCellValue('B' . $row, $authors);
            $sheet->setCellValue('C' . $row, $resource->publisher);
            $sheet->setCellValue('D' . $row, $resource->type->type_name);
            $sheet->setCellValue('E' . $row, $subjectsText);
            $sheet->setCellValue('F' . $row, $resource->isbn);
            $sheet->setCellValue('G' . $row, $resource->copyright);
            $sheet->setCellValue('H' . $row, $qty['usable']);
            $sheet->setCellValue('I' . $row, $qty['partially_damaged']);
            $sheet->setCellValue('J' . $row, $qty['damaged']);
            $sheet->setCellValue('K' . $row, $qty['lost']);
            $sheet->setCellValue('L' . $row, $qty['condemnable']);
            $sheet->setCellValue('M' . $row, $total);

            // Wrap text on columns that can get long so they stay readable without manual resizing
            $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true);
            $sheet->getStyle('E' . $row)->getAlignment()->setWrapText(true);

            $sheet->getStyle('H' . $row . ':M' . $row)
                  ->getAlignment()
                  ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
        }

        $dataRange = 'A1:M' . ($row - 1);
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
            $sheet->setCellValue('H' . $totalRow, '=SUM(H2:H' . ($row - 1) . ')');
            $sheet->setCellValue('I' . $totalRow, '=SUM(I2:I' . ($row - 1) . ')');
            $sheet->setCellValue('J' . $totalRow, '=SUM(J2:J' . ($row - 1) . ')');
            $sheet->setCellValue('K' . $totalRow, '=SUM(K2:K' . ($row - 1) . ')');
            $sheet->setCellValue('L' . $totalRow, '=SUM(L2:L' . ($row - 1) . ')');
            $sheet->setCellValue('M' . $totalRow, '=SUM(M2:M' . ($row - 1) . ')');

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
            $sheet->getStyle('A' . $totalRow . ':M' . $totalRow)->applyFromArray($totalStyle);
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