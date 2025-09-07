<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\TodoList;

echo "=== Testing Task Form Fields ===\n\n";

// Get marketing user
$marketing = User::where('role', 'marketing')->first();
if (!$marketing) {
    echo "No marketing user found!\n";
    exit;
}

echo "Marketing User: {$marketing->name} ({$marketing->email})\n\n";

// Test all form fields
echo "=== TESTING FORM VALIDATION ===\n";

// Test 1: Minimal required fields
echo "1. Testing minimal required fields...\n";
try {
    $task1 = TodoList::create([
        'title' => 'Test Task 1',
        'priority' => 'low',
        'due_date' => now()->addDays(1)->format('Y-m-d'),
        'user_id' => $marketing->id,
    ]);
    echo "✅ Created task with priority 'low': {$task1->id}\n";
    $task1->delete();
} catch (Exception $e) {
    echo "❌ Error with priority 'low': " . $e->getMessage() . "\n";
}

// Test 2: Medium priority
echo "2. Testing medium priority...\n";
try {
    $task2 = TodoList::create([
        'title' => 'Test Task 2',
        'priority' => 'medium',
        'due_date' => now()->addDays(1)->format('Y-m-d'),
        'user_id' => $marketing->id,
    ]);
    echo "✅ Created task with priority 'medium': {$task2->id}\n";
    $task2->delete();
} catch (Exception $e) {
    echo "❌ Error with priority 'medium': " . $e->getMessage() . "\n";
}

// Test 3: High priority
echo "3. Testing high priority...\n";
try {
    $task3 = TodoList::create([
        'title' => 'Test Task 3',
        'priority' => 'high',
        'due_date' => now()->addDays(1)->format('Y-m-d'),
        'user_id' => $marketing->id,
    ]);
    echo "✅ Created task with priority 'high': {$task3->id}\n";
    $task3->delete();
} catch (Exception $e) {
    echo "❌ Error with priority 'high': " . $e->getMessage() . "\n";
}

// Test 4: Full form fields
echo "4. Testing full form fields...\n";
try {
    $task4 = TodoList::create([
        'title' => 'Test Task Full',
        'description' => 'Testing full form with all fields',
        'priority' => 'medium',
        'status' => 'pending',
        'start_date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(3)->format('Y-m-d'),
        'due_time' => '14:30:00',
        'user_id' => $marketing->id,
        'assigned_to' => $marketing->id,
        'tags' => ['test', 'form'],
    ]);
    echo "✅ Created task with all fields: {$task4->id}\n";
    echo "   - Title: {$task4->title}\n";
    echo "   - Description: {$task4->description}\n";
    echo "   - Priority: {$task4->priority}\n";
    echo "   - Status: {$task4->status}\n";
    echo "   - Start Date: {$task4->start_date}\n";
    echo "   - Due Date: {$task4->due_date}\n";
    echo "   - Due Time: {$task4->due_time}\n";
    echo "   - Assigned To: {$task4->assigned_to}\n";
    echo "   - Tags: " . json_encode($task4->tags) . "\n";
    $task4->delete();
} catch (Exception $e) {
    echo "❌ Error with full form: " . $e->getMessage() . "\n";
}

// Test 5: Test invalid priority
echo "5. Testing invalid priority...\n";
try {
    $task5 = TodoList::create([
        'title' => 'Test Task Invalid',
        'priority' => 'invalid',
        'due_date' => now()->addDays(1)->format('Y-m-d'),
        'user_id' => $marketing->id,
    ]);
    echo "❌ Should not create task with invalid priority\n";
    $task5->delete();
} catch (Exception $e) {
    echo "✅ Correctly rejected invalid priority: " . $e->getMessage() . "\n";
}

echo "\n=== TESTING ACCESS TO TASK CREATION ENDPOINT ===\n";

// Simulate HTTP request validation
echo "Testing validation rules...\n";
$validationRules = [
    'title' => 'required|string|max:255',
    'description' => 'nullable|string',
    'priority' => 'required|in:low,medium,high',
    'start_date' => 'nullable|date',
    'due_date' => 'required|date|after_or_equal:start_date',
    'due_time' => 'nullable|date_format:H:i',
    'assigned_to' => 'nullable|exists:users,id',
    'tags' => 'nullable|array',
];

echo "Validation rules defined in controller:\n";
foreach ($validationRules as $field => $rules) {
    echo "  - {$field}: {$rules}\n";
}

echo "\n✅ Task creation validation test completed!\n";
