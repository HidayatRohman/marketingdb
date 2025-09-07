<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\TodoList;

echo "=== Testing Marketing Task Creation ===\n\n";

// Get marketing user
$marketing = User::where('role', 'marketing')->first();
if (!$marketing) {
    echo "No marketing user found!\n";
    exit;
}

echo "Marketing User: {$marketing->name} ({$marketing->email})\n\n";

// Check permissions
echo "=== PERMISSIONS ===\n";
echo "canCrud: " . ($marketing->canCrud() ? 'Yes' : 'No') . "\n";
echo "canOnlyView: " . ($marketing->canOnlyView() ? 'Yes' : 'No') . "\n";
echo "canOnlyViewOwn: " . ($marketing->canOnlyViewOwn() ? 'Yes' : 'No') . "\n";
echo "hasLimitedAccess: " . ($marketing->hasLimitedAccess() ? 'Yes' : 'No') . "\n\n";

// Try to create a task
echo "=== CREATING TASK ===\n";
try {
    $task = TodoList::create([
        'title' => 'Test Task by Marketing',
        'description' => 'Testing if marketing can create tasks',
        'priority' => 'medium',
        'status' => 'pending',
        'due_date' => now()->addDays(3)->format('Y-m-d'),
        'user_id' => $marketing->id,
    ]);
    echo "✅ Task created successfully! ID: {$task->id}\n";
    echo "Title: {$task->title}\n";
    echo "Priority: {$task->priority}\n";
    echo "Status: {$task->status}\n";
    echo "Due Date: {$task->due_date}\n";
    
    // Check if task can be retrieved
    $retrievedTask = TodoList::find($task->id);
    if ($retrievedTask) {
        echo "✅ Task can be retrieved successfully\n";
    } else {
        echo "❌ Task cannot be retrieved\n";
    }
    
    // Clean up
    $task->delete();
    echo "✅ Test task deleted.\n";
} catch (Exception $e) {
    echo "❌ Error creating task: " . $e->getMessage() . "\n";
}

// Check existing tasks
echo "\n=== EXISTING TASKS ===\n";
$existingTasks = TodoList::where('user_id', $marketing->id)->count();
echo "Marketing user has {$existingTasks} existing tasks\n";

echo "\n✅ Marketing task creation test completed!\n";
