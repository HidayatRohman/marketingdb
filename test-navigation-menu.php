<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Helpers\RoleHelper;

// Initialize Laravel app
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Navigation Menu Based on Roles...\n\n";

// Test Super Admin navigation
$superAdmin = User::where('role', 'super_admin')->first();
if ($superAdmin) {
    echo "=== SUPER ADMIN NAVIGATION ===\n";
    echo "User: " . $superAdmin->name . "\n";
    $navigation = RoleHelper::getNavigationItems($superAdmin);
    foreach ($navigation as $item) {
        echo "✅ " . $item['name'] . " (Visible)\n";
        if (isset($item['actions'])) {
            echo "   - Create: " . ($item['actions']['create'] ? 'Yes' : 'No') . "\n";
            echo "   - Edit: " . ($item['actions']['edit'] ? 'Yes' : 'No') . "\n";
            echo "   - Delete: " . ($item['actions']['delete'] ? 'Yes' : 'No') . "\n";
        }
    }
    echo "\n";
}

// Test Admin navigation
$admin = User::where('role', 'admin')->first();
if ($admin) {
    echo "=== ADMIN NAVIGATION ===\n";
    echo "User: " . $admin->name . "\n";
    $navigation = RoleHelper::getNavigationItems($admin);
    foreach ($navigation as $item) {
        echo "✅ " . $item['name'] . " (Visible)\n";
        if (isset($item['actions'])) {
            echo "   - Create: " . ($item['actions']['create'] ? 'Yes' : 'No') . "\n";
            echo "   - Edit: " . ($item['actions']['edit'] ? 'Yes' : 'No') . "\n";
            echo "   - Delete: " . ($item['actions']['delete'] ? 'Yes' : 'No') . "\n";
        }
    }
    echo "\n";
}

// Test Marketing navigation
$marketing = User::where('role', 'marketing')->first();
if ($marketing) {
    echo "=== MARKETING NAVIGATION ===\n";
    echo "User: " . $marketing->name . "\n";
    $navigation = RoleHelper::getNavigationItems($marketing);
    foreach ($navigation as $item) {
        echo "✅ " . $item['name'] . " (Visible)\n";
        if (isset($item['actions'])) {
            echo "   - Create: " . ($item['actions']['create'] ? 'Yes' : 'No') . "\n";
            echo "   - Edit: " . ($item['actions']['edit'] ? 'Yes' : 'No') . "\n";
            echo "   - Delete: " . ($item['actions']['delete'] ? 'Yes' : 'No') . "\n";
        }
    }
    echo "\n";
}

// Show what Marketing user CANNOT access
echo "=== HIDDEN MENUS FOR MARKETING ===\n";
$allMenus = ['Dashboard', 'Mitra', 'Users', 'Brands', 'Labels'];
$marketingMenus = array_column(RoleHelper::getNavigationItems($marketing), 'name');
$hiddenMenus = array_diff($allMenus, $marketingMenus);

foreach ($hiddenMenus as $hiddenMenu) {
    echo "❌ " . $hiddenMenu . " (Hidden)\n";
}

echo "\n✅ Navigation menu test completed!\n";
