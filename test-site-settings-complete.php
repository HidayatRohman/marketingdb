<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SiteSetting;
use Illuminate\Support\Facades\DB;

echo "Checking Site Settings Data:\n";
echo "============================\n";

try {
    // Check if table exists and has data
    $count = DB::table('site_settings')->count();
    echo "Total settings in database: " . $count . "\n\n";
    
    if ($count === 0) {
        echo "No settings found. Inserting default data...\n";
        
        // Insert default data
        DB::table('site_settings')->insert([
            [
                'key' => 'site_title',
                'value' => 'Laravel Starter Kit',
                'type' => 'text',
                'description' => 'Title aplikasi yang ditampilkan di browser',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'site_description',
                'value' => 'Marketing Database Management System',
                'type' => 'textarea',
                'description' => 'Deskripsi singkat tentang aplikasi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'site_logo',
                'value' => null,
                'type' => 'file',
                'description' => 'Logo utama aplikasi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'site_favicon',
                'value' => null,
                'type' => 'file',
                'description' => 'Favicon aplikasi (format .ico, .png)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        
        echo "✅ Default settings inserted!\n\n";
    }
    
    // Display all settings
    $settings = DB::table('site_settings')->get();
    foreach ($settings as $setting) {
        echo "Key: " . $setting->key . "\n";
        echo "Value: " . ($setting->value ?: '(null)') . "\n";
        echo "Type: " . $setting->type . "\n";
        echo "Description: " . $setting->description . "\n";
        echo "---\n";
    }
    
    echo "\nTesting SiteSetting model methods:\n";
    $siteTitle = SiteSetting::get('site_title', 'Default Title');
    echo "Site Title: " . $siteTitle . "\n";
    
    $siteDescription = SiteSetting::get('site_description', 'Default Description');
    echo "Site Description: " . $siteDescription . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
