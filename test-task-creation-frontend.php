<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Models\TodoList;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Task Creation Frontend Compatibility ===\n\n";

// Get a marketing user
$user = User::where('role', 'marketing')->first();
echo "Marketing User: {$user->name} ({$user->email})\n\n";

// Create a task manually to test database compatibility
$taskData = [
    'title' => 'Test Frontend Compatibility',
    'description' => 'Testing if frontend data format works with backend',
    'priority' => 'medium',
    'status' => 'pending',
    'start_date' => '2025-09-07',
    'due_date' => '2025-09-10',
    'due_time' => '14:30',
    'user_id' => $user->id,
    'assigned_to' => null,
    'tags' => ["frontend", "test"],
];

try {
    $task = TodoList::create($taskData);
    echo "✅ Task created successfully with frontend-compatible data!\n";
    echo "   - ID: {$task->id}\n";
    echo "   - Title: {$task->title}\n";
    echo "   - Priority: {$task->priority}\n";
    echo "   - Start Date: {$task->start_date}\n";
    echo "   - Due Date: {$task->due_date}\n";
    echo "   - Due Time: {$task->due_time}\n";
    echo "   - Tags: " . json_encode($task->tags) . "\n";
    
    // Test retrieving the task as it would be sent to frontend
    $retrievedTask = TodoList::with(['user', 'assignedUser'])->find($task->id);
    echo "\n✅ Task retrieved with relationships:\n";
    echo "   - User: {$retrievedTask->user->name}\n";
    echo "   - Assigned User: " . ($retrievedTask->assignedUser ? $retrievedTask->assignedUser->name : 'None') . "\n";
    
    // Cleanup
    $task->delete();
    echo "\n✅ Test task deleted.\n";
    
} catch (Exception $e) {
    echo "❌ Error creating task: " . $e->getMessage() . "\n";
}

// Test validation rules simulation
echo "\n=== TESTING VALIDATION RULES ===\n";

$invalidData = [
    'title' => '',  // Invalid - required
    'priority' => 'invalid',  // Invalid - not in enum
    'due_date' => 'invalid-date',  // Invalid - not a date
];

foreach ($invalidData as $field => $value) {
    try {
        $testData = $taskData;
        $testData[$field] = $value;
        
        TodoList::create($testData);
        echo "❌ Should have failed for invalid {$field}\n";
    } catch (Exception $e) {
        echo "✅ Correctly rejected invalid {$field}: " . substr($e->getMessage(), 0, 100) . "...\n";
    }
}

echo "\n✅ Frontend compatibility test completed!\n";
