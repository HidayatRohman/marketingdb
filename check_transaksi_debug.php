<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=== Debug Transaksi Data ===\n";

// Check total transaksi
echo "Total transaksi: " . Transaksi::count() . "\n\n";

// Check 10 transaksi terbaru
echo "10 Transaksi terbaru:\n";
$latestTransaksi = Transaksi::with('user')
    ->orderBy('created_at', 'desc')
    ->take(10)
    ->get();

foreach ($latestTransaksi as $transaksi) {
    $user = $transaksi->user;
    echo "ID: {$transaksi->id} | Nama Mitra: {$transaksi->nama_mitra} | User: {$user->name} (ID: {$user->id}, Role: {$user->role}) | Created: {$transaksi->created_at}\n";
}

// Cari transaksi dengan nama mitra "Cahyo"
echo "\nMencari transaksi dengan nama mitra 'Cahyo':\n";
$cahyoTransaksi = Transaksi::where('nama_mitra', 'like', '%Cahyo%')
    ->with('user')
    ->get();

if ($cahyoTransaksi->count() > 0) {
    foreach ($cahyoTransaksi as $transaksi) {
        $user = $transaksi->user;
        echo "FOUND - ID: {$transaksi->id} | Nama Mitra: {$transaksi->nama_mitra} | User: {$user->name} (ID: {$user->id}, Role: {$user->role})\n";
    }
} else {
    echo "Tidak ada transaksi dengan nama mitra 'Cahyo'\n";
}

// Check user distribution in transaksi
echo "\nDistribusi user di tabel transaksi:\n";
$userDistribution = Transaksi::select('user_id', DB::raw('COUNT(*) as count'))
    ->groupBy('user_id')
    ->orderBy('count', 'desc')
    ->get();

foreach ($userDistribution as $item) {
    $user = User::find($item->user_id);
    $userName = $user ? $user->name : 'Unknown';
    $userRole = $user ? $user->role : 'Unknown';
    echo "User ID: {$item->user_id} ({$userName} - {$userRole}) - Count: {$item->count}\n";
}

// Check active sessions
echo "\nUser yang sedang login:\n";
$sessions = DB::table('sessions')
    ->whereNotNull('user_id')
    ->get();

foreach ($sessions as $session) {
    $user = User::find($session->user_id);
    if ($user) {
        echo "User: {$user->name} (ID: {$user->id}, Role: {$user->role}) | Last Activity: " . date('Y-m-d H:i:s', $session->last_activity) . "\n";
        
        // Check transaksi for this user
        $userTransaksiCount = Transaksi::where('user_id', $user->id)->count();
        echo "  - Transaksi milik user ini: {$userTransaksiCount}\n";
    }
}

echo "\n=== End Debug ===\n";