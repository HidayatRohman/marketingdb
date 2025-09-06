<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Mitra;
use App\Models\Brand;
use App\Models\Label;
use App\Models\TodoList;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = auth()->user();
        
        // Validate date inputs
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Basic Statistics - role-based
        $userStats = [];
        $mitraStats = [];
        $brandStats = [];
        $labelStats = [];

        if ($currentUser->hasFullAccess() || $currentUser->hasReadOnlyAccess()) {
            // Super Admin and Admin can see all statistics
            $userStats = [
                'total' => User::count(),
                'super_admin' => User::where('role', 'super_admin')->count(),
                'admin' => User::where('role', 'admin')->count(),
                'marketing' => User::where('role', 'marketing')->count(),
            ];

            $mitraQuery = Mitra::query();
        } else {
            // Marketing can only see their own statistics
            $userStats = [
                'total' => 1, // Only themselves
                'super_admin' => 0,
                'admin' => 0,
                'marketing' => 1,
            ];

            $mitraQuery = Mitra::where('user_id', $currentUser->id);
        }

        $mitraStats = [
            'total' => $mitraQuery->count(),
            'masuk' => (clone $mitraQuery)->where('chat', 'masuk')->count(),
            'followup' => (clone $mitraQuery)->where('chat', 'followup')->count(),
            'today' => (clone $mitraQuery)->whereDate('tanggal_lead', Carbon::today())->count(),
            'this_week' => (clone $mitraQuery)->whereBetween('tanggal_lead', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])->count(),
            'this_month' => (clone $mitraQuery)->whereMonth('tanggal_lead', Carbon::now()->month)
                           ->whereYear('tanggal_lead', Carbon::now()->year)->count(),
        ];

        if ($currentUser->hasFullAccess() || $currentUser->hasReadOnlyAccess()) {
            $brandStats = [
                'total' => Brand::count(),
                'with_logo' => Brand::whereNotNull('logo')->count(),
            ];

            $labelStats = [
                'total' => Label::count(),
            ];
        }

        // Chat Analytics per Marketing (Harian)
        $chatAnalytics = $this->getChatAnalytics($currentUser);

        // Chat Analytics per Marketing (Periode)
        $periodAnalytics = $this->getPeriodAnalytics($request, $currentUser);

        // Label Distribution
        $labelDistribution = $this->getLabelDistribution($currentUser);

        // Closing Rate Analysis
        $closingAnalysis = $this->getClosingAnalysis($currentUser);

        // Daily Chat Trends (Last 30 days)
        $dailyTrends = $this->getDailyTrends($currentUser);

        // Top Performing Marketing
        $topMarketing = $this->getTopMarketing($currentUser);

        // Brand Performance
        $brandPerformance = $this->getBrandPerformance($currentUser);

        // Recent Activities
        $recentActivitiesQuery = Mitra::with(['brand', 'label', 'user']);
        if ($currentUser->hasLimitedAccess()) {
            $recentActivitiesQuery->where('user_id', $currentUser->id);
        }
        $recentActivities = $recentActivitiesQuery->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Task Management Statistics
        $taskStats = $this->getTaskStatistics($currentUser);

        return Inertia::render('Dashboard', [
            'userStats' => $userStats,
            'mitraStats' => $mitraStats,
            'brandStats' => $brandStats,
            'labelStats' => $labelStats,
            'chatAnalytics' => $chatAnalytics,
            'periodAnalytics' => $periodAnalytics,
            'labelDistribution' => $labelDistribution,
            'closingAnalysis' => $closingAnalysis,
            'dailyTrends' => $dailyTrends,
            'topMarketing' => $topMarketing,
            'brandPerformance' => $brandPerformance,
            'recentActivities' => $recentActivities,
            'taskStats' => $taskStats,
            'permissions' => [
                'canCrud' => $currentUser->canCrud(),
                'canOnlyView' => $currentUser->canOnlyView(),
                'canOnlyViewOwn' => $currentUser->canOnlyViewOwn(),
            ],
        ]);
    }

    private function getChatAnalytics($currentUser)
    {
        $query = User::where('role', 'marketing');
        
        if ($currentUser->hasLimitedAccess()) {
            $query->where('id', $currentUser->id);
        }

        return $query->withCount([
                'mitras as total_leads',
                'mitras as today_leads' => function ($query) {
                    $query->whereDate('tanggal_lead', Carbon::today());
                },
                'mitras as masuk_leads' => function ($query) {
                    $query->where('chat', 'masuk');
                },
                'mitras as followup_leads' => function ($query) {
                    $query->where('chat', 'followup');
                }
            ])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'total_leads' => $user->total_leads,
                    'today_leads' => $user->today_leads,
                    'masuk_leads' => $user->masuk_leads,
                    'followup_leads' => $user->followup_leads,
                    'conversion_rate' => $user->total_leads > 0 ? 
                        round(($user->followup_leads / $user->total_leads) * 100, 2) : 0,
                ];
            });
    }

    private function getPeriodAnalytics($request, $currentUser)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $query = User::where('role', 'marketing');
        
        if ($currentUser->hasLimitedAccess()) {
            $query->where('id', $currentUser->id);
        }

        return $query->withCount([
                'mitras as period_total' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('tanggal_lead', [$startDate, $endDate]);
                },
                'mitras as period_masuk' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('tanggal_lead', [$startDate, $endDate])
                          ->where('chat', 'masuk');
                },
                'mitras as period_followup' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('tanggal_lead', [$startDate, $endDate])
                          ->where('chat', 'followup');
                }
            ])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'period_total' => $user->period_total,
                    'period_masuk' => $user->period_masuk,
                    'period_followup' => $user->period_followup,
                    'period_conversion_rate' => $user->period_total > 0 ? 
                        round(($user->period_followup / $user->period_total) * 100, 2) : 0,
                ];
            });
    }

    private function getLabelDistribution($currentUser)
    {
        $mitraQuery = Mitra::query();
        if ($currentUser->hasLimitedAccess()) {
            $mitraQuery->where('user_id', $currentUser->id);
        }
        $totalMitras = $mitraQuery->count();
        
        $labelQuery = Label::query();
        
        return $labelQuery->withCount(['mitras' => function ($query) use ($currentUser) {
                if ($currentUser->hasLimitedAccess()) {
                    $query->where('user_id', $currentUser->id);
                }
            }])
            ->get()
            ->map(function ($label) use ($totalMitras) {
                return [
                    'id' => $label->id,
                    'nama' => $label->nama,
                    'warna' => $label->warna,
                    'count' => $label->mitras_count,
                    'percentage' => $totalMitras > 0 ? 
                        round(($label->mitras_count / $totalMitras) * 100, 2) : 0,
                ];
            })
            ->sortByDesc('count')
            ->values();
    }

    private function getClosingAnalysis($currentUser)
    {
        $mitraQuery = Mitra::query();
        if ($currentUser->hasLimitedAccess()) {
            $mitraQuery->where('user_id', $currentUser->id);
        }
        
        $totalLeads = $mitraQuery->count();
        $closedLeads = (clone $mitraQuery)->where('chat', 'followup')->count();
        $openLeads = (clone $mitraQuery)->where('chat', 'masuk')->count();

        $marketingQuery = User::where('role', 'marketing');
        if ($currentUser->hasLimitedAccess()) {
            $marketingQuery->where('id', $currentUser->id);
        }

        return [
            'total_leads' => $totalLeads,
            'closed_leads' => $closedLeads,
            'open_leads' => $openLeads,
            'closing_rate' => $totalLeads > 0 ? round(($closedLeads / $totalLeads) * 100, 2) : 0,
            'by_marketing' => $marketingQuery->withCount([
                    'mitras as total',
                    'mitras as closed' => function ($query) {
                        $query->where('chat', 'followup');
                    }
                ])
                ->get()
                ->map(function ($user) {
                    return [
                        'name' => $user->name,
                        'total' => $user->total,
                        'closed' => $user->closed,
                        'rate' => $user->total > 0 ? round(($user->closed / $user->total) * 100, 2) : 0,
                    ];
                })
                ->sortByDesc('rate')
                ->values(),
        ];
    }

    private function getDailyTrends($currentUser)
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        
        $query = Mitra::select(
                DB::raw('DATE(tanggal_lead) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN chat = "masuk" THEN 1 ELSE 0 END) as masuk'),
                DB::raw('SUM(CASE WHEN chat = "followup" THEN 1 ELSE 0 END) as followup')
            )
            ->where('tanggal_lead', '>=', $thirtyDaysAgo);
            
        if ($currentUser->hasLimitedAccess()) {
            $query->where('user_id', $currentUser->id);
        }
            
        return $query->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('Y-m-d'),
                    'date_formatted' => Carbon::parse($item->date)->format('M d'),
                    'total' => $item->total,
                    'masuk' => $item->masuk,
                    'followup' => $item->followup,
                ];
            });
    }

    private function getTopMarketing($currentUser)
    {
        $query = User::where('role', 'marketing');
        
        if ($currentUser->hasLimitedAccess()) {
            $query->where('id', $currentUser->id);
        }
        
        return $query
            ->withCount([
                'mitras as total_leads',
                'mitras as closed_leads' => function ($query) {
                    $query->where('chat', 'followup');
                }
            ])
            ->having('total_leads', '>', 0)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'total_leads' => $user->total_leads,
                    'closed_leads' => $user->closed_leads,
                    'closing_rate' => round(($user->closed_leads / $user->total_leads) * 100, 2),
                ];
            })
            ->sortByDesc('closing_rate')
            ->take(5)
            ->values();
    }

    private function getBrandPerformance($currentUser)
    {
        return Brand::withCount([
                'mitras as total_leads' => function ($query) use ($currentUser) {
                    if ($currentUser->hasLimitedAccess()) {
                        $query->where('user_id', $currentUser->id);
                    }
                },
                'mitras as closed_leads' => function ($query) use ($currentUser) {
                    $query->where('chat', 'followup');
                    if ($currentUser->hasLimitedAccess()) {
                        $query->where('user_id', $currentUser->id);
                    }
                }
            ])
            ->having('total_leads', '>', 0)
            ->get()
            ->map(function ($brand) {
                return [
                    'id' => $brand->id,
                    'nama' => $brand->nama,
                    'logo_url' => $brand->logo_url,
                    'total_leads' => $brand->total_leads,
                    'closed_leads' => $brand->closed_leads,
                    'closing_rate' => round(($brand->closed_leads / $brand->total_leads) * 100, 2),
                ];
            })
            ->sortByDesc('total_leads')
            ->values();
    }

    private function getTaskStatistics($currentUser)
    {
        // Base query for tasks
        $baseQuery = TodoList::query();
        
        if ($currentUser->hasLimitedAccess()) {
            // Marketing users can only see their own tasks and tasks assigned to them
            $baseQuery->where(function ($query) use ($currentUser) {
                $query->where('user_id', $currentUser->id)
                      ->orWhere('assigned_to', $currentUser->id);
            });
        }

        // Overall task statistics
        $overallStats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
            'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
            'overdue' => (clone $baseQuery)->where('status', '!=', 'completed')
                        ->where('due_date', '<', Carbon::today())->count(),
        ];

        // Task statistics per marketing user
        $marketingQuery = User::where('role', 'marketing');
        
        if ($currentUser->hasLimitedAccess()) {
            $marketingQuery->where('id', $currentUser->id);
        }

        $marketingStats = $marketingQuery->withCount([
                // Tasks created by user
                'todoLists as created_total',
                'todoLists as created_pending' => function ($query) {
                    $query->where('status', 'pending');
                },
                'todoLists as created_in_progress' => function ($query) {
                    $query->where('status', 'in_progress');
                },
                'todoLists as created_completed' => function ($query) {
                    $query->where('status', 'completed');
                },
                // Tasks assigned to user
                'assignedTodoLists as assigned_total',
                'assignedTodoLists as assigned_pending' => function ($query) {
                    $query->where('status', 'pending');
                },
                'assignedTodoLists as assigned_in_progress' => function ($query) {
                    $query->where('status', 'in_progress');
                },
                'assignedTodoLists as assigned_completed' => function ($query) {
                    $query->where('status', 'completed');
                },
                'assignedTodoLists as assigned_overdue' => function ($query) {
                    $query->where('status', '!=', 'completed')
                          ->where('due_date', '<', Carbon::today());
                }
            ])
            ->get()
            ->map(function ($user) {
                $totalTasks = $user->created_total + $user->assigned_total;
                $completedTasks = $user->created_completed + $user->assigned_completed;
                $inProgressTasks = $user->created_in_progress + $user->assigned_in_progress;
                $pendingTasks = $user->created_pending + $user->assigned_pending;
                
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_total' => $user->created_total,
                    'created_pending' => $user->created_pending,
                    'created_in_progress' => $user->created_in_progress,
                    'created_completed' => $user->created_completed,
                    'assigned_total' => $user->assigned_total,
                    'assigned_pending' => $user->assigned_pending,
                    'assigned_in_progress' => $user->assigned_in_progress,
                    'assigned_completed' => $user->assigned_completed,
                    'assigned_overdue' => $user->assigned_overdue,
                    'total_tasks' => $totalTasks,
                    'pending_tasks' => $pendingTasks,
                    'in_progress_tasks' => $inProgressTasks,
                    'completed_tasks' => $completedTasks,
                    'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
                ];
            });

        return [
            'overall' => $overallStats,
            'by_marketing' => $marketingStats,
        ];
    }
}
