<?php

namespace App\Services;

use App\Models\Mitra;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class MitraExportService
{
    private const HEADERS = [
        'A1' => 'ID',
        'B1' => 'Nama',
        'C1' => 'No. Telepon',
        'D1' => 'Tanggal Lead',
        'E1' => 'Brand',
        'F1' => 'Label',
        'G1' => 'Status Chat',
        'H1' => 'Kota',
        'I1' => 'Provinsi',
        'J1' => 'Marketing',
        'K1' => 'Webinar',
        'L1' => 'Komentar',
        'M1' => 'Created At',
        'N1' => 'Updated At'
    ];

    private const COLUMN_WIDTHS = [
        'A' => 8,   // ID
        'B' => 20,  // Nama
        'C' => 15,  // No. Telepon
        'D' => 12,  // Tanggal Lead
        'E' => 15,  // Brand
        'F' => 15,  // Label
        'G' => 12,  // Status Chat
        'H' => 15,  // Kota
        'I' => 15,  // Provinsi
        'J' => 15,  // Marketing
        'K' => 10,  // Webinar
        'L' => 30,  // Komentar
        'M' => 18,  // Created At
        'N' => 18,  // Updated At
    ];

    public function export(Request $request): array
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            // Get filtered data
            $mitras = $this->getFilteredData($request, $user);
            
            // Create spreadsheet
            $spreadsheet = $this->createSpreadsheet($mitras, $request);
            
            // Generate filename
            $filename = $this->generateFilename($request);
            
            // Create writer
            $writer = new Xlsx($spreadsheet);
            
            return [
                'success' => true,
                'writer' => $writer,
                'filename' => $filename,
                'total_records' => $mitras->count(),
            ];
            
        } catch (\Exception $e) {
            \Log::error('Mitra export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'filters' => $request->all()
            ]);
            
            throw $e;
        }
    }

    private function getFilteredData(Request $request, $user): Collection
    {
        $query = Mitra::with(['brand', 'label', 'user']);

        // Apply role-based filtering
        $query = $user->applyRoleFilter($query, 'user_id');

        // Apply search filter
        if ($request->filled('search')) {
            $this->applySearchFilter($query, $request->search);
        }

        // Apply date filters
        $this->applyDateFilters($query, $request);

        // Apply other filters
        if ($request->filled('chat')) {
            $query->where('chat', $request->chat);
        }

        if ($request->filled('label')) {
            $query->where('label_id', $request->label);
        }

        if ($request->filled('user') && ($user->hasFullAccess() || $user->hasReadOnlyAccess())) {
            $query->where('user_id', $request->user);
        }

        // Order by tanggal_lead descending
        $query->orderBy('tanggal_lead', 'desc')->orderBy('created_at', 'desc');

        return $query->get();
    }

    private function applySearchFilter(Builder $query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('nama', 'like', "%{$search}%")
              ->orWhere('no_telp', 'like', "%{$search}%")
              ->orWhere('kota', 'like', "%{$search}%")
              ->orWhere('provinsi', 'like', "%{$search}%")
              ->orWhereHas('brand', function ($brandQuery) use ($search) {
                  $brandQuery->where('nama', 'like', "%{$search}%");
              })
              ->orWhereHas('label', function ($labelQuery) use ($search) {
                  $labelQuery->where('nama', 'like', "%{$search}%");
              })
              ->orWhereHas('user', function ($userQuery) use ($search) {
                  $userQuery->where('name', 'like', "%{$search}%");
              });
        });
    }

    private function applyDateFilters(Builder $query, Request $request): void
    {
        if ($request->filled('periode_start')) {
            $query->whereDate('tanggal_lead', '>=', $request->periode_start);
        }

        if ($request->filled('periode_end')) {
            $query->whereDate('tanggal_lead', '<=', $request->periode_end);
        }
    }

    private function createSpreadsheet(Collection $mitras, Request $request): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Mitra');

        // Set headers
        $this->setHeaders($sheet);
        
        // Style headers
        $this->styleHeaders($sheet);
        
        // Add data
        $this->addData($sheet, $mitras);
        
        // Set column widths
        $this->setColumnWidths($sheet);
        
        // Apply data styling
        $this->styleData($sheet, $mitras->count());

        // Add metadata sheet
        $this->addMetadataSheet($spreadsheet, $mitras->count(), $request);

        return $spreadsheet;
    }

    private function setHeaders(Worksheet $sheet): void
    {
        foreach (self::HEADERS as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
    }

    private function styleHeaders(Worksheet $sheet): void
    {
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '059669'] // Emerald-600
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'FFFFFF']
                ]
            ]
        ];
        
        $sheet->getStyle('A1:M1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(25);
    }

    private function addData(Worksheet $sheet, Collection $mitras): void
    {
        $row = 2;
        
        foreach ($mitras as $mitra) {
            $sheet->setCellValue('A' . $row, $mitra->id);
            $sheet->setCellValue('B' . $row, $mitra->nama);
            $sheet->setCellValueExplicit('C' . $row, $mitra->no_telp, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('D' . $row, $mitra->tanggal_lead ? $mitra->tanggal_lead->format('Y-m-d') : '');
            $sheet->setCellValue('E' . $row, $mitra->brand->nama ?? '');
            $sheet->setCellValue('F' . $row, $mitra->label->nama ?? '');
            $sheet->setCellValue('G' . $row, $this->formatChatStatus($mitra->chat));
            $sheet->setCellValue('H' . $row, $mitra->kota ?? '');
            $sheet->setCellValue('I' . $row, $mitra->provinsi ?? '');
            $sheet->setCellValue('J' . $row, $mitra->user->name ?? '');
            $sheet->setCellValue('K' . $row, $mitra->webinar ?? 'Tidak');
            $sheet->setCellValue('L' . $row, $mitra->komentar ?? '');
            $sheet->setCellValue('M' . $row, $mitra->created_at->format('Y-m-d H:i:s'));
            $sheet->setCellValue('N' . $row, $mitra->updated_at->format('Y-m-d H:i:s'));
            
            $row++;
        }
    }

    private function formatChatStatus(string $chat): string
    {
        return match($chat) {
            'masuk' => 'Masuk',
            'followup' => 'Follow Up',
            default => ucfirst($chat)
        };
    }

    private function setColumnWidths(Worksheet $sheet): void
    {
        foreach (self::COLUMN_WIDTHS as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    private function styleData(Worksheet $sheet, int $dataCount): void
    {
        if ($dataCount === 0) return;

        $dataRange = 'A2:M' . ($dataCount + 1);
        
        $dataStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP
            ]
        ];
        
        $sheet->getStyle($dataRange)->applyFromArray($dataStyle);

        // Alternating row colors
        for ($row = 2; $row <= $dataCount + 1; $row += 2) {
            $sheet->getStyle("A{$row}:M{$row}")->getFill()
                  ->setFillType(Fill::FILL_SOLID)
                  ->getStartColor()->setRGB('F9FAFB');
        }
    }

    private function addMetadataSheet(Spreadsheet $spreadsheet, int $recordCount, Request $request): void
    {
        $metadataSheet = $spreadsheet->createSheet();
        $metadataSheet->setTitle('Export Info');

        $metadata = [
            ['Export Information'],
            [''],
            ['Export Date', now()->format('Y-m-d H:i:s')],
            ['Exported by', auth()->user()->name],
            ['Total Records', $recordCount],
            ['Filters Applied', ''],
        ];

        // Add filter information
        if ($request->filled('search')) {
            $metadata[] = ['Search Term', $request->search];
        }
        
        if ($request->filled('periode_start')) {
            $metadata[] = ['Start Date', $request->periode_start];
        }
        
        if ($request->filled('periode_end')) {
            $metadata[] = ['End Date', $request->periode_end];
        }
        
        if ($request->filled('chat')) {
            $metadata[] = ['Chat Status', $this->formatChatStatus($request->chat)];
        }
        
        if ($request->filled('label')) {
            $label = \App\Models\Label::find($request->label);
            $metadata[] = ['Label', $label ? $label->nama : 'Unknown'];
        }

        $row = 1;
        foreach ($metadata as $data) {
            $metadataSheet->setCellValue('A' . $row, $data[0]);
            if (isset($data[1])) {
                $metadataSheet->setCellValue('B' . $row, $data[1]);
            }
            $row++;
        }

        // Style metadata sheet
        $metadataSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $metadataSheet->getStyle('A3:A' . ($row - 1))->getFont()->setBold(true);
        $metadataSheet->getColumnDimension('A')->setWidth(20);
        $metadataSheet->getColumnDimension('B')->setWidth(30);
    }

    private function generateFilename(Request $request): string
    {
        $filename = 'data-mitra-' . now()->format('Y-m-d-H-i-s');
        
        if ($request->filled('periode_start')) {
            $filename .= '-dari-' . $request->periode_start;
        }
        
        if ($request->filled('periode_end')) {
            $filename .= '-sampai-' . $request->periode_end;
        }
        
        return $filename . '.xlsx';
    }
}