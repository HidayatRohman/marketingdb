<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Mitra;
use App\Models\Brand;
use App\Models\Label;

echo "=== Testing Dashboard Data ===\n\n";

// Test basic stats
echo "1. Testing basic statistics:\n";
$userCount = User::count();
$mitraCount = Mitra::count();
$brandCount = Brand::count();
$labelCount = Label::count();

echo "   - Users: {$userCount}\n";
echo "   - Mitras: {$mitraCount}\n";
echo "   - Brands: {$brandCount}\n";
echo "   - Labels: {$labelCount}\n";

// Test marketing users
echo "\n2. Testing marketing users:\n";
$marketingUsers = User::where('role', 'marketing')->get();
echo "   - Marketing users found: " . $marketingUsers->count() . "\n";

foreach ($marketingUsers as $user) {
    $leadsCount = $user->mitras()->count();
    echo "   - {$user->name}: {$leadsCount} leads\n";
}

// Test label distribution
echo "\n3. Testing label distribution:\n";
$labels = Label::withCount('mitras')->get();
foreach ($labels as $label) {
    echo "   - {$label->nama}: {$label->mitras_count} mitras\n";
}

// Test brand performance
echo "\n4. Testing brand performance:\n";
$brands = Brand::withCount('mitras')->get();
foreach ($brands as $brand) {
    echo "   - {$brand->nama}: {$brand->mitras_count} mitras\n";
}

echo "\n=== Test Complete ===\n";
