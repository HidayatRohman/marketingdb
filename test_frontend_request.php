<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\TransaksiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

echo "=== Test Frontend Request Simulation ===\n\n";

// Login as user ID 1
$user = User::find(1);
Auth::login($user);
echo "Logged in as: {$user->name}\n\n";

// Simulate frontend request data
$requestData = [
    'user_id' => 1,
    'nama_marketing' => 'Test Marketing Frontend',
    'tanggal_tf' => '2025-01-10',
    'tanggal_lead_masuk' => '2025-01-10',
    'periode_lead' => 'Januari',
    'no_wa' => '081234567891',
    'usia' => 30,
    'nama_mitra' => 'Test Mitra Frontend',
    'paket_brand_id' => 1,
    'lead_awal_brand_id' => 2,
    'sumber' => 'Web',
    'kabupaten' => 'Bandung',
    'provinsi' => 'Jawa Barat',
    'status_pembayaran' => 'Dp / TJ',
    'nominal_masuk' => 2000000,
    'harga_paket' => 2500000,
    'nama_paket' => 'Test Paket Frontend',
];

echo "Request data:\n";
foreach ($requestData as $key => $value) {
    echo "- {$key}: {$value}\n";
}

// Create request object
$request = new Request($requestData);
$request->setMethod('POST');

echo "\nSimulating controller store method...\n";

try {
    $controller = new TransaksiController();
    $response = $controller->store($request);
    
    echo "Response status: {$response->getStatusCode()}\n";
    
    // Get the created transaction ID from response
    if ($response->getStatusCode() === 302) {
        echo "Redirect response received (success)\n";
        
        // Find the latest transaction
        $latestTransaction = \App\Models\Transaksi::latest()->first();
        
        echo "\nLatest transaction details:\n";
        echo "- ID: {$latestTransaction->id}\n";
        echo "- paket_brand_id: {$latestTransaction->paket_brand_id}\n";
        echo "- lead_awal_brand_id: {$latestTransaction->lead_awal_brand_id}\n";
        echo "- nama_paket: {$latestTransaction->nama_paket}\n";
        echo "- nama_marketing: {$latestTransaction->nama_marketing}\n";
        
        if ($latestTransaction->paketBrand) {
            echo "- Paket Brand: {$latestTransaction->paketBrand->nama}\n";
        }
        
        if ($latestTransaction->leadAwalBrand) {
            echo "- Lead Awal Brand: {$latestTransaction->leadAwalBrand->nama}\n";
        }
    }
    
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
    echo "File: {$e->getFile()}:{$e->getLine()}\n";
}

echo "\n=== Test Complete ===\n";