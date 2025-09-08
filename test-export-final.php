<?php

echo "Testing Export Functionality\n";
echo "============================\n\n";

try {
    // Test basic Laravel bootstrap
    require_once 'vendor/autoload.php';
    $app = require_once 'bootstrap/app.php';
    $kernel = $app->make('Illuminate\Contracts\Http\Kernel');
    $kernel->bootstrap();
    
    echo "✓ Laravel bootstrapped\n";
    
    // Test route access
    echo "\n1. Testing route registration:\n";
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    $exportRoute = null;
    
    foreach ($routes as $route) {
        if ($route->uri() === 'mitras/export') {
            $exportRoute = $route;
            break;
        }
    }
    
    if ($exportRoute) {
        echo "   ✓ Export route found\n";
        echo "   URI: " . $exportRoute->uri() . "\n";
        echo "   Methods: " . implode(', ', $exportRoute->methods()) . "\n";
        echo "   Action: " . $exportRoute->getActionName() . "\n";
    } else {
        echo "   ✗ Export route not found\n";
    }
    
    // Test controller instantiation
    echo "\n2. Testing controller:\n";
    try {
        $controller = new \App\Http\Controllers\MitraController();
        echo "   ✓ Controller instantiated\n";
        
        if (method_exists($controller, 'export')) {
            echo "   ✓ Export method exists\n";
        } else {
            echo "   ✗ Export method missing\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Controller error: " . $e->getMessage() . "\n";
    }
    
    // Test dependencies
    echo "\n3. Testing dependencies:\n";
    
    // Test PhpSpreadsheet
    if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        echo "   ✓ PhpSpreadsheet available\n";
    } else {
        echo "   ✗ PhpSpreadsheet missing\n";
    }
    
    // Test CSV writer
    if (class_exists('PhpOffice\PhpSpreadsheet\Writer\Csv')) {
        echo "   ✓ CSV Writer available\n";
    } else {
        echo "   ✗ CSV Writer missing\n";
    }
    
    // Test Mitra model
    if (class_exists('App\Models\Mitra')) {
        echo "   ✓ Mitra model available\n";
    } else {
        echo "   ✗ Mitra model missing\n";
    }
    
    echo "\n4. Test URLs:\n";
    echo "   Debug: /debug-export\n";
    echo "   Debug Mitra: /debug-mitra-export?export=csv\n";
    echo "   Actual: /mitras/export?export=csv\n";
    echo "   Test Page: /test-export.html\n";
    
} catch (Exception $e) {
    echo "✗ Bootstrap failed: " . $e->getMessage() . "\n";
}

echo "\n5. Manual test steps:\n";
echo "   1. Visit /debug-export to check authentication\n";
echo "   2. Visit /debug-mitra-export?export=csv (no middleware)\n";
echo "   3. If both work, try /mitras/export?export=csv (with middleware)\n";
echo "   4. Check browser developer tools for errors\n";

?>
