<?php

namespace App\Http\Controllers;

use App\Models\TodoList;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class TodoListController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $selectedDate = $request->get('date', now()->format('Y-m-d'));
        $view = $request->get('view', 'calendar'); // calendar or list
        
        // Get filter parameters
        $status = $request->get('status', 'all');
        $priority = $request->get('priority', 'all');
        $assigned = $request->get('assigned', 'all');
        $user = $request->get('user', 'all');
        $search = $request->get('search', '');
        
        $query = TodoList::with(['user', 'assignedUser'])
            ->where(function($q) {
                $q->where('user_id', auth()->id())
                  ->orWhere('assigned_to', auth()->id());
            });

        // Apply filters
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        if ($priority !== 'all') {
            $query->where('priority', $priority);
        }
        
        if ($assigned !== 'all') {
            if ($assigned === 'me') {
                $query->where(function($q) {
                    $q->where('user_id', auth()->id())
                      ->orWhereNull('assigned_to');
                });
            } else {
                $query->where('assigned_to', '!=', null)
                      ->where('user_id', '!=', auth()->id());
            }
        }
        
        if ($user !== 'all') {
            $query->where('user_id', $user);
        }
        
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if ($view === 'calendar') {
            // For calendar view, get todos for the entire month
            $startOfMonth = Carbon::parse($selectedDate)->startOfMonth();
            $endOfMonth = Carbon::parse($selectedDate)->endOfMonth();
            
            $todos = $query->whereBetween('due_date', [$startOfMonth, $endOfMonth])
                          ->orderBy('due_date')
                          ->orderBy('due_time')
                          ->get();
        } else {
            // For list view, get todos for selected date or all if filters applied
            if ($status === 'all' && $priority === 'all' && $assigned === 'all' && $user === 'all' && empty($search)) {
                // No filters, show only selected date
                $todos = $query->whereDate('due_date', $selectedDate)
                              ->orderBy('due_time')
                              ->orderBy('priority')
                              ->get();
            } else {
                // Filters applied, show all matching todos
                $todos = $query->orderBy('due_date')
                              ->orderBy('due_time')
                              ->orderBy('priority')
                              ->get();
            }
        }

        $users = User::select('id', 'name', 'email')->get();

        return Inertia::render('TodoList/Index', [
            'todos' => $todos,
            'users' => $users,
            'selectedDate' => $selectedDate,
            'view' => $view,
            'filters' => [
                'status' => $status,
                'priority' => $priority,
                'assigned' => $assigned,
                'user' => $user,
                'search' => $search,
            ],
            'stats' => [
                'total' => TodoList::where(function($q) {
                    $q->where('user_id', auth()->id())
                      ->orWhere('assigned_to', auth()->id());
                })->count(),
                'completed' => TodoList::where(function($q) {
                    $q->where('user_id', auth()->id())
                      ->orWhere('assigned_to', auth()->id());
                })->where('status', 'completed')->count(),
                'pending' => TodoList::where(function($q) {
                    $q->where('user_id', auth()->id())
                      ->orWhere('assigned_to', auth()->id());
                })->where('status', 'pending')->count(),
                'overdue' => TodoList::where(function($q) {
                    $q->where('user_id', auth()->id())
                      ->orWhere('assigned_to', auth()->id());
                })
                    ->where('status', '!=', 'completed')
                    ->where('due_date', '<', now()->format('Y-m-d'))
                    ->count(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:pending,in_progress,completed',
            'due_date' => 'required|date',
            'due_time' => 'nullable|date_format:H:i',
            'assigned_to' => 'nullable|exists:users,id',
            'tags' => 'nullable|array',
        ]);

        $todo = TodoList::create([
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'status' => $request->status,
            'due_date' => $request->due_date,
            'due_time' => $request->due_time,
            'user_id' => auth()->id(),
            'assigned_to' => $request->assigned_to,
            'tags' => $request->tags,
        ]);

        return redirect()->back()->with('success', 'Todo berhasil ditambahkan');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TodoList $todoList)
    {
        // Check if user can update this todo
        if ($todoList->user_id !== auth()->id() && $todoList->assigned_to !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:pending,in_progress,completed',
            'due_date' => 'required|date',
            'due_time' => 'nullable|date_format:H:i',
            'assigned_to' => 'nullable|exists:users,id',
            'tags' => 'nullable|array',
        ]);

        $todoList->update($request->only([
            'title', 'description', 'priority', 'status', 
            'due_date', 'due_time', 'assigned_to', 'tags'
        ]));

        return redirect()->back()->with('success', 'Todo berhasil diperbarui');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TodoList $todoList)
    {
        // Check if user can delete this todo
        if ($todoList->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $todoList->delete();

        return redirect()->back()->with('success', 'Todo berhasil dihapus');
    }

    /**
     * Update todo status
     */
    public function updateStatus(Request $request, TodoList $todoList)
    {
        // Check if user can update this todo
        if ($todoList->user_id !== auth()->id() && $todoList->assigned_to !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'status' => 'required|in:pending,in_progress,completed'
        ]);

        $todoList->update(['status' => $request->status]);

        return response()->json(['success' => true]);
    }

    /**
     * Get todos for calendar
     */
    public function calendar(Request $request)
    {
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);
        
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $todos = TodoList::with(['user', 'assignedUser'])
            ->where(function($q) {
                $q->where('user_id', auth()->id())
                  ->orWhere('assigned_to', auth()->id());
            })
            ->whereBetween('due_date', [$startDate, $endDate])
            ->orderBy('due_date')
            ->orderBy('due_time')
            ->get()
            ->groupBy(function($todo) {
                return $todo->due_date->format('Y-m-d');
            });

        return response()->json($todos);
    }
}
