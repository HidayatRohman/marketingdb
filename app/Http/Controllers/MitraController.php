<?php

namespace App\Http\Controllers;

use App\Models\Mitra;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MitraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $mitras = Mitra::latest()->paginate(10);
        
        return Inertia::render('Mitra/Index', [
            'mitras' => $mitras,
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
            'produk' => 'required|string|max:255',
            'chat' => 'required|in:masuk,followup',
            'kota' => 'required|string|max:255',
            'provinsi' => 'required|string|max:255',
            'transaksi' => 'nullable|numeric|min:0',
            'komentar' => 'nullable|string',
        ]);

        Mitra::create($validated);

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
            'produk' => 'required|string|max:255',
            'chat' => 'required|in:masuk,followup',
            'kota' => 'required|string|max:255',
            'provinsi' => 'required|string|max:255',
            'transaksi' => 'nullable|numeric|min:0',
            'komentar' => 'nullable|string',
        ]);

        $mitra->update($validated);

        return redirect()->route('mitras.index')
            ->with('success', 'Mitra berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mitra $mitra)
    {
        $mitra->delete();

        return redirect()->route('mitras.index')
            ->with('success', 'Mitra berhasil dihapus.');
    }
}
