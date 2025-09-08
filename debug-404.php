<?php

echo "Testing Export 404 Issue\n";
echo "========================\n\n";

try {
    // Bootstrap Laravel
    require_once 'vendor/autoload.php';
    $app = require_once 'bootstrap/app.php';
    $kernel = $app->make('Illuminate\Contracts\Http\Kernel');
    $kernel->bootstrap();
    
    echo "✓ Laravel bootstrapped successfully\n\n";
    
    // Test 1: Check if routes exist
    echo "1. Testing route registration:\n";
    
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    $exportRoutes = [];
    
    foreach ($routes as $route) {
        if (strpos($route->uri(), 'export') !== false || strpos($route->uri(), 'mitras') !== false) {
            $exportRoutes[] = [
                'uri' => $route->uri(),
                'methods' => implode(', ', $route->methods()),
                'middleware' => implode(', ', $route->gatherMiddleware()),
                'action' => $route->getActionName()
            ];
        }
    }
    
    foreach ($exportRoutes as $route) {
        echo "   Route: {$route['uri']}\n";
        echo "   Methods: {$route['methods']}\n";
        echo "   Middleware: {$route['middleware']}\n";
        echo "   Action: {$route['action']}\n\n";
    }
    
    // Test 2: Check middleware
    echo "2. Testing middleware issues:\n";
    
    // Check if role.access middleware exists
    $middlewareExists = class_exists('App\Http\Middleware\RoleAccess');
    echo "   Role access middleware exists: " . ($middlewareExists ? "✓" : "✗") . "\n";
    
    // Test 3: Try to create a test request
    echo "\n3. Testing direct controller access:\n";
    
    try {
        $controller = new \App\Http\Controllers\MitraController();
        echo "   ✓ MitraController can be instantiated\n";
        
        // Check if export method exists
        if (method_exists($controller, 'export')) {
            echo "   ✓ Export method exists\n";
        } else {
            echo "   ✗ Export method not found\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Controller error: " . $e->getMessage() . "\n";
    }
    
    echo "\n4. Possible causes of 404:\n";
    echo "   a) Route cache issues - try: php artisan route:clear\n";
    echo "   b) Middleware blocking access\n";
    echo "   c) Authentication required but session lost\n";
    echo "   d) Route order conflicts\n";
    echo "   e) .htaccess or web server configuration\n";
    
    echo "\n5. Debug URLs to test:\n";
    echo "   /debug-export - Should show JSON response\n";
    echo "   /test-export-csv - Should download CSV\n";
    echo "   /mitras/export?export=csv - Actual export URL\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

?>
