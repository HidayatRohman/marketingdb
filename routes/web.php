<?php

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

        return Inertia::render('Dashboard', [
            'userStats' => $userStats,
        ]);
    })->name('dashboard');

    Route::resource('users', UserController::class);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
