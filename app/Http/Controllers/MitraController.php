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
     * Export mitras data
     */
    public function export(Request $request)
    {
        // Add logging for debugging
        \Log::info('Export request received', [
            'user_id' => auth()->id(),
            'user_role' => auth()->user()->role ?? 'unknown',
            'filters' => $request->all()
        ]);

        $user = auth()->user();
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
        $sheet->getStyle('A1:M1')->getFont()->setBold(true);
        $sheet->getStyle('A1:M1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E2E8F0');

        // Add data
        $row = 2;
        foreach ($mitras as $mitra) {
            $sheet->setCellValue('A' . $row, $mitra->id);
            $sheet->setCellValue('B' . $row, $mitra->nama);
            $sheet->setCellValue('C' . $row, $mitra->no_telp);
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

        // Determine file format and create output
        $format = $request->get('export', 'xlsx');
        $filename = 'mitra-data-' . date('Y-m-d') . '.' . $format;
        
        if ($format === 'csv') {
            // Use simple CSV output for better compatibility
            $output = fopen('php://temp', 'w+');
            
            // Write headers
            fputcsv($output, [
                'ID', 'Nama', 'No. Telepon', 'Tanggal Lead', 'Brand', 
                'Label', 'Status Chat', 'Kota', 'Provinsi', 'Marketing', 
                'Komentar', 'Dibuat Pada', 'Diupdate Pada'
            ]);
            
            // Write data
            foreach ($mitras as $mitra) {
                fputcsv($output, [
                    $mitra->id,
                    $mitra->nama,
                    $mitra->no_telp,
                    $mitra->tanggal_lead,
                    $mitra->brand->nama ?? '',
                    $mitra->label->nama ?? '',
                    $mitra->chat === 'masuk' ? 'Masuk' : 'Follow Up',
                    $mitra->kota,
                    $mitra->provinsi,
                    $mitra->user->name ?? '',
                    $mitra->komentar,
                    $mitra->created_at->format('Y-m-d H:i:s'),
                    $mitra->updated_at->format('Y-m-d H:i:s')
                ]);
            }
            
            rewind($output);
            $csvContent = stream_get_contents($output);
            fclose($output);
            
            return response($csvContent, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length' => strlen($csvContent),
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);
        } else {
            // Use PhpSpreadsheet for XLSX
            $writer = new Xlsx($spreadsheet);
            $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            
            return new StreamedResponse(function() use ($writer) {
                $writer->save('php://output');
            }, 200, [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);
        }
    }

    /**
     * Download import template
     */
    public function downloadTemplate(Request $request)
    {
        // Create spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

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

        // Add comprehensive instructions
        $instructions = "PANDUAN IMPORT DATA MITRA\n\n";
        $instructions .= "FIELD WAJIB:\n";
        $instructions .= "• Nama: Nama lengkap mitra (tidak boleh kosong)\n";
        $instructions .= "• No. Telepon: Nomor telepon (format bebas, sistem akan otomatis format)\n";
        $instructions .= "• Tanggal Lead: Format YYYY-MM-DD (contoh: 2024-01-15)\n\n";
        
        $instructions .= "FIELD OPSIONAL:\n";
        $instructions .= "• ID: Kosongkan untuk data baru\n";
        $instructions .= "• Brand: Nama brand (akan dibuat otomatis jika belum ada)\n";
        $instructions .= "• Label: Gunakan label yang sudah ada di sistem\n";
        $instructions .= "• Status Chat: 'Masuk' (default) atau 'Follow Up'\n";
        $instructions .= "• Kota & Provinsi: Boleh kosong\n";
        $instructions .= "• Komentar: Catatan tambahan\n\n";
        
        $instructions .= "FIELD AUTO:\n";
        $instructions .= "• Marketing: Otomatis diisi dengan user yang import\n";
        $instructions .= "• Dibuat/Diupdate: Otomatis diisi sistem\n\n";
        
        $instructions .= "TIPS SUKSES:\n";
        $instructions .= "1. Hapus baris contoh ini sebelum import\n";
        $instructions .= "2. Pastikan format tanggal benar (YYYY-MM-DD)\n";
        $instructions .= "3. Jangan import data terlalu banyak sekaligus\n";
        $instructions .= "4. Test dengan 1-2 baris dulu\n";
        $instructions .= "5. Backup data sebelum import\n\n";
        
        $instructions .= "CONTOH DATA VALID:\n";
        $instructions .= "Nama: John Doe\n";
        $instructions .= "Telepon: 081234567890\n";
        $instructions .= "Tanggal: 2024-01-15\n";
        $instructions .= "Brand: Brand A\n";
        $instructions .= "Status: Masuk";

        // Add instruction as comment to B1
        $sheet->getComment('B1')->setText($instructions);
        $sheet->getComment('B1')->setWidth('400px');
        $sheet->getComment('B1')->setHeight('300px');

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

        // Determine file format
        $format = $request->get('format', 'xlsx');
        $filename = 'mitra-import-template.' . $format;
        
        if ($format === 'csv') {
            $writer = new Csv($spreadsheet);
            $writer->setDelimiter(',');
            $writer->setEnclosure('"');
            $writer->setLineEnding("\r\n");
            $contentType = 'text/csv';
        } else {
            $writer = new Xlsx($spreadsheet);
            $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        }

        return new StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
        ]);
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
     * Import mitras data
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx,xls|max:2048'
        ]);

        $file = $request->file('file');
        $user = auth()->user();

        try {
            // Load the spreadsheet
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Remove header row
            $header = array_shift($rows);
            
            $imported = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 because we removed header and arrays are 0-indexed
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                try {
                    // Map columns (adjust based on your export format)
                    $nama = $row[1] ?? null;
                    $no_telp = $row[2] ?? null;
                    $tanggal_lead = $row[3] ?? null;
                    $brand_nama = $row[4] ?? null;
                    $label_nama = $row[5] ?? null;
                    $chat_status = $row[6] ?? null;
                    $kota = $row[7] ?? null;
                    $provinsi = $row[8] ?? null;
                    $komentar = $row[10] ?? null;

                    // Validate required fields
                    if (empty($nama) || empty($no_telp) || empty($tanggal_lead)) {
                        $errors[] = "Baris {$rowNumber}: Nama, No. Telepon, dan Tanggal Lead wajib diisi.";
                        continue;
                    }

                    // Find or create brand
                    $brand = null;
                    if (!empty($brand_nama)) {
                        $brand = Brand::firstOrCreate(['nama' => trim($brand_nama)]);
                    }

                    // Find label
                    $label = null;
                    if (!empty($label_nama)) {
                        $label = Label::where('nama', trim($label_nama))->first();
                    }

                    // Determine chat status
                    $chat = 'masuk';
                    if (!empty($chat_status)) {
                        $chat = (strtolower(trim($chat_status)) === 'follow up') ? 'followup' : 'masuk';
                    }

                    // Parse date
                    try {
                        $tanggal_lead = \Carbon\Carbon::parse($tanggal_lead)->format('Y-m-d');
                    } catch (\Exception $e) {
                        $errors[] = "Baris {$rowNumber}: Format tanggal tidak valid.";
                        continue;
                    }

                    // Create mitra
                    $mitraData = [
                        'nama' => trim($nama),
                        'no_telp' => trim($no_telp),
                        'tanggal_lead' => $tanggal_lead,
                        'brand_id' => $brand ? $brand->id : null,
                        'label_id' => $label ? $label->id : null,
                        'chat' => $chat,
                        'kota' => trim($kota) ?: '',
                        'provinsi' => trim($provinsi) ?: '',
                        'komentar' => trim($komentar),
                        'user_id' => $user->id, // Assign to current user
                    ];

                    Mitra::create($mitraData);
                    $imported++;

                } catch (\Exception $e) {
                    $errors[] = "Baris {$rowNumber}: {$e->getMessage()}";
                }
            }

            $response = [
                'imported' => $imported,
                'errors' => $errors
            ];

            if ($imported > 0) {
                $response['message'] = "Berhasil mengimport {$imported} data mitra.";
                if (!empty($errors)) {
                    $response['message'] .= ' ' . count($errors) . ' baris memiliki error.';
                }
            } else {
                $response['message'] = 'Tidak ada data yang berhasil diimport.';
            }

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengimport data: ' . $e->getMessage()
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
