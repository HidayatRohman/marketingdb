<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Mitra;

echo "=== Data Mitra dengan Brand ===\n\n";

$mitras = Mitra::with('brand')->get();

echo "Total mitras: " . $mitras->count() . "\n\n";

foreach ($mitras as $mitra) {
    echo "Nama: {$mitra->nama}\n";
    echo "Brand: {$mitra->brand->nama}\n";
    echo "Kota: {$mitra->kota}\n";
    echo "Transaksi: Rp " . number_format($mitra->transaksi, 0, ',', '.') . "\n";
    echo "---\n";
}
