<?php

require_once 'vendor/autoload.php';

use App\Models\TodoList;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\TodoListController;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing TodoListController with board view\n";
echo "==========================================\n";

// Create a mock request
$request = new Request([
    'date' => '2025-09-07',
    'view' => 'board'
]);

// Create a user and authenticate
$user = User::first();
if (!$user) {
    echo "No users found in database!\n";
    exit(1);
}

auth()->login($user);
echo "Authenticated as: {$user->name} (ID: {$user->id})\n";
echo "User role: " . ($user->isSuperAdmin() ? 'Super Admin' : 'Regular User') . "\n\n";

// Create controller instance and call index method
$controller = new TodoListController();
$response = $controller->index($request);

// Get the data from the response
$props = $response->props;

echo "Response data:\n";
echo "Selected Date: {$props['selectedDate']}\n";
echo "View: {$props['view']}\n";
echo "Total todos returned: " . count($props['todos']) . "\n\n";

if (count($props['todos']) > 0) {
    echo "Todos found:\n";
    foreach ($props['todos'] as $todo) {
        echo "- ID: {$todo['id']}, Title: {$todo['title']}, Due: {$todo['due_date']}\n";
    }
} else {
    echo "No todos found! This indicates the issue is still present.\n";
}

echo "\nStats:\n";
foreach ($props['stats'] as $key => $value) {
    echo "- {$key}: {$value}\n";
}

echo "\n✅ Test completed successfully!\n";
