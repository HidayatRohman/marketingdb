<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SiteSetting;
use Illuminate\Support\Facades\DB;

echo "Testing Site Settings Debug:\n";
echo "============================\n\n";

// Check if site_settings table exists
try {
    // Use MySQL syntax instead of SQLite
    $exists = DB::select("SHOW TABLES LIKE 'site_settings'");
    if (empty($exists)) {
        echo "❌ site_settings table does not exist!\n";
        echo "Run: php artisan migrate\n\n";
        exit;
    } else {
        echo "✅ site_settings table exists\n\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking table: " . $e->getMessage() . "\n\n";
    exit;
}

// Check current settings
echo "Current Settings:\n";
echo "-----------------\n";
try {
    $settings = DB::table('site_settings')->get();
    
    if ($settings->isEmpty()) {
        echo "❌ No settings found in database!\n";
        echo "Run: php artisan migrate:fresh --seed\n\n";
    } else {
        foreach ($settings as $setting) {
            echo "Key: {$setting->key}\n";
            echo "Value: " . ($setting->value ?: 'NULL') . "\n";
            echo "Type: {$setting->type}\n";
            echo "---\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error reading settings: " . $e->getMessage() . "\n";
}

echo "\nTesting SiteSetting Model:\n";
echo "--------------------------\n";

// Test getting site title
try {
    $siteTitle = SiteSetting::get('site_title', 'Default Title');
    echo "✅ Site Title: " . $siteTitle . "\n";
} catch (Exception $e) {
    echo "❌ Error getting site title: " . $e->getMessage() . "\n";
}

// Test setting site title
try {
    SiteSetting::set('site_title', 'Test Title from PHP', 'text', 'Test description');
    echo "✅ Site title set successfully\n";
} catch (Exception $e) {
    echo "❌ Error setting site title: " . $e->getMessage() . "\n";
}

// Test getting updated site title
try {
    $updatedTitle = SiteSetting::get('site_title');
    echo "✅ Updated Site Title: " . $updatedTitle . "\n";
} catch (Exception $e) {
    echo "❌ Error getting updated site title: " . $e->getMessage() . "\n";
}

echo "\nTesting File Storage:\n";
echo "---------------------\n";

// Check if storage directory exists
$storageDir = storage_path('app/public/site-assets');
if (!is_dir($storageDir)) {
    echo "❌ Storage directory does not exist: {$storageDir}\n";
    echo "Creating directory...\n";
    mkdir($storageDir, 0755, true);
    echo "✅ Storage directory created\n";
} else {
    echo "✅ Storage directory exists: {$storageDir}\n";
}

// Check storage link
$publicStorageLink = public_path('storage');
if (!is_link($publicStorageLink) && !is_dir($publicStorageLink)) {
    echo "❌ Storage link does not exist\n";
    echo "Run: php artisan storage:link\n";
} else {
    echo "✅ Storage link exists\n";
}

echo "\nDone!\n";
