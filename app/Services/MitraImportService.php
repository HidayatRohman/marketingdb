<?php

namespace App\Services;

use App\Models\Mitra;
use App\Models\Brand;
use App\Models\Label;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class MitraImportService
{
    private const REQUIRED_FIELDS = ['nama', 'no_telp', 'tanggal_lead'];
    private const MAX_BATCH_SIZE = 100;
    
    public function import(UploadedFile $file): array
    {
        try {
            DB::beginTransaction();
            
            $result = [
                'success' => false,
                'imported' => 0,
                'skipped' => 0,
                'errors' => [],
                'warnings' => [],
                'total_processed' => 0
            ];
            
            // Load and validate file
            $spreadsheet = $this->loadSpreadsheet($file);
            $rows = $this->extractDataRows($spreadsheet);
            
            if (empty($rows)) {
                throw new \Exception('File tidak berisi data yang valid');
            }
            
            $result['total_processed'] = count($rows);
            
            // Process rows in batches
            $batches = array_chunk($rows, self::MAX_BATCH_SIZE);
            
            foreach ($batches as $batchIndex => $batch) {
                $batchResult = $this->processBatch($batch, $batchIndex * self::MAX_BATCH_SIZE);
                
                $result['imported'] += $batchResult['imported'];
                $result['skipped'] += $batchResult['skipped'];
                $result['errors'] = array_merge($result['errors'], $batchResult['errors']);
                $result['warnings'] = array_merge($result['warnings'], $batchResult['warnings']);
            }
            
            // Determine success
            $result['success'] = $result['imported'] > 0;
            
            if ($result['success']) {
                DB::commit();
                $result['message'] = $this->generateSuccessMessage($result);
            } else {
                DB::rollBack();
                $result['message'] = 'Tidak ada data yang berhasil diimport.';
            }
            
            return $result;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Mitra import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'file_name' => $file->getClientOriginalName()
            ]);
            
            return [
                'success' => false,
                'message' => 'Import gagal: ' . $e->getMessage(),
                'imported' => 0,
                'skipped' => 0,
                'errors' => [$e->getMessage()],
                'warnings' => [],
                'total_processed' => 0
            ];
        }
    }
    
    public function generateTemplate(): array
    {
        try {
            $spreadsheet = $this->createTemplate();
            $writer = new Xlsx($spreadsheet);
            
            return [
                'success' => true,
                'writer' => $writer,
                'filename' => 'template-import-mitra.xlsx'
            ];
            
        } catch (\Exception $e) {
            \Log::error('Template generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
    
    private function loadSpreadsheet(UploadedFile $file): Spreadsheet
    {
        // Validate file size (max 10MB)
        if ($file->getSize() > 10485760) {
            throw new \Exception('File terlalu besar. Maksimal 10MB.');
        }
        
        // Validate file type
        $allowedMimeTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel'
        ];
        
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            throw new \Exception('Format file tidak didukung. Hanya file Excel (.xlsx, .xls) yang diperbolehkan.');
        }
        
        try {
            return IOFactory::load($file->getPathname());
        } catch (\Exception $e) {
            throw new \Exception('File Excel tidak dapat dibaca. Pastikan file tidak rusak.');
        }
    }
    
    private function extractDataRows(Spreadsheet $spreadsheet): array
    {
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // Remove header and empty rows
        if (count($rows) > 0) {
            array_shift($rows); // Remove header
        }
        
        // Remove requirements row if exists
        if (count($rows) > 0 && isset($rows[0][1]) && strpos($rows[0][1], '(') !== false) {
            array_shift($rows);
        }
        
        // Filter out empty rows
        return array_filter($rows, function($row) {
            return !empty(array_filter($row));
        });
    }
    
    private function processBatch(array $rows, int $startIndex): array
    {
        $result = [
            'imported' => 0,
            'skipped' => 0,
            'errors' => [],
            'warnings' => []
        ];
        
        foreach ($rows as $index => $row) {
            $rowNumber = $startIndex + $index + 3; // +3 for header, requirements, and 0-based index
            
            try {
                $processResult = $this->processRow($row, $rowNumber);
                
                if ($processResult['imported']) {
                    $result['imported']++;
                } else {
                    $result['skipped']++;
                }
                
                $result['errors'] = array_merge($result['errors'], $processResult['errors']);
                $result['warnings'] = array_merge($result['warnings'], $processResult['warnings']);
                
            } catch (\Exception $e) {
                $result['errors'][] = "Baris {$rowNumber}: {$e->getMessage()}";
            }
        }
        
        return $result;
    }
    
    private function processRow(array $row, int $rowNumber): array
    {
        $result = [
            'imported' => false,
            'errors' => [],
            'warnings' => []
        ];
        
        // Extract data from row
        $data = $this->extractRowData($row);
        
        // Validate required fields
        $validation = $this->validateRowData($data, $rowNumber);
        if (!$validation['valid']) {
            $result['errors'] = $validation['errors'];
            return $result;
        }
        
        // Check for duplicates
        $duplicate = $this->checkDuplicate($data['no_telp'], $rowNumber);
        if ($duplicate) {
            $result['errors'][] = $duplicate;
            return $result;
        }
        
        // Process related models
        $processed = $this->processRelatedData($data, $rowNumber);
        $result['warnings'] = array_merge($result['warnings'], $processed['warnings']);
        
        // Create Mitra record
        $mitraData = $this->prepareMitraData($data, $processed);
        
        try {
            Mitra::create($mitraData);
            $result['imported'] = true;
        } catch (\Exception $e) {
            $result['errors'][] = "Baris {$rowNumber}: Gagal menyimpan data - {$e->getMessage()}";
        }
        
        return $result;
    }
    
    private function extractRowData(array $row): array
    {
        return [
            'nama' => trim($row[1] ?? ''),
            'no_telp' => trim($row[2] ?? ''),
            'tanggal_lead' => trim($row[3] ?? ''),
            'brand_nama' => trim($row[4] ?? ''),
            'label_nama' => trim($row[5] ?? ''),
            'chat_status' => trim($row[6] ?? ''),
            'kota' => trim($row[7] ?? ''),
            'provinsi' => trim($row[8] ?? ''),
            'webinar' => trim($row[10] ?? ''),
            'komentar' => trim($row[11] ?? ''),
        ];
    }
    
    private function validateRowData(array $data, int $rowNumber): array
    {
        $errors = [];
        
        // Validate required fields
        foreach (self::REQUIRED_FIELDS as $field) {
            if (empty($data[$field])) {
                $fieldLabel = $this->getFieldLabel($field);
                $errors[] = "Baris {$rowNumber}: {$fieldLabel} wajib diisi.";
            }
        }
        
        // Validate phone number
        if (!empty($data['no_telp'])) {
            $cleanPhone = preg_replace('/[^0-9+]/', '', $data['no_telp']);
            if (strlen($cleanPhone) < 10 || strlen($cleanPhone) > 15) {
                $errors[] = "Baris {$rowNumber}: Format nomor telepon tidak valid.";
            }
        }
        
        // Validate date
        if (!empty($data['tanggal_lead'])) {
            try {
                $date = Carbon::parse($data['tanggal_lead']);
                if ($date->gt(now()->addYear())) {
                    $errors[] = "Baris {$rowNumber}: Tanggal lead tidak boleh lebih dari 1 tahun ke depan.";
                }
            } catch (\Exception $e) {
                $errors[] = "Baris {$rowNumber}: Format tanggal tidak valid. Gunakan format YYYY-MM-DD.";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    private function checkDuplicate(string $phone, int $rowNumber): ?string
    {
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
        
        $existing = Mitra::where('no_telp', $cleanPhone)->first();
        if ($existing) {
            return "Baris {$rowNumber}: Nomor telepon {$cleanPhone} sudah ada dalam sistem.";
        }
        
        return null;
    }
    
    private function processRelatedData(array $data, int $rowNumber): array
    {
        $warnings = [];
        $processed = [
            'brand_id' => null,
            'label_id' => null,
            'chat' => 'masuk'
        ];
        
        // Process brand
        if (!empty($data['brand_nama'])) {
            $brand = Brand::firstOrCreate(
                ['nama' => $data['brand_nama']],
                ['created_at' => now(), 'updated_at' => now()]
            );
            $processed['brand_id'] = $brand->id;
            
            if ($brand->wasRecentlyCreated) {
                $warnings[] = "Baris {$rowNumber}: Brand '{$data['brand_nama']}' dibuat otomatis.";
            }
        }
        
        // Process label
        if (!empty($data['label_nama'])) {
            $label = Label::where('nama', 'LIKE', $data['label_nama'])->first();
            if ($label) {
                $processed['label_id'] = $label->id;
            } else {
                $warnings[] = "Baris {$rowNumber}: Label '{$data['label_nama']}' tidak ditemukan, diabaikan.";
            }
        }
        
        // Process chat status
        if (!empty($data['chat_status'])) {
            $chatLower = strtolower($data['chat_status']);
            if (in_array($chatLower, ['follow up', 'followup', 'follow-up'])) {
                $processed['chat'] = 'followup';
            }
        }
        
        return [
            'warnings' => $warnings,
            'data' => $processed
        ];
    }
    
    private function prepareMitraData(array $data, array $processed): array
    {
        $cleanPhone = preg_replace('/[^0-9+]/', '', $data['no_telp']);
        
        // Validate webinar value
        $webinar = 'Tidak'; // default value
        if (!empty($data['webinar'])) {
            $webinarValue = strtolower(trim($data['webinar']));
            if (in_array($webinarValue, ['ikut', 'ya', 'yes', '1'])) {
                $webinar = 'Ikut';
            }
        }
        
        return [
            'nama' => $data['nama'],
            'no_telp' => $cleanPhone,
            'tanggal_lead' => Carbon::parse($data['tanggal_lead'])->format('Y-m-d'),
            'brand_id' => $processed['data']['brand_id'],
            'label_id' => $processed['data']['label_id'],
            'chat' => $processed['data']['chat'],
            'kota' => $data['kota'] ?: null,
            'provinsi' => $data['provinsi'] ?: null,
            'webinar' => $webinar,
            'komentar' => $data['komentar'] ?: null,
            'user_id' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
    
    private function getFieldLabel(string $field): string
    {
        return match($field) {
            'nama' => 'Nama',
            'no_telp' => 'No. Telepon',
            'tanggal_lead' => 'Tanggal Lead',
            default => ucfirst($field)
        };
    }
    
    private function generateSuccessMessage(array $result): string
    {
        $message = "Berhasil mengimport {$result['imported']} data mitra.";
        
        if (!empty($result['errors'])) {
            $message .= " " . count($result['errors']) . " baris memiliki error.";
        }
        
        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} baris dilewati.";
        }
        
        if (!empty($result['warnings'])) {
            $message .= " " . count($result['warnings']) . " peringatan.";
        }
        
        return $message;
    }
    
    private function createTemplate(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import Mitra');

        // Set headers
        $headers = [
            'A1' => 'ID',
            'B1' => 'Nama *',
            'C1' => 'No. Telepon *',
            'D1' => 'Tanggal Lead *',
            'E1' => 'Brand',
            'F1' => 'Label',
            'G1' => 'Status Chat',
            'H1' => 'Kota',
            'I1' => 'Provinsi',
            'J1' => 'Webinar',
            'K1' => 'Komentar',
            'L1' => 'Created (Auto)',
            'M1' => 'Updated (Auto)'
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style headers
        $this->styleTemplateHeaders($sheet);

        // Add requirements row
        $this->addRequirementsRow($sheet);

        // Add sample data
        $this->addSampleData($sheet);

        // Set column widths
        $this->setTemplateColumnWidths($sheet);

        // Add instructions sheet
        $this->addInstructionsSheet($spreadsheet);

        return $spreadsheet;
    }
    
    private function styleTemplateHeaders(Worksheet $sheet): void
    {
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'] // Indigo-600
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
    
    private function addRequirementsRow(Worksheet $sheet): void
    {
        $requirements = [
            'A2' => '(Kosongkan)',
            'B2' => '(WAJIB)',
            'C2' => '(WAJIB)',
            'D2' => '(WAJIB)',
            'E2' => '(Opsional)',
            'F2' => '(Opsional)',
            'G2' => '(Opsional)',
            'H2' => '(Opsional)',
            'I2' => '(Opsional)',
            'J2' => '(Opsional)',
            'K2' => '(Opsional)',
            'L2' => '(Otomatis)',
            'M2' => '(Otomatis)'
        ];

        foreach ($requirements as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        $reqStyle = [
            'font' => [
                'italic' => true,
                'size' => 9,
                'color' => ['rgb' => '666666']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER
            ]
        ];
        
        $sheet->getStyle('A2:M2')->applyFromArray($reqStyle);
    }
    
    private function addSampleData(Worksheet $sheet): void
    {
        $sampleData = [
            ['', 'John Doe', '081234567890', '2024-01-15', 'Brand A', 'Hot Lead', 'Masuk', 'Jakarta', 'DKI Jakarta', 'Ikut', 'Tertarik dengan produk premium', '', ''],
            ['', 'Jane Smith', '087654321098', '2024-01-16', 'Brand B', 'Warm Lead', 'Follow Up', 'Surabaya', 'Jawa Timur', 'Tidak', 'Perlu follow up dalam 3 hari', '', ''],
            ['', 'Ahmad Rahman', '082111222333', '2024-01-17', 'Brand C', 'Cold Lead', 'Masuk', 'Bandung', 'Jawa Barat', 'Ikut', 'Inquiry produk via WhatsApp', '', '']
        ];

        $row = 3;
        foreach ($sampleData as $data) {
            $col = 'A';
            foreach ($data as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }

        // Style sample data
        $sampleStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0FDF4'] // Green-50
            ]
        ];
        $sheet->getStyle('A3:M5')->applyFromArray($sampleStyle);
    }
    
    private function setTemplateColumnWidths(Worksheet $sheet): void
    {
        $widths = [
            'A' => 8,   'B' => 18,  'C' => 15,  'D' => 14,
            'E' => 12,  'F' => 12,  'G' => 12,  'H' => 12,
            'I' => 15,  'J' => 10,  'K' => 25,  'L' => 15,
            'M' => 15
        ];

        foreach ($widths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }
    
    private function addInstructionsSheet(Spreadsheet $spreadsheet): void
    {
        $instructionSheet = $spreadsheet->createSheet();
        $instructionSheet->setTitle('Panduan Import');
        
        $instructions = [
            ['PANDUAN IMPORT DATA MITRA'],
            [''],
            ['PERSIAPAN SEBELUM IMPORT:'],
            ['1. Download template ini dan isi data mitra'],
            ['2. Hapus baris contoh (baris 3-5) sebelum import'],
            ['3. Pastikan semua field wajib (*) terisi'],
            ['4. Simpan file dalam format .xlsx'],
            [''],
            ['FIELD YANG WAJIB DIISI:'],
            ['• Nama: Nama lengkap mitra'],
            ['• No. Telepon: Format bebas, sistem akan otomatis clean'],
            ['• Tanggal Lead: Format YYYY-MM-DD (contoh: 2024-01-15)'],
            [''],
            ['FIELD OPSIONAL:'],
            ['• Brand: Akan dibuat otomatis jika belum ada'],
            ['• Label: Harus sudah ada di sistem, jika tidak akan diabaikan'],
            ['• Status Chat: "Masuk" atau "Follow Up" (default: Masuk)'],
            ['• Kota & Provinsi: Boleh kosong'],
            ['• Komentar: Catatan tambahan'],
            [''],
            ['TIPS SUKSES IMPORT:'],
            ['1. Import maksimal 100 data per file'],
            ['2. Test dengan 1-2 baris dulu sebelum import banyak'],
            ['3. Backup data sebelum import'],
            ['4. Periksa format tanggal dengan teliti'],
            ['5. Nomor telepon duplikat akan ditolak'],
            [''],
            ['CONTOH DATA VALID:'],
            ['Nama: John Doe'],
            ['Telepon: 081234567890 atau +6281234567890'],
            ['Tanggal: 2024-01-15'],
            ['Brand: Brand ABC (akan dibuat jika belum ada)'],
            ['Status: Masuk atau Follow Up'],
            [''],
            ['TROUBLESHOOTING:'],
            ['• Jika import gagal, periksa format file (.xlsx)'],
            ['• Pastikan tidak ada karakter khusus di nama file'],
            ['• Maksimal ukuran file: 10MB'],
            ['• Jika ada error, lihat detail di hasil import']
        ];

        $row = 1;
        foreach ($instructions as $instruction) {
            $instructionSheet->setCellValue('A' . $row, $instruction[0]);
            $row++;
        }

        // Style instruction sheet
        $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('1F2937');
        $instructionSheet->getStyle('A3')->getFont()->setBold(true)->getColor()->setRGB('059669');
        $instructionSheet->getStyle('A9')->getFont()->setBold(true)->getColor()->setRGB('DC2626');
        $instructionSheet->getStyle('A14')->getFont()->setBold(true)->getColor()->setRGB('7C2D12');
        $instructionSheet->getStyle('A21')->getFont()->setBold(true)->getColor()->setRGB('1D4ED8');
        $instructionSheet->getStyle('A28')->getFont()->setBold(true)->getColor()->setRGB('059669');
        $instructionSheet->getStyle('A35')->getFont()->setBold(true)->getColor()->setRGB('B91C1C');
        
        $instructionSheet->getColumnDimension('A')->setWidth(60);
    }
}