<?php

namespace App\Http\Controllers;

use App\Models\Label;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LabelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $currentUser = auth()->user();
        $labels = Label::orderBy('nama')->get();
        
        return Inertia::render('Labels/Index', [
            'labels' => $labels,
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
        
        return Inertia::render('Labels/Create', [
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
            'nama' => 'required|string|max:255|unique:labels,nama',
            'warna' => 'required|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
        ]);

        Label::create($validated);

        return redirect()->route('labels.index')
            ->with('success', 'Label berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Label $label)
    {
        return Inertia::render('Labels/Show', [
            'label' => $label,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Label $label)
    {
        return Inertia::render('Labels/Edit', [
            'label' => $label,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Label $label)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:labels,nama,' . $label->id,
            'warna' => 'required|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
        ]);

        $label->update($validated);

        return redirect()->route('labels.index')
            ->with('success', 'Label berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Label $label)
    {
        // Check if label is being used by any mitra
        if ($label->mitras()->count() > 0) {
            return redirect()->route('labels.index')
                ->with('error', 'Label tidak dapat dihapus karena masih digunakan oleh mitra.');
        }

        $label->delete();

        return redirect()->route('labels.index')
            ->with('success', 'Label berhasil dihapus.');
    }
}
