<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Transaksi;
use App\Models\User;
use App\Models\Brand;
use App\Models\Sumber;

echo "=== Membuat Transaksi Cahyo ===\n";

// Get first user, brand, sumber, and mitra for testing
$user = User::first();
$brand = Brand::first();
$sumber = Sumber::first();
$mitra = \App\Models\Mitra::first();

if (!$user || !$brand || !$sumber || !$mitra) {
    echo "Error: Missing required data (user, brand, sumber, or mitra)\n";
    exit(1);
}

echo "User: {$user->name} (ID: {$user->id}, Role: {$user->role})\n";
echo "Brand: {$brand->nama} (ID: {$brand->id})\n";
echo "Sumber: {$sumber->nama} (ID: {$sumber->id})\n";
echo "Mitra: {$mitra->nama} (ID: {$mitra->id})\n\n";

echo "Sebelum membuat transaksi baru:\n";
echo "Total transaksi: " . Transaksi::count() . "\n\n";

// Create test transaction with nama_mitra "Cahyo"
$transaksi = Transaksi::create([
    'user_id' => $user->id,
    'mitra_id' => $mitra->id,
    'tanggal_tf' => date('Y-m-d'),
    'tanggal_lead_masuk' => date('Y-m-d'),
    'periode_lead' => 'Januari',
    'no_wa' => '081234567890',
    'usia' => 30,
    'nama_mitra' => 'Cahyo',
    'paket_brand_id' => $brand->id,
    'lead_awal_brand_id' => $brand->id,
    'sumber_id' => $sumber->id,
    'kabupaten' => 'Yogyakarta',
    'provinsi' => 'DI Yogyakarta',
    'status_pembayaran' => 'Dp / TJ',
    'nominal_masuk' => 2000000,
    'harga_paket' => 2500000,
    'nama_paket' => 'Paket Premium Cahyo'
]);

echo "Transaksi Cahyo berhasil dibuat:\n";
echo "ID: " . $transaksi->id . "\n";
echo "Nama Mitra: " . $transaksi->nama_mitra . "\n";
echo "Nama Paket: " . $transaksi->nama_paket . "\n";
echo "User ID: " . $transaksi->user_id . "\n";
echo "Created: " . $transaksi->created_at . "\n\n";

echo "Setelah membuat transaksi baru:\n";
echo "Total transaksi sekarang: " . Transaksi::count() . "\n\n";

// Verify the data is saved correctly
$savedTransaksi = Transaksi::find($transaksi->id);
echo "Verifikasi data tersimpan:\n";
echo "Nama Mitra dari database: " . ($savedTransaksi->nama_mitra ?? 'null') . "\n";
echo "User ID dari database: " . ($savedTransaksi->user_id ?? 'null') . "\n";

echo "\n=== Selesai ===\n";