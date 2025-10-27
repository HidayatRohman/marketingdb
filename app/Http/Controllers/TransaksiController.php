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
}
