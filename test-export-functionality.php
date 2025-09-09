<?php

// Test the Mitra export functionality
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Mitra Export Functionality\n";
echo "================================\n\n";

// Test 1: Check if route exists
echo "1. Checking if export route exists...\n";
try {
    $url = route('mitras.export');
    echo "✅ Export route URL: {$url}\n";
} catch (Exception $e) {
    echo "❌ Export route not found: {$e->getMessage()}\n";
}

// Test 2: Check if MitraController export method exists
echo "\n2. Checking MitraController export method...\n";
try {
    $controller = new \App\Http\Controllers\MitraController();
    if (method_exists($controller, 'export')) {
        echo "✅ MitraController::export method exists\n";
    } else {
        echo "❌ MitraController::export method not found\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking controller: {$e->getMessage()}\n";
}

// Test 3: Check if we have sample data
echo "\n3. Checking database data...\n";
try {
    $mitraCount = \App\Models\Mitra::count();
    echo "✅ Total Mitras in database: {$mitraCount}\n";
    
    if ($mitraCount > 0) {
        $firstMitra = \App\Models\Mitra::with(['brand', 'label', 'user'])->first();
        $brandName = $firstMitra->brand ? $firstMitra->brand->nama : 'No brand';
        echo "✅ Sample mitra: {$firstMitra->nama} ({$brandName})\n";
    }
} catch (Exception $e) {
    echo "❌ Database error: {$e->getMessage()}\n";
}

// Test 4: Check PhpSpreadsheet
echo "\n4. Checking PhpSpreadsheet library...\n";
try {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    echo "✅ PhpSpreadsheet is working\n";
} catch (Exception $e) {
    echo "❌ PhpSpreadsheet error: {$e->getMessage()}\n";
}

// Test 5: Test CSV generation
echo "\n5. Testing CSV generation...\n";
try {
    $output = fopen('php://temp', 'w+');
    fputcsv($output, ['Test', 'Data', 'Export']);
    fputcsv($output, ['Row1', 'Value1', 'Data1']);
    rewind($output);
    $csvContent = stream_get_contents($output);
    fclose($output);
    
    if (!empty($csvContent)) {
        echo "✅ CSV generation working\n";
        echo "Sample CSV content:\n{$csvContent}\n";
    } else {
        echo "❌ CSV generation failed\n";
    }
} catch (Exception $e) {
    echo "❌ CSV generation error: {$e->getMessage()}\n";
}

echo "\nTest completed.\n";
