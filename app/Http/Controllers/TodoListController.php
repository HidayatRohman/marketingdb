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
        
        $query = TodoList::with(['user', 'assignedUser']);
        
        // Role-based access control
        if (auth()->user()->isSuperAdmin()) {
            // Super Admin can see all todos
            // No additional where clause needed
        } else {
            // Regular users can only see todos they created or are assigned to
            $query->where(function($q) {
                $q->where('user_id', auth()->id())
                  ->orWhere('assigned_to', auth()->id());
            });
        }

        // Apply filters
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        if ($priority !== 'all') {
            $query->where('priority', $priority);
        }
        
        if ($assigned !== 'all') {
            if ($assigned === 'me') {
                if (auth()->user()->isSuperAdmin()) {
                    // For Super Admin, "me" doesn't make sense, so skip this filter
                } else {
                    $query->where(function($q) {
                        $q->where('user_id', auth()->id())
                          ->orWhereNull('assigned_to');
                    });
                }
            } elseif ($assigned === 'others') {
                $query->where('assigned_to', '!=', null);
                if (!auth()->user()->isSuperAdmin()) {
                    $query->where('user_id', '!=', auth()->id());
                }
            } elseif ($assigned === 'unassigned') {
                // Only for Super Admin - todos that are not assigned to anyone
                if (auth()->user()->isSuperAdmin()) {
                    $query->whereNull('assigned_to');
                }
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
        } elseif ($view === 'board') {
            // For board view, get todos for the entire week
            $startOfWeek = Carbon::parse($selectedDate)->startOfWeek(Carbon::MONDAY);
            $endOfWeek = Carbon::parse($selectedDate)->endOfWeek(Carbon::SUNDAY);
            
            $todos = $query->where(function($q) use ($startOfWeek, $endOfWeek) {
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
        } else {
            // For list view
            $hasFilters = ($status !== 'all' || $priority !== 'all' || $assigned !== 'all' || $user !== 'all' || !empty($search));
            
            if (auth()->user()->isSuperAdmin() && $hasFilters) {
                // Super Admin with filters can see all todos regardless of date
                $todos = $query->orderBy('due_date')
                              ->orderBy('due_time') 
                              ->orderBy('priority')
                              ->get();
            } else {
                // For all other cases (regular users or Super Admin without filters), filter by selected date
                $todos = $query->whereDate('due_date', $selectedDate)
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
                'total' => $this->getTotalTodosCount(),
                'completed' => $this->getCompletedTodosCount(),
                'pending' => $this->getPendingTodosCount(),
                'overdue' => $this->getOverdueTodosCount(),
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
            'start_date' => 'nullable|date',
            'due_date' => 'required|date|after_or_equal:start_date',
            'due_time' => 'nullable|date_format:H:i',
            'assigned_to' => 'nullable|exists:users,id',
            'tags' => 'nullable|array',
        ]);

        $todo = TodoList::create([
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'status' => $request->status,
            'start_date' => $request->start_date,
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

    /**
     * Get base query for role-based access
     */
    private function getBaseStatsQuery()
    {
        $query = TodoList::query();
        
        if (auth()->user()->isSuperAdmin()) {
            // Super Admin can see all todos
            return $query;
        } else {
            // Regular users can only see todos they created or are assigned to
            return $query->where(function($q) {
                $q->where('user_id', auth()->id())
                  ->orWhere('assigned_to', auth()->id());
            });
        }
    }

    /**
     * Get total todos count based on user role
     */
    private function getTotalTodosCount(): int
    {
        return $this->getBaseStatsQuery()->count();
    }

    /**
     * Get completed todos count based on user role
     */
    private function getCompletedTodosCount(): int
    {
        return $this->getBaseStatsQuery()->where('status', 'completed')->count();
    }

    /**
     * Get pending todos count based on user role
     */
    private function getPendingTodosCount(): int
    {
        return $this->getBaseStatsQuery()->where('status', 'pending')->count();
    }

    /**
     * Get overdue todos count based on user role
     */
    private function getOverdueTodosCount(): int
    {
        return $this->getBaseStatsQuery()
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now()->format('Y-m-d'))
            ->count();
    }
}
