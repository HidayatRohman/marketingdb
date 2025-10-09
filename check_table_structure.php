<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Struktur Tabel Transaksis ===\n";

$columns = DB::select('DESCRIBE transaksis');

foreach($columns as $column) {
    echo sprintf("%-20s | %-30s | %-5s | %-5s | %-10s | %s\n", 
        $column->Field, 
        $column->Type, 
        $column->Null, 
        $column->Key, 
        $column->Default ?? 'NULL', 
        $column->Extra
    );
}

echo "\n=== Total Kolom: " . count($columns) . " ===\n";