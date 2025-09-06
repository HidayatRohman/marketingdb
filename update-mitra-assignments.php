<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Mitra;
use App\Models\Label;

echo "=== Updating Mitra Data ===\n\n";

// Get marketing users and labels
$marketingUsers = User::where('role', 'marketing')->get();
$labels = Label::all();

echo "Marketing users: " . $marketingUsers->count() . "\n";
echo "Labels: " . $labels->count() . "\n\n";

if ($marketingUsers->count() > 0 && $labels->count() > 0) {
    // Update all mitras with random marketing user and label
    $mitras = Mitra::all();
    
    foreach ($mitras as $mitra) {
        $randomUser = $marketingUsers->random();
        $randomLabel = $labels->random();
        
        $mitra->update([
            'user_id' => $randomUser->id,
            'label_id' => $randomLabel->id
        ]);
        
        echo "Updated {$mitra->nama} - User: {$randomUser->name}, Label: {$randomLabel->nama}\n";
    }
    
    echo "\n=== Update Complete ===\n";
} else {
    echo "No marketing users or labels found!\n";
}

// Show updated stats
echo "\nUpdated statistics:\n";
$marketingUsers = User::where('role', 'marketing')
    ->withCount('mitras')
    ->get();

foreach ($marketingUsers as $user) {
    if ($user->mitras_count > 0) {
        echo "- {$user->name}: {$user->mitras_count} leads\n";
    }
}

$labels = Label::withCount('mitras')->get();
echo "\nLabel distribution:\n";
foreach ($labels as $label) {
    echo "- {$label->nama}: {$label->mitras_count} mitras\n";
}
