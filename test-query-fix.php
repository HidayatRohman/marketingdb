<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing the fixed query...\n";

try {
    // Test the first query fix
    $users = App\Models\User::where('role', 'marketing')
        ->whereHas('mitras')
        ->withCount([
            'mitras as total_leads',
            'mitras as closed_leads' => function ($query) {
                $query->where('chat', 'followup');
            }
        ])
        ->get();
    
    echo "✓ Marketing users query executed successfully! Found " . $users->count() . " users with leads.\n";

    // Test the second query fix
    $brands = App\Models\Brand::whereHas('mitras')
        ->withCount([
            'mitras as total_leads',
            'mitras as closed_leads' => function ($query) {
                $query->where('chat', 'followup');
            }
        ])
        ->get();
    
    echo "✓ Brands query executed successfully! Found " . $brands->count() . " brands with leads.\n";
    echo "\n✅ All query fixes are working properly!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
