<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=== Check Active Sessions ===\n";

// Get all sessions from database
$sessions = DB::table('sessions')
    ->whereNotNull('user_id')
    ->get();

echo "Active sessions count: " . $sessions->count() . "\n\n";

foreach ($sessions as $session) {
    $user = User::find($session->user_id);
    if ($user) {
        echo "Session ID: {$session->id}\n";
        echo "User: {$user->name} (ID: {$user->id}, Role: {$user->role})\n";
        echo "Last Activity: " . date('Y-m-d H:i:s', $session->last_activity) . "\n";
        
        // Check if this user has data in the filtered date range
        $dataCount = \App\Models\Mitra::where('user_id', $user->id)
            ->whereDate('tanggal_lead', '>=', '2025-09-10')
            ->whereDate('tanggal_lead', '<=', '2025-09-11')
            ->count();
        
        echo "Data in filtered range (2025-09-10 to 2025-09-11): {$dataCount}\n";
        echo "---\n";
    }
}

echo "\n=== End Check ===\n";