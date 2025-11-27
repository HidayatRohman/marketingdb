<?php

namespace App\Http\Controllers;

use App\Models\CsRepeat;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class CsRepeatController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = auth()->user();
        // Determine period range (default: month of latest data if available, otherwise current month)
        $defaultStart = now()->startOfMonth()->toDateString();
        $defaultEnd = now()->endOfMonth()->toDateString();
        if (\Illuminate\Support\Facades\Schema::hasTable('cs_repeats')) {
            $latestDate = CsRepeat::max('tanggal');
            if ($latestDate) {
                $defaultStart = \Carbon\Carbon::parse($latestDate)->startOfMonth()->toDateString();
                $defaultEnd = \Carbon\Carbon::parse($latestDate)->endOfMonth()->toDateString();
            }
        }
        $periodeStart = $request->filled('periode_start') ? $request->input('periode_start') : $defaultStart;
        $periodeEnd = $request->filled('periode_end') ? $request->input('periode_end') : $defaultEnd;

        if (!Schema::hasTable('cs_repeats')) {
            $items = new LengthAwarePaginator([], 0, 10, 1);
            $charts = [
                'dailyTransaksi' => [],
                'dailyByProduct' => [],
            ];
            $summary = [
                'totalOmset' => 0,
                'jumlahTransaksi' => 0,
            ];
        } else {
            $query = CsRepeat::with('product');
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nama_pelanggan', 'like', "%{$search}%")
                      ->orWhere('no_tlp', 'like', "%{$search}%")
                      ->orWhere('bio_pelanggan', 'like', "%{$search}%")
                      ->orWhere('kota', 'like', "%{$search}%")
                      ->orWhere('provinsi', 'like', "%{$search}%")
                      ->orWhere('chat', 'like', "%{$search}%")
                      ->orWhere('keterangan', 'like', "%{$search}%");
                });
            }
            if ($request->has('product_id') && $request->product_id) {
                $query->where('product_id', $request->product_id);
            }
            // Apply period filter to list items
            $query->whereBetween('tanggal', [$periodeStart, $periodeEnd]);
            $items = $query->latest()->paginate(10)->withQueryString();

            // Build charts data for selected period
            $startDate = $periodeStart;
            $endDate = $periodeEnd;

            $filterQuery = CsRepeat::query();
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $filterQuery->where(function ($q) use ($search) {
                    $q->where('nama_pelanggan', 'like', "%{$search}%")
                      ->orWhere('no_tlp', 'like', "%{$search}%")
                      ->orWhere('bio_pelanggan', 'like', "%{$search}%")
                      ->orWhere('kota', 'like', "%{$search}%")
                      ->orWhere('provinsi', 'like', "%{$search}%")
                      ->orWhere('chat', 'like', "%{$search}%")
                      ->orWhere('keterangan', 'like', "%{$search}%");
                });
            }
            if ($request->has('product_id') && $request->product_id) {
                $filterQuery->where('product_id', $request->product_id);
            }
            $filterQuery->whereBetween('tanggal', [$startDate, $endDate]);

            // Summary totals for current filters
            $summary = [
                'totalOmset' => (int) ($filterQuery->clone()->sum('transaksi') ?? 0),
                'jumlahTransaksi' => (int) ($filterQuery->clone()->count() ?? 0),
            ];

            // Daily transaksi totals
            $dailyTotals = $filterQuery->clone()
                ->selectRaw('DATE(tanggal) as d, COALESCE(SUM(transaksi),0) as total')
                ->groupBy('d')
                ->orderBy('d')
                ->get()
                ->keyBy('d');

            // Generate dates array between start and end inclusive
            $dates = [];
            $cursor = 
                \Carbon\Carbon::parse($startDate)->startOfDay();
            $endCursor = \Carbon\Carbon::parse($endDate)->startOfDay();
            while ($cursor->lte($endCursor)) {
                $dates[] = $cursor->toDateString();
                $cursor->addDay();
            }
            $dailyTransaksi = collect($dates)->map(function ($d) use ($dailyTotals) {
                $row = $dailyTotals->get($d);
                return [
                    'date' => $d,
                    'total' => $row ? (int) $row->total : 0,
                ];
            });

            // Daily totals by product name
            $dailyByProductRaw = $filterQuery->clone()
                ->leftJoin('products', 'products.id', '=', 'cs_repeats.product_id')
                ->selectRaw('DATE(tanggal) as d, COALESCE(products.nama, "Tanpa Produk") as product, COALESCE(SUM(transaksi),0) as total')
                ->groupBy('d', 'product')
                ->orderBy('d')
                ->get();

            $grouped = $dailyByProductRaw->groupBy('d');
            $dailyByProduct = collect($dates)->map(function ($d) use ($grouped) {
                $rows = $grouped->get($d, collect());
                $products = [];
                $total = 0;
                foreach ($rows as $row) {
                    $products[$row->product] = (int) $row->total;
                    $total += (int) $row->total;
                }
                return [
                    'date' => $d,
                    'products' => $products,
                    'total' => $total,
                ];
            });

            $charts = [
                'dailyTransaksi' => $dailyTransaksi,
                'dailyByProduct' => $dailyByProduct,
            ];

            $allItems = $filterQuery->clone()->select('nama_pelanggan', 'no_tlp', 'bio_pelanggan', 'tanggal', 'transaksi')->get();
        }
        $products = Schema::hasTable('products')
            ? Product::select('id', 'nama')->orderBy('nama')->get()
            : collect([]);

        return Inertia::render('CS/Repeat/Index', [
            'items' => $items,
            'products' => $products,
            'allItems' => $allItems ?? collect([]),
            'filters' => [
                'search' => $request->search,
                'product_id' => $request->product_id,
                'periode_start' => $periodeStart,
                'periode_end' => $periodeEnd,
            ],
            'charts' => $charts,
            'summary' => $summary,
            'permissions' => [
                'canCrud' => $currentUser->canCrud(),
                'canOnlyView' => $currentUser->canOnlyView(),
                'canOnlyViewOwn' => $currentUser->canOnlyViewOwn(),
            ],
        ]);
    }

    /**
     * Analytics: Summary (total omset dan jumlah transaksi) berdasarkan rentang tanggal.
     * Query params: start_date, end_date (default ke bulan berjalan bila kosong)
     */
    public function analyticsSummary(Request $request)
    {
        if (!Schema::hasTable('cs_repeats')) {
            return response()->json([
                'data' => [
                    'totalOmset' => 0,
                    'jumlahTransaksi' => 0,
                ],
            ]);
        }

        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());

        $query = CsRepeat::query()->whereBetween('tanggal', [$startDate, $endDate]);

        // Optional: filter by product_id jika diberikan (Dashboard saat ini tidak memakainya)
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        $totalOmset = (int) ($query->clone()->sum('transaksi') ?? 0);
        $jumlahTransaksi = (int) ($query->clone()->count() ?? 0);

        return response()->json([
            'data' => [
                'totalOmset' => $totalOmset,
                'jumlahTransaksi' => $jumlahTransaksi,
            ],
        ]);
    }

    /**
     * Analytics: Transaksi harian (tanggal -> total transaksi) berdasarkan rentang tanggal.
     */
    public function analyticsDailyTransaksi(Request $request)
    {
        if (!Schema::hasTable('cs_repeats')) {
            return response()->json(['data' => []]);
        }

        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());

        $baseQuery = CsRepeat::query()->whereBetween('tanggal', [$startDate, $endDate]);
        if ($request->filled('product_id')) {
            $baseQuery->where('product_id', $request->product_id);
        }

        $dailyTotals = $baseQuery->clone()
            ->selectRaw('DATE(tanggal) as d, COALESCE(SUM(transaksi),0) as total')
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->keyBy('d');

        // Generate inclusive date range
        $dates = [];
        $cursor = \Carbon\Carbon::parse($startDate)->startOfDay();
        $endCursor = \Carbon\Carbon::parse($endDate)->startOfDay();
        while ($cursor->lte($endCursor)) {
            $dates[] = $cursor->toDateString();
            $cursor->addDay();
        }

        $series = collect($dates)->map(function ($d) use ($dailyTotals) {
            $row = $dailyTotals->get($d);
            return [
                'date' => $d,
                'total' => $row ? (int) $row->total : 0,
            ];
        });

        return response()->json(['data' => $series]);
    }

    /**
     * Analytics: Transaksi harian per produk (tanggal -> {products: map, total}) berdasarkan rentang tanggal.
     */
    public function analyticsDailyByProduct(Request $request)
    {
        if (!Schema::hasTable('cs_repeats')) {
            return response()->json(['data' => []]);
        }

        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());

        $baseQuery = CsRepeat::query()->whereBetween('tanggal', [$startDate, $endDate]);
        if ($request->filled('product_id')) {
            $baseQuery->where('product_id', $request->product_id);
        }

        $raw = $baseQuery->clone()
            ->leftJoin('products', 'products.id', '=', 'cs_repeats.product_id')
            ->selectRaw('DATE(tanggal) as d, COALESCE(products.nama, "Tanpa Produk") as product, COALESCE(SUM(transaksi),0) as total')
            ->groupBy('d', 'product')
            ->orderBy('d')
            ->get();

        $grouped = $raw->groupBy('d');

        // Generate inclusive date range
        $dates = [];
        $cursor = \Carbon\Carbon::parse($startDate)->startOfDay();
        $endCursor = \Carbon\Carbon::parse($endDate)->startOfDay();
        while ($cursor->lte($endCursor)) {
            $dates[] = $cursor->toDateString();
            $cursor->addDay();
        }

        $series = collect($dates)->map(function ($d) use ($grouped) {
            $rows = $grouped->get($d, collect());
            $products = [];
            $total = 0;
            foreach ($rows as $row) {
                $products[$row->product] = (int) $row->total;
                $total += (int) $row->total;
            }
            return [
                'date' => $d,
                'products' => $products,
                'total' => $total,
            ];
        });

        return response()->json(['data' => $series]);
    }

    public function create()
    {
        $products = Schema::hasTable('products')
            ? Product::select('id', 'nama')->orderBy('nama')->get()
            : collect([]);
        return Inertia::render('CS/Repeat/Create', [
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_pelanggan' => 'required|string|max:255',
            'no_tlp' => 'required|string|max:50',
            'bio_pelanggan' => 'nullable|string',
            'product_id' => 'nullable|exists:products,id',
            'tanggal' => 'required|date',
            'chat' => 'nullable|in:Baru,Follow Up,Follow Up 2,Followup 3',
            'kota' => 'nullable|string|max:255',
            'provinsi' => 'nullable|string|max:255',
            'transaksi' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
        ]);

        CsRepeat::create($validated);
        return redirect()->route('cs-repeats.index')->with('success', 'CS Repeat berhasil ditambahkan.');
    }

    public function edit(CsRepeat $csRepeat)
    {
        $products = Schema::hasTable('products')
            ? Product::select('id', 'nama')->orderBy('nama')->get()
            : collect([]);
        return Inertia::render('CS/Repeat/Edit', [
            'item' => $csRepeat->load('product'),
            'products' => $products,
        ]);
    }

    public function update(Request $request, CsRepeat $csRepeat)
    {
        $validated = $request->validate([
            'nama_pelanggan' => 'required|string|max:255',
            'no_tlp' => 'required|string|max:50',
            'bio_pelanggan' => 'nullable|string',
            'product_id' => 'nullable|exists:products,id',
            'tanggal' => 'required|date',
            'chat' => 'nullable|in:Baru,Follow Up,Follow Up 2,Followup 3',
            'kota' => 'nullable|string|max:255',
            'provinsi' => 'nullable|string|max:255',
            'transaksi' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
        ]);

        $csRepeat->update($validated);
        return redirect()->route('cs-repeats.index')->with('success', 'CS Repeat berhasil diperbarui.');
    }

    public function destroy(CsRepeat $csRepeat)
    {
        $csRepeat->delete();
        return redirect()->route('cs-repeats.index')->with('success', 'CS Repeat berhasil dihapus.');
    }
}
