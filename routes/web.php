<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\MitraController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        $userStats = [
            'total' => \App\Models\User::count(),
            'super_admin' => \App\Models\User::where('role', 'super_admin')->count(),
            'admin' => \App\Models\User::where('role', 'admin')->count(),
            'marketing' => \App\Models\User::where('role', 'marketing')->count(),
        ];

        $mitraStats = [
            'total' => \App\Models\Mitra::count(),
            'masuk' => \App\Models\Mitra::where('chat', 'masuk')->count(),
            'followup' => \App\Models\Mitra::where('chat', 'followup')->count(),
        ];

        $brandStats = [
            'total' => \App\Models\Brand::count(),
        ];

        return Inertia::render('Dashboard', [
            'userStats' => $userStats,
            'mitraStats' => $mitraStats,
            'brandStats' => $brandStats,
        ]);
    })->name('dashboard');

    Route::resource('users', UserController::class);
    Route::resource('mitras', MitraController::class);
    Route::resource('brands', BrandController::class);
    Route::resource('labels', LabelController::class);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
