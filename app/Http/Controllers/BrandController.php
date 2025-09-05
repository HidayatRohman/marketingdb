<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Brand::query();

        // Apply search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('nama', 'like', "%{$search}%");
        }

        $brands = $query->latest()->paginate(10)->withQueryString();
        
        return Inertia::render('Brand/Index', [
            'brands' => $brands,
            'filters' => [
                'search' => $request->search,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Brand/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'logo' => 'nullable|string|max:255',
        ]);

        Brand::create($validated);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Brand berhasil ditambahkan.']);
        }

        return redirect()->route('brands.index')
            ->with('success', 'Brand berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Brand $brand)
    {
        return Inertia::render('Brand/Show', [
            'brand' => $brand,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Brand $brand)
    {
        return Inertia::render('Brand/Edit', [
            'brand' => $brand,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'logo' => 'nullable|string|max:255',
        ]);

        $brand->update($validated);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Brand berhasil diperbarui.']);
        }

        return redirect()->route('brands.index')
            ->with('success', 'Brand berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Brand $brand)
    {
        $brand->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Brand berhasil dihapus.']);
        }

        return redirect()->route('brands.index')
            ->with('success', 'Brand berhasil dihapus.');
    }
}
