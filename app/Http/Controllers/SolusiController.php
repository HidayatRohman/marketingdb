<?php

namespace App\Http\Controllers;

use App\Models\Solusi;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SolusiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $currentUser = auth()->user();
        $solusis = Solusi::orderBy('nama')->get();

        return Inertia::render('Solusis/Index', [
            'solusis' => $solusis,
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
            'nama' => 'required|string|max:255|unique:solusis,nama',
            'warna' => [
                'required',
                'string',
                'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            ],
        ]);

        Solusi::create($validated);

        return redirect()->route('solusis.index')
            ->with('success', 'Solusi berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Solusi $solusi)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:solusis,nama,' . $solusi->id,
            'warna' => [
                'required',
                'string',
                'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            ],
        ]);

        $solusi->update($validated);

        return redirect()->route('solusis.index')
            ->with('success', 'Solusi berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Solusi $solusi)
    {
        $solusi->delete();

        return redirect()->route('solusis.index')
            ->with('success', 'Solusi berhasil dihapus.');
    }
}