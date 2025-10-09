<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Transaksi;

echo "=== Detail Transaksi Cahyo ===\n";

$cahyo = Transaksi::where('nama_mitra', 'Cahyo')->first();

if ($cahyo) {
    echo "Transaksi Cahyo ditemukan:\n";
    echo "ID: {$cahyo->id}\n";
    echo "tanggal_tf: " . ($cahyo->tanggal_tf ?? 'NULL') . "\n";
    echo "tanggal_lead_masuk: " . ($cahyo->tanggal_lead_masuk ?? 'NULL') . "\n";
    echo "periode_lead: " . ($cahyo->periode_lead ?? 'NULL') . "\n";
    echo "status_pembayaran: " . ($cahyo->status_pembayaran ?? 'NULL') . "\n";
    echo "user_id: " . ($cahyo->user_id ?? 'NULL') . "\n";
    echo "mitra_id: " . ($cahyo->mitra_id ?? 'NULL') . "\n";
    echo "sumber_id: " . ($cahyo->sumber_id ?? 'NULL') . "\n";
    echo "sumber: " . ($cahyo->sumber ?? 'NULL') . "\n";
    echo "created_at: {$cahyo->created_at}\n";
    echo "updated_at: {$cahyo->updated_at}\n";
    
    // Check raw attributes
    echo "\nRaw attributes:\n";
    foreach ($cahyo->getAttributes() as $key => $value) {
        echo "{$key}: " . ($value ?? 'NULL') . "\n";
    }
} else {
    echo "Transaksi Cahyo tidak ditemukan\n";
}

echo "\n=== End Detail ===\n";