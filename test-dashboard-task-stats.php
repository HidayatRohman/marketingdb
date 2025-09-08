<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\TodoList;
use App\Models\User;
use Carbon\Carbon;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Dashboard Task Statistics Logic\n";
echo "=====================================\n\n";

// Test the actual counts from database
echo "1. Overall Task Counts:\n";
$totalTasks = TodoList::count();
$pendingTasks = TodoList::where('status', 'pending')->count();
$inProgressTasks = TodoList::where('status', 'in_progress')->count();
$completedTasks = TodoList::where('status', 'completed')->count();
$overdueTasks = TodoList::where('status', '!=', 'completed')
                       ->where('due_date', '<', Carbon::today())->count();

echo "   Total: {$totalTasks}\n";
echo "   Pending: {$pendingTasks}\n"; 
echo "   In Progress: {$inProgressTasks}\n";
echo "   Completed: {$completedTasks}\n";
echo "   Overdue: {$overdueTasks}\n\n";

echo "2. Per Marketing User (using corrected logic):\n";

$marketingUsers = User::where('role', 'marketing')->get();

foreach ($marketingUsers as $user) {
    echo "   {$user->name} ({$user->email}):\n";
    
    // Use the same logic as TaskManagementController - tasks where user is creator OR assigned to
    $userTasksQuery = TodoList::where(function ($query) use ($user) {
        $query->where('user_id', $user->id)
              ->orWhere('assigned_to', $user->id);
    });
    
    $userTotal = (clone $userTasksQuery)->count();
    $userPending = (clone $userTasksQuery)->where('status', 'pending')->count();
    $userInProgress = (clone $userTasksQuery)->where('status', 'in_progress')->count();
    $userCompleted = (clone $userTasksQuery)->where('status', 'completed')->count();
    $userOverdue = (clone $userTasksQuery)->where('status', '!=', 'completed')
                                          ->where('due_date', '<', Carbon::today())->count();
    
    // Also show the breakdown
    $createdTotal = TodoList::where('user_id', $user->id)->count();
    $assignedTotal = TodoList::where('assigned_to', $user->id)->count();
    
    echo "     Total Tasks: {$userTotal} (Created: {$createdTotal}, Assigned: {$assignedTotal})\n";
    echo "     Pending: {$userPending}\n";
    echo "     In Progress: {$userInProgress}\n";
    echo "     Completed: {$userCompleted}\n";
    echo "     Overdue: {$userOverdue}\n";
    
    $completionRate = $userTotal > 0 ? round(($userCompleted / $userTotal) * 100, 2) : 0;
    echo "     Completion Rate: {$completionRate}%\n\n";
}

echo "3. Checking for potential duplication:\n";
foreach ($marketingUsers as $user) {
    $duplicatedTasks = TodoList::where('user_id', $user->id)
                               ->where('assigned_to', $user->id)
                               ->count();
    if ($duplicatedTasks > 0) {
        echo "   {$user->name}: {$duplicatedTasks} tasks both created by and assigned to them\n";
    }
}

echo "\nTest completed.\n";
