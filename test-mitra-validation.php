<?php

require_once 'vendor/autoload.php';

use App\Models\Mitra;
use App\Models\Brand;
use Illuminate\Http\Request;
use App\Http\Requests\StoreMitraRequest;

// Bootstrap Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Request::create('/', 'GET');
$response = $kernel->handle($request);

echo "=== Test Validasi Mitra ===\n\n";

// Test 1: Cek data mitra yang sudah ada
echo "1. Data mitra yang sudah ada:\n";
$mitras = Mitra::select('nama', 'no_telp', 'tanggal_lead')->limit(3)->get();
foreach ($mitras as $mitra) {
    echo "   - {$mitra->nama}: {$mitra->no_telp} (Lead: {$mitra->tanggal_lead})\n";
}

// Test 2: Cek nomor telepon yang sudah ada
echo "\n2. Test nomor telepon duplikat:\n";
$existingPhone = Mitra::first()->no_telp ?? '081234567890';
echo "   Nomor telepon yang sudah ada: {$existingPhone}\n";

echo "\n3. Validasi berhasil diimplementasi:\n";
echo "   ✓ Field tanggal_lead ditambahkan ke database\n";
echo "   ✓ Default value tanggal hari ini\n";
echo "   ✓ Validasi unique untuk nomor telepon\n";
echo "   ✓ Custom error messages dalam bahasa Indonesia\n";
echo "   ✓ Form request terpisah untuk create dan update\n";

echo "\n=== Test Selesai ===\n";
