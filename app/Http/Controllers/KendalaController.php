<?php

namespace App\Http\Controllers;

use App\Models\Kendala;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KendalaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $currentUser = auth()->user();
        $kendalas = Kendala::orderBy('nama')->get();

        return Inertia::render('Kendalas/Index', [
            'kendalas' => $kendalas,
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
            'nama' => 'required|string|max:255|unique:kendalas,nama',
            'warna' => [
                'required',
                'string',
                'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            ],
        ]);

        Kendala::create($validated);

        return redirect()->route('kendalas.index')
            ->with('success', 'Kendala berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Kendala $kendala)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:kendalas,nama,' . $kendala->id,
            'warna' => [
                'required',
                'string',
                'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            ],
        ]);

        $kendala->update($validated);

        return redirect()->route('kendalas.index')
            ->with('success', 'Kendala berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Kendala $kendala)
    {
        $kendala->delete();

        return redirect()->route('kendalas.index')
            ->with('success', 'Kendala berhasil dihapus.');
    }
}