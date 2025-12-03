<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;

use App\Models\Brand;
use App\Models\Sumber;
use App\Models\Pekerjaan;
use App\Models\Mitra;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class TransaksiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        // Build base query once for consistent aggregates across pagination
        $baseQuery = Transaksi::with(['user', 'paketBrand', 'leadAwalBrand', 'sumberRef', 'pekerjaan']);

        // Apply role-based filtering
        $baseQuery = $user->applyRoleFilter($baseQuery, 'user_id');

        // Apply search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $baseQuery->where(function ($q) use ($search) {
                $q->where('nama_paket', 'like', "%{$search}%")
                  ->orWhere('kabupaten', 'like', "%{$search}%")
                  ->orWhere('provinsi', 'like', "%{$search}%")
                  ->orWhere('no_wa', 'like', "%{$search}%")
                  // Support searching by Nama Mitra
                  ->orWhere('nama_mitra', 'like', "%{$search}%")
                  // Support searching by Marketing (user name)
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply periode filter with defaults: current month
        $startDate = $request->get('periode_start', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('periode_end', now()->endOfMonth()->format('Y-m-d'));
        $baseQuery->whereDate('tanggal_tf', '>=', $startDate);
        $baseQuery->whereDate('tanggal_tf', '<=', $endDate);

        // Apply brand filter
        if ($request->has('brand_id') && $request->brand_id) {
            $baseQuery->where('paket_brand_id', $request->brand_id);
        }

        $perPage = $request->get('per_page', 10);
        $transaksis = (clone $baseQuery)->orderBy('created_at', 'desc')->paginate($perPage);

        $statusCounts = [
            'all' => (clone $baseQuery)->count(),
            'dp_tj' => (clone $baseQuery)->where('status_pembayaran', 'Dp / TJ')->count(),
            'tambahan_dp' => (clone $baseQuery)->where('status_pembayaran', 'Tambahan Dp')->count(),
            'pelunasan' => (clone $baseQuery)->where('status_pembayaran', 'Pelunasan')->count(),
        ];

        // Total nominal over filtered dataset (not limited by pagination)
        $totalNominal = (clone $baseQuery)->sum('nominal_masuk');

        // Get data for filters
        $brands = Brand::select('id', 'nama')->get();
        $sumbers = Sumber::select('id', 'nama', 'warna')->orderBy('nama')->get();
        $pekerjaans = Pekerjaan::select('id', 'nama', 'warna')->orderBy('nama')->get();

        return Inertia::render('Transaksi/Index', [
            'transaksis' => $transaksis,
            'brands' => $brands,
            'sumbers' => $sumbers,
            'pekerjaans' => $pekerjaans,
            'currentUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
            'filters' => [
                'search' => $request->search,
                'brand_id' => $request->brand_id,
                'periode_start' => $startDate,
                'periode_end' => $endDate,
                'per_page' => $perPage,
            ],
            'permissions' => [
                'canCrud' => $user->canCrud(),
                'canOnlyView' => $user->canOnlyView(),
                'canOnlyViewOwn' => $user->canOnlyViewOwn(),
            ],
            'totalNominal' => (float) $totalNominal,
            'statusCounts' => $statusCounts,
        ]);
    }

    /**
     * Export daftar transaksi ke XLSX berdasarkan filter (periode tanggal & brand)
     */
    public function export(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                abort(403);
            }

            // Build base query with relations for display
            $query = Transaksi::with(['user', 'paketBrand', 'leadAwalBrand', 'pekerjaan']);

            // Apply role-based filtering
            $query = $user->applyRoleFilter($query, 'user_id');

            // Date filters: support both periode_* and start_date/end_date
            $startDate = $request->get('periode_start', $request->get('start_date'));
            $endDate = $request->get('periode_end', $request->get('end_date'));
            if ($startDate) {
                $query->whereDate('tanggal_tf', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('tanggal_tf', '<=', $endDate);
            }

            // Optional brand filter (paket brand)
            if ($request->filled('brand_id')) {
                $query->where('paket_brand_id', $request->brand_id);
            }

            // Optional simple search filter (same fields as index)
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nama_paket', 'like', "%{$search}%")
                      ->orWhere('kabupaten', 'like', "%{$search}%")
                      ->orWhere('provinsi', 'like', "%{$search}%")
                      ->orWhere('no_wa', 'like', "%{$search}%")
                      ->orWhere('nama_mitra', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $items = $query->orderBy('tanggal_tf', 'asc')->get();

            // Prepare spreadsheet
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Daftar Transaksi');

            // Header row
            $sheet->setCellValue('A1', 'Tanggal TF');
            $sheet->setCellValue('B1', 'Marketing');
            $sheet->setCellValue('C1', 'Nama Mitra');
            $sheet->setCellValue('D1', 'No WA');
            $sheet->setCellValue('E1', 'Usia');
            $sheet->setCellValue('F1', 'Pekerjaan');
            $sheet->setCellValue('G1', 'Status Pembayaran');
            $sheet->setCellValue('H1', 'Nominal Masuk');
            $sheet->setCellValue('I1', 'Harga Paket');
            $sheet->setCellValue('J1', 'Nama Paket');
            $sheet->setCellValue('K1', 'Paket Brand');
            $sheet->setCellValue('L1', 'Lead Awal Brand');
            $sheet->setCellValue('M1', 'Sumber');
            $sheet->setCellValue('N1', 'Kabupaten');
            $sheet->setCellValue('O1', 'Provinsi');
            $sheet->setCellValue('P1', 'Tanggal Lead Masuk');
            $sheet->setCellValue('Q1', 'Periode Lead');

            $row = 2;
            foreach ($items as $trx) {
                $sheet->setCellValue('A' . $row, (string) ($trx->tanggal_tf ? \Carbon\Carbon::parse($trx->tanggal_tf)->format('Y-m-d') : ''));
                $sheet->setCellValue('B' . $row, (string) (optional($trx->user)->name ?? '-'));
                $sheet->setCellValue('C' . $row, (string) ($trx->nama_mitra ?? '-'));
                // Ensure phone stored as string to prevent scientific notation
                $sheet->setCellValueExplicit('D' . $row, (string) ($trx->no_wa ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue('E' . $row, $trx->usia !== null ? (int) $trx->usia : null);
                $sheet->setCellValue('F' . $row, (string) (optional($trx->pekerjaan)->nama ?? '-'));
                $sheet->setCellValue('G' . $row, (string) ($trx->status_pembayaran ?? '-'));
                $sheet->setCellValue('H' . $row, (float) ($trx->nominal_masuk ?? 0));
                $sheet->setCellValue('I' . $row, (float) ($trx->harga_paket ?? 0));
                $sheet->setCellValue('J' . $row, (string) ($trx->nama_paket ?? '-'));
                $sheet->setCellValue('K' . $row, (string) (optional($trx->paketBrand)->nama ?? '-'));
                $sheet->setCellValue('L' . $row, (string) (optional($trx->leadAwalBrand)->nama ?? '-'));
                $sheet->setCellValue('M' . $row, (string) (trim((string) ($trx->sumber ?? '')) !== '' ? $trx->sumber : (optional($trx->sumberRef)->nama ?? 'Unknown')));
                $sheet->setCellValue('N' . $row, (string) ($trx->kabupaten ?? '-'));
                $sheet->setCellValue('O' . $row, (string) ($trx->provinsi ?? '-'));
                $sheet->setCellValue('P' . $row, (string) ($trx->tanggal_lead_masuk ? \Carbon\Carbon::parse($trx->tanggal_lead_masuk)->format('Y-m-d') : ''));
                $sheet->setCellValue('Q' . $row, (string) ($trx->periode_lead ?? '-'));
                $row++;
            }

            $filename = 'export-transaksi-' . now()->format('Ymd_His') . '.xlsx';
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Transaksi export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'filters' => $request->all(),
            ]);
            return back()->with('error', 'Export gagal: ' . $e->getMessage());
        }
    }

    /**
     * Get payment status analytics data for chart
     */
    public function getPaymentStatusAnalytics(Request $request)
    {
        $user = auth()->user();
        $query = Transaksi::query();

        // Apply role-based filtering
        $query = $user->applyRoleFilter($query, 'user_id');

        // Apply date range filter (default to current month)
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));
        
        $query->whereBetween('tanggal_tf', [$startDate, $endDate]);

        // Apply optional filters for marketing and brand
        $marketingId = $request->get('marketing');
        // Support both 'brand_id' and 'brand' param names
        $brandId = $request->get('brand_id', $request->get('brand'));
        if ($marketingId) {
            $query->where('user_id', $marketingId);
        }
        if ($brandId) {
            // Filter by lead awal brand for transaction analytics
            $query->where('lead_awal_brand_id', $brandId);
        }

        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver");

        if ($connection === 'sqlite') {
            $data = $query->selectRaw('
                    strftime("%Y-%m-%d", tanggal_tf) as date,
                    status_pembayaran,
                    COUNT(*) as count
                ')
                ->groupBy('date', 'status_pembayaran')
                ->orderBy('date')
                ->get();
        } elseif ($connection === 'pgsql') {
            $data = $query->selectRaw('
                    CAST(tanggal_tf AS DATE) as date,
                    status_pembayaran,
                    COUNT(*) as count
                ')
                ->groupBy('date', 'status_pembayaran')
                ->orderBy('date')
                ->get();
        } else {
            $data = $query->selectRaw('
                    DATE(tanggal_tf) as date,
                    status_pembayaran,
                    COUNT(*) as count
                ')
                ->groupBy('date', 'status_pembayaran')
                ->orderBy('date')
                ->get();
        }

        return response()->json([
            'data' => $data,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'tanggal_tf' => 'required|date',
            'tanggal_lead_masuk' => 'required|date',
            'periode_lead' => 'required|string|in:Januari,Februari,Maret,April,Mei,Juni,Juli,Agustus,September,Oktober,November,Desember',
            'no_wa' => 'required|string|max:20',
            'usia' => 'required|integer|min:17|max:80',
            'nama_mitra' => 'required|string|max:255',
            'pekerjaan_id' => 'nullable|exists:pekerjaans,id',
            'paket_brand_id' => 'required|exists:brands,id',
            'lead_awal_brand_id' => 'required|exists:brands,id',
            'sumber_id' => 'nullable|exists:sumbers,id',
            'sumber' => 'nullable|string|max:255',
            'kabupaten' => 'required|string|max:255',
            'provinsi' => 'required|string|max:255',
            'status_pembayaran' => 'required|in:Dp / TJ,Tambahan Dp,Pelunasan',
            'nominal_masuk' => 'required|numeric|min:0',
            'harga_paket' => 'required|numeric|min:0',
            'nama_paket' => 'required|string|max:255',
        ]);

        // Derive sumber string from sumber_id or fallback to provided sumber/Unknown
        $sumberString = 'Unknown';
        if (!empty($validated['sumber_id'])) {
            $ref = Sumber::find($validated['sumber_id']);
            $sumberString = $ref ? $ref->nama : 'Unknown';
        } elseif (!empty($validated['sumber'])) {
            $sumberString = $validated['sumber'];
        }

        // If DB still has non-null mitra_id (e.g., SQLite dev), link or create Mitra
        $mitraId = null;
        if (Schema::hasColumn('transaksis', 'mitra_id')) {
            $cleanPhone = preg_replace('/[^0-9]/', '', (string)($validated['no_wa'] ?? ''));
            $existingMitra = null;
            if (!empty($cleanPhone)) {
                $existingMitra = Mitra::where('no_telp', $cleanPhone)->first();
            }
            if (!$existingMitra && !empty($validated['nama_mitra'])) {
                $existingMitra = Mitra::where('nama', $validated['nama_mitra'])->first();
            }
            if (!$existingMitra) {
                // Create a minimal Mitra to satisfy FK; defaults aligned with schema
                $existingMitra = Mitra::create([
                    'nama' => $validated['nama_mitra'],
                    'no_telp' => $cleanPhone ?: '000',
                    'tanggal_lead' => $validated['tanggal_lead_masuk'],
                    'user_id' => $user->id,
                    'brand_id' => $validated['paket_brand_id'],
                    'label_id' => null,
                    'chat' => 'masuk',
                    'kota' => $validated['kabupaten'],
                    'provinsi' => $validated['provinsi'],
                    'komentar' => null,
                    'webinar' => 'Tidak',
                ]);
            }
            $mitraId = $existingMitra?->id;
        }

        $payload = [
            'user_id' => $user->id,
            'tanggal_tf' => $validated['tanggal_tf'],
            'tanggal_lead_masuk' => $validated['tanggal_lead_masuk'],
            'periode_lead' => $validated['periode_lead'],
            'no_wa' => $validated['no_wa'],
            'usia' => $validated['usia'],
            'nama_mitra' => $validated['nama_mitra'] ?? null,
            'pekerjaan_id' => $validated['pekerjaan_id'] ?? null,
            'paket_brand_id' => $validated['paket_brand_id'],
            'lead_awal_brand_id' => $validated['lead_awal_brand_id'],
            'sumber_id' => $validated['sumber_id'] ?? null,
            'sumber' => $sumberString,
            'kabupaten' => $validated['kabupaten'],
            'provinsi' => $validated['provinsi'],
            'status_pembayaran' => $validated['status_pembayaran'],
            'nominal_masuk' => $validated['nominal_masuk'],
            'harga_paket' => $validated['harga_paket'],
            'nama_paket' => $validated['nama_paket'],
        ];
        if ($mitraId !== null) {
            $payload['mitra_id'] = $mitraId;
        }

        Transaksi::create($payload);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Transaksi berhasil ditambahkan.']);
        }

        return redirect()->route('transaksis.index')
            ->with('success', 'Transaksi berhasil ditambahkan.');
    }

    /**
     * Get source analytics data for chart
     */
    public function getSourceAnalytics(Request $request)
    {
        $user = auth()->user();
        $query = Transaksi::query();

        // Apply role-based filtering
        $query = $user->applyRoleFilter($query, 'user_id');

        // Apply date range filter only if provided (default: all time)
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        if ($startDate && $endDate) {
            $query->whereBetween('tanggal_tf', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->whereDate('tanggal_tf', '>=', $startDate);
        } elseif ($endDate) {
            $query->whereDate('tanggal_tf', '<=', $endDate);
        }

        // Apply optional filters for marketing and brand
        $marketingId = $request->get('marketing');
        $brandId = $request->get('brand_id', $request->get('brand'));
        if ($marketingId) {
            $query->where('user_id', $marketingId);
        }
        if ($brandId) {
            $query->where('lead_awal_brand_id', $brandId);
        }

        // Group by sumber string and aggregate counts and total nominal
        $data = $query->selectRaw('
                COALESCE(NULLIF(TRIM(sumber), ""), "Unknown") as sumber,
                COUNT(*) as count,
                COALESCE(SUM(nominal_masuk), 0) as total_nominal
            ')
            ->groupBy('sumber')
            ->orderByDesc('count')
            ->get();

        return response()->json([
            'data' => $data,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * Get pekerjaan analytics data for chart
     */
    public function getPekerjaanAnalytics(Request $request)
    {
        $user = auth()->user();
        $query = Transaksi::query();

        // Apply role-based filtering
        $query = $user->applyRoleFilter($query, 'user_id');

        // Apply date range filter (default to current year)
        $startDate = $request->get('start_date', now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfYear()->format('Y-m-d'));

        $query->whereBetween('tanggal_tf', [$startDate, $endDate]);

        // Apply optional filters for marketing and brand
        $marketingId = $request->get('marketing');
        $brandId = $request->get('brand_id', $request->get('brand'));
        if ($marketingId) {
            $query->where('user_id', $marketingId);
        }
        if ($brandId) {
            $query->where('lead_awal_brand_id', $brandId);
        }

        // Join pekerjaan table to get pekerjaan name and aggregate counts & totals
        $data = $query->leftJoin('pekerjaans', 'transaksis.pekerjaan_id', '=', 'pekerjaans.id')
            ->selectRaw('
                COALESCE(pekerjaans.nama, "Unknown") as pekerjaan,
                COUNT(*) as count,
                COALESCE(SUM(transaksis.nominal_masuk), 0) as total_nominal
            ')
            ->groupBy('pekerjaan')
            ->orderByDesc('count')
            ->get();

        return response()->json([
            'data' => $data,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * Get age analytics data for chart
     */
    public function getAgeAnalytics(Request $request)
    {
        $user = auth()->user();
        $query = Transaksi::query();

        // Apply role-based filtering
        $query = $user->applyRoleFilter($query, 'user_id');

        // Apply date range filter only if provided (default: all time)
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        if ($startDate && $endDate) {
            $query->whereBetween('tanggal_tf', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->whereDate('tanggal_tf', '>=', $startDate);
        } elseif ($endDate) {
            $query->whereDate('tanggal_tf', '<=', $endDate);
        }

        // Apply optional filters for marketing and brand
        $marketingId = $request->get('marketing');
        $brandId = $request->get('brand_id', $request->get('brand'));
        if ($marketingId) {
            $query->where('user_id', $marketingId);
        }
        if ($brandId) {
            $query->where('lead_awal_brand_id', $brandId);
        }

        $data = $query->selectRaw('
                CASE
                    WHEN usia IS NULL THEN "Unknown"
                    WHEN usia BETWEEN 0 AND 17 THEN "0-17"
                    WHEN usia BETWEEN 18 AND 24 THEN "18-24"
                    WHEN usia BETWEEN 25 AND 34 THEN "25-34"
                    WHEN usia BETWEEN 35 AND 44 THEN "35-44"
                    WHEN usia BETWEEN 45 AND 54 THEN "45-54"
                    WHEN usia BETWEEN 55 AND 64 THEN "55-64"
                    ELSE "65+"
                END AS usia_bucket,
                COUNT(*) as count,
                COALESCE(SUM(nominal_masuk), 0) as total_nominal
            ')
            ->groupBy('usia_bucket')
            ->orderByDesc('count')
            ->get();

        return response()->json([
            'data' => $data,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * Get lead awal analytics data for chart
     */
    public function getLeadAwalAnalytics(Request $request)
    {
        $user = auth()->user();
        $query = Transaksi::query();

        // Apply role-based filtering
        $query = $user->applyRoleFilter($query, 'user_id');

        // Apply date range filter only if provided (default: all time)
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        if ($startDate && $endDate) {
            $query->whereBetween('tanggal_tf', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->whereDate('tanggal_tf', '>=', $startDate);
        } elseif ($endDate) {
            $query->whereDate('tanggal_tf', '<=', $endDate);
        }

        // Apply optional filters for marketing and brand
        $marketingId = $request->get('marketing');
        $brandId = $request->get('brand_id', $request->get('brand'));
        if ($marketingId) {
            $query->where('user_id', $marketingId);
        }
        if ($brandId) {
            $query->where('lead_awal_brand_id', $brandId);
        }

        $data = $query
            ->leftJoin('brands as lead_awal_brands', 'transaksis.lead_awal_brand_id', '=', 'lead_awal_brands.id')
            ->selectRaw('
                COALESCE(lead_awal_brands.nama, "Unknown") as lead_awal,
                COUNT(*) as count,
                COALESCE(SUM(transaksis.nominal_masuk), 0) as total_nominal
            ')
            ->groupBy('lead_awal')
            ->orderByDesc('count')
            ->get();

        return response()->json([
            'data' => $data,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaksi $transaksi)
    {
        $user = auth()->user();
        
        // Check if user can access this transaksi
        if ($user->isMarketing() && $transaksi->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk melihat data ini.');
        }

        return Inertia::render('Transaksi/Show', [
            'transaksi' => $transaksi->load(['user', 'paketBrand', 'leadAwalBrand', 'sumberRef', 'pekerjaan']),
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
    public function update(Request $request, Transaksi $transaksi)
    {
        $user = auth()->user();

        // Marketing can only update own records
        if ($user->isMarketing() && $transaksi->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk mengupdate data ini.');
        }

        $validated = $request->validate([
            'tanggal_tf' => 'required|date',
            'tanggal_lead_masuk' => 'required|date',
            'periode_lead' => 'required|string|in:Januari,Februari,Maret,April,Mei,Juni,Juli,Agustus,September,Oktober,November,Desember',
            'no_wa' => 'required|string|max:20',
            'usia' => 'required|integer|min:17|max:80',
            'nama_mitra' => 'required|string|max:255',
            'pekerjaan_id' => 'nullable|exists:pekerjaans,id',
            'paket_brand_id' => 'required|exists:brands,id',
            'lead_awal_brand_id' => 'required|exists:brands,id',
            'sumber_id' => 'nullable|exists:sumbers,id',
            'sumber' => 'nullable|string|max:255',
            'kabupaten' => 'required|string|max:255',
            'provinsi' => 'required|string|max:255',
            'status_pembayaran' => 'required|in:Dp / TJ,Tambahan Dp,Pelunasan',
            'nominal_masuk' => 'required|numeric|min:0',
            'harga_paket' => 'required|numeric|min:0',
            'nama_paket' => 'required|string|max:255',
        ]);

        // Derive sumber string from sumber_id or fallback
        $sumberString = 'Unknown';
        if (!empty($validated['sumber_id'])) {
            $ref = Sumber::find($validated['sumber_id']);
            $sumberString = $ref ? $ref->nama : 'Unknown';
        } elseif (!empty($validated['sumber'])) {
            $sumberString = $validated['sumber'];
        }

        $transaksi->update([
            // Keep original user_id; do not force-change on update
            'tanggal_tf' => $validated['tanggal_tf'],
            'tanggal_lead_masuk' => $validated['tanggal_lead_masuk'],
            'periode_lead' => $validated['periode_lead'],
            'no_wa' => $validated['no_wa'],
            'usia' => $validated['usia'],
            'nama_mitra' => $validated['nama_mitra'] ?? null,
            'pekerjaan_id' => $validated['pekerjaan_id'] ?? null,
            'paket_brand_id' => $validated['paket_brand_id'],
            'lead_awal_brand_id' => $validated['lead_awal_brand_id'],
            'sumber_id' => $validated['sumber_id'] ?? null,
            'sumber' => $sumberString,
            'kabupaten' => $validated['kabupaten'],
            'provinsi' => $validated['provinsi'],
            'status_pembayaran' => $validated['status_pembayaran'],
            'nominal_masuk' => $validated['nominal_masuk'],
            'harga_paket' => $validated['harga_paket'],
            'nama_paket' => $validated['nama_paket'],
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Transaksi berhasil diperbarui.']);
        }

        return redirect()->route('transaksis.index')
            ->with('success', 'Transaksi berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaksi $transaksi)
    {
        $user = auth()->user();

        // Marketing can only delete own records
        if ($user->isMarketing() && $transaksi->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus data ini.');
        }

        $transaksi->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Transaksi berhasil dihapus.']);
        }

        return redirect()->route('transaksis.index')
            ->with('success', 'Transaksi berhasil dihapus.');
    }
}
