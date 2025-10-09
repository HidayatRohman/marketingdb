<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Brand;
use App\Models\Transaksi;
use App\Http\Controllers\TransaksiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

echo "=== TEST COMPLETE FLOW: CREATE TO DISPLAY ===\n\n";

try {
    // 1. Login sebagai user
    $user = User::where('email', 'admin@example.com')->first();
    if (!$user) {
        $user = User::first();
    }
    
    if (!$user) {
        echo "❌ No users found in database\n";
        exit(1);
    }
    
    Auth::login($user);
    echo "✅ Logged in as: {$user->name} (ID: {$user->id}, Role: {$user->role})\n\n";
    
    // 2. Get available brands
    $brands = Brand::select('id', 'nama')->get();
    echo "📋 Available brands: " . $brands->count() . "\n";
    foreach ($brands->take(3) as $brand) {
        echo "   - {$brand->id}: {$brand->nama}\n";
    }
    echo "\n";
    
    // 3. Count existing transactions BEFORE create
    $beforeCount = Transaksi::count();
    echo "📊 Transactions before create: {$beforeCount}\n\n";
    
    // 3.5. Get available mitras for foreign key
    $mitras = \App\Models\Mitra::take(1)->get();
    if ($mitras->isEmpty()) {
        echo "❌ No mitras found in database. Creating a test mitra...\n";
        $testMitra = \App\Models\Mitra::create([
            'nama' => 'Test Mitra for Flow',
            'no_telp' => '081234567890',
            'brand_id' => $brands->first()->id,
            'chat' => 'masuk',
            'kota' => 'Test Kota',
            'provinsi' => 'Test Provinsi',
            'user_id' => $user->id
        ]);
        echo "✅ Test mitra created with ID: {$testMitra->id}\n";
        $mitraId = $testMitra->id;
    } else {
        $mitraId = $mitras->first()->id;
        echo "✅ Using existing mitra ID: {$mitraId}\n";
    }
    echo "\n";
    
    // 4. Create new transaction
    $newTransactionData = [
        'user_id' => $user->id,
        'mitra_id' => $mitraId,
        'tanggal_tf' => now()->format('Y-m-d'),
        'tanggal_lead_masuk' => now()->format('Y-m-d'),
        'periode_lead' => 'Januari',
        'no_wa' => '081234567890',
        'usia' => 25,
        'nama_mitra' => 'Test Mitra Flow',
        'paket_brand_id' => $brands->first()->id,
        'lead_awal_brand_id' => $brands->skip(1)->first()->id ?? $brands->first()->id,
        'sumber' => 'IG',
        'kabupaten' => 'Test Kabupaten',
        'provinsi' => 'Test Provinsi',
        'status_pembayaran' => 'Dp / TJ',
        'nominal_masuk' => 500000,
        'harga_paket' => 1000000,
        'nama_paket' => 'Test Package Flow'
    ];
    
    echo "🔄 Creating new transaction...\n";
    $newTransaction = Transaksi::create($newTransactionData);
    echo "✅ Transaction created with ID: {$newTransaction->id}\n\n";
    
    // 5. Count transactions AFTER create
    $afterCount = Transaksi::count();
    echo "📊 Transactions after create: {$afterCount}\n";
    echo "📈 Difference: " . ($afterCount - $beforeCount) . "\n\n";
    
    // 6. Test controller query (simulate what frontend calls)
    echo "🔍 Testing controller query (simulating frontend request)...\n";
    
    $controller = new TransaksiController();
    $request = new Request();
    
    // Simulate the query that controller uses
    $query = Transaksi::with(['user', 'paketBrand', 'leadAwalBrand']);
    
    // Apply role-based filtering (same as controller)
    $query = $user->applyRoleFilter($query, 'user_id');
    
    // Get paginated results (same as controller)
    $transaksis = $query->orderBy('created_at', 'desc')->paginate(10);
    
    echo "📋 Query results:\n";
    echo "   - Total found: {$transaksis->total()}\n";
    echo "   - Current page items: {$transaksis->count()}\n";
    echo "   - First item ID: " . ($transaksis->first() ? $transaksis->first()->id : 'None') . "\n";
    echo "   - Our new transaction ID: {$newTransaction->id}\n";
    
    // 7. Check if our new transaction is in the results
    $foundNewTransaction = $transaksis->where('id', $newTransaction->id)->first();
    if ($foundNewTransaction) {
        echo "✅ New transaction FOUND in query results!\n";
        echo "   - Position in results: " . ($transaksis->search(function($item) use ($newTransaction) {
            return $item->id === $newTransaction->id;
        }) + 1) . "\n";
    } else {
        echo "❌ New transaction NOT FOUND in query results!\n";
        
        // Debug: Check if it exists in database
        $dbCheck = Transaksi::find($newTransaction->id);
        if ($dbCheck) {
            echo "   - Transaction exists in DB: ✅\n";
            echo "   - Created at: {$dbCheck->created_at}\n";
            echo "   - User ID: {$dbCheck->user_id}\n";
            
            // Check role filtering
            echo "\n🔍 Debugging role filtering...\n";
            $queryWithoutRole = Transaksi::with(['user', 'paketBrand', 'leadAwalBrand'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);
            
            $foundWithoutRole = $queryWithoutRole->where('id', $newTransaction->id)->first();
            if ($foundWithoutRole) {
                echo "   - Found WITHOUT role filter: ✅\n";
                echo "   - Issue is likely with role-based filtering\n";
                
                // Check what applyRoleFilter does
                echo "\n🔍 Checking role filter method...\n";
                echo "   - User role: {$user->role}\n";
                echo "   - User ID: {$user->id}\n";
                echo "   - Transaction user_id: {$newTransaction->user_id}\n";
                
            } else {
                echo "   - NOT found even without role filter: ❌\n";
            }
        } else {
            echo "   - Transaction does NOT exist in DB: ❌\n";
        }
    }
    
    echo "\n";
    
    // 8. Show recent transactions for comparison
    echo "📋 Recent transactions (top 5):\n";
    $recent = Transaksi::with(['user', 'paketBrand', 'leadAwalBrand'])
        ->orderBy('created_at', 'desc')
        ->take(5)
        ->get();
    
    foreach ($recent as $index => $transaction) {
        $isNew = $transaction->id === $newTransaction->id ? ' 🆕' : '';
        echo "   " . ($index + 1) . ". ID: {$transaction->id}, Created: {$transaction->created_at}, User: {$transaction->user->name}{$isNew}\n";
    }
    
    echo "\n=== TEST COMPLETED ===\n";
    
} catch (Exception $e) {
    echo "❌ Error during test: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}