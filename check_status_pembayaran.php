<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Checking status_pembayaran column ===\n\n";

// Check column structure
$columns = DB::select('SHOW COLUMNS FROM transaksis WHERE Field = "status_pembayaran"');

if (!empty($columns)) {
    $column = $columns[0];
    echo "Column Type: {$column->Type}\n";
    echo "Null: {$column->Null}\n";
    echo "Default: {$column->Default}\n";
} else {
    echo "Column not found!\n";
}

// Check existing values
echo "\nExisting status_pembayaran values in database:\n";
$existingValues = DB::table('transaksis')
    ->select('status_pembayaran')
    ->distinct()
    ->get();

foreach ($existingValues as $value) {
    echo "- '{$value->status_pembayaran}'\n";
}

echo "\n=== Check Complete ===\n";