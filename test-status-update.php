<?php

use App\Models\TodoList;
use App\Models\User;

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Task Status Update...\n\n";

// Get a test task
$task = TodoList::first();
if (!$task) {
    echo "No tasks found. Creating a test task...\n";
    $user = User::first();
    $task = TodoList::create([
        'title' => 'Test Task for Status Update',
        'description' => 'Test description',
        'priority' => 'medium',
        'status' => 'pending',
        'due_date' => date('Y-m-d', strtotime('+7 days')),
        'user_id' => $user->id,
    ]);
}

echo "Task ID: {$task->id}\n";
echo "Current Status: {$task->status}\n";

// Test status update
$newStatus = $task->status === 'pending' ? 'in_progress' : 'pending';
echo "Updating status to: {$newStatus}\n";

try {
    $task->update(['status' => $newStatus]);
    $task->refresh();
    echo "✓ Status updated successfully to: {$task->status}\n";
    
    // Test the controller method
    echo "\nTesting controller validation...\n";
    $validator = Illuminate\Support\Facades\Validator::make(
        ['status' => $newStatus],
        ['status' => 'required|in:pending,in_progress,completed']
    );
    
    if ($validator->fails()) {
        echo "✗ Validation failed: " . json_encode($validator->errors()) . "\n";
    } else {
        echo "✓ Validation passed\n";
    }
    
} catch (Exception $e) {
    echo "✗ Status update failed: " . $e->getMessage() . "\n";
}

echo "\nTest completed.\n";
