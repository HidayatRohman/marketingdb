<?php

namespace App\Http\Controllers;

use App\Models\Mitra;
use App\Models\Brand;
use App\Models\Label;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MitraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Mitra::with(['brand', 'label']);

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
                  });
            });
        }

        // Apply chat filter
        if ($request->has('chat') && $request->chat) {
            $query->where('chat', $request->chat);
        }

        // Apply label filter
        if ($request->has('label') && $request->label) {
            $query->where('label_id', $request->label);
        }

        $mitras = $query->latest()->paginate(10)->withQueryString();
        $brands = Brand::all();
        $labels = Label::all();
        
        return Inertia::render('Mitra/Index', [
            'mitras' => $mitras,
            'brands' => $brands,
            'labels' => $labels,
            'filters' => [
                'search' => $request->search,
                'chat' => $request->chat,
                'label' => $request->label,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Mitra/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'no_telp' => 'required|string|max:20',
            'brand_id' => 'required|exists:brands,id',
            'label_id' => 'nullable|exists:labels,id',
            'chat' => 'required|in:masuk,followup',
            'kota' => 'required|string|max:255',
            'provinsi' => 'required|string|max:255',
            'komentar' => 'nullable|string',
        ]);

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
        return Inertia::render('Mitra/Show', [
            'mitra' => $mitra,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Mitra $mitra)
    {
        return Inertia::render('Mitra/Edit', [
            'mitra' => $mitra,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Mitra $mitra)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'no_telp' => 'required|string|max:20',
            'brand_id' => 'required|exists:brands,id',
            'label_id' => 'nullable|exists:labels,id',
            'chat' => 'required|in:masuk,followup',
            'kota' => 'required|string|max:255',
            'provinsi' => 'required|string|max:255',
            'komentar' => 'nullable|string',
        ]);

        $mitra->update($validated);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Mitra berhasil diperbarui.']);
        }

        return redirect()->route('mitras.index')
            ->with('success', 'Mitra berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mitra $mitra)
    {
        $mitra->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Mitra berhasil dihapus.']);
        }

        return redirect()->route('mitras.index')
            ->with('success', 'Mitra berhasil dihapus.');
    }
}
