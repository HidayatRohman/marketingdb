<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SiteSetting;

class TestSiteSettingsController extends Controller
{
    public function test(Request $request)
    {
        if ($request->isMethod('post')) {
            // Debug incoming request
            \Log::info('Test Site Settings - POST Request:', [
                'all_data' => $request->all(),
                'files' => $request->allFiles(),
                'content_type' => $request->header('Content-Type'),
                'method' => $request->method(),
                'is_form_data' => str_contains($request->header('Content-Type', ''), 'multipart/form-data')
            ]);

            // Simple validation
            $rules = [
                'site_title' => 'required|string|max:255',
                'site_description' => 'nullable|string|max:1000'
            ];

            try {
                $validated = $request->validate($rules);
                
                \Log::info('Validation passed:', $validated);
                
                // Update settings
                SiteSetting::set('site_title', $validated['site_title']);
                SiteSetting::set('site_description', $validated['site_description'] ?? '');
                
                return response()->json([
                    'success' => true,
                    'message' => 'Settings updated successfully',
                    'validated_data' => $validated
                ]);
                
            } catch (\Illuminate\Validation\ValidationException $e) {
                \Log::error('Validation failed:', [
                    'errors' => $e->errors(),
                    'input' => $request->all()
                ]);
                
                return response()->json([
                    'success' => false,
                    'errors' => $e->errors(),
                    'input' => $request->all()
                ], 422);
            }
        }

        return response()->json([
            'method' => $request->method(),
            'current_settings' => [
                'site_title' => SiteSetting::get('site_title'),
                'site_description' => SiteSetting::get('site_description')
            ]
        ]);
    }
}
