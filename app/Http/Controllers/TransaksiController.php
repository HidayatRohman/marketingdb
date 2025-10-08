<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;

use App\Models\Brand;
use App\Models\Sumber;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TransaksiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Transaksi::with(['user', 'paketBrand', 'leadAwalBrand']);

        // Apply role-based filtering
        $query = $user->applyRoleFilter($query, 'user_id');

        // Apply search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_paket', 'like', "%{$search}%")
                  ->orWhere('kabupaten', 'like', "%{$search}%")
                  ->orWhere('provinsi', 'like', "%{$search}%")
                  ->orWhere('no_wa', 'like', "%{$search}%")
                  ->orWhere('nama_mitra', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply periode filter
        if ($request->has('periode_start') && $request->periode_start) {
            $query->whereDate('tanggal_tf', '>=', $request->periode_start);
        }

        if ($request->has('periode_end') && $request->periode_end) {
            $query->whereDate('tanggal_tf', '<=', $request->periode_end);
        }

        // Apply status pembayaran filter
        if ($request->has('status_pembayaran') && $request->status_pembayaran) {
            $query->where('status_pembayaran', $request->status_pembayaran);
        }

        $perPage = $request->get('per_page', 10);
        $transaksis = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Get data for filters
        $brands = Brand::select('id', 'nama')->get();
        $sumbers = Sumber::select('id', 'nama', 'warna')->get();

        return Inertia::render('Transaksi/Index', [
            'transaksis' => $transaksis,
            'brands' => $brands,
            'sumbers' => $sumbers,
            'currentUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
            'filters' => [
                'search' => $request->search,
                'status_pembayaran' => $request->status_pembayaran,
                'periode_start' => $request->periode_start,
                'periode_end' => $request->periode_end,
                'per_page' => $perPage,
            ],
            'permissions' => [
                'canCrud' => $user->canCrud(),
                'canOnlyView' => $user->canOnlyView(),
                'canOnlyViewOwn' => $user->canOnlyViewOwn(),
            ],
        ]);
        

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_mitra' => 'required|string|max:255',
            'tanggal_tf' => 'required|date',
            'tanggal_lead_masuk' => 'required|date',
            'periode_lead' => 'required|in:Januari,Februari,Maret,April,Mei,Juni,Juli,Agustus,September,Oktober,November,Desember',
            'usia' => 'required|integer|min:17|max:80',
            'paket_brand_id' => 'required|exists:brands,id',
            'lead_awal_brand_id' => 'required|exists:brands,id',
            'sumber_id' => 'nullable|exists:sumbers,id',
            'sumber' => 'required|in:Unknown,IG,FB,WA,Tiktok,Web,Google,Organik,Teman',
            'kabupaten' => 'required|string|max:255',
            'provinsi' => 'required|string|max:255',
            'status_pembayaran' => 'required|in:Dp / TJ,Tambahan Dp,Pelunasan',
            'nominal_masuk' => 'required|numeric|min:0',
            'harga_paket' => 'required|numeric|min:0',
            'nama_paket' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()
                ->with('error', 'Terjadi kesalahan validasi. Silakan periksa kembali data yang dimasukkan.');
        }

        $validated = $validator->validated();
        $user = auth()->user();
        
        // For marketing role, always use current user ID
        if ($user->role === 'marketing') {
            $validated['user_id'] = $user->id;
        } elseif (empty($validated['user_id'])) {
            $validated['user_id'] = $user->id;
        }

        try {
            Transaksi::create($validated);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Transaksi berhasil ditambahkan.']);
            }

            return redirect()->route('transaksis.index')
                ->with('success', 'Transaksi berhasil ditambahkan.');
        } catch (\Exception $e) {
            \Log::error('Error creating transaksi: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Terjadi kesalahan saat menyimpan data.'], 500);
            }
            
            return back()->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaksi $transaksi)
    {
        $user = auth()->user();
        
        // Check if user can access this transaksi
        if ($user->isMarketing() && $transaksi->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk melihat data ini.');
        }

        return Inertia::render('Transaksi/Show', [
            'transaksi' => $transaksi->load(['user', 'paketBrand', 'leadAwalBrand']),
            'permissions' => [
                'canCrud' => $user->canCrud(),
                'canOnlyView' => $user->canOnlyView(),
                'canOnlyViewOwn' => $user->canOnlyViewOwn(),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaksi $transaksi)
    {
        $user = auth()->user();
        
        // Check if user can update this transaksi
        if ($user->isMarketing() && $transaksi->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk mengupdate data ini.');
        }

        $validator = Validator::make($request->all(), [
            'nama_mitra' => 'required|string|max:255',
            'tanggal_tf' => 'required|date',
            'tanggal_lead_masuk' => 'required|date',
            'periode_lead' => 'required|in:Januari,Februari,Maret,April,Mei,Juni,Juli,Agustus,September,Oktober,November,Desember',
            'usia' => 'required|integer|min:17|max:80',
            'paket_brand_id' => 'required|exists:brands,id',
            'lead_awal_brand_id' => 'required|exists:brands,id',
            'sumber_id' => 'nullable|exists:sumbers,id',
            'sumber' => 'required|in:Unknown,IG,FB,WA,Tiktok,Web,Google,Organik,Teman',
            'kabupaten' => 'required|string|max:255',
            'provinsi' => 'required|string|max:255',
            'status_pembayaran' => 'required|in:Dp / TJ,Tambahan Dp,Pelunasan',
            'nominal_masuk' => 'required|numeric|min:0',
            'harga_paket' => 'required|numeric|min:0',
            'nama_paket' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()
                ->with('error', 'Terjadi kesalahan validasi. Silakan periksa kembali data yang dimasukkan.');
        }

        $validated = $validator->validated();

        // For marketing role, always keep current user ID
        if ($user->role === 'marketing') {
            $validated['user_id'] = $user->id;
        }

        try {
            $transaksi->update($validated);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Transaksi berhasil diperbarui.']);
            }

            return redirect()->route('transaksis.index')
                ->with('success', 'Transaksi berhasil diperbarui.');
        } catch (\Exception $e) {
            \Log::error('Error updating transaksi: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Terjadi kesalahan saat memperbarui data.'], 500);
            }
            
            return back()->withInput()
                ->with('error', 'Terjadi kesalahan saat memperbarui data. Silakan coba lagi.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaksi $transaksi)
    {
        $user = auth()->user();
        
        // Check if user can delete this transaksi
        if ($user->isMarketing() && $transaksi->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus data ini.');
        }

        $transaksi->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Transaksi berhasil dihapus.']);
        }

        return redirect()->route('transaksis.index')
            ->with('success', 'Transaksi berhasil dihapus.');
    }
}
