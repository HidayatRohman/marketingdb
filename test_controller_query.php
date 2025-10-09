<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Transaksi;
use App\Models\User;

echo "=== Test Controller Query ===\n";

// Simulate the same query as in TransaksiController
$user = User::find(1); // Super Admin
echo "User: {$user->name} (Role: {$user->role})\n\n";

// Base query like in controller
$query = Transaksi::with(['user', 'paketBrand', 'leadAwalBrand']);

// Apply role-based filtering
$query = $user->applyRoleFilter($query, 'user_id');

echo "Query after role filter:\n";
$transaksis = $query->orderBy('created_at', 'desc')->get();

echo "Total transaksi found: " . $transaksis->count() . "\n\n";

foreach ($transaksis as $transaksi) {
    echo "ID: {$transaksi->id} | Nama Mitra: {$transaksi->nama_mitra} | User: {$transaksi->user->name} | tanggal_tf: {$transaksi->tanggal_tf}\n";
}

// Test with pagination like in controller
echo "\n=== Test with Pagination ===\n";
$query2 = Transaksi::with(['user', 'paketBrand', 'leadAwalBrand']);
$query2 = $user->applyRoleFilter($query2, 'user_id');
$paginatedResult = $query2->orderBy('created_at', 'desc')->paginate(10);

echo "Paginated total: {$paginatedResult->total()}\n";
echo "Current page: {$paginatedResult->currentPage()}\n";
echo "Per page: {$paginatedResult->perPage()}\n";
echo "Items on current page: " . $paginatedResult->count() . "\n\n";

foreach ($paginatedResult->items() as $transaksi) {
    echo "ID: {$transaksi->id} | Nama Mitra: {$transaksi->nama_mitra} | User: {$transaksi->user->name}\n";
}

echo "\n=== End Test ===\n";