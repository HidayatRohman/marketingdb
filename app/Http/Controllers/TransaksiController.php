<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;

use App\Models\Brand;
use App\Models\Sumber;
use App\Models\Pekerjaan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TransaksiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Transaksi::with(['user', 'paketBrand', 'leadAwalBrand', 'sumberRef', 'pekerjaan']);

        // Apply role-based filtering
        $query = $user->applyRoleFilter($query, 'user_id');

        // Apply search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_paket', 'like', "%{$search}%")
                  ->orWhere('kabupaten', 'like', "%{$search}%")
                  ->orWhere('provinsi', 'like', "%{$search}%")
                  ->orWhere('no_wa', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply periode filter
        if ($request->has('periode_start') && $request->periode_start) {
            $query->whereDate('tanggal_tf', '>=', $request->periode_start);
        }

        if ($request->has('periode_end') && $request->periode_end) {
            $query->whereDate('tanggal_tf', '<=', $request->periode_end);
        }

        // Apply brand filter
        if ($request->has('brand_id') && $request->brand_id) {
            $query->where('paket_brand_id', $request->brand_id);
        }

        $perPage = $request->get('per_page', 10);
        $transaksis = $query->orderBy('created_at', 'desc')->paginate($perPage);

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
     * Get payment status analytics data for chart
     */
    public function getPaymentStatusAnalytics(Request $request)
    {
        $user = auth()->user();
        $query = Transaksi::query();

        // Apply role-based filtering
        $query = $user->applyRoleFilter($query, 'user_id');

        // Apply date range filter (default to current year)
        $startDate = $request->get('start_date', now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfYear()->format('Y-m-d'));
        
        $query->whereBetween('tanggal_tf', [$startDate, $endDate]);

        // Group by month and status_pembayaran
        $data = $query->selectRaw('
                YEAR(tanggal_tf) as year,
                MONTH(tanggal_tf) as month,
                status_pembayaran,
                COUNT(*) as count
            ')
            ->groupBy('year', 'month', 'status_pembayaran')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // Format data for chart
        $chartData = [];
        $months = [];
        
        // Generate all months in the date range
        $start = Carbon::parse($startDate)->startOfMonth();
        $end = Carbon::parse($endDate)->endOfMonth();
        
        while ($start <= $end) {
            $monthKey = $start->format('Y-m');
            $monthLabel = $start->format('M Y');
            $months[] = $monthLabel;
            
            $chartData[$monthKey] = [
                'month' => $monthLabel,
                'dp' => 0,
                'tambahan_dp' => 0,
                'pelunasan' => 0,
                'total' => 0
            ];
            
            $start->addMonth();
        }

        // Fill data from database
        foreach ($data as $item) {
            $monthKey = $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
            
            if (isset($chartData[$monthKey])) {
                switch ($item->status_pembayaran) {
                    case 'Dp / TJ':
                        $chartData[$monthKey]['dp'] = $item->count;
                        break;
                    case 'Tambahan Dp':
                        $chartData[$monthKey]['tambahan_dp'] = $item->count;
                        break;
                    case 'Pelunasan':
                        $chartData[$monthKey]['pelunasan'] = $item->count;
                        break;
                }
                $chartData[$monthKey]['total'] += $item->count;
            }
        }

        return response()->json([
            'data' => array_values($chartData),
            'months' => $months
        ]);
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

        // Apply date range filter (default to current year)
        $startDate = $request->get('start_date', now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfYear()->format('Y-m-d'));
        
        $query->whereBetween('tanggal_tf', [$startDate, $endDate]);

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
     * Get age analytics data for chart
     */
    public function getAgeAnalytics(Request $request)
    {
        $user = auth()->user();
        $query = Transaksi::query();

        // Apply role-based filtering
        $query = $user->applyRoleFilter($query, 'user_id');

        // Apply date range filter (default to current year)
        $startDate = $request->get('start_date', now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfYear()->format('Y-m-d'));

        $query->whereBetween('tanggal_tf', [$startDate, $endDate]);

        // Group by age buckets and aggregate counts and total nominal
        $data = $query->selectRaw('
                CASE
                    WHEN usia IS NULL OR usia <= 0 THEN "Unknown"
                    WHEN usia BETWEEN 17 AND 24 THEN "17-24"
                    WHEN usia BETWEEN 25 AND 34 THEN "25-34"
                    WHEN usia BETWEEN 35 AND 44 THEN "35-44"
                    WHEN usia BETWEEN 45 AND 54 THEN "45-54"
                    ELSE "55+"
                END AS usia_bucket,
                COUNT(*) AS count,
                COALESCE(SUM(nominal_masuk), 0) AS total_nominal
            ')
            ->groupBy('usia_bucket')
            ->orderByRaw("FIELD(usia_bucket, 'Unknown','17-24','25-34','35-44','45-54','55+')")
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
     * Get lead awal (brand) analytics data for chart
     */
    public function getLeadAwalAnalytics(Request $request)
    {
        $user = auth()->user();
        $query = Transaksi::query();

        // Apply role-based filtering
        $query = $user->applyRoleFilter($query, 'user_id');

        // Apply date range filter (default to current year)
        $startDate = $request->get('start_date', now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfYear()->format('Y-m-d'));

        $query->whereBetween('tanggal_tf', [$startDate, $endDate]);

        // Join brands table to get lead awal brand name and aggregate counts & totals
        $data = $query->leftJoin('brands', 'transaksis.lead_awal_brand_id', '=', 'brands.id')
            ->selectRaw('
                COALESCE(brands.nama, "Unknown") as lead_awal,
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
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal_tf' => 'required|date',
            'tanggal_lead_masuk' => 'required|date',
            'periode_lead' => 'required|in:Januari,Februari,Maret,April,Mei,Juni,Juli,Agustus,September,Oktober,November,Desember',
            'usia' => 'required|integer|min:17|max:80',
            'no_wa' => 'required|string|max:20',
            'nama_mitra' => 'required|string|max:255',
            'pekerjaan_id' => 'nullable|exists:pekerjaans,id',
            'paket_brand_id' => 'required|exists:brands,id',
            'lead_awal_brand_id' => 'required|exists:brands,id',
            'sumber_id' => 'required|exists:sumbers,id',
            'kabupaten' => 'required|string|max:255',
            'provinsi' => 'required|string|max:255',
            'status_pembayaran' => 'required|in:Dp / TJ,Tambahan Dp,Pelunasan',
            'nominal_masuk' => 'required|numeric|min:0',
            'harga_paket' => 'required|numeric|min:0',
            'nama_paket' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()
                ->with('error', 'Terjadi kesalahan validasi. Silakan periksa kembali data yang dimasukkan.');
        }

        $validated = $validator->validated();
        $user = auth()->user();
        
        // For marketing role, always use current user ID
        if ($user->role === 'marketing') {
            $validated['user_id'] = $user->id;
        } elseif (empty($validated['user_id'])) {
            $validated['user_id'] = $user->id;
        }

        try {
            // Debug logging
            \Log::info('Creating transaksi with data:', $validated);
            
            // Set sumber name string based on sumber_id for backward compatibility
            if (isset($validated['sumber_id'])) {
                $sumber = Sumber::find($validated['sumber_id']);
                if ($sumber) {
                    // Store sumber name directly to allow flexible labels
                    $validated['sumber'] = $sumber->nama;
                }
            }

            $transaksi = Transaksi::create($validated);
            
            \Log::info('Transaksi created successfully with ID: ' . $transaksi->id);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Transaksi berhasil ditambahkan.']);
            }

            return redirect()->route('transaksis.index')
                ->with('success', 'Transaksi berhasil ditambahkan.');
        } catch (\Exception $e) {
            \Log::error('Error creating transaksi: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            \Log::error('Validated data: ', $validated);
            
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage()], 500);
            }
            
            return back()->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
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
        
        // Check if user can update this transaksi
        if ($user->isMarketing() && $transaksi->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk mengupdate data ini.');
        }

        $validator = Validator::make($request->all(), [
            'tanggal_tf' => 'required|date',
            'tanggal_lead_masuk' => 'required|date',
            'periode_lead' => 'required|in:Januari,Februari,Maret,April,Mei,Juni,Juli,Agustus,September,Oktober,November,Desember',
            'usia' => 'required|integer|min:17|max:80',
            'nama_mitra' => 'nullable|string|max:255',
            'pekerjaan_id' => 'nullable|exists:pekerjaans,id',
            'paket_brand_id' => 'required|exists:brands,id',
            'lead_awal_brand_id' => 'required|exists:brands,id',
            'sumber_id' => 'required|exists:sumbers,id',
            'kabupaten' => 'required|string|max:255',
            'provinsi' => 'required|string|max:255',
            'status_pembayaran' => 'required|in:Dp / TJ,Tambahan Dp,Pelunasan',
            'nominal_masuk' => 'required|numeric|min:0',
            'harga_paket' => 'required|numeric|min:0',
            'nama_paket' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()
                ->with('error', 'Terjadi kesalahan validasi. Silakan periksa kembali data yang dimasukkan.');
        }

        $validated = $validator->validated();

        // For marketing role, always keep current user ID
        if ($user->role === 'marketing') {
            $validated['user_id'] = $user->id;
        }

        try {
            // Set sumber string from sumber_id for backward compatibility
            if (isset($validated['sumber_id'])) {
                $sumber = Sumber::find($validated['sumber_id']);
                if ($sumber) {
                    // Store sumber name directly to allow flexible labels
                    $validated['sumber'] = $sumber->nama;
                }
            }

            $transaksi->update($validated);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Transaksi berhasil diperbarui.']);
            }

            return redirect()->route('transaksis.index')
                ->with('success', 'Transaksi berhasil diperbarui.');
        } catch (\Exception $e) {
            \Log::error('Error updating transaksi: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Terjadi kesalahan saat memperbarui data.'], 500);
            }
            
            return back()->withInput()
                ->with('error', 'Terjadi kesalahan saat memperbarui data. Silakan coba lagi.');
        }
    }

    /**
     * Map sumber nama from Sumbers table to enum-compatible value
     * Allowed enums: Unknown, IG, FB, WA, Tiktok, Web, Google, Organik, Teman
     */
    private function mapSumberEnum(string $nama): string
    {
        $n = mb_strtolower(trim($nama));

        // Tiktok mapping
        if (str_contains($n, 'tiktok')) {
            return 'Tiktok';
        }

        // Instagram mapping
        if (str_contains($n, 'instagram') || $n === 'ig' || str_contains($n, 'ig ')) {
            return 'IG';
        }

        // Facebook mapping
        if (str_contains($n, 'facebook') || $n === 'fb' || str_contains($n, 'fb ')) {
            return 'FB';
        }

        // WhatsApp mapping
        if (str_contains($n, 'whatsapp') || str_contains($n, 'wa')) {
            return 'WA';
        }

        // Web/Website mapping
        if (str_contains($n, 'web')) {
            return 'Web';
        }

        // Google mapping
        if (str_contains($n, 'google')) {
            return 'Google';
        }

        // Organik mapping
        if (str_contains($n, 'organik')) {
            return 'Organik';
        }

        // Teman mapping
        if (str_contains($n, 'teman')) {
            return 'Teman';
        }

        // Fallback
        return 'Unknown';
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaksi $transaksi)
    {
        $user = auth()->user();
        
        // Check if user can delete this transaksi
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
