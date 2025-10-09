<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Brand;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

echo "=== Debug Frontend Brands Data ===\n\n";

// Login as user ID 1
$user = User::find(1);
Auth::login($user);

// Get brands data exactly like controller does
$brands = Brand::select('id', 'nama')->get();

echo "Brands data that should be available in frontend:\n";
foreach ($brands as $brand) {
    echo "- ID: {$brand->id}, Nama: {$brand->nama}\n";
}

echo "\nTotal brands: " . $brands->count() . "\n";

echo "\nBrands as JSON (like what frontend receives):\n";
echo json_encode($brands->toArray(), JSON_PRETTY_PRINT) . "\n";

echo "\n=== Debug Complete ===\n";