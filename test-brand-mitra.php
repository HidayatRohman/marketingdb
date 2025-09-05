<?php

// Test script untuk menguji relasi Brand dan Mitra
require_once 'vendor/autoload.php';

// Load Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Brand;
use App\Models\Mitra;

echo "=== Testing Brand-Mitra Relationship ===\n\n";

// Test 1: Ambil semua brands
echo "1. Daftar semua brands:\n";
$brands = Brand::all();
foreach ($brands as $brand) {
    echo "   - ID: {$brand->id}, Nama: {$brand->nama}\n";
}

// Test 2: Buat mitra baru dengan brand_id
echo "\n2. Membuat mitra baru dengan brand_id:\n";
try {
    $mitra = Mitra::create([
        'nama' => 'Test Mitra',
        'no_telp' => '081234567890',
        'brand_id' => 1, // Gunakan brand pertama
        'chat' => 'masuk',
        'kota' => 'Jakarta',
        'provinsi' => 'DKI Jakarta',
        'transaksi' => 1000000.50,
        'komentar' => 'Test mitra dengan brand relation'
    ]);
    echo "   ✅ Mitra berhasil dibuat dengan ID: {$mitra->id}\n";
    
    // Test 3: Akses brand melalui relasi
    echo "\n3. Mengakses brand melalui relasi:\n";
    $brand = $mitra->brand;
    echo "   - Brand dari mitra: {$brand->nama}\n";
    
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 4: Akses mitras melalui brand
echo "\n4. Mengakses mitras melalui brand:\n";
$brand = Brand::find(1);
if ($brand) {
    $mitras = $brand->mitras;
    echo "   - Brand '{$brand->nama}' memiliki {$mitras->count()} mitra(s)\n";
    foreach ($mitras as $mitra) {
        echo "     * {$mitra->nama} ({$mitra->kota})\n";
    }
}

echo "\n=== Test selesai ===\n";
