<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\MitraController;
use App\Http\Controllers\TaskManagementController;
use App\Http\Controllers\TodoListController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TestSiteSettingsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Test route for export debugging
Route::get('/test-export-csv', function() {
    $data = [
        ['ID', 'Nama', 'No. Telepon', 'Tanggal Lead'],
        [1, 'Test User', '081234567890', '2024-01-15'],
        [2, 'User Kedua', '087654321098', '2024-01-16']
    ];
    
    $csv = fopen('php://temp', 'w+');
    foreach ($data as $row) {
        fputcsv($csv, $row);
    }
    rewind($csv);
    $output = stream_get_contents($csv);
    fclose($csv);
    
    return response($output, 200, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="test-export.csv"',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma' => 'no-cache',
        'Expires' => '0'
    ]);
});

// Test PhpSpreadsheet export
Route::get('/test-export-phpspreadsheet', function() {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    $sheet->setCellValue('A1', 'ID');
    $sheet->setCellValue('B1', 'Nama');
    $sheet->setCellValue('C1', 'No. Telepon');
    
    $sheet->setCellValue('A2', '1');
    $sheet->setCellValue('B2', 'Test User');
    $sheet->setCellValue('C2', '081234567890');
    
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
    $writer->setDelimiter(',');
    $writer->setEnclosure('"');
    
    return new \Symfony\Component\HttpFoundation\StreamedResponse(function() use ($writer) {
        $writer->save('php://output');
    }, 200, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="test-phpspreadsheet.csv"',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma' => 'no-cache',
        'Expires' => '0'
    ]);
});

// Debug export route (without middleware)
Route::get('/debug-export', function() {
    return response()->json([
        'status' => 'success',
        'message' => 'Export endpoint is reachable',
        'user' => auth()->check() ? auth()->user()->name : 'Not authenticated',
        'timestamp' => now()
    ]);
});

// Debug mitra export (without middleware for testing)
Route::get('/debug-mitra-export', [\App\Http\Controllers\MitraController::class, 'export']);

// Direct test for authenticated export
Route::get('/test-auth-export', function() {
    $user = auth()->user();
    if (!$user) {
        return response()->json([
            'error' => 'Not authenticated',
            'message' => 'Please login first'
        ]);
    }
    
    return response()->json([
        'authenticated' => true,
        'user' => $user->only(['id', 'name', 'email', 'role']),
        'message' => 'User is authenticated, export should work'
    ]);
});

// Test route for debugging
Route::match(['GET', 'POST'], '/test-site-settings', [TestSiteSettingsController::class, 'test'])->withoutMiddleware(['web']);

// Simple HTML test
Route::get('/test-site-settings-simple', function() {
    $settings = [
        'site_title' => \App\Models\SiteSetting::get('site_title', 'Default Title'),
        'site_description' => \App\Models\SiteSetting::get('site_description', 'Default Description'),
        'site_logo' => \App\Models\SiteSetting::get('site_logo'),
        'site_favicon' => \App\Models\SiteSetting::get('site_favicon'),
    ];
    return view('test-site-settings', compact('settings'));
});

Route::post('/test-site-settings-simple', function(\Illuminate\Http\Request $request) {
    $request->validate([
        'site_title' => 'required|string|max:255',
        'site_description' => 'nullable|string|max:1000',
        'site_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        'site_favicon' => 'nullable|image|mimes:ico,png|max:1024',
    ]);
    
    \App\Models\SiteSetting::set('site_title', $request->site_title);
    \App\Models\SiteSetting::set('site_description', $request->site_description ?? '');
    
    return back()->with('success', 'Settings updated successfully!');
});

