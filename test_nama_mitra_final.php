<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaksi;
use App\Models\User;
use App\Models\Brand;
use App\Models\Sumber;

// Get first user, brand, and sumber for testing
$user = User::first();
$brand = Brand::first();
$sumber = Sumber::first();

if (!$user || !$brand || !$sumber) {
    echo "Error: Missing required data (user, brand, or sumber)\n";
    exit(1);
}

echo "Sebelum membuat transaksi baru:\n";
echo "Total transaksi: " . Transaksi::count() . "\n\n";

// Create test transaction with nama_mitra
$transaksi = Transaksi::create([
    'user_id' => $user->id,
    'nama_marketing' => $user->name,
    'tanggal_tf' => date('Y-m-d'),
    'tanggal_lead_masuk' => date('Y-m-d'),
    'periode_lead' => '2025-01',
    'no_wa' => '081234567890',
    'usia' => 25,
    'nama_mitra' => 'Test Mitra Partner Baru',
    'paket_brand_id' => $brand->id,
    'lead_awal_brand_id' => $brand->id,
    'sumber_id' => $sumber->id,
    'sumber' => $sumber->nama,
    'kabupaten' => 'Jakarta Selatan',
    'provinsi' => 'DKI Jakarta',
    'status_pembayaran' => 'Lunas',
    'nominal_masuk' => 1000000,
    'harga_paket' => 1500000,
    'nama_paket' => 'Test Package Baru'
]);

echo "Transaksi test berhasil dibuat:\n";
echo "ID: " . $transaksi->id . "\n";
echo "Nama Mitra: " . $transaksi->nama_mitra . "\n";
echo "Nama Paket: " . $transaksi->nama_paket . "\n";
echo "\nSetelah membuat transaksi baru:\n";
echo "Total transaksi sekarang: " . Transaksi::count() . "\n";

// Verify the data is saved correctly
$savedTransaksi = Transaksi::find($transaksi->id);
echo "\nVerifikasi data tersimpan:\n";
echo "Nama Mitra dari database: " . ($savedTransaksi->nama_mitra ?? 'null') . "\n";