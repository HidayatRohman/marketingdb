<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SiteSettingController extends Controller
{
    /**
     * Display the site settings form.
     */
    public function edit()
    {
        $settings = [
            'site_title' => SiteSetting::get('site_title', 'Laravel Starter Kit'),
            'site_description' => SiteSetting::get('site_description', 'Marketing Database Management System'),
            'site_logo' => SiteSetting::where('key', 'site_logo')->first()?->value,
            'site_favicon' => SiteSetting::where('key', 'site_favicon')->first()?->value,
            'site_logo_url' => SiteSetting::get('site_logo'),
            'site_favicon_url' => SiteSetting::get('site_favicon'),
            'ppn_rate' => SiteSetting::get('ppn_rate', '11'),
        ];

        return Inertia::render('settings/SiteSettings', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update the site settings.
     */
    public function update(Request $request)
    {
        // Debug log untuk melihat data yang masuk
        \Log::info('Site Settings Update - Raw Request:', [
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'all_data' => $request->all(),
            'files' => $request->allFiles(),
            'input_site_title' => $request->input('site_title'),
            'has_site_title' => $request->has('site_title'),
            'filled_site_title' => $request->filled('site_title'),
        ]);

        // Custom validation dengan pesan error yang jelas
        $validator = \Validator::make($request->all(), [
            'site_title' => 'required|string|max:255',
            'site_description' => 'nullable|string|max:1000',
            'site_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'site_favicon' => 'nullable|image|mimes:ico,png|max:1024',
            'ppn_rate' => 'nullable|numeric|min:0|max:100',
        ], [
            'site_title.required' => 'Judul situs harus diisi.',
            'site_title.string' => 'Judul situs harus berupa teks.',
            'site_title.max' => 'Judul situs maksimal 255 karakter.',
            'site_logo.image' => 'Logo harus berupa file gambar.',
            'site_logo.mimes' => 'Logo harus berformat: jpeg, png, jpg, gif, atau svg.',
            'site_logo.max' => 'Logo maksimal 2MB.',
            'site_favicon.image' => 'Favicon harus berupa file gambar.',
            'site_favicon.mimes' => 'Favicon harus berformat: ico atau png.',
            'site_favicon.max' => 'Favicon maksimal 1MB.',
            'ppn_rate.numeric' => 'PPN harus berupa angka.',
            'ppn_rate.min' => 'PPN minimal 0%.',
            'ppn_rate.max' => 'PPN maksimal 100%.',
        ]);

        if ($validator->fails()) {
            \Log::error('Site Settings Validation Failed:', [
                'errors' => $validator->errors()->toArray(),
                'input' => $request->all()
            ]);
            
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Update site title
            SiteSetting::set(
                'site_title',
                $request->site_title,
                'text',
                'Title aplikasi yang ditampilkan di browser'
            );

            // Update site description
            SiteSetting::set(
                'site_description',
                $request->site_description ?? '',
                'textarea',
                'Deskripsi singkat tentang aplikasi'
            );

            // Update PPN rate (percentage 0-100)
            SiteSetting::set(
                'ppn_rate',
                $request->ppn_rate !== null ? (string) $request->ppn_rate : null,
                'text',
                'Persentase PPN (0–100) untuk perhitungan pajak'
            );

            // Handle logo upload
            if ($request->hasFile('site_logo')) {
                $oldLogo = SiteSetting::where('key', 'site_logo')->first();
                
                // Delete old logo file if exists
                if ($oldLogo && $oldLogo->value && Storage::disk('public')->exists($oldLogo->value)) {
                    Storage::disk('public')->delete($oldLogo->value);
                }

                $logoPath = $request->file('site_logo')->store('site-assets', 'public');
                SiteSetting::set(
                    'site_logo',
                    $logoPath,
                    'file',
                    'Logo utama aplikasi'
                );
            }

            // Handle favicon upload
            if ($request->hasFile('site_favicon')) {
                $oldFavicon = SiteSetting::where('key', 'site_favicon')->first();
                
                // Delete old favicon file if exists
                if ($oldFavicon && $oldFavicon->value && Storage::disk('public')->exists($oldFavicon->value)) {
                    Storage::disk('public')->delete($oldFavicon->value);
                }

                $faviconPath = $request->file('site_favicon')->store('site-assets', 'public');
                SiteSetting::set(
                    'site_favicon',
                    $faviconPath,
                    'file',
                    'Favicon aplikasi (format .ico, .png)'
                );
            }

            return back()->with('success', 'Pengaturan situs berhasil diperbarui!');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan saat memperbarui pengaturan: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete a file setting (logo or favicon).
     */
    public function deleteFile(Request $request)
    {
        $request->validate([
            'key' => 'required|in:site_logo,site_favicon',
        ]);

        try {
            $setting = SiteSetting::where('key', $request->key)->first();
            
            if ($setting && $setting->value && Storage::disk('public')->exists($setting->value)) {
                Storage::disk('public')->delete($setting->value);
            }

            // Reset the setting value to null
            SiteSetting::set($request->key, null, 'file');

            return back()->with('success', 'File berhasil dihapus!');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menghapus file: ' . $e->getMessage()]);
        }
    }
}
