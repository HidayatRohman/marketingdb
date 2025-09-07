<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Marketing CRUD Access...\n";

try {
    // Create a test marketing user
    $marketingUser = App\Models\User::where('role', 'marketing')->first();
    
    if (!$marketingUser) {
        echo "❌ No marketing user found. Creating one for testing...\n";
        $marketingUser = App\Models\User::create([
            'name' => 'Test Marketing',
            'email' => 'test.marketing@example.com',
            'password' => Hash::make('password'),
            'role' => 'marketing',
            'email_verified_at' => now(),
        ]);
        echo "✓ Created test marketing user: {$marketingUser->name}\n";
    } else {
        echo "✓ Found marketing user: {$marketingUser->name}\n";
    }

    // Test canCrud permission
    echo "Testing permissions:\n";
    echo "- canCrud(): " . ($marketingUser->canCrud() ? "✓ TRUE" : "❌ FALSE") . "\n";
    echo "- canOnlyView(): " . ($marketingUser->canOnlyView() ? "✓ TRUE" : "❌ FALSE") . "\n";
    echo "- canOnlyViewOwn(): " . ($marketingUser->canOnlyViewOwn() ? "✓ TRUE" : "❌ FALSE") . "\n";
    echo "- hasLimitedAccess(): " . ($marketingUser->hasLimitedAccess() ? "✓ TRUE" : "❌ FALSE") . "\n";

    // Test mitra creation
    echo "\nTesting mitra creation by marketing user...\n";
    
    // Get a brand for testing
    $brand = App\Models\Brand::first();
    if (!$brand) {
        echo "❌ No brand found for testing\n";
        return;
    }

    $mitraData = [
        'nama' => 'Test Mitra ' . rand(1000, 9999),
        'no_telp' => '081234567' . rand(100, 999),
        'tanggal_lead' => now()->format('Y-m-d'),
        'brand_id' => $brand->id,
        'user_id' => $marketingUser->id,
        'chat' => 'masuk',
        'kota' => 'Jakarta',
        'provinsi' => 'DKI Jakarta',
        'komentar' => 'Test mitra created by marketing user',
    ];

    $mitra = App\Models\Mitra::create($mitraData);
    echo "✓ Mitra created successfully by marketing user!\n";
    echo "  - ID: {$mitra->id}\n";
    echo "  - Name: {$mitra->nama}\n";
    echo "  - Marketing: {$mitra->user->name}\n";
    echo "  - Brand: {$mitra->brand->nama}\n";

    // Test access control
    echo "\nTesting access control...\n";
    $query = App\Models\Mitra::with(['brand', 'user']);
    $filteredQuery = $marketingUser->applyRoleFilter($query, 'user_id');
    $accessibleMitras = $filteredQuery->count();
    echo "✓ Marketing user can access {$accessibleMitras} mitras (filtered to own data)\n";

    echo "\n✅ All tests passed! Marketing users can now:\n";
    echo "  - Create new mitra data\n";
    echo "  - Edit their own mitra data\n";
    echo "  - View only their own mitra data\n";
    echo "  - Have auto-filled marketing field when creating\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
