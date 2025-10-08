<?php

namespace App\Http\Controllers;

use App\Models\Sumber;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SumberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $currentUser = auth()->user();
        $sumbers = Sumber::orderBy('nama')->get();
        
        return Inertia::render('Sumbers/Index', [
            'sumbers' => $sumbers,
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
        return Inertia::render('Sumbers/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:sumbers,nama',
            'warna' => [
                'required',
                'string',
                'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'
            ]
        ]);

        Sumber::create($validated);

        return redirect()->route('sumbers.index')
            ->with('success', 'Sumber berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Sumber $sumber)
    {
        return Inertia::render('Sumbers/Show', [
            'sumber' => $sumber,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Sumber $sumber)
    {
        return Inertia::render('Sumbers/Edit', [
            'sumber' => $sumber,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Sumber $sumber)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:sumbers,nama,' . $sumber->id,
            'warna' => [
                'required',
                'string',
                'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'
            ]
        ]);

        $sumber->update($validated);

        return redirect()->route('sumbers.index')
            ->with('success', 'Sumber berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sumber $sumber)
    {
        // Check if sumber is being used by any transaksi
        if ($sumber->transaksis()->count() > 0) {
            return redirect()->route('sumbers.index')
                ->with('error', 'Sumber tidak dapat dihapus karena masih digunakan oleh transaksi.');
        }

        $sumber->delete();

        return redirect()->route('sumbers.index')
            ->with('success', 'Sumber berhasil dihapus.');
    }
}
