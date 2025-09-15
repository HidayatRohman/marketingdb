<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Mitra;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=== Debug Filter Data ===\n";

// Check user distribution
echo "\nUser distribution in mitras table:\n";
$userDistribution = Mitra::select('user_id', DB::raw('COUNT(*) as count'))
    ->groupBy('user_id')
    ->orderBy('count', 'desc')
    ->get();

foreach ($userDistribution as $item) {
    $user = User::find($item->user_id);
    $userName = $user ? $user->name : 'Unknown';
    $userRole = $user ? $user->role : 'Unknown';
    echo "User ID: {$item->user_id} ({$userName} - {$userRole}) - Count: {$item->count}\n";
}

// Check data for specific dates
echo "\nData for 2025-09-10 and 2025-09-11 by user:\n";
$dateData = Mitra::select('user_id', DB::raw('COUNT(*) as count'))
    ->whereDate('tanggal_lead', '>=', '2025-09-10')
    ->whereDate('tanggal_lead', '<=', '2025-09-11')
    ->groupBy('user_id')
    ->get();

foreach ($dateData as $item) {
    $user = User::find($item->user_id);
    $userName = $user ? $user->name : 'Unknown';
    $userRole = $user ? $user->role : 'Unknown';
    echo "User ID: {$item->user_id} ({$userName} - {$userRole}) - Count: {$item->count}\n";
}

echo "\n=== End Debug ===\n";