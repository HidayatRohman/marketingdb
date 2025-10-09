<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Transaksi;

echo "=== Latest Transactions ===\n\n";

$transactions = Transaksi::latest()->take(3)->get();

foreach ($transactions as $transaction) {
    echo "ID: {$transaction->id}\n";
    echo "- nama_marketing: {$transaction->nama_marketing}\n";
    echo "- nama_paket: {$transaction->nama_paket}\n";
    echo "- paket_brand_id: {$transaction->paket_brand_id}\n";
    echo "- lead_awal_brand_id: {$transaction->lead_awal_brand_id}\n";
    echo "- created_at: {$transaction->created_at}\n";
    echo "\n";
}

echo "=== End ===\n";