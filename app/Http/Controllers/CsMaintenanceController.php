<?php

namespace App\Http\Controllers;

use App\Models\CsMaintenance;
use App\Models\Product;
use App\Models\Kendala;
use App\Models\Solusi;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class CsMaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = auth()->user();
        if (!Schema::hasTable('cs_maintenances')) {
            $items = new LengthAwarePaginator([], 0, 10, 1);
        } else {
            $query = CsMaintenance::with('product');
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nama_pelanggan', 'like', "%{$search}%")
                      ->orWhere('no_tlp', 'like', "%{$search}%")
                      ->orWhere('kota', 'like', "%{$search}%")
                      ->orWhere('provinsi', 'like', "%{$search}%")
                      ->orWhere('kendala', 'like', "%{$search}%")
                      ->orWhere('solusi', 'like', "%{$search}%")
                      ->orWhere('chat', 'like', "%{$search}%");
                });
            }
            if ($request->has('product_id') && $request->product_id) {
                $query->where('product_id', $request->product_id);
            }
            $items = $query->latest()->paginate(10)->withQueryString();
        }
        $products = Schema::hasTable('products')
            ? Product::select('id', 'nama')->orderBy('nama')->get()
            : collect([]);

        return Inertia::render('CS/Maintenance/Index', [
            'items' => $items,
            'products' => $products,
            'filters' => [
                'search' => $request->search,
                'product_id' => $request->product_id,
            ],
            'permissions' => [
                'canCrud' => $currentUser->canCrud(),
                'canOnlyView' => $currentUser->canOnlyView(),
                'canOnlyViewOwn' => $currentUser->canOnlyViewOwn(),
            ],
        ]);
    }

    public function create()
    {
        $products = Schema::hasTable('products')
            ? Product::select('id', 'nama')->orderBy('nama')->get()
            : collect([]);
        $kendalas = Schema::hasTable('kendalas')
            ? Kendala::select('id', 'nama', 'warna')->orderBy('nama')->get()
            : collect([]);
        $solusis = Schema::hasTable('solusis')
            ? Solusi::select('id', 'nama', 'warna')->orderBy('nama')->get()
            : collect([]);
        return Inertia::render('CS/Maintenance/Create', [
            'products' => $products,
            'kendalas' => $kendalas,
            'solusis' => $solusis,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_pelanggan' => 'required|string|max:255',
            'no_tlp' => 'required|string|max:50',
            'product_id' => 'nullable|exists:products,id',
            'tanggal' => 'required|date',
            'chat' => 'nullable|in:Baru,Follow Up,Follow Up 2,Followup 3',
            'kota' => 'nullable|string|max:255',
            'provinsi' => 'nullable|string|max:255',
            'kendala' => 'nullable|string',
            'solusi' => 'nullable|string',
        ]);

        CsMaintenance::create($validated);
        return redirect()->route('cs-maintenances.index')->with('success', 'CS Maintenance berhasil ditambahkan.');
    }

    public function edit(CsMaintenance $csMaintenance)
    {
        $products = Schema::hasTable('products')
            ? Product::select('id', 'nama')->orderBy('nama')->get()
            : collect([]);
        $kendalas = Schema::hasTable('kendalas')
            ? Kendala::select('id', 'nama', 'warna')->orderBy('nama')->get()
            : collect([]);
        $solusis = Schema::hasTable('solusis')
            ? Solusi::select('id', 'nama', 'warna')->orderBy('nama')->get()
            : collect([]);
        return Inertia::render('CS/Maintenance/Edit', [
            'item' => $csMaintenance->load('product'),
            'products' => $products,
            'kendalas' => $kendalas,
            'solusis' => $solusis,
        ]);
    }

    public function update(Request $request, CsMaintenance $csMaintenance)
    {
        $validated = $request->validate([
            'nama_pelanggan' => 'required|string|max:255',
            'no_tlp' => 'required|string|max:50',
            'product_id' => 'nullable|exists:products,id',
            'tanggal' => 'required|date',
            'chat' => 'nullable|in:Baru,Follow Up,Follow Up 2,Followup 3',
            'kota' => 'nullable|string|max:255',
            'provinsi' => 'nullable|string|max:255',
            'kendala' => 'nullable|string',
            'solusi' => 'nullable|string',
        ]);

        $csMaintenance->update($validated);
        return redirect()->route('cs-maintenances.index')->with('success', 'CS Maintenance berhasil diperbarui.');
    }

    public function destroy(CsMaintenance $csMaintenance)
    {
        $csMaintenance->delete();
        return redirect()->route('cs-maintenances.index')->with('success', 'CS Maintenance berhasil dihapus.');
    }

    /**
     * Analytics: jumlah maintenance per hari berdasarkan rentang tanggal.
     * Query params: start_date, end_date (default ke bulan berjalan bila kosong)
     * Response: { data: Array<{ date: string, count: number }> }
     */
    public function analyticsDailyCount(Request $request)
    {
        if (!Schema::hasTable('cs_maintenances')) {
            return response()->json(['data' => []]);
        }

        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());

        $baseQuery = CsMaintenance::query()
            ->whereBetween('tanggal', [$startDate, $endDate]);

        if ($request->filled('product_id')) {
            $baseQuery->where('product_id', $request->product_id);
        }

        $dailyCounts = $baseQuery->clone()
            ->selectRaw('DATE(tanggal) as d, COUNT(*) as cnt')
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

        $series = collect($dates)->map(function ($d) use ($dailyCounts) {
            $row = $dailyCounts->get($d);
            return [
                'date' => $d,
                'count' => $row ? (int) $row->cnt : 0,
            ];
        });

        return response()->json(['data' => $series]);
    }
}