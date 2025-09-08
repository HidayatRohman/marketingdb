<?php

// Test script untuk debugging export
echo "Testing Export Functionality\n";
echo "============================\n\n";

// Test basic CSV creation
echo "1. Testing basic CSV creation...\n";

// Create simple CSV content
$csvContent = "ID,Nama,No. Telepon,Tanggal Lead\n";
$csvContent .= "1,\"Test User\",\"081234567890\",\"2024-01-15\"\n";
$csvContent .= "2,\"User Kedua\",\"087654321098\",\"2024-01-16\"\n";

// Test write to file
$testFile = 'public/test-export.csv';
file_put_contents($testFile, $csvContent);

if (file_exists($testFile)) {
    echo "✓ CSV file created successfully\n";
    echo "  Size: " . filesize($testFile) . " bytes\n";
    echo "  Content:\n" . file_get_contents($testFile) . "\n";
    unlink($testFile);
} else {
    echo "✗ Failed to create CSV file\n";
}

// Test Laravel route
echo "\n2. Testing Laravel export route...\n";

try {
    // Bootstrap Laravel
    require_once 'vendor/autoload.php';
    $app = require_once 'bootstrap/app.php';
    $kernel = $app->make('Illuminate\Contracts\Http\Kernel');
    $kernel->bootstrap();
    
    echo "✓ Laravel bootstrapped\n";
    
    // Test route existence
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    $exportRoute = null;
    
    foreach ($routes as $route) {
        if ($route->uri() === 'mitras/export') {
            $exportRoute = $route;
            break;
        }
    }
    
    if ($exportRoute) {
        echo "✓ Export route found: " . $exportRoute->uri() . "\n";
        echo "  Methods: " . implode(', ', $exportRoute->methods()) . "\n";
        echo "  Action: " . $exportRoute->getActionName() . "\n";
    } else {
        echo "✗ Export route not found\n";
    }
    
} catch (Exception $e) {
    echo "✗ Laravel bootstrap failed: " . $e->getMessage() . "\n";
}

echo "\n3. Alternative export solution...\n";
echo "Creating simple export endpoint for testing...\n";

// Create a simple test export endpoint
$testController = '<?php

// Simple test export - add this temporarily to web.php
Route::get(\'/test-export-csv\', function() {
    $data = [
        [\'ID\', \'Nama\', \'No. Telepon\', \'Tanggal Lead\'],
        [1, \'Test User\', \'081234567890\', \'2024-01-15\'],
        [2, \'User Kedua\', \'087654321098\', \'2024-01-16\']
    ];
    
    $csv = fopen(\'php://temp\', \'w+\');
    foreach ($data as $row) {
        fputcsv($csv, $row);
    }
    rewind($csv);
    $output = stream_get_contents($csv);
    fclose($csv);
    
    return response($output, 200, [
        \'Content-Type\' => \'text/csv\',
        \'Content-Disposition\' => \'attachment; filename="test-export.csv"\',
        \'Cache-Control\' => \'no-cache, no-store, must-revalidate\',
        \'Pragma\' => \'no-cache\',
        \'Expires\' => \'0\'
    ]);
});';

file_put_contents('test-export-route.txt', $testController);
echo "✓ Test export code saved to test-export-route.txt\n";

echo "\nNext steps:\n";
echo "1. Add the test route from test-export-route.txt to routes/web.php\n";
echo "2. Visit /test-export-csv to see if basic export works\n";
echo "3. If it works, the issue might be with PhpSpreadsheet\n";
echo "4. If it doesn't work, the issue is with headers/browser\n";

?>