Route::get('/', function () {
    $brands = \App\Models\Brand::all(); // Get all brands with all attributes including logo_url accessor
    return Inertia::render('Welcome', [
        'brands' => $brands
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard - All roles can access
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->middleware('role.access:view')
        ->name('dashboard');

    // Users Management - Only Super Admin can CRUD, others can view
    Route::middleware('role.access:view')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
    });
    
    Route::middleware('role.access:create')->group(function () {
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
    });
    
    Route::middleware('role.access:edit')->group(function () {
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');
    });
    
    Route::middleware('role.access:destroy')->group(function () {
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // Mitras Management - Role-based access with data filtering
    Route::middleware('role.access:view')->group(function () {
        Route::get('mitras', [MitraController::class, 'index'])->name('mitras.index');
        Route::get('mitras/{mitra}', [MitraController::class, 'show'])->name('mitras.show');
    });
    
    Route::middleware('role.access:create')->group(function () {
        Route::get('mitras/create', [MitraController::class, 'create'])->name('mitras.create');
        Route::post('mitras', [MitraController::class, 'store'])->name('mitras.store');
    });
    
    Route::middleware('role.access:edit')->group(function () {
        Route::get('mitras/{mitra}/edit', [MitraController::class, 'edit'])->name('mitras.edit');
        Route::put('mitras/{mitra}', [MitraController::class, 'update'])->name('mitras.update');
        Route::patch('mitras/{mitra}', [MitraController::class, 'update'])->name('mitras.update');
    });
    
    Route::middleware('role.access:destroy')->group(function () {
        Route::delete('mitras/{mitra}', [MitraController::class, 'destroy'])->name('mitras.destroy');
    });

    // Mitra Export/Import - Role-based access
    Route::middleware('role.access:view')->group(function () {
        Route::get('mitras/export', [MitraController::class, 'export'])->name('mitras.export');
        Route::get('mitras/template', [MitraController::class, 'downloadTemplate'])->name('mitras.template');
        Route::get('mitras/guide', [MitraController::class, 'downloadGuide'])->name('mitras.guide');
    });
    
    Route::middleware('role.access:create')->group(function () {
        Route::post('mitras/import', [MitraController::class, 'import'])->name('mitras.import');
    });

    // Brands Management - Only Super Admin can CRUD, others can view
    Route::middleware('role.access:view')->group(function () {
        Route::get('brands', [BrandController::class, 'index'])->name('brands.index');
        Route::get('brands/{brand}', [BrandController::class, 'show'])->name('brands.show');
    });
    
    Route::middleware('role.access:create')->group(function () {
        Route::get('brands/create', [BrandController::class, 'create'])->name('brands.create');
        Route::post('brands', [BrandController::class, 'store'])->name('brands.store');
    });
    
    Route::middleware('role.access:edit')->group(function () {
        Route::get('brands/{brand}/edit', [BrandController::class, 'edit'])->name('brands.edit');
        Route::put('brands/{brand}', [BrandController::class, 'update'])->name('brands.update');
        Route::patch('brands/{brand}', [BrandController::class, 'update'])->name('brands.update');
    });
    
    Route::middleware('role.access:destroy')->group(function () {
        Route::delete('brands/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');
    });

    // Labels Management - Only Super Admin can CRUD, others can view
    Route::middleware('role.access:view')->group(function () {
        Route::get('labels', [LabelController::class, 'index'])->name('labels.index');
        Route::get('labels/{label}', [LabelController::class, 'show'])->name('labels.show');
    });
    
    Route::middleware('role.access:create')->group(function () {
        Route::get('labels/create', [LabelController::class, 'create'])->name('labels.create');
        Route::post('labels', [LabelController::class, 'store'])->name('labels.store');
    });
    
    Route::middleware('role.access:edit')->group(function () {
        Route::get('labels/{label}/edit', [LabelController::class, 'edit'])->name('labels.edit');
        Route::put('labels/{label}', [LabelController::class, 'update'])->name('labels.update');
        Route::patch('labels/{label}', [LabelController::class, 'update'])->name('labels.update');
    });
    
    Route::middleware('role.access:destroy')->group(function () {
        Route::delete('labels/{label}', [LabelController::class, 'destroy'])->name('labels.destroy');
    });

    // Todo Lists - All authenticated users can access
    Route::prefix('todos')->name('todos.')->group(function () {
        Route::get('/', [TodoListController::class, 'index'])->name('index');
        Route::post('/', [TodoListController::class, 'store'])->name('store');
        Route::put('/{todoList}', [TodoListController::class, 'update'])->name('update');
        Route::delete('/{todoList}', [TodoListController::class, 'destroy'])->name('destroy');
        Route::patch('/{todoList}/status', [TodoListController::class, 'updateStatus'])->name('updateStatus');
        Route::get('/calendar', [TodoListController::class, 'calendar'])->name('calendar');
    });

    // Task Management - All authenticated users can access
    Route::prefix('task-management')->name('task-management.')->group(function () {
        Route::get('/', [TaskManagementController::class, 'index'])->name('index');
        Route::post('/', [TaskManagementController::class, 'store'])->name('store');
        Route::put('/{todoList}', [TaskManagementController::class, 'update'])->name('update');
        Route::delete('/{todoList}', [TaskManagementController::class, 'destroy'])->name('destroy');
        Route::patch('/{todoList}/status', [TaskManagementController::class, 'updateStatus'])->name('updateStatus');
        Route::get('/tasks', [TaskManagementController::class, 'getTasks'])->name('getTasks');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
