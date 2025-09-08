<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Task Management Status Update Routes...\n\n";

// Test route exists
$routes = collect(Route::getRoutes()->getRoutes())
    ->filter(function($route) {
        return str_contains($route->uri(), 'task-management') && 
               str_contains($route->uri(), 'status');
    });

foreach ($routes as $route) {
    echo "Route found: " . $route->methods()[0] . " " . $route->uri() . "\n";
    echo "Name: " . $route->getName() . "\n";
    echo "Action: " . $route->getActionName() . "\n\n";
}

echo "Routes test completed!\n";
