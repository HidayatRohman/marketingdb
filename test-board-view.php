<?php

require_once 'vendor/autoload.php';

use App\Models\TodoList;
use App\Models\User;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$selectedDate = '2025-09-07';

echo "Testing Board View for date: $selectedDate\n";
echo "=======================================\n";

// Calculate week range - Backend logic (NEW)
$selectedCarbon = Carbon::parse($selectedDate);

// If the selected date is Sunday, show the upcoming week (next Monday to Sunday)
if ($selectedCarbon->isSunday()) {
    $startOfWeek = $selectedCarbon->copy()->addDay()->startOfWeek(Carbon::MONDAY);
    $endOfWeek = $selectedCarbon->copy()->addDay()->endOfWeek(Carbon::SUNDAY);
} else {
    $startOfWeek = $selectedCarbon->startOfWeek(Carbon::MONDAY);
    $endOfWeek = $selectedCarbon->endOfWeek(Carbon::SUNDAY);
}

echo "Backend Logic (NEW):\n";
echo "Start of Week: " . $startOfWeek->format('Y-m-d') . "\n";
echo "End of Week: " . $endOfWeek->format('Y-m-d') . "\n";
echo "Selected Date: $selectedDate (Day of week: " . Carbon::parse($selectedDate)->format('l') . ")\n";
echo "Is Sunday: " . ($selectedCarbon->isSunday() ? 'Yes' : 'No') . "\n\n";

// Calculate week range - Backend logic (OLD)
$oldStartOfWeek = Carbon::parse($selectedDate)->startOfWeek(Carbon::MONDAY);
$oldEndOfWeek = Carbon::parse($selectedDate)->endOfWeek(Carbon::SUNDAY);

echo "Backend Logic (OLD):\n";
echo "Start of Week: " . $oldStartOfWeek->format('Y-m-d') . "\n";
echo "End of Week: " . $oldEndOfWeek->format('Y-m-d') . "\n\n";

// Check total todos in database
$totalTodos = TodoList::count();
echo "Total todos in database: $totalTodos\n";

if ($totalTodos > 0) {
    echo "\nAll todos in database:\n";
    $allTodos = TodoList::select('id', 'title', 'due_date', 'status')->get();
    foreach($allTodos as $todo) {
        echo "- ID: {$todo->id}, Title: {$todo->title}, Due: {$todo->due_date}, Status: {$todo->status}\n";
    }
}

// Check todos for the week (board view logic)
echo "\n--- Board View Query ---\n";
$boardTodos = TodoList::where(function($q) use ($startOfWeek, $endOfWeek) {
    // Include todos that have due_date in this week
    $q->whereBetween('due_date', [$startOfWeek, $endOfWeek])
    // OR todos that span across this week (start_date before week, due_date in/after week)
    ->orWhere(function($q2) use ($startOfWeek, $endOfWeek) {
        $q2->whereNotNull('start_date')
           ->where('start_date', '<=', $endOfWeek)
           ->where('due_date', '>=', $startOfWeek);
    });
})
->orderBy('due_date')
->orderBy('due_time')
->get();

echo "Todos found for board view: " . $boardTodos->count() . "\n";

if ($boardTodos->count() > 0) {
    echo "\nBoard view todos:\n";
    foreach($boardTodos as $todo) {
        echo "- ID: {$todo->id}, Title: {$todo->title}, Due: {$todo->due_date}, Start: " . ($todo->start_date ?? 'null') . "\n";
    }
} else {
    echo "No todos found for board view!\n";
    
    // Let's check if there are any todos with dates around this period
    echo "\nChecking todos around this date range:\n";
    $beforeWeek = TodoList::where('due_date', '<', $startOfWeek)->count();
    $afterWeek = TodoList::where('due_date', '>', $endOfWeek)->count();
    echo "Todos before week: $beforeWeek\n";
    echo "Todos after week: $afterWeek\n";
    
    // Show some sample todos with their dates
    echo "\nSample todos with dates:\n";
    $sampleTodos = TodoList::select('id', 'title', 'due_date', 'start_date')->take(10)->get();
    foreach($sampleTodos as $todo) {
        echo "- ID: {$todo->id}, Due: {$todo->due_date}, Start: " . ($todo->start_date ?? 'null') . "\n";
    }
}

echo "\n--- Done ---\n";
