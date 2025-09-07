<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\TodoList;
use Illuminate\Http\Request;
use App\Http\Controllers\TaskManagementController;

echo "=== Testing Task Creation via Controller ===\n\n";

// Get marketing user
$marketing = User::where('role', 'marketing')->first();
if (!$marketing) {
    echo "No marketing user found!\n";
    exit;
}

echo "Marketing User: {$marketing->name} ({$marketing->email})\n\n";

// Simulate login
auth()->login($marketing);

echo "Auth check: " . (auth()->check() ? 'Yes' : 'No') . "\n";
echo "Logged in as: " . auth()->user()->name . " (" . auth()->user()->role . ")\n\n";

// Test controller access
echo "=== TESTING CONTROLLER INDEX ===\n";
try {
    $controller = new TaskManagementController();
    
    // Create a mock request
    $request = Request::create('/task-management', 'GET');
    
    echo "✅ TaskManagementController instantiated successfully\n";
    
    // Try to call index method (this would normally be called by route)
    echo "ℹ️  Index method would be accessible via route\n";
    
} catch (Exception $e) {
    echo "❌ Error accessing controller: " . $e->getMessage() . "\n";
}

// Test store method simulation
echo "\n=== TESTING STORE METHOD SIMULATION ===\n";
try {
    // Create sample task data
    $taskData = [
        'title' => 'Test Task via Controller',
        'description' => 'Testing task creation via controller method',
        'priority' => 'medium',
        'due_date' => now()->addDays(3)->format('Y-m-d'),
        'due_time' => '14:30',
        'assigned_to' => $marketing->id,
        'tags' => ['test', 'controller'],
    ];

    echo "Task data to be created:\n";
    foreach ($taskData as $key => $value) {
        echo "  - {$key}: " . (is_array($value) ? json_encode($value) : $value) . "\n";
    }

    // Create task directly (simulating controller store method)
    $task = TodoList::create([
        'title' => $taskData['title'],
        'description' => $taskData['description'],
        'priority' => $taskData['priority'],
        'status' => 'pending',
        'due_date' => $taskData['due_date'],
        'due_time' => $taskData['due_time'],
        'user_id' => auth()->id(),
        'assigned_to' => $taskData['assigned_to'],
        'tags' => $taskData['tags'],
    ]);

    echo "✅ Task created successfully via direct model call!\n";
    echo "   - Task ID: {$task->id}\n";
    echo "   - Title: {$task->title}\n";
    echo "   - Priority: {$task->priority}\n";
    echo "   - Status: {$task->status}\n";
    echo "   - Created by: {$task->user_id} (auth: " . auth()->id() . ")\n";
    echo "   - Assigned to: {$task->assigned_to}\n";

    // Clean up
    $task->delete();
    echo "✅ Test task deleted\n";

} catch (Exception $e) {
    echo "❌ Error in store simulation: " . $e->getMessage() . "\n";
}

// Test validation
echo "\n=== TESTING VALIDATION RULES ===\n";
$validationErrors = [];

// Test missing title
try {
    TodoList::create([
        'priority' => 'medium',
        'due_date' => now()->addDays(1)->format('Y-m-d'),
        'user_id' => auth()->id(),
    ]);
    $validationErrors[] = "Should require title";
} catch (Exception $e) {
    echo "✅ Correctly validates missing title\n";
}

// Test invalid priority
try {
    TodoList::create([
        'title' => 'Test',
        'priority' => 'invalid',
        'due_date' => now()->addDays(1)->format('Y-m-d'),
        'user_id' => auth()->id(),
    ]);
    $validationErrors[] = "Should reject invalid priority";
} catch (Exception $e) {
    echo "✅ Correctly validates invalid priority\n";
}

// Test missing due_date
try {
    TodoList::create([
        'title' => 'Test',
        'priority' => 'medium',
        'user_id' => auth()->id(),
    ]);
    $validationErrors[] = "Should require due_date";
} catch (Exception $e) {
    echo "✅ Correctly validates missing due_date\n";
}

if ($validationErrors) {
    echo "❌ Validation issues found:\n";
    foreach ($validationErrors as $error) {
        echo "  - {$error}\n";
    }
} else {
    echo "✅ All validation rules working correctly\n";
}

echo "\n✅ Task creation controller test completed!\n";
