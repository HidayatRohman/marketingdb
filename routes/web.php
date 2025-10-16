<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\PekerjaanController;
use App\Http\Controllers\MitraController;
use App\Http\Controllers\SumberController;
use App\Http\Controllers\TaskManagementController;
use App\Http\Controllers\TodoListController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

    // Analisa Bisnis - All roles can view
    Route::get('analisa-bisnis', [DashboardController::class, 'businessAnalytics'])
        ->middleware('role.access:view')
        ->name('analytics.business');

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
    });
    
    Route::middleware('role.access:destroy')->group(function () {
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // Mitra Export/Import - MUST BE BEFORE dynamic routes to prevent conflicts
    // All users can export, download template, and import
    Route::middleware('role.access:view')->group(function () {
        Route::get('mitras/export', [MitraController::class, 'export'])->name('mitras.export');
        Route::get('mitras/template', [MitraController::class, 'downloadTemplate'])->name('mitras.template');
        Route::post('mitras/import', [MitraController::class, 'import'])->name('mitras.import');
    });
    
    Route::middleware('role.access:create')->group(function () {
        Route::get('mitras/create', [MitraController::class, 'create'])->name('mitras.create');
        Route::post('mitras', [MitraController::class, 'store'])->name('mitras.store');
    });

    // Mitras Management - Role-based access with data filtering
    Route::middleware('role.access:view')->group(function () {
        Route::get('mitras', [MitraController::class, 'index'])->name('mitras.index');
    });
    
    // Dynamic routes MUST be at the end to prevent conflicts
    Route::middleware('role.access:view')->group(function () {
        Route::get('mitras/{mitra}', [MitraController::class, 'show'])->name('mitras.show');
    });
    
    Route::middleware('role.access:edit')->group(function () {
        Route::get('mitras/{mitra}/edit', [MitraController::class, 'edit'])->name('mitras.edit');
        Route::put('mitras/{mitra}', [MitraController::class, 'update'])->name('mitras.update');
    });
    
    Route::middleware('role.access:destroy')->group(function () {
        Route::delete('mitras/{mitra}', [MitraController::class, 'destroy'])->name('mitras.destroy');
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
    });
    
    Route::middleware('role.access:destroy')->group(function () {
        Route::delete('labels/{label}', [LabelController::class, 'destroy'])->name('labels.destroy');
    });

    // Sumber Management - Role-based access
    Route::middleware('role.access:view')->group(function () {
        Route::get('sumbers', [SumberController::class, 'index'])->name('sumbers.index');
        Route::get('sumbers/{sumber}', [SumberController::class, 'show'])->name('sumbers.show');
    });
    
    Route::middleware('role.access:create')->group(function () {
        Route::get('sumbers/create', [SumberController::class, 'create'])->name('sumbers.create');
        Route::post('sumbers', [SumberController::class, 'store'])->name('sumbers.store');
    });
    
    Route::middleware('role.access:edit')->group(function () {
        Route::get('sumbers/{sumber}/edit', [SumberController::class, 'edit'])->name('sumbers.edit');
        Route::put('sumbers/{sumber}', [SumberController::class, 'update'])->name('sumbers.update');
    });
    
    Route::middleware('role.access:destroy')->group(function () {
        Route::delete('sumbers/{sumber}', [SumberController::class, 'destroy'])->name('sumbers.destroy');
    });

    // Pekerjaan Management - Role-based access
    Route::middleware('role.access:view')->group(function () {
        Route::get('pekerjaans', [PekerjaanController::class, 'index'])->name('pekerjaans.index');
        Route::get('pekerjaans/{pekerjaan}', [PekerjaanController::class, 'show'])->name('pekerjaans.show');
    });
    
    Route::middleware('role.access:create')->group(function () {
        Route::get('pekerjaans/create', [PekerjaanController::class, 'create'])->name('pekerjaans.create');
        Route::post('pekerjaans', [PekerjaanController::class, 'store'])->name('pekerjaans.store');
    });
    
    Route::middleware('role.access:edit')->group(function () {
        Route::get('pekerjaans/{pekerjaan}/edit', [PekerjaanController::class, 'edit'])->name('pekerjaans.edit');
        Route::put('pekerjaans/{pekerjaan}', [PekerjaanController::class, 'update'])->name('pekerjaans.update');
    });
    
    Route::middleware('role.access:destroy')->group(function () {
        Route::delete('pekerjaans/{pekerjaan}', [PekerjaanController::class, 'destroy'])->name('pekerjaans.destroy');
    });

    // Transaksi Management - Role-based access with data filtering
    Route::middleware('role.access:view')->group(function () {
        Route::get('transaksis', [TransaksiController::class, 'index'])->name('transaksis.index');
        Route::get('transaksis/analytics/payment-status', [TransaksiController::class, 'getPaymentStatusAnalytics'])->name('transaksis.analytics.payment-status');
        Route::get('transaksis/analytics/sumber', [TransaksiController::class, 'getSourceAnalytics'])->name('transaksis.analytics.sumber');
        Route::get('transaksis/analytics/pekerjaan', [TransaksiController::class, 'getPekerjaanAnalytics'])->name('transaksis.analytics.pekerjaan');
        Route::get('transaksis/analytics/usia', [TransaksiController::class, 'getAgeAnalytics'])->name('transaksis.analytics.usia');
        Route::get('transaksis/analytics/lead-awal', [TransaksiController::class, 'getLeadAwalAnalytics'])->name('transaksis.analytics.lead-awal');
        Route::get('transaksis/{transaksi}', [TransaksiController::class, 'show'])->name('transaksis.show');
    });
    
    Route::middleware('role.access:create')->group(function () {
        Route::post('transaksis', [TransaksiController::class, 'store'])->name('transaksis.store');
    });
    
    Route::middleware('role.access:edit')->group(function () {
        Route::put('transaksis/{transaksi}', [TransaksiController::class, 'update'])->name('transaksis.update');
    });
    
    Route::middleware('role.access:destroy')->group(function () {
        Route::delete('transaksis/{transaksi}', [TransaksiController::class, 'destroy'])->name('transaksis.destroy');
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

    // Iklan Budget Routes - All authenticated users can access
    Route::prefix('iklan-budgets')->name('iklan-budgets.')->group(function () {
        Route::middleware('role.access:view')->group(function () {
            Route::get('/', [\App\Http\Controllers\IklanBudgetController::class, 'index'])->name('index');
            Route::get('/analytics/monthly-spent', [\App\Http\Controllers\IklanBudgetController::class, 'monthlySpent'])->name('analytics.monthly-spent');
        });
        
        Route::middleware('role.access:create')->group(function () {
            Route::post('/', [\App\Http\Controllers\IklanBudgetController::class, 'store'])->name('store');
            Route::post('/generate-monthly', [\App\Http\Controllers\IklanBudgetController::class, 'generateMonthlyBudget'])->name('generate-monthly');
        });
        
        Route::middleware('role.access:edit')->group(function () {
            Route::put('/{iklanBudget}', [\App\Http\Controllers\IklanBudgetController::class, 'update'])->name('update');
        });
        
        Route::middleware('role.access:destroy')->group(function () {
            Route::delete('/{iklanBudget}', [\App\Http\Controllers\IklanBudgetController::class, 'destroy'])->name('destroy');
        });
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
