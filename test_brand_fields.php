<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Transaksi;
use App\Models\Brand;
use App\Models\User;

echo "=== Test Brand Fields Storage ===\n\n";

// Cek brands yang tersedia
echo "Available Brands:\n";
$brands = Brand::all();
foreach ($brands as $brand) {
    echo "- ID: {$brand->id}, Nama: {$brand->nama}\n";
}

if ($brands->count() == 0) {
    echo "No brands found! Creating test brands...\n";
    
    $brand1 = Brand::create([
        'nama' => 'Test Brand 1',
        'logo' => null
    ]);
    
    $brand2 = Brand::create([
        'nama' => 'Test Brand 2', 
        'logo' => null
    ]);
    
    echo "Created brands: {$brand1->id} and {$brand2->id}\n";
    $brands = Brand::all();
}

// Ambil brand pertama dan kedua untuk test
$paketBrand = $brands->first();
$leadAwalBrand = $brands->skip(1)->first() ?? $brands->first();

echo "\nUsing brands for test:\n";
echo "- Paket Brand: ID {$paketBrand->id} - {$paketBrand->nama}\n";
echo "- Lead Awal Brand: ID {$leadAwalBrand->id} - {$leadAwalBrand->nama}\n";

// Buat transaksi baru dengan brand fields
echo "\nCreating new transaction with brand fields...\n";

$transaksi = Transaksi::create([
    'user_id' => 1,
    'nama_marketing' => 'Test Marketing',
    'tanggal_tf' => '2025-01-10',
    'tanggal_lead_masuk' => '2025-01-10',
    'periode_lead' => 'Januari',
    'no_wa' => '081234567890',
    'usia' => 25,
    'nama_mitra' => 'Test Mitra',
    'paket_brand_id' => $paketBrand->id,
    'lead_awal_brand_id' => $leadAwalBrand->id,
    'sumber' => 'Web',
    'kabupaten' => 'Jakarta',
    'provinsi' => 'DKI Jakarta',
    'status_pembayaran' => 'Dp / TJ',
    'nominal_masuk' => 1000000,
    'harga_paket' => 1500000,
    'nama_paket' => 'Test Paket Brand',
    'mitra_id' => 1,
    'sumber_id' => 1
]);

echo "Transaction created with ID: {$transaksi->id}\n";

// Verifikasi data yang tersimpan
echo "\nVerifying saved data:\n";
$savedTransaksi = Transaksi::find($transaksi->id);

echo "- paket_brand_id: {$savedTransaksi->paket_brand_id}\n";
echo "- lead_awal_brand_id: {$savedTransaksi->lead_awal_brand_id}\n";
echo "- nama_paket: {$savedTransaksi->nama_paket}\n";

// Test relasi
echo "\nTesting relationships:\n";
if ($savedTransaksi->paketBrand) {
    echo "- Paket Brand relation: {$savedTransaksi->paketBrand->nama}\n";
} else {
    echo "- Paket Brand relation: NOT FOUND\n";
}

if ($savedTransaksi->leadAwalBrand) {
    echo "- Lead Awal Brand relation: {$savedTransaksi->leadAwalBrand->nama}\n";
} else {
    echo "- Lead Awal Brand relation: NOT FOUND\n";
}

echo "\n=== Test Complete ===\n";