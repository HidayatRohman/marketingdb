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
                  // Support searching by Nama Mitra
                  ->orWhere('nama_mitra', 'like', "%{$search}%")
                  // Support searching by Marketing (user name)
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

        // Total nominal over filtered dataset (not limited by pagination)
        $totalNominal = (clone $query)->sum('nominal_masuk');

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
            'totalNominal' => (float) $totalNominal,
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

        return response()->json([
            'data' => $data,
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
                END AS age_group,
                COUNT(*) as count,
                COALESCE(SUM(nominal_masuk), 0) as total_nominal
            ')
            ->groupBy('age_group')
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
