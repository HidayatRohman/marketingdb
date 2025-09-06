<?php

namespace App\Http\Controllers;

use App\Models\TodoList;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class TaskManagementController extends Controller
{
    /**
     * Display the task management kanban board
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Base query with role-based access
        $baseQuery = TodoList::with(['user', 'assignedUser']);
        
        if (!$user->isSuperAdmin()) {
            $baseQuery->where(function($q) {
                $q->where('user_id', auth()->id())
                  ->orWhere('assigned_to', auth()->id());
            });
        }

        // Get tasks grouped by status
        $pendingTasks = (clone $baseQuery)->where('status', 'pending')->orderBy('priority', 'desc')->orderBy('due_date')->get();
        $inProgressTasks = (clone $baseQuery)->where('status', 'in_progress')->orderBy('priority', 'desc')->orderBy('due_date')->get();
        $completedTasks = (clone $baseQuery)->where('status', 'completed')->orderBy('updated_at', 'desc')->get();

        // Get summary statistics
        $summary = [
            'total' => $baseQuery->count(),
            'pending' => $pendingTasks->count(),
            'in_progress' => $inProgressTasks->count(),
            'completed' => $completedTasks->count(),
            'overdue' => (clone $baseQuery)->where('due_date', '<', Carbon::today())->where('status', '!=', 'completed')->count(),
            'today' => (clone $baseQuery)->where('due_date', Carbon::today())->count(),
            'this_week' => (clone $baseQuery)->whereBetween('due_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count(),
        ];

        // Get users for assignment
        $users = User::select('id', 'name', 'email')->get();

        return Inertia::render('TaskManagement/Index', [
            'tasks' => [
                'pending' => $pendingTasks,
                'in_progress' => $inProgressTasks,
                'completed' => $completedTasks,
            ],
            'summary' => $summary,
            'users' => $users,
        ]);
    }

    /**
     * Store a new task
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'start_date' => 'nullable|date',
            'due_date' => 'required|date|after_or_equal:start_date',
            'due_time' => 'nullable|date_format:H:i',
            'assigned_to' => 'nullable|exists:users,id',
            'tags' => 'nullable|array',
        ]);

        TodoList::create([
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'status' => 'pending', // Default status for new tasks
            'start_date' => $request->start_date,
            'due_date' => $request->due_date,
            'due_time' => $request->due_time,
            'user_id' => auth()->id(),
            'assigned_to' => $request->assigned_to,
            'tags' => $request->tags,
        ]);

        return redirect()->back()->with('success', 'Task berhasil ditambahkan');
    }

    /**
     * Update task
     */
    public function update(Request $request, TodoList $todoList)
    {
        // Check permission
        if ($todoList->user_id !== auth()->id() && $todoList->assigned_to !== auth()->id() && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:pending,in_progress,completed',
            'start_date' => 'nullable|date',
            'due_date' => 'required|date|after_or_equal:start_date',
            'due_time' => 'nullable|date_format:H:i',
            'assigned_to' => 'nullable|exists:users,id',
            'tags' => 'nullable|array',
        ]);

        $todoList->update($request->only([
            'title', 'description', 'priority', 'status', 'start_date',
            'due_date', 'due_time', 'assigned_to', 'tags'
        ]));

        return redirect()->back()->with('success', 'Task berhasil diperbarui');
    }

    /**
     * Update task status (for drag and drop)
     */
    public function updateStatus(Request $request, TodoList $todoList)
    {
        // Check permission
        if ($todoList->user_id !== auth()->id() && $todoList->assigned_to !== auth()->id() && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'status' => 'required|in:pending,in_progress,completed'
        ]);

        $todoList->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Status task berhasil diperbarui'
        ]);
    }

    /**
     * Delete task
     */
    public function destroy(TodoList $todoList)
    {
        // Check permission
        if ($todoList->user_id !== auth()->id() && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }

        $todoList->delete();

        return redirect()->back()->with('success', 'Task berhasil dihapus');
    }

    /**
     * Get tasks data for API calls
     */
    public function getTasks(Request $request)
    {
        $user = auth()->user();
        
        $baseQuery = TodoList::with(['user', 'assignedUser']);
        
        if (!$user->isSuperAdmin()) {
            $baseQuery->where(function($q) {
                $q->where('user_id', auth()->id())
                  ->orWhere('assigned_to', auth()->id());
            });
        }

        $pendingTasks = (clone $baseQuery)->where('status', 'pending')->orderBy('priority', 'desc')->orderBy('due_date')->get();
        $inProgressTasks = (clone $baseQuery)->where('status', 'in_progress')->orderBy('priority', 'desc')->orderBy('due_date')->get();
        $completedTasks = (clone $baseQuery)->where('status', 'completed')->orderBy('updated_at', 'desc')->get();

        return response()->json([
            'tasks' => [
                'pending' => $pendingTasks,
                'in_progress' => $inProgressTasks,
                'completed' => $completedTasks,
            ]
        ]);
    }
}
