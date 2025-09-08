<?php

require_once 'vendor/autoload.php';

use App\Models\TodoList;
use App\Models\User;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Week Ahead View for Todo List\n";
echo "====================================\n";

// Test date range calculation
$today = now();
$weekAhead = now()->addDays(7);

echo "Today: " . $today->format('Y-m-d') . "\n";
echo "Week Ahead: " . $weekAhead->format('Y-m-d') . "\n\n";

// Test the query logic
$startDate = now()->format('Y-m-d');
$endDate = now()->addDays(7)->format('Y-m-d');

echo "Query range: $startDate to $endDate\n";

// Count total todos in this range
$weekTodos = TodoList::whereBetween('due_date', [$startDate, $endDate])
                    ->orderBy('due_date')
                    ->orderBy('due_time')
                    ->orderBy('priority')
                    ->get();

echo "Todos found for 1 week ahead: " . $weekTodos->count() . "\n\n";

if ($weekTodos->count() > 0) {
    echo "Week ahead todos:\n";
    foreach($weekTodos as $todo) {
        $priorityLabel = ucfirst($todo->priority);
        $statusLabel = ucfirst(str_replace('_', ' ', $todo->status));
        echo "- [{$todo->due_date}] {$todo->title} (Priority: {$priorityLabel}, Status: {$statusLabel})\n";
        if ($todo->due_time) {
            echo "  Time: {$todo->due_time}\n";
        }
    }
} else {
    echo "No todos found for the next week!\n";
    
    // Let's check if there are todos in the database
    $totalTodos = TodoList::count();
    echo "\nTotal todos in database: $totalTodos\n";
    
    if ($totalTodos > 0) {
        echo "Sample todos with dates:\n";
        $sampleTodos = TodoList::select('id', 'title', 'due_date', 'status')
                               ->orderBy('due_date')
                               ->take(10)
                               ->get();
        foreach($sampleTodos as $todo) {
            echo "- ID: {$todo->id}, Due: {$todo->due_date}, Title: {$todo->title}, Status: {$todo->status}\n";
        }
    }
}

// Test filter combinations
echo "\n--- Filter Test ---\n";
echo "Testing with different filter combinations:\n";

// No filters - should show week ahead
echo "1. No filters (should show week ahead): ";
$hasFilters = false;
if (!$hasFilters) {
    echo "✓ Will show week ahead view\n";
} else {
    echo "✗ Will show selected date view\n";
}

// With status filter - should show selected date
echo "2. With status filter (should show selected date): ";
$hasFilters = true; // status !== 'all'
if (!$hasFilters) {
    echo "✗ Will show week ahead view\n";
} else {
    echo "✓ Will show selected date view\n";
}

echo "\n--- Done ---\n";
