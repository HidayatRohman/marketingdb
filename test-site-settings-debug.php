<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\SiteSetting;

echo "🔧 Debugging Site Settings Page\n";
echo "===============================\n\n";

// Check if user with super_admin role exists
$superAdmin = User::where('role', 'super_admin')->first();
if ($superAdmin) {
    echo "✅ Super Admin user found: " . $superAdmin->name . " (ID: " . $superAdmin->id . ")\n";
} else {
    echo "❌ No Super Admin user found. Creating one...\n";
    
    // Create a super admin user for testing
    $superAdmin = User::create([
        'name' => 'Super Admin',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'role' => 'super_admin',
        'email_verified_at' => now(),
    ]);
    
    echo "✅ Created Super Admin: " . $superAdmin->email . " (password: password)\n";
}

// Check site settings
echo "\n📊 Current Site Settings:\n";
$title = SiteSetting::get('site_title', 'Default Title');
$description = SiteSetting::get('site_description', 'Default Description');
$logo = SiteSetting::get('site_logo');
$favicon = SiteSetting::get('site_favicon');

echo "   Title: " . $title . "\n";
echo "   Description: " . $description . "\n";
echo "   Logo URL: " . ($logo ?: 'No logo set') . "\n";
echo "   Favicon URL: " . ($favicon ?: 'No favicon set') . "\n";

// Check controller
echo "\n🎛️ Testing SiteSettingController:\n";
try {
    $controller = new \App\Http\Controllers\Settings\SiteSettingController();
    echo "✅ SiteSettingController can be instantiated\n";
} catch (Exception $e) {
    echo "❌ Error with SiteSettingController: " . $e->getMessage() . "\n";
}

// Check if storage directory exists
$storagePath = storage_path('app/public/site-assets');
if (!is_dir($storagePath)) {
    echo "\n📁 Creating storage directory: " . $storagePath . "\n";
    mkdir($storagePath, 0755, true);
    echo "✅ Storage directory created\n";
} else {
    echo "\n✅ Storage directory exists: " . $storagePath . "\n";
}

// Check public storage link
$publicStoragePath = public_path('storage');
if (is_link($publicStoragePath) || is_dir($publicStoragePath)) {
    echo "✅ Public storage link exists\n";
} else {
    echo "❌ Public storage link missing\n";
}

echo "\n🌐 Access Instructions:\n";
echo "1. Start development server: php artisan serve\n";
echo "2. Login with: " . $superAdmin->email . " / password\n";
echo "3. Go to: http://localhost:8000/settings/site\n";
echo "4. Upload logo and favicon files\n";

echo "\n🐛 If page still doesn't work, check:\n";
echo "   • Browser console for JavaScript errors\n";
echo "   • Laravel logs: storage/logs/laravel.log\n";
echo "   • Server response: check Network tab in browser dev tools\n";
echo "   • Make sure you're logged in as super_admin\n";

echo "\n✨ System Status: Ready for testing!\n";
