<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

echo "=== Check New Transaction Issue ===\n\n";

// Login as user ID 1
$user = User::find(1);
Auth::login($user);
echo "Logged in as: {$user->name}\n\n";

// Get total count of transactions
$totalTransaksi = Transaksi::count();
echo "Total transactions in database: {$totalTransaksi}\n\n";

// Get latest 5 transactions
echo "Latest 5 transactions:\n";
$latestTransaksi = Transaksi::with(['paketBrand', 'leadAwalBrand', 'user'])
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get();

foreach ($latestTransaksi as $index => $transaksi) {
    echo ($index + 1) . ". ID: {$transaksi->id}\n";
    echo "   Nama Marketing: {$transaksi->nama_marketing}\n";
    echo "   Nama Paket: {$transaksi->nama_paket}\n";
    echo "   Created At: {$transaksi->created_at}\n";
    echo "   Updated At: {$transaksi->updated_at}\n";
    echo "   User: " . ($transaksi->user ? $transaksi->user->name : 'NULL') . "\n";
    echo "   Paket Brand: " . ($transaksi->paketBrand ? $transaksi->paketBrand->nama : 'NULL') . "\n";
    echo "   Lead Awal Brand: " . ($transaksi->leadAwalBrand ? $transaksi->leadAwalBrand->nama : 'NULL') . "\n";
    echo "\n";
}

// Check transactions created today
echo "=== Transactions created today ===\n";
$todayTransaksi = Transaksi::whereDate('created_at', Carbon::today())
    ->orderBy('created_at', 'desc')
    ->get();

if ($todayTransaksi->count() > 0) {
    echo "Found {$todayTransaksi->count()} transactions created today:\n";
    foreach ($todayTransaksi as $transaksi) {
        echo "- ID: {$transaksi->id}, Marketing: {$transaksi->nama_marketing}, Created: {$transaksi->created_at}\n";
    }
} else {
    echo "No transactions created today\n";
}

// Check transactions created in last hour
echo "\n=== Transactions created in last hour ===\n";
$recentTransaksi = Transaksi::where('created_at', '>=', Carbon::now()->subHour())
    ->orderBy('created_at', 'desc')
    ->get();

if ($recentTransaksi->count() > 0) {
    echo "Found {$recentTransaksi->count()} transactions created in last hour:\n";
    foreach ($recentTransaksi as $transaksi) {
        echo "- ID: {$transaksi->id}, Marketing: {$transaksi->nama_marketing}, Created: {$transaksi->created_at}\n";
    }
} else {
    echo "No transactions created in last hour\n";
}

echo "\n=== Database Connection Test ===\n";
try {
    $dbTest = \DB::select('SELECT COUNT(*) as count FROM transaksis');
    echo "Direct DB query result: {$dbTest[0]->count} transactions\n";
} catch (\Exception $e) {
    echo "Database error: {$e->getMessage()}\n";
}

echo "\n=== Test Complete ===\n";