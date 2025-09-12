<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

echo "Testing database configuration...\n";

$driver = config('database.default');
echo "Default database: {$driver}\n";

$connectionConfig = config("database.connections.{$driver}");
$connectionDriver = $connectionConfig['driver'] ?? 'unknown';
echo "Connection driver: {$connectionDriver}\n";

// Test hour expression logic
if ($connectionDriver === 'sqlite') {
    $hourExpression = "strftime('%H', created_at)";
    echo "Using SQLite syntax: {$hourExpression}\n";
} else {
    $hourExpression = "HOUR(created_at)";
    echo "Using MySQL syntax: {$hourExpression}\n";
}

echo "Configuration test completed.\n";