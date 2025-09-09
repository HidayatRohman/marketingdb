<?php

namespace App\Http\Controllers;

use App\Models\Mitra;
use App\Models\Brand;
use App\Models\Label;
use App\Http\Requests\StoreMitraRequest;
use App\Http\Requests\UpdateMitraRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

class MitraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Mitra::with(['brand', 'label', 'user']);

        // Apply role-based filtering
        $query = $user->applyRoleFilter($query, 'user_id');

        // Apply search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
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

        // Apply periode filter
        if ($request->has('periode_start') && $request->periode_start) {
            $query->whereDate('tanggal_lead', '>=', $request->periode_start);
        }

        if ($request->has('periode_end') && $request->periode_end) {
            $query->whereDate('tanggal_lead', '<=', $request->periode_end);
        }

        // Apply chat filter
        if ($request->has('chat') && $request->chat) {
            $query->where('chat', $request->chat);
        }

        // Apply label filter
        if ($request->has('label') && $request->label) {
            $query->where('label_id', $request->label);
        }

        // Apply user (marketing) filter - only for Super Admin and Admin
        if ($request->has('user') && $request->user && $user->hasFullAccess()) {
            $query->where('user_id', $request->user);
        }

        // Default ordering by tanggal_lead (newest first), then by created_at
        $query->orderBy('tanggal_lead', 'desc')->orderBy('created_at', 'desc');

        // Get per_page parameter, default to 30
        $perPage = $request->get('per_page', 30);
        $perPage = in_array($perPage, [10, 20, 30, 50, 100]) ? $perPage : 30;

        $mitras = $query->paginate($perPage)->withQueryString();
        $brands = Brand::all();
        $labels = Label::all();
        
        // Get marketing users (users with role marketing) 
        $marketingUsers = collect();
        if ($user->hasFullAccess() || $user->hasReadOnlyAccess()) {
            $marketingUsers = \App\Models\User::where('role', 'marketing')->get(['id', 'name']);
        } elseif ($user->isMarketing()) {
            // For marketing users, only show themselves in the list
            $marketingUsers = collect([['id' => $user->id, 'name' => $user->name]]);
        }
        
        return Inertia::render('Mitra/Index', [
            'mitras' => $mitras,
            'brands' => $brands,
            'labels' => $labels,
            'users' => $marketingUsers,
            'currentUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
            'filters' => [
                'search' => $request->search,
                'chat' => $request->chat,
                'label' => $request->label,
                'user' => $request->user,
                'periode_start' => $request->periode_start,
                'periode_end' => $request->periode_end,
                'per_page' => $perPage,
            ],
            'permissions' => [
                'canCrud' => $user->canCrud(),
                'canOnlyView' => $user->canOnlyView(),
                'canOnlyViewOwn' => $user->canOnlyViewOwn(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();
        
        return Inertia::render('Mitra/Create', [
            'permissions' => [
                'canCrud' => $user->canCrud(),
                'canOnlyView' => $user->canOnlyView(),
                'canOnlyViewOwn' => $user->canOnlyViewOwn(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMitraRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();
        
        // For marketing role, always use current user ID
        if ($user->role === 'marketing') {
            $validated['user_id'] = $user->id;
        } elseif (empty($validated['user_id'])) {
            // For admin/super_admin, use provided user_id or current user if empty
            $validated['user_id'] = $user->id;
        }

        // Set default values for kota and provinsi if empty
        if (empty($validated['kota'])) {
            $validated['kota'] = 'Unknown';
        }
        if (empty($validated['provinsi'])) {
            $validated['provinsi'] = 'Unknown';
        }

        Mitra::create($validated);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Mitra berhasil ditambahkan.']);
        }

        return redirect()->route('mitras.index')
            ->with('success', 'Mitra berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Mitra $mitra)
    {
        $user = auth()->user();
        
        // Check if user can access this mitra
        if ($user->isMarketing() && $mitra->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk melihat data ini.');
        }

        return Inertia::render('Mitra/Show', [
            'mitra' => $mitra->load(['brand', 'label', 'user']),
            'permissions' => [
                'canCrud' => $user->canCrud(),
                'canOnlyView' => $user->canOnlyView(),
                'canOnlyViewOwn' => $user->canOnlyViewOwn(),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Mitra $mitra)
    {
        $user = auth()->user();
        
        // Check if user can edit this mitra
        if ($user->isMarketing() && $mitra->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit data ini.');
        }

        return Inertia::render('Mitra/Edit', [
            'mitra' => $mitra->load(['brand', 'label']),
            'permissions' => [
                'canCrud' => $user->canCrud(),
                'canOnlyView' => $user->canOnlyView(),
                'canOnlyViewOwn' => $user->canOnlyViewOwn(),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMitraRequest $request, Mitra $mitra)
    {
        $user = auth()->user();
        
        // Check if user can update this mitra
        if ($user->isMarketing() && $mitra->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk mengupdate data ini.');
        }

        $validated = $request->validated();

        // For marketing role, always keep current user ID (don't allow change)
        if ($user->role === 'marketing') {
            $validated['user_id'] = $user->id;
        }

        // Set default values for kota and provinsi if empty
        if (empty($validated['kota'])) {
            $validated['kota'] = 'Unknown';
        }
        if (empty($validated['provinsi'])) {
            $validated['provinsi'] = 'Unknown';
        }

        $mitra->update($validated);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Mitra berhasil diperbarui.']);
        }

        return redirect()->route('mitras.index')
            ->with('success', 'Mitra berhasil diperbarui.');
    }

    /**
     * Export mitras data in XLSX format - Direct download
     */
    public function export(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $query = Mitra::with(['brand', 'label', 'user']);

            // Apply role-based filtering
            $query = $user->applyRoleFilter($query, 'user_id');

            // Apply same filters as index method
            if ($request->has('search') && $request->search) {
                $search = $request->search;
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

            if ($request->has('periode_start') && $request->periode_start) {
                $query->whereDate('tanggal_lead', '>=', $request->periode_start);
            }

            if ($request->has('periode_end') && $request->periode_end) {
                $query->whereDate('tanggal_lead', '<=', $request->periode_end);
            }

            if ($request->has('chat') && $request->chat) {
                $query->where('chat', $request->chat);
            }

            if ($request->has('label') && $request->label) {
                $query->where('label_id', $request->label);
            }

            if ($request->has('user') && $request->user && $user->hasFullAccess()) {
                $query->where('user_id', $request->user);
            }

            $mitras = $query->orderBy('tanggal_lead', 'desc')->get();

            // Create spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Data Mitra');

            // Set headers
            $headers = [
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
                'K1' => 'Komentar',
                'L1' => 'Dibuat Pada',
                'M1' => 'Diupdate Pada'
            ];

            foreach ($headers as $cell => $value) {
                $sheet->setCellValue($cell, $value);
            }

            // Style headers
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '059669']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'FFFFFF']
                    ]
                ]
            ];
            $sheet->getStyle('A1:M1')->applyFromArray($headerStyle);

            // Add data
            $row = 2;
            foreach ($mitras as $mitra) {
                $sheet->setCellValue('A' . $row, $mitra->id);
                $sheet->setCellValue('B' . $row, $mitra->nama);
                $sheet->setCellValue('C' . $row, "'" . $mitra->no_telp); // Add quote to preserve leading zeros
                $sheet->setCellValue('D' . $row, $mitra->tanggal_lead);
                $sheet->setCellValue('E' . $row, $mitra->brand->nama ?? '');
                $sheet->setCellValue('F' . $row, $mitra->label->nama ?? '');
                $sheet->setCellValue('G' . $row, $mitra->chat === 'masuk' ? 'Masuk' : 'Follow Up');
                $sheet->setCellValue('H' . $row, $mitra->kota);
                $sheet->setCellValue('I' . $row, $mitra->provinsi);
                $sheet->setCellValue('J' . $row, $mitra->user->name ?? '');
                $sheet->setCellValue('K' . $row, $mitra->komentar);
                $sheet->setCellValue('L' . $row, $mitra->created_at->format('Y-m-d H:i:s'));
                $sheet->setCellValue('M' . $row, $mitra->updated_at->format('Y-m-d H:i:s'));
                $row++;
            }

            // Auto-size columns
            foreach (range('A', 'M') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            // Set minimum column widths
            $sheet->getColumnDimension('B')->setWidth(15); // Nama
            $sheet->getColumnDimension('C')->setWidth(15); // No. Telepon
            $sheet->getColumnDimension('K')->setWidth(25); // Komentar

            // Add data styling
            if ($row > 2) {
                $dataStyle = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC']
                        ]
                    ]
                ];
                $sheet->getStyle('A2:M' . ($row - 1))->applyFromArray($dataStyle);
            }

            // Create filename with timestamp and filters info
            $filename = 'data-mitra-' . date('Y-m-d-H-i-s');
            if ($request->has('periode_start') && $request->periode_start) {
                $filename .= '-dari-' . $request->periode_start;
            }
            if ($request->has('periode_end') && $request->periode_end) {
                $filename .= '-sampai-' . $request->periode_end;
            }
            $filename .= '.xlsx';
            
            // Create writer and output directly as download
            $writer = new Xlsx($spreadsheet);
            
            // Return StreamedResponse for direct download
            return response()->streamDownload(function() use ($writer) {
                $writer->save('php://output');
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);
        
        } catch (\Exception $e) {
            \Log::error('Export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'filters' => $request->all()
            ]);
            
            return response()->json(['error' => 'Export gagal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Download import template in XLSX format
     */
    public function downloadTemplate(Request $request)
    {
        try {
            // Create spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Template Import Mitra');

            // Set headers
            $headers = [
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
                'K1' => 'Komentar',
                'L1' => 'Dibuat Pada',
                'M1' => 'Diupdate Pada'
            ];

            foreach ($headers as $cell => $value) {
                $sheet->setCellValue($cell, $value);
            }

            // Style headers
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ];
            $sheet->getStyle('A1:M1')->applyFromArray($headerStyle);

            // Add field requirements row
            $requirements = [
                'A2' => '(Opsional)',
                'B2' => '(WAJIB)',
                'C2' => '(WAJIB)',
                'D2' => '(WAJIB)',
                'E2' => '(Opsional)',
                'F2' => '(Opsional)',
                'G2' => '(Opsional)',
                'H2' => '(Opsional)',
                'I2' => '(Opsional)',
                'J2' => '(Auto)',
                'K2' => '(Opsional)',
                'L2' => '(Auto)',
                'M2' => '(Auto)'
            ];

            foreach ($requirements as $cell => $value) {
                $sheet->setCellValue($cell, $value);
            }

            // Style requirements row
            $reqStyle = [
                'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '666666']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
            ];
            $sheet->getStyle('A2:M2')->applyFromArray($reqStyle);

            // Add sample data with proper examples
            $sampleData = [
                // Row 3: Example 1
                ['', 'John Doe', '081234567890', '2024-01-15', 'Brand A', 'Hot Lead', 'Masuk', 'Jakarta', 'DKI Jakarta', '', 'Tertarik dengan produk premium', '', ''],
                // Row 4: Example 2  
                ['', 'Jane Smith', '087654321098', '2024-01-16', 'Brand B', 'Warm Lead', 'Follow Up', 'Surabaya', 'Jawa Timur', '', 'Perlu follow up dalam 3 hari', '', ''],
                // Row 5: Example 3
                ['', 'Ahmad Rahman', '082111222333', '2024-01-17', 'Brand C', 'Cold Lead', 'Masuk', 'Bandung', 'Jawa Barat', '', 'Inquiry produk via WhatsApp', '', '']
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
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F0FDF4']
                ]
            ];
            $sheet->getStyle('A3:M5')->applyFromArray($sampleStyle);

            // Auto-size columns
            foreach (range('A', 'M') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            // Set minimum column widths
            $sheet->getColumnDimension('B')->setWidth(15); // Nama
            $sheet->getColumnDimension('C')->setWidth(15); // No. Telepon
            $sheet->getColumnDimension('D')->setWidth(12); // Tanggal Lead
            $sheet->getColumnDimension('E')->setWidth(12); // Brand
            $sheet->getColumnDimension('F')->setWidth(12); // Label
            $sheet->getColumnDimension('G')->setWidth(12); // Status Chat
            $sheet->getColumnDimension('K')->setWidth(25); // Komentar

            // Add borders to data area
            $borderStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ]
            ];
            $sheet->getStyle('A1:M5')->applyFromArray($borderStyle);

            // Add instructions sheet
            $instructionSheet = $spreadsheet->createSheet();
            $instructionSheet->setTitle('Panduan Import');
            
            $instructions = [
                ['PANDUAN IMPORT DATA MITRA'],
                [''],
                ['FIELD WAJIB:'],
                ['• Nama: Nama lengkap mitra (tidak boleh kosong)'],
                ['• No. Telepon: Nomor telepon (format bebas, sistem akan otomatis format)'],
                ['• Tanggal Lead: Format YYYY-MM-DD (contoh: 2024-01-15)'],
                [''],
                ['FIELD OPSIONAL:'],
                ['• ID: Kosongkan untuk data baru'],
                ['• Brand: Nama brand (akan dibuat otomatis jika belum ada)'],
                ['• Label: Gunakan label yang sudah ada di sistem'],
                ['• Status Chat: "Masuk" (default) atau "Follow Up"'],
                ['• Kota & Provinsi: Boleh kosong'],
                ['• Komentar: Catatan tambahan'],
                [''],
                ['FIELD AUTO:'],
                ['• Marketing: Otomatis diisi dengan user yang import'],
                ['• Dibuat/Diupdate: Otomatis diisi sistem'],
                [''],
                ['TIPS SUKSES:'],
                ['1. Hapus baris contoh sebelum import'],
                ['2. Pastikan format tanggal benar (YYYY-MM-DD)'],
                ['3. Jangan import data terlalu banyak sekaligus'],
                ['4. Test dengan 1-2 baris dulu'],
                ['5. Backup data sebelum import'],
                [''],
                ['CONTOH DATA VALID:'],
                ['Nama: John Doe'],
                ['Telepon: 081234567890'],
                ['Tanggal: 2024-01-15'],
                ['Brand: Brand A'],
                ['Status: Masuk']
            ];

            $instructionRow = 1;
            foreach ($instructions as $instruction) {
                $instructionSheet->setCellValue('A' . $instructionRow, $instruction[0]);
                $instructionRow++;
            }

            // Style instruction sheet
            $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $instructionSheet->getStyle('A3')->getFont()->setBold(true);
            $instructionSheet->getStyle('A8')->getFont()->setBold(true);
            $instructionSheet->getStyle('A16')->getFont()->setBold(true);
            $instructionSheet->getStyle('A19')->getFont()->setBold(true);
            $instructionSheet->getStyle('A26')->getFont()->setBold(true);
            $instructionSheet->getColumnDimension('A')->setWidth(50);

            $filename = 'template-import-mitra.xlsx';
            
            $writer = new Xlsx($spreadsheet);

            return new StreamedResponse(function() use ($writer) {
                $writer->save('php://output');
            }, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);

        } catch (\Exception $e) {
            \Log::error('Template download failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Download template gagal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Download import guide
     */
    public function downloadGuide()
    {
        $guideContent = file_get_contents(base_path('TEMPLATE_IMPORT_MITRA.md'));
        
        return response($guideContent, 200, [
            'Content-Type' => 'text/markdown',
            'Content-Disposition' => 'attachment; filename="panduan-import-mitra.md"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Import mitras data from XLSX file
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:5120' // Max 5MB
        ], [
            'file.required' => 'File import wajib dipilih.',
            'file.mimes' => 'File harus berformat XLSX atau XLS.',
            'file.max' => 'Ukuran file maksimal 5MB.'
        ]);

        $file = $request->file('file');
        $user = auth()->user();

        try {
            // Load the spreadsheet
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Remove header row and requirements row
            if (count($rows) > 0) array_shift($rows); // Remove header
            if (count($rows) > 0 && isset($rows[0][1]) && strpos($rows[0][1], '(') !== false) {
                array_shift($rows); // Remove requirements row if exists
            }
            
            $imported = 0;
            $errors = [];
            $skipped = 0;

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 3; // +3 because we removed header, requirements, and arrays are 0-indexed
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    $skipped++;
                    continue;
                }

                try {
                    // Map columns (adjust based on your export format)
                    $nama = trim($row[1] ?? '');
                    $no_telp = trim($row[2] ?? '');
                    $tanggal_lead = trim($row[3] ?? '');
                    $brand_nama = trim($row[4] ?? '');
                    $label_nama = trim($row[5] ?? '');
                    $chat_status = trim($row[6] ?? '');
                    $kota = trim($row[7] ?? '');
                    $provinsi = trim($row[8] ?? '');
                    $komentar = trim($row[10] ?? '');

                    // Validate required fields
                    if (empty($nama)) {
                        $errors[] = "Baris {$rowNumber}: Nama wajib diisi.";
                        continue;
                    }

                    if (empty($no_telp)) {
                        $errors[] = "Baris {$rowNumber}: No. Telepon wajib diisi.";
                        continue;
                    }

                    if (empty($tanggal_lead)) {
                        $errors[] = "Baris {$rowNumber}: Tanggal Lead wajib diisi.";
                        continue;
                    }

                    // Clean phone number (remove any quotes or formatting)
                    $no_telp = str_replace("'", '', $no_telp);
                    $no_telp = preg_replace('/[^0-9+]/', '', $no_telp);

                    // Check for duplicate phone number
                    $existingMitra = Mitra::where('no_telp', $no_telp)->first();
                    if ($existingMitra) {
                        $errors[] = "Baris {$rowNumber}: Nomor telepon {$no_telp} sudah ada dalam sistem.";
                        continue;
                    }

                    // Find or create brand
                    $brand = null;
                    if (!empty($brand_nama)) {
                        $brand = Brand::firstOrCreate(
                            ['nama' => $brand_nama],
                            ['created_at' => now(), 'updated_at' => now()]
                        );
                    }

                    // Find label
                    $label = null;
                    if (!empty($label_nama)) {
                        $label = Label::where('nama', 'LIKE', $label_nama)->first();
                        if (!$label) {
                            $errors[] = "Baris {$rowNumber}: Label '{$label_nama}' tidak ditemukan dalam sistem.";
                        }
                    }

                    // Determine chat status
                    $chat = 'masuk';
                    if (!empty($chat_status)) {
                        $chat_lower = strtolower($chat_status);
                        if (in_array($chat_lower, ['follow up', 'followup', 'follow-up'])) {
                            $chat = 'followup';
                        }
                    }

                    // Parse date
                    try {
                        $parsedDate = \Carbon\Carbon::parse($tanggal_lead);
                        $tanggal_lead_formatted = $parsedDate->format('Y-m-d');
                        
                        // Check if date is not too far in the future
                        if ($parsedDate->gt(now()->addYear())) {
                            $errors[] = "Baris {$rowNumber}: Tanggal lead terlalu jauh di masa depan.";
                            continue;
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Baris {$rowNumber}: Format tanggal '{$tanggal_lead}' tidak valid. Gunakan format YYYY-MM-DD.";
                        continue;
                    }

                    // Create mitra
                    $mitraData = [
                        'nama' => $nama,
                        'no_telp' => $no_telp,
                        'tanggal_lead' => $tanggal_lead_formatted,
                        'brand_id' => $brand ? $brand->id : null,
                        'label_id' => $label ? $label->id : null,
                        'chat' => $chat,
                        'kota' => $kota ?: '',
                        'provinsi' => $provinsi ?: '',
                        'komentar' => $komentar ?: null,
                        'user_id' => $user->id, // Assign to current user
                        'created_at' => now(),
                        'updated_at' => now()
                    ];

                    Mitra::create($mitraData);
                    $imported++;

                } catch (\Exception $e) {
                    $errors[] = "Baris {$rowNumber}: {$e->getMessage()}";
                }
            }

            // Prepare response
            $response = [
                'success' => true,
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
                'total_processed' => $imported + count($errors) + $skipped
            ];

            if ($imported > 0) {
                $response['message'] = "Berhasil mengimport {$imported} data mitra.";
                if (!empty($errors)) {
                    $response['message'] .= " " . count($errors) . " baris memiliki error.";
                }
                if ($skipped > 0) {
                    $response['message'] .= " {$skipped} baris kosong dilewati.";
                }
            } else {
                $response['success'] = false;
                $response['message'] = 'Tidak ada data yang berhasil diimport.';
            }

            return response()->json($response);

        } catch (\Exception $e) {
            \Log::error('Import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'file_name' => $file->getClientOriginalName()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengimport data: ' . $e->getMessage(),
                'imported' => 0,
                'errors' => []
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mitra $mitra)
    {
        $user = auth()->user();
        
        // Check if user can delete this mitra
        if ($user->isMarketing() && $mitra->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus data ini.');
        }

        $mitra->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Mitra berhasil dihapus.']);
        }

        return redirect()->route('mitras.index')
            ->with('success', 'Mitra berhasil dihapus.');
    }
}
