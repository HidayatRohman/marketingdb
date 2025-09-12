<?php

echo "Testing database configuration...\n";

// Read database config directly
$configPath = __DIR__ . '/config/database.php';
if (file_exists($configPath)) {
    $databaseConfig = include $configPath;
    $defaultConnection = env('DB_CONNECTION', 'sqlite');
    
    echo "Default connection: {$defaultConnection}\n";
    
    if (isset($databaseConfig['connections'][$defaultConnection])) {
        $driver = $databaseConfig['connections'][$defaultConnection]['driver'];
        echo "Driver: {$driver}\n";
        
        // Test hour expression logic
        if ($driver === 'sqlite') {
            $hourExpression = "strftime('%H', created_at)";
            echo "Using SQLite syntax: {$hourExpression}\n";
        } else {
            $hourExpression = "HOUR(created_at)";
            echo "Using MySQL syntax: {$hourExpression}\n";
        }
    }
} else {
    echo "Config file not found\n";
}

echo "Test completed.\n";