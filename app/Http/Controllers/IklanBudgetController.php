<?php

namespace App\Http\Controllers;

use App\Models\IklanBudget;
use App\Models\Brand;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class IklanBudgetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $currentUser = auth()->user();
        $query = IklanBudget::with('brand');
        
        // Set default periode ke bulan berjalan
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        
        // Tentukan periode aktif: gunakan filter jika ada, jika tidak pakai default bulan berjalan
        $activeStart = $request->filled('start_date') ? Carbon::parse($request->start_date) : $startOfMonth;
        $activeEnd = $request->filled('end_date') ? Carbon::parse($request->end_date) : $endOfMonth;
        
        // Terapkan filter periode aktif untuk data tabel
        $query->inPeriod($activeStart, $activeEnd);
        
        // Filter berdasarkan brand jika ada
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }
        
        $iklanBudgets = $query->orderBy('tanggal', 'desc')->paginate(31);
        
        // Update closing, omset, dan spent_plus_tax untuk data halaman saat ini
        $ppnRate = (float) SiteSetting::get('ppn_rate', 11);
        $spentMultiplier = 1 + ($ppnRate / 100.0);
        foreach ($iklanBudgets->items() as $budget) {
            $closing = IklanBudget::calculateClosingForDate($budget->tanggal, $budget->brand_id);
            $omset = IklanBudget::calculateOmsetForDate($budget->tanggal, $budget->brand_id);
            $spentPlusTax = ((float) ($budget->spent_amount ?? 0)) * $spentMultiplier;
            
            // Update the model instance for display (persist to database)
            $budget->closing = $closing;
            $budget->omset = $omset;
            $budget->spent_plus_tax = $spentPlusTax;
            
            $budget->save();
        }
        
        // Hitung total untuk periode aktif (global, terpisah dari data tabel)
        $totalQuery = IklanBudget::query()->inPeriod($activeStart, $activeEnd);
        
        // Filter berdasarkan brand untuk total juga
        if ($request->filled('brand_id')) {
            $totalQuery->where('brand_id', $request->brand_id);
        }
        
        $totals = $totalQuery->getTotals(
            $activeStart->format('Y-m-d'),
            $activeEnd->format('Y-m-d'),
            $request->brand_id
        )->first();
        
        // Calculate correct AVG CPL: Total Spent / Total Leads
        if ($totals && $totals->total_leads > 0) {
            $totals->avg_cost_per_lead = $totals->total_spent / $totals->total_leads;
        } else {
            $totals->avg_cost_per_lead = 0;
        }
        
        // Summary report by brand (global, bukan dari halaman tabel)
        $selectedBrand = $request->brand_id;
        $ppnRate = (float) SiteSetting::get('ppn_rate', 11);
        $spentMultiplier = 1 + ($ppnRate / 100.0);

        // Gunakan periode aktif untuk summary report
        $startDate = $activeStart->format('Y-m-d');
        $endDate = $activeEnd->format('Y-m-d');

        // Build expressions dengan periode aktif
        $spentExpr = 'COALESCE(SUM(CASE WHEN iklan_budgets.tanggal BETWEEN "' . $startDate . '" AND "' . $endDate . '" THEN iklan_budgets.spent_amount END), 0)';
        $leadSub = 'SELECT COUNT(*) FROM mitras WHERE mitras.brand_id = brands.id AND mitras.tanggal_lead BETWEEN "' . $startDate . '" AND "' . $endDate . '"';
        $closingSub = 'SELECT COUNT(*) FROM transaksis WHERE transaksis.lead_awal_brand_id = brands.id AND transaksis.tanggal_tf BETWEEN "' . $startDate . '" AND "' . $endDate . '"';
        $omsetSub = 'SELECT COALESCE(SUM(transaksis.nominal_masuk), 0) FROM transaksis WHERE transaksis.lead_awal_brand_id = brands.id AND transaksis.tanggal_tf BETWEEN "' . $startDate . '" AND "' . $endDate . '"';

        $reportQuery = Brand::select(
            'brands.id',
            'brands.nama',
            DB::raw($spentExpr . ' as spent'),
            DB::raw('(' . $leadSub . ') as real_lead'),
            DB::raw('(' . $closingSub . ') as closing'),
            DB::raw('(' . $omsetSub . ') as omset')
        )
        ->leftJoin('iklan_budgets', 'brands.id', '=', 'iklan_budgets.brand_id')
        ->groupBy('brands.id', 'brands.nama');

        if (!empty($selectedBrand)) {
            $reportQuery->where('brands.id', $selectedBrand);
        }

        $reportRows = $reportQuery->get();

        $reportSummary = $reportRows->map(function ($row) use ($spentMultiplier) {
            $spent = (float) ($row->spent ?? 0);
            $spentWithTax = $spent * $spentMultiplier;
            $realLead = (int) ($row->real_lead ?? 0);
            $closing = (int) ($row->closing ?? 0);
            $omset = (float) ($row->omset ?? 0);

            return [
                'brand' => $row->nama,
                'spent' => $spent,
                'spent_with_tax' => $spentWithTax,
                'real_lead' => $realLead,
                'closing' => $closing,
                'omset' => $omset,
                'cost_per_lead' => $realLead > 0 ? ($spent / $realLead) : 0,
                'roas' => $spentWithTax > 0 ? ($omset / $spentWithTax) : 0,
            ];
        })->values();
        
        // Get brands for select options
        $brands = Brand::select('id', 'nama')->orderBy('nama')->get();
        
        return Inertia::render('IklanBudget/Index', [
            'iklanBudgets' => $iklanBudgets,
            'totals' => $totals,
            'reportSummary' => $reportSummary,
            'brands' => $brands,
            // Kirimkan filters dengan default bulan berjalan jika tidak disediakan
            'filters' => [
                'start_date' => $activeStart->format('Y-m-d'),
                'end_date' => $activeEnd->format('Y-m-d'),
                'brand_id' => $request->brand_id,
            ],
            'permissions' => [
                'canCrud' => $currentUser->canCrud(),
                'canOnlyView' => $currentUser->canOnlyView(),
                'canOnlyViewOwn' => $currentUser->canOnlyViewOwn(),
                'role' => $currentUser->role,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'brand_id' => 'required|exists:brands,id',
            'spent_amount' => 'required|numeric|min:0',
        ]);

        // Add custom validation for unique combination of tanggal and brand_id
        $validator->after(function ($validator) use ($request) {
            $exists = IklanBudget::where('tanggal', $request->tanggal)
                ->where('brand_id', $request->brand_id)
                ->exists();
            
            if ($exists) {
                $validator->errors()->add('tanggal', 'Kombinasi tanggal dan brand sudah ada.');
            }
        });

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $iklanBudget = IklanBudget::create($validator->validated());
        // Hitung dan simpan Spent+PPN setelah create
        $ppnRate = (float) SiteSetting::get('ppn_rate', 11);
        $spentMultiplier = 1 + ($ppnRate / 100.0);
        $iklanBudget->spent_plus_tax = ((float) ($iklanBudget->spent_amount ?? 0)) * $spentMultiplier;
        $iklanBudget->save();

        return redirect()->route('iklan-budgets.index')
            ->with('success', 'Data budget iklan berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, IklanBudget $iklanBudget)
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'brand_id' => 'required|exists:brands,id',
            'spent_amount' => 'required|numeric|min:0',
        ]);

        // Add custom validation for unique combination of tanggal dan brand_id (excluding current record)
        $validator->after(function ($validator) use ($request, $iklanBudget) {
            $exists = IklanBudget::where('tanggal', $request->tanggal)
                ->where('brand_id', $request->brand_id)
                ->where('id', '!=', $iklanBudget->id)
                ->exists();
            
            if ($exists) {
                $validator->errors()->add('tanggal', 'Kombinasi tanggal dan brand sudah ada.');
            }
        });

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $iklanBudget->update($validator->validated());
        // Recalculate Spent+PPN setelah update
        $ppnRate = (float) SiteSetting::get('ppn_rate', 11);
        $spentMultiplier = 1 + ($ppnRate / 100.0);
        $iklanBudget->spent_plus_tax = ((float) ($iklanBudget->spent_amount ?? 0)) * $spentMultiplier;
        $iklanBudget->save();

        return redirect()->route('iklan-budgets.index')
            ->with('success', 'Data budget iklan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(IklanBudget $iklanBudget)
    {
        $iklanBudget->delete();

        return redirect()->route('iklan-budgets.index')
            ->with('success', 'Data budget iklan berhasil dihapus.');
    }

    /**
     * Hapus banyak data budget iklan sekaligus.
     */
    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids', []);

        if (!is_array($ids) || empty($ids)) {
            return back()->with('error', 'Tidak ada data yang dipilih untuk dihapus.');
        }

        try {
            $deleted = IklanBudget::whereIn('id', $ids)->delete();

            return redirect()->route('iklan-budgets.index')
                ->with('success', "Berhasil menghapus {$deleted} data budget iklan.");
        } catch (\Throwable $e) {
            \Log::error('Bulk delete IklanBudget gagal', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'ids' => $ids,
            ]);

            return back()->with('error', 'Gagal menghapus data terpilih: ' . $e->getMessage());
        }
    }

    /**
     * Generate budget data untuk satu bulan penuh
     */
    public function generateMonthlyBudget(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'year' => 'required|integer|min:2020|max:2030',
            'month' => 'required|integer|min:1|max:12',
            'default_budget' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $year = $request->year;
        $month = $request->month;
        $defaultBudget = $request->default_budget;
        
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        
        $created = 0;
        $current = $startDate->copy();
        
        while ($current <= $endDate) {
            $exists = IklanBudget::where('tanggal', $current->format('Y-m-d'))->exists();
            
            if (!$exists) {
                IklanBudget::create([
                    'tanggal' => $current->format('Y-m-d'),
                    'budget_amount' => $defaultBudget,
                    'spent_amount' => 0,
                    'closing' => 0,
                    'omset' => 0
                ]);
                $created++;
            }
            
            $current->addDay();
        }
        
        return redirect()->route('iklan-budgets.index')
            ->with('success', "Berhasil membuat {$created} data budget untuk bulan {$month}/{$year}.");
    }

    /**
     * Analytics: Get monthly spent totals for a given year and optional brand
     */
    public function monthlySpent(Request $request)
    {
        $year = (int) ($request->get('year', now()->year));
        $brandId = $request->get('brand_id');

        try {
            $query = IklanBudget::query()->whereYear('tanggal', $year);

            if (!empty($brandId)) {
                $query->where('brand_id', $brandId);
            }

            $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
            if ($driver === 'sqlite') {
                $monthExpr = "CAST(strftime('%m', tanggal) AS INTEGER)";
            } elseif ($driver === 'pgsql') {
                $monthExpr = "EXTRACT(MONTH FROM tanggal)";
            } else {
                $monthExpr = "MONTH(tanggal)";
            }

            $rows = $query
                ->selectRaw($monthExpr . ' as month, COALESCE(SUM(spent_amount), 0) as total_spent')
                ->groupBy(\Illuminate\Support\Facades\DB::raw($monthExpr))
                ->orderBy('month')
                ->get();
        } catch (\Throwable $e) {
            \Log::error('monthlySpent analytics failed', [
                'error' => $e->getMessage(),
                'driver' => \Illuminate\Support\Facades\DB::connection()->getDriverName(),
                'year' => $year,
                'brand_id' => $brandId,
            ]);
            $rows = collect([]);
        }

        // Prepare a full 12-month series with zero filling
        $monthLabels = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $found = $rows->firstWhere('month', $m);
            $data[] = [
                'month' => $m,
                'label' => $monthLabels[$m],
                'spent' => (float) ($found->total_spent ?? 0),
            ];
        }

        return response()->json([
            'year' => $year,
            'brand_id' => $brandId,
            'data' => $data,
        ]);
    }

    /**
     * Analytics: Get monthly leads totals for a given year and optional brand
     */
    public function monthlyLeads(Request $request)
    {
        $year = (int) ($request->get('year', now()->year));
        $brandId = $request->get('brand_id');

        try {
            $query = \App\Models\Mitra::query()->whereYear('tanggal_lead', $year);

            if (!empty($brandId)) {
                $query->where('brand_id', $brandId);
            }

            $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
            if ($driver === 'sqlite') {
                $monthExpr = "CAST(strftime('%m', tanggal_lead) AS INTEGER)";
            } elseif ($driver === 'pgsql') {
                $monthExpr = "EXTRACT(MONTH FROM tanggal_lead)";
            } else {
                $monthExpr = "MONTH(tanggal_lead)";
            }

            $rows = $query
                ->selectRaw($monthExpr . ' as month, COUNT(*) as total_leads')
                ->groupBy(\Illuminate\Support\Facades\DB::raw($monthExpr))
                ->orderBy('month')
                ->get();
        } catch (\Throwable $e) {
            \Log::error('monthlyLeads analytics failed', [
                'error' => $e->getMessage(),
                'driver' => \Illuminate\Support\Facades\DB::connection()->getDriverName(),
                'year' => $year,
                'brand_id' => $brandId,
            ]);
            $rows = collect([]);
        }

        // Prepare a full 12-month series with zero filling
        $monthLabels = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $found = $rows->firstWhere('month', $m);
            $data[] = [
                'month' => $m,
                'label' => $monthLabels[$m],
                'leads' => (int) ($found->total_leads ?? 0),
            ];
        }

        return response()->json([
            'year' => $year,
            'brand_id' => $brandId,
            'data' => $data,
        ]);
    }

    /**
     * Export data IklanBudget berdasarkan filter ke Excel (Tanggal, Brand, Spent)
     */
    public function export(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                abort(403);
            }

            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            $activeStart = $request->filled('start_date') ? Carbon::parse($request->start_date) : $startOfMonth;
            $activeEnd = $request->filled('end_date') ? Carbon::parse($request->end_date) : $endOfMonth;

            $query = IklanBudget::with('brand')->inPeriod($activeStart, $activeEnd);
            if ($request->filled('brand_id')) {
                $query->where('brand_id', $request->brand_id);
            }

            $budgets = $query->orderBy('tanggal', 'asc')->get();

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Budget Iklan');

            // Header
            $sheet->setCellValue('A1', 'Tanggal');
            $sheet->setCellValue('B1', 'Brand');
            $sheet->setCellValue('C1', 'Spent');

            $row = 2;
            foreach ($budgets as $budget) {
                $sheet->setCellValue('A' . $row, Carbon::parse($budget->tanggal)->format('Y-m-d'));
                $sheet->setCellValue('B' . $row, optional($budget->brand)->nama);
                $sheet->setCellValue('C' . $row, (float) $budget->spent_amount);
                $row++;
            }

            $filename = 'export-iklan-budget-' . now()->format('Ymd_His') . '.xlsx';
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        } catch (\Throwable $e) {
            \Log::error('IklanBudget export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'filters' => $request->all(),
            ]);
            return back()->with('error', 'Export gagal: ' . $e->getMessage());
        }
    }

    /**
     * Download template import Excel (Tanggal, Brand, Spent)
     */
    public function downloadTemplate()
    {
        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Template Import');

            // Header
            $sheet->setCellValue('A1', 'Tanggal');
            $sheet->setCellValue('B1', 'Brand');
            $sheet->setCellValue('C1', 'Spent');

            // Contoh data
            $sheet->setCellValue('A2', now()->format('Y-m-d'));
            $sampleBrand = Brand::select('nama')->orderBy('nama')->first();
            $sheet->setCellValue('B2', $sampleBrand ? $sampleBrand->nama : 'Contoh Brand');
            $sheet->setCellValue('C2', 100000);

            $filename = 'template-import-iklan-budget.xlsx';
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        } catch (\Throwable $e) {
            \Log::error('IklanBudget template generation failed', [
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Gagal mengunduh template: ' . $e->getMessage());
        }
    }

    /**
     * Import data dari Excel (Tanggal, Brand, Spent) dengan upsert per (brand_id, tanggal)
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // Max 10MB
        ]);

        try {
            DB::beginTransaction();

            $file = $request->file('file');

            // Check file size
            if ($file->getSize() > 10 * 1024 * 1024) { // 10MB
                throw new \Exception('File too large. Maximum size is 10MB.');
            }

            // Increase memory limit and execution time for large files
            ini_set('memory_limit', '1G');
            set_time_limit(300); // 5 minutes

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $imported = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];

            if (count($rows) === 0) {
                throw new \Exception('File kosong atau tidak dapat dibaca.');
            }

            // Remove header
            array_shift($rows);

            $batchSize = 100;
            $totalRows = count($rows);
            $processedRows = [];

            foreach ($rows as $index => $row) {
                $line = $index + 2; // Header di baris 1
                $tanggalRaw = trim((string)($row[0] ?? ''));
                $brandRaw = trim((string)($row[1] ?? ''));
                $spentRaw = trim((string)($row[2] ?? ''));

                // Skip baris kosong
                if ($tanggalRaw === '' && $brandRaw === '' && $spentRaw === '') {
                    continue;
                }

                if ($tanggalRaw === '') {
                    $skipped++; $errors[] = "Baris {$line}: Tanggal kosong"; continue;
                }
                if ($brandRaw === '') {
                    $skipped++; $errors[] = "Baris {$line}: Brand kosong"; continue;
                }

                // Parse tanggal
                try {
                    $tanggal = Carbon::parse($tanggalRaw)->format('Y-m-d');
                } catch (\Throwable $e) {
                    $skipped++; $errors[] = "Baris {$line}: Format tanggal tidak valid"; continue;
                }

                // Resolve brand
                $brandId = null;
                if (is_numeric($brandRaw)) {
                    $brandId = (int) $brandRaw;
                    if (!Brand::where('id', $brandId)->exists()) {
                        $skipped++; $errors[] = "Baris {$line}: Brand ID tidak ditemukan"; continue;
                    }
                } else {
                    $brand = Brand::where('nama', $brandRaw)->first();
                    if (!$brand) {
                        $skipped++; $errors[] = "Baris {$line}: Brand '{$brandRaw}' tidak ditemukan"; continue;
                    }
                    $brandId = $brand->id;
                }

                // Parse spent
                $spentVal = 0;
                if ($spentRaw !== '' && is_numeric($spentRaw)) {
                    $spentVal = (float) $spentRaw;
                } else {
                    $clean = preg_replace('/[^0-9.,]/', '', $spentRaw);
                    $clean = str_replace('.', '', $clean);
                    $clean = str_replace(',', '.', $clean);
                    $spentVal = is_numeric($clean) ? (float) $clean : 0;
                }

                $processedRows[] = [
                    'tanggal' => $tanggal,
                    'brand_id' => $brandId,
                    'spent_amount' => $spentVal,
                    'line' => $line
                ];

                // Process in batches
                if (count($processedRows) >= $batchSize) {
                    $this->processBatch($processedRows, $imported, $updated, $skipped, $errors);
                    $processedRows = [];
                }
            }

            // Process remaining rows
            if (!empty($processedRows)) {
                $this->processBatch($processedRows, $imported, $updated, $skipped, $errors);
            }

            DB::commit();

            $message = "Import selesai. {$imported} baru, {$updated} diperbarui, {$skipped} dilewati.";
            if (!empty($errors)) {
                $message .= ' ' . count($errors) . ' catatan kesalahan.';
            }

            // Jika request AJAX/JSON, kembalikan payload JSON
            $payload = [
                'success' => true,
                'message' => $message,
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $errors,
                'total_processed' => $imported + $updated + $skipped,
            ];
            if ($request->expectsJson()) {
                return response()->json($payload);
            }

            return redirect()->route('iklan-budgets.index')
                ->with('success', $message)
                ->with('import_errors', $errors);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('IklanBudget import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ]);

            // Jika request AJAX/JSON, kembalikan error JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import gagal: ' . $e->getMessage(),
                    'imported' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'errors' => [$e->getMessage()],
                    'total_processed' => 0,
                ], 500);
            }

            return back()->with('error', 'Import gagal: ' . $e->getMessage());
        }
    }

    /**
     * Process batch of import rows
     */
    private function processBatch(array $rows, int &$imported, int &$updated, int &$skipped, array &$errors)
    {
        foreach ($rows as $row) {
            try {
                $existing = IklanBudget::where('brand_id', $row['brand_id'])->where('tanggal', $row['tanggal'])->first();
                $ppnRate = (float) SiteSetting::get('ppn_rate', 11);
                $spentMultiplier = 1 + ($ppnRate / 100.0);

                if ($existing) {
                    $existing->spent_amount = $row['spent_amount'];
                    $existing->closing = IklanBudget::calculateClosingForDate($row['tanggal'], $row['brand_id']);
                    $existing->omset = IklanBudget::calculateOmsetForDate($row['tanggal'], $row['brand_id']);
                    $existing->spent_plus_tax = ((float) $row['spent_amount']) * $spentMultiplier;
                    $existing->save();
                    $updated++;
                } else {
                    $model = IklanBudget::create([
                        'tanggal' => $row['tanggal'],
                        'brand_id' => $row['brand_id'],
                        'spent_amount' => $row['spent_amount'],
                    ]);
                    $model->closing = IklanBudget::calculateClosingForDate($row['tanggal'], $row['brand_id']);
                    $model->omset = IklanBudget::calculateOmsetForDate($row['tanggal'], $row['brand_id']);
                    $model->spent_plus_tax = ((float) $row['spent_amount']) * $spentMultiplier;
                    $model->save();
                    $imported++;
                }
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "Baris {$row['line']}: Gagal menyimpan - " . $e->getMessage();
            }
        }
    }
}