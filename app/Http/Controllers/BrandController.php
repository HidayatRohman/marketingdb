<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $currentUser = auth()->user();
        $query = Brand::query();

        // Apply search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('nama', 'like', "%{$search}%");
        }

        $brands = $query->latest()->paginate(10)->withQueryString();
        
        // Get province analytics by brand
        $provinceAnalytics = $this->getProvinceAnalytics($currentUser, $request->get('selected_brand'));
        
        return Inertia::render('Brand/Index', [
            'brands' => $brands,
            'provinceAnalytics' => $provinceAnalytics,
            'filters' => [
                'search' => $request->search,
                'selected_brand' => $request->get('selected_brand'),
            ],
            'permissions' => [
                'canCrud' => $currentUser->canCrud(),
                'canOnlyView' => $currentUser->canOnlyView(),
                'canOnlyViewOwn' => $currentUser->canOnlyViewOwn(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $currentUser = auth()->user();
        
        return Inertia::render('Brand/Create', [
            'permissions' => [
                'canCrud' => $currentUser->canCrud(),
                'canOnlyView' => $currentUser->canOnlyView(),
                'canOnlyViewOwn' => $currentUser->canOnlyViewOwn(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('brands', 'public');
        }

        Brand::create([
            'nama' => $validated['nama'],
            'logo' => $logoPath,
        ]);

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
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $logoPath = $brand->logo;
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($brand->logo && \Storage::disk('public')->exists($brand->logo)) {
                \Storage::disk('public')->delete($brand->logo);
            }
            $logoPath = $request->file('logo')->store('brands', 'public');
        }

        $brand->update([
            'nama' => $validated['nama'],
            'logo' => $logoPath,
        ]);

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
        // Delete logo file if exists
        if ($brand->logo && \Storage::disk('public')->exists($brand->logo)) {
            \Storage::disk('public')->delete($brand->logo);
        }
        
        $brand->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Brand berhasil dihapus.']);
        }

        return redirect()->route('brands.index')
            ->with('success', 'Brand berhasil dihapus.');
    }

    /**
     * Get province analytics by brand for chart visualization
     */
    private function getProvinceAnalytics($currentUser, $selectedBrandId = null)
    {
        $query = \App\Models\Mitra::query();
        
        // Apply role-based filtering
        if ($currentUser->hasLimitedAccess()) {
            $query->where('user_id', $currentUser->id);
        }
        
        // Filter by selected brand if provided
        if ($selectedBrandId) {
            $query->where('brand_id', $selectedBrandId);
        }
        
        // Get top 7 provinces by mitra count
        $provinceData = $query->select('provinsi', \DB::raw('COUNT(*) as total'))
            ->whereNotNull('provinsi')
            ->where('provinsi', '!=', '')
            ->where('provinsi', '!=', 'Unknown')
            ->groupBy('provinsi')
            ->orderByDesc('total')
            ->limit(7)
            ->get();
        
        return [
            'labels' => $provinceData->pluck('provinsi')->toArray(),
            'data' => $provinceData->pluck('total')->toArray(),
            'total' => $provinceData->sum('total'),
            'selected_brand' => $selectedBrandId ? \App\Models\Brand::find($selectedBrandId)?->nama : 'Semua Brand',
        ];
    }
}
