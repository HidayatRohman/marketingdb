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
        if (!Schema::hasTable('cs_repeats')) {
            $items = new LengthAwarePaginator([], 0, 10, 1);
        } else {
            $query = CsRepeat::with('product');
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nama_pelanggan', 'like', "%{$search}%")
                      ->orWhere('no_tlp', 'like', "%{$search}%")
                      ->orWhere('kota', 'like', "%{$search}%")
                      ->orWhere('provinsi', 'like', "%{$search}%")
                      ->orWhere('chat', 'like', "%{$search}%")
                      ->orWhere('keterangan', 'like', "%{$search}%");
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

        return Inertia::render('CS/Repeat/Index', [
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
        return Inertia::render('CS/Repeat/Create', [
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
            'chat' => 'nullable|string',
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
            'product_id' => 'nullable|exists:products,id',
            'tanggal' => 'required|date',
            'chat' => 'nullable|string',
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