<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;

// Initialize Laravel app
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Role-Based Access Control...\n\n";

// Test Super Admin
$superAdmin = User::where('role', 'super_admin')->first();
if ($superAdmin) {
    echo "Super Admin User: " . $superAdmin->name . "\n";
    echo "- Can CRUD: " . ($superAdmin->canCrud() ? 'Yes' : 'No') . "\n";
    echo "- Can only view: " . ($superAdmin->canOnlyView() ? 'Yes' : 'No') . "\n";
    echo "- Can only view own: " . ($superAdmin->canOnlyViewOwn() ? 'Yes' : 'No') . "\n";
    echo "- Has full access: " . ($superAdmin->hasFullAccess() ? 'Yes' : 'No') . "\n\n";
}

// Test Admin
$admin = User::where('role', 'admin')->first();
if ($admin) {
    echo "Admin User: " . $admin->name . "\n";
    echo "- Can CRUD: " . ($admin->canCrud() ? 'Yes' : 'No') . "\n";
    echo "- Can only view: " . ($admin->canOnlyView() ? 'Yes' : 'No') . "\n";
    echo "- Can only view own: " . ($admin->canOnlyViewOwn() ? 'Yes' : 'No') . "\n";
    echo "- Has read-only access: " . ($admin->hasReadOnlyAccess() ? 'Yes' : 'No') . "\n\n";
}

// Test Marketing
$marketing = User::where('role', 'marketing')->first();
if ($marketing) {
    echo "Marketing User: " . $marketing->name . "\n";
    echo "- Can CRUD: " . ($marketing->canCrud() ? 'Yes' : 'No') . "\n";
    echo "- Can only view: " . ($marketing->canOnlyView() ? 'Yes' : 'No') . "\n";
    echo "- Can only view own: " . ($marketing->canOnlyViewOwn() ? 'Yes' : 'No') . "\n";
    echo "- Has limited access: " . ($marketing->hasLimitedAccess() ? 'Yes' : 'No') . "\n\n";
}

echo "Role-based access control test completed!\n";
