<?php

namespace App\Http\Controllers;

use App\Models\Mitra;
use App\Models\Brand;
use App\Models\Label;
use App\Http\Requests\StoreMitraRequest;
use App\Http\Requests\UpdateMitraRequest;
use App\Services\MitraExportService;
use App\Services\MitraImportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MitraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Mitra::with(['brand', 'label', 'user']);

        // Apply role-based filtering
        $query = $user->applyRoleFilter($query, 'user_id');

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
                  })
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply periode filter
        if ($request->has('periode_start') && $request->periode_start) {
            $query->whereDate('tanggal_lead', '>=', $request->periode_start);
        }

        if ($request->has('periode_end') && $request->periode_end) {
            $query->whereDate('tanggal_lead', '<=', $request->periode_end);
        }

        // Apply chat filter
        if ($request->has('chat') && $request->chat) {
            $query->where('chat', $request->chat);
        }

        // Apply label filter
        if ($request->has('label') && $request->label) {
            $query->where('label_id', $request->label);
        }

        // Apply user (marketing) filter - only for Super Admin and Admin
        if ($request->has('user') && $request->user && $user->hasFullAccess()) {
            $query->where('user_id', $request->user);
        }

        // Default ordering by tanggal_lead (newest first), then by created_at
        $query->orderBy('tanggal_lead', 'desc')->orderBy('created_at', 'desc');

        // Get per_page parameter, default to 30
        $perPage = $request->get('per_page', 30);
        $perPage = in_array($perPage, [10, 20, 30, 50, 100]) ? $perPage : 30;

        $mitras = $query->paginate($perPage)->withQueryString();
        $brands = Brand::all();
        $labels = Label::all();
        
        // Get marketing users (users with role marketing) 
        $marketingUsers = collect();
        if ($user->hasFullAccess() || $user->hasReadOnlyAccess()) {
            $marketingUsers = \App\Models\User::where('role', 'marketing')->get(['id', 'name']);
        } elseif ($user->isMarketing()) {
            // For marketing users, only show themselves in the list
            $marketingUsers = collect([['id' => $user->id, 'name' => $user->name]]);
        }
        
        // Get hourly analysis data
        $hourlyAnalysis = $this->getHourlyAnalysis($request, $user);
        
        return Inertia::render('Mitra/Index', [
            'mitras' => $mitras,
            'brands' => $brands,
            'labels' => $labels,
            'users' => $marketingUsers,
            'hourlyAnalysis' => $hourlyAnalysis,
            'currentUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
            'filters' => [
                'search' => $request->search,
                'chat' => $request->chat,
                'label' => $request->label,
                'user' => $request->user,
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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();
        
        return Inertia::render('Mitra/Create', [
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
    public function store(StoreMitraRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();
        
        // For marketing role, always use current user ID
        if ($user->role === 'marketing') {
            $validated['user_id'] = $user->id;
        } elseif (empty($validated['user_id'])) {
            // For admin/super_admin, use provided user_id or current user if empty
            $validated['user_id'] = $user->id;
        }

        // Set default values for kota and provinsi if empty
        if (empty($validated['kota'])) {
            $validated['kota'] = 'Unknown';
        }
        if (empty($validated['provinsi'])) {
            $validated['provinsi'] = 'Unknown';
        }

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
        $user = auth()->user();
        
        // Check if user can access this mitra
        if ($user->isMarketing() && $mitra->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk melihat data ini.');
        }

        return Inertia::render('Mitra/Show', [
            'mitra' => $mitra->load(['brand', 'label', 'user']),
            'permissions' => [
                'canCrud' => $user->canCrud(),
                'canOnlyView' => $user->canOnlyView(),
                'canOnlyViewOwn' => $user->canOnlyViewOwn(),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Mitra $mitra)
    {
        $user = auth()->user();
        
        // Check if user can edit this mitra
        if ($user->isMarketing() && $mitra->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit data ini.');
        }

        return Inertia::render('Mitra/Edit', [
            'mitra' => $mitra->load(['brand', 'label']),
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
    public function update(UpdateMitraRequest $request, Mitra $mitra)
    {
        $user = auth()->user();
        
        // Check if user can update this mitra
        if ($user->isMarketing() && $mitra->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk mengupdate data ini.');
        }

        $validated = $request->validated();

        // For marketing role, always keep current user ID (don't allow change)
        if ($user->role === 'marketing') {
            $validated['user_id'] = $user->id;
        }

        // Set default values for kota and provinsi if empty
        if (empty($validated['kota'])) {
            $validated['kota'] = 'Unknown';
        }
        if (empty($validated['provinsi'])) {
            $validated['provinsi'] = 'Unknown';
        }

        $mitra->update($validated);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Mitra berhasil diperbarui.']);
        }

        return redirect()->route('mitras.index')
            ->with('success', 'Mitra berhasil diperbarui.');
    }

    /**
     * Export mitras data in XLSX format using service
     */
    public function export(Request $request)
    {
        try {
            // Temporarily test without service injection
            $exportService = app(\App\Services\MitraExportService::class);
            $result = $exportService->export($request);
            
            if (!$result['success']) {
                return response()->json([
                    'error' => 'Export gagal: ' . ($result['message'] ?? 'Unknown error')
                ], 500);
            }
            
            return response()->streamDownload(function() use ($result) {
                $result['writer']->save('php://output');
            }, $result['filename'], [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Export error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'error' => 'Export gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download import template using service
     */
    public function downloadTemplate(Request $request)
    {
        try {
            $importService = app(\App\Services\MitraImportService::class);
            $result = $importService->generateTemplate();
            
            if (!$result['success']) {
                return response()->json([
                    'error' => 'Template generation gagal: ' . ($result['message'] ?? 'Unknown error')
                ], 500);
            }
            
            return new StreamedResponse(function() use ($result) {
                $result['writer']->save('php://output');
            }, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Download template gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import mitras data from XLSX file using service
     */
    public function import(Request $request)
    {
        // Force JSON response for API calls
        $request->headers->set('Accept', 'application/json');
        
        try {
            $request->validate([
                'file' => 'required|mimes:xlsx,xls|max:10240' // Max 10MB
            ], [
                'file.required' => 'File import wajib dipilih.',
                'file.mimes' => 'File harus berformat XLSX atau XLS.',
                'file.max' => 'Ukuran file maksimal 10MB.'
            ]);

            $file = $request->file('file');
            
            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'File import tidak ditemukan.',
                    'imported' => 0,
                    'skipped' => 0,
                    'errors' => ['File import tidak ditemukan.'],
                    'warnings' => [],
                    'total_processed' => 0
                ], 400);
            }
            
            $importService = app(\App\Services\MitraImportService::class);
            $result = $importService->import($file);
            return response()->json($result);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . implode(', ', $e->validator->errors()->all()),
                'imported' => 0,
                'skipped' => 0,
                'errors' => $e->validator->errors()->all(),
                'warnings' => [],
                'total_processed' => 0
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Import error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $request->hasFile('file') ? $request->file('file')->getClientOriginalName() : 'No file'
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Import gagal: ' . $e->getMessage(),
                'imported' => 0,
                'skipped' => 0,
                'errors' => [$e->getMessage()],
                'warnings' => [],
                'total_processed' => 0
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mitra $mitra)
    {
        $user = auth()->user();
        
        // Check if user can delete this mitra
        if ($user->isMarketing() && $mitra->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus data ini.');
        }

        $mitra->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Mitra berhasil dihapus.']);
        }

        return redirect()->route('mitras.index')
            ->with('success', 'Mitra berhasil dihapus.');
    }

    /**
     * Get hourly lead analysis data grouped by brands
     */
    private function getHourlyAnalysis(Request $request, $user)
    {
        $query = Mitra::with('brand');

        // Apply role-based filtering
        $query = $user->applyRoleFilter($query, 'user_id');

        // Apply the same filters as main index
        if ($request->has('periode_start') && $request->periode_start) {
            $query->whereDate('tanggal_lead', '>=', $request->periode_start);
        }

        if ($request->has('periode_end') && $request->periode_end) {
            $query->whereDate('tanggal_lead', '<=', $request->periode_end);
        }

        if ($request->has('chat') && $request->chat) {
            $query->where('chat', $request->chat);
        }

        if ($request->has('label') && $request->label) {
            $query->where('label_id', $request->label);
        }

        if ($request->has('user') && $request->user && $user->hasFullAccess()) {
            $query->where('user_id', $request->user);
        }

        // Get database driver to use appropriate hour extraction function
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver");
        
        if ($connection === 'sqlite') {
            // SQLite uses strftime function
            $hourExpression = "strftime('%H', mitras.created_at)";
        } else {
            // MySQL/PostgreSQL use HOUR function
            $hourExpression = "HOUR(mitras.created_at)";
        }

        // Get data with hour extraction from mitras.created_at
        $results = $query->selectRaw("
                {$hourExpression} as hour,
                brands.nama as brand_name,
                COUNT(*) as lead_count
            ")
            ->join('brands', 'mitras.brand_id', '=', 'brands.id')
            ->groupBy('hour', 'brands.nama')
            ->orderBy('hour')
            ->get();

        // Initialize 24-hour structure
        $hourlyData = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $hourlyData[$hour] = [
                'hour' => $hour,
                'brands' => [],
                'total' => 0
            ];
        }

        // Get all brands to ensure consistent structure
        $brands = Brand::pluck('nama')->toArray();
        foreach ($brands as $brandName) {
            for ($hour = 0; $hour < 24; $hour++) {
                $hourlyData[$hour]['brands'][$brandName] = 0;
            }
        }

        // Fill with actual data
        foreach ($results as $result) {
            $hour = (int) $result->hour; // Convert string to integer for array key
            $brandName = $result->brand_name;
            $count = $result->lead_count;

            $hourlyData[$hour]['brands'][$brandName] = $count;
            $hourlyData[$hour]['total'] += $count;
        }

        return array_values($hourlyData);
    }

    /**
     * API endpoint to get hourly analysis data (for refresh functionality)
     */
    public function getHourlyAnalysisData(Request $request)
    {
        $user = auth()->user();
        $data = $this->getHourlyAnalysis($request, $user);
        
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
