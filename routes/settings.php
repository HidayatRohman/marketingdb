<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SiteSettingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');

    // Site Settings - Only Super Admin can access
    Route::middleware('role.access:edit')->group(function () {
        Route::get('settings/site', [SiteSettingController::class, 'edit'])->name('site-settings.edit');
        Route::patch('settings/site', [SiteSettingController::class, 'update'])->name('site-settings.update');
        Route::delete('settings/site/file', [SiteSettingController::class, 'deleteFile'])->name('site-settings.delete-file');
    });
});
