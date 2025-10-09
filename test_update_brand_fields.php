<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Transaksi;
use App\Models\Brand;
use App\Models\User;
use App\Http\Controllers\TransaksiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=== Test Update Brand Fields ===\n\n";

// Login as user ID 1
$user = User::find(1);
Auth::login($user);
echo "Logged in as: {$user->name}\n\n";

// Get existing transaction
$transaksi = Transaksi::latest()->first();
if (!$transaksi) {
    echo "No existing transaction found!\n";
    exit(1);
}

echo "Testing update for transaction ID: {$transaksi->id}\n";
echo "Current values:\n";
echo "- paket_brand_id: {$transaksi->paket_brand_id}\n";
echo "- lead_awal_brand_id: {$transaksi->lead_awal_brand_id}\n";
echo "- nama_paket: {$transaksi->nama_paket}\n";

// Get available brands
$brands = Brand::all();
echo "\nAvailable brands:\n";
foreach ($brands as $brand) {
    echo "- ID: {$brand->id}, Nama: {$brand->nama}\n";
}

// Choose different brands for update
$newPaketBrand = $brands->where('id', '!=', $transaksi->paket_brand_id)->first();
$newLeadAwalBrand = $brands->where('id', '!=', $transaksi->lead_awal_brand_id)->first();

if (!$newPaketBrand || !$newLeadAwalBrand) {
    echo "\nNot enough brands to test update!\n";
    exit(1);
}

echo "\nUpdating to:\n";
echo "- New paket_brand_id: {$newPaketBrand->id} ({$newPaketBrand->nama})\n";
echo "- New lead_awal_brand_id: {$newLeadAwalBrand->id} ({$newLeadAwalBrand->nama})\n";

// Prepare update data
$updateData = [
    'user_id' => $transaksi->user_id,
    'nama_marketing' => $transaksi->nama_marketing,
    'tanggal_tf' => $transaksi->tanggal_tf,
    'tanggal_lead_masuk' => $transaksi->tanggal_lead_masuk,
    'periode_lead' => $transaksi->periode_lead,
    'no_wa' => $transaksi->no_wa,
    'usia' => $transaksi->usia,
    'nama_mitra' => $transaksi->nama_mitra,
    'paket_brand_id' => $newPaketBrand->id,
    'lead_awal_brand_id' => $newLeadAwalBrand->id,
    'sumber' => $transaksi->sumber,
    'kabupaten' => $transaksi->kabupaten,
    'provinsi' => $transaksi->provinsi,
    'status_pembayaran' => $transaksi->status_pembayaran,
    'nominal_masuk' => $transaksi->nominal_masuk,
    'harga_paket' => $transaksi->harga_paket,
    'nama_paket' => 'Updated Paket Brand Test',
];

echo "\nSimulating controller update method...\n";

try {
    // Create request object
    $request = new Request($updateData);
    $request->setMethod('PUT');
    
    $controller = new TransaksiController();
    $response = $controller->update($request, $transaksi);
    
    echo "Response status: {$response->getStatusCode()}\n";
    
    if ($response->getStatusCode() === 302) {
        echo "Redirect response received (success)\n";
        
        // Verify the update
        $updatedTransaksi = Transaksi::find($transaksi->id);
        
        echo "\nVerifying updated data:\n";
        echo "- paket_brand_id: {$updatedTransaksi->paket_brand_id} (expected: {$newPaketBrand->id})\n";
        echo "- lead_awal_brand_id: {$updatedTransaksi->lead_awal_brand_id} (expected: {$newLeadAwalBrand->id})\n";
        echo "- nama_paket: {$updatedTransaksi->nama_paket}\n";
        
        // Test relationships
        echo "\nTesting relationships:\n";
        if ($updatedTransaksi->paketBrand) {
            echo "- Paket Brand: {$updatedTransaksi->paketBrand->nama}\n";
        } else {
            echo "- Paket Brand relation: NOT FOUND\n";
        }
        
        if ($updatedTransaksi->leadAwalBrand) {
            echo "- Lead Awal Brand: {$updatedTransaksi->leadAwalBrand->nama}\n";
        } else {
            echo "- Lead Awal Brand relation: NOT FOUND\n";
        }
        
        // Check if update was successful
        $updateSuccess = (
            $updatedTransaksi->paket_brand_id == $newPaketBrand->id &&
            $updatedTransaksi->lead_awal_brand_id == $newLeadAwalBrand->id
        );
        
        echo "\nUpdate result: " . ($updateSuccess ? 'SUCCESS' : 'FAILED') . "\n";
        
    } else {
        echo "Unexpected response status\n";
    }
    
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
    echo "File: {$e->getFile()}:{$e->getLine()}\n";
}

echo "\n=== Test Complete ===\n";