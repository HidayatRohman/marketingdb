<?php

use App\Models\TodoList;
use App\Models\User;

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Task Management functionality...\n\n";

// Test 1: Check if we can create a task
echo "1. Testing task creation...\n";
$user = User::first();
if (!$user) {
    echo "ERROR: No users found in database\n";
    exit(1);
}

try {
    $task = TodoList::create([
        'title' => 'Test Task - ' . date('Y-m-d H:i:s'),
        'description' => 'This is a test task',
        'priority' => 'medium',
        'status' => 'pending',
        'due_date' => date('Y-m-d', strtotime('+7 days')),
        'user_id' => $user->id,
    ]);
    
    echo "✓ Task created successfully with ID: {$task->id}\n";
} catch (Exception $e) {
    echo "✗ Task creation failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Check if we can update task status
echo "\n2. Testing task status update...\n";
try {
    $task->update(['status' => 'in_progress']);
    $task->refresh();
    
    if ($task->status === 'in_progress') {
        echo "✓ Task status updated successfully to: {$task->status}\n";
    } else {
        echo "✗ Task status update failed. Current status: {$task->status}\n";
    }
} catch (Exception $e) {
    echo "✗ Task status update failed: " . $e->getMessage() . "\n";
}

// Test 3: Check task counts by status
echo "\n3. Testing task count queries...\n";
$pendingCount = TodoList::where('status', 'pending')->count();
$inProgressCount = TodoList::where('status', 'in_progress')->count();
$completedCount = TodoList::where('status', 'completed')->count();

echo "Pending tasks: {$pendingCount}\n";
echo "In Progress tasks: {$inProgressCount}\n";
echo "Completed tasks: {$completedCount}\n";

// Clean up test task
echo "\n4. Cleaning up test task...\n";
try {
    $task->delete();
    echo "✓ Test task deleted successfully\n";
} catch (Exception $e) {
    echo "✗ Test task deletion failed: " . $e->getMessage() . "\n";
}

echo "\nTask Management test completed!\n";
