<?php

namespace App\Http\Controllers;

use App\Models\CsMaintenance;
use App\Models\Product;
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
        return Inertia::render('CS/Maintenance/Create', [
            'products' => $products,
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
        return Inertia::render('CS/Maintenance/Edit', [
            'item' => $csMaintenance->load('product'),
            'products' => $products,
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
}