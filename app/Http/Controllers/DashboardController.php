<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Mitra;
use App\Models\Brand;
use App\Models\Label;
use App\Models\TodoList;
use App\Models\IklanBudget;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = auth()->user();
        // Redirect CS role to CS Repeat page as default landing
        if ($currentUser && $currentUser->role === 'cs') {
            return redirect()->route('cs-repeats.index');
        }
        
        // Validate date inputs
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'marketing' => 'nullable|exists:users,id',
            'brand' => 'nullable|exists:brands,id',
        ]);

        // Get filter parameters with default to current month
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $selectedMarketing = $request->get('marketing');
        $selectedBrand = $request->get('brand');
        
        // Set default to current month if no dates provided
        if (!$startDate && !$endDate) {
            $startDate = now()->startOfMonth()->format('Y-m-d');
            $endDate = now()->endOfMonth()->format('Y-m-d');
        }

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

        // Apply filters to mitra query
        if ($startDate && $endDate) {
            $mitraQuery->whereBetween('tanggal_lead', [$startDate, $endDate]);
        }

        if ($selectedMarketing) {
            $mitraQuery->where('user_id', $selectedMarketing);
        }

        if ($selectedBrand) {
            $mitraQuery->where('brand_id', $selectedBrand);
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
        $chatAnalytics = $this->getChatAnalytics($currentUser, $request);

        // Chat Analytics per Marketing (Periode)
        $periodAnalytics = $this->getPeriodAnalytics($request, $currentUser);

        // Label Distribution
        $labelDistribution = $this->getLabelDistribution($currentUser, $request);

        // Closing Rate Analysis
        $closingAnalysis = $this->getClosingAnalysis($currentUser, $request);

        // Daily Chat Trends (Last 30 days)
        $dailyTrends = $this->getDailyTrends($currentUser, $request);

        // Top Performing Marketing
        $topMarketing = $this->getTopMarketing($currentUser, $request);

        // Brand Performance
        $brandPerformance = $this->getBrandPerformance($currentUser, $request);

        // Recent Activities
        $recentActivitiesQuery = Mitra::with(['brand', 'label', 'user']);
        if ($currentUser->hasLimitedAccess()) {
            $recentActivitiesQuery->where('user_id', $currentUser->id);
        }
        
        // Apply filters to recent activities
        if ($startDate && $endDate) {
            $recentActivitiesQuery->whereBetween('tanggal_lead', [$startDate, $endDate]);
        }
        if ($selectedMarketing) {
            $recentActivitiesQuery->where('user_id', $selectedMarketing);
        }
        if ($selectedBrand) {
            $recentActivitiesQuery->where('brand_id', $selectedBrand);
        }
        
        $recentActivities = $recentActivitiesQuery->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Task Management Statistics
        $taskStats = $this->getTaskStatistics($currentUser);

        // Summary Report
        $summaryReport = $this->getSummaryReport($currentUser, $request);

        // Get data for filter dropdowns
        $marketingUsers = [];
        $brands = [];
        
        if ($currentUser->hasFullAccess() || $currentUser->hasReadOnlyAccess()) {
            $marketingUsers = User::where('role', 'marketing')
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
            $brands = Brand::select('id', 'nama')
                ->orderBy('nama')
                ->get();
        } else {
            // Marketing users only see themselves
            $marketingUsers = collect([
                (object)['id' => $currentUser->id, 'name' => $currentUser->name]
            ]);
            $brands = Brand::select('id', 'nama')
                ->orderBy('nama')
                ->get();
        }

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
            'summaryReport' => $summaryReport,
            'marketingUsers' => $marketingUsers,
            'brands' => $brands,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'marketing' => $selectedMarketing,
                'brand' => $selectedBrand,
            ],
            'permissions' => [
                'canCrud' => $currentUser->canCrud(),
                'canOnlyView' => $currentUser->canOnlyView(),
                'canOnlyViewOwn' => $currentUser->canOnlyViewOwn(),
                'hasFullAccess' => $currentUser->hasFullAccess(),
                'hasReadOnlyAccess' => $currentUser->hasReadOnlyAccess(),
                'hasLimitedAccess' => $currentUser->hasLimitedAccess(),
            ],
        ]);
    }

    /**
     * Business analytics page: brand and marketing performance with filters.
     */
    public function businessAnalytics(Request $request)
    {
        $currentUser = auth()->user();

        // Validate date inputs and optional filters
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'marketing' => 'nullable|exists:users,id',
            'brand' => 'nullable|exists:brands,id',
        ]);

        // Get filter parameters with default to current month
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $selectedMarketing = $request->get('marketing');
        $selectedBrand = $request->get('brand');

        if (!$startDate && !$endDate) {
            $startDate = now()->startOfMonth()->format('Y-m-d');
            $endDate = now()->endOfMonth()->format('Y-m-d');
        }

        // Compute analytics using existing helpers
        $topMarketing = $this->getTopMarketing($currentUser, $request);
        $brandPerformance = $this->getBrandPerformance($currentUser, $request);

        // Get data for filter dropdowns
        $marketingUsers = [];
        $brands = [];
        if ($currentUser->hasFullAccess() || $currentUser->hasReadOnlyAccess()) {
            $marketingUsers = User::where('role', 'marketing')
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
            $brands = Brand::select('id', 'nama')
                ->orderBy('nama')
                ->get();
        } else {
            $marketingUsers = collect([
                (object)['id' => $currentUser->id, 'name' => $currentUser->name]
            ]);
            $brands = Brand::select('id', 'nama')
                ->orderBy('nama')
                ->get();
        }

        return Inertia::render('AnalisaBisnis/Index', [
            'topMarketing' => $topMarketing,
            'brandPerformance' => $brandPerformance,
            'marketingUsers' => $marketingUsers,
            'brands' => $brands,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'marketing' => $selectedMarketing,
                'brand' => $selectedBrand,
            ],
            'permissions' => [
                'canCrud' => $currentUser->canCrud(),
                'canOnlyView' => $currentUser->canOnlyView(),
                'canOnlyViewOwn' => $currentUser->canOnlyViewOwn(),
                'hasFullAccess' => $currentUser->hasFullAccess(),
                'hasReadOnlyAccess' => $currentUser->hasReadOnlyAccess(),
                'hasLimitedAccess' => $currentUser->hasLimitedAccess(),
            ],
        ]);
    }

    private function getChatAnalytics($currentUser, $request = null)
    {
        $startDate = $request ? $request->get('start_date') : null;
        $endDate = $request ? $request->get('end_date') : null;
        $selectedMarketing = $request ? $request->get('marketing') : null;
        $selectedBrand = $request ? $request->get('brand') : null;
        
        $query = User::where('role', 'marketing');
        
        if ($currentUser->hasLimitedAccess()) {
            $query->where('id', $currentUser->id);
        }
        
        if ($selectedMarketing) {
            $query->where('id', $selectedMarketing);
        }

        return $query->withCount([
                'mitras as total_leads' => function ($query) use ($startDate, $endDate, $selectedBrand) {
                    if ($startDate && $endDate) {
                        $query->whereBetween('tanggal_lead', [$startDate, $endDate]);
                    }
                    if ($selectedBrand) {
                        $query->where('brand_id', $selectedBrand);
                    }
                },
                'mitras as today_leads' => function ($query) use ($selectedBrand) {
                    $query->whereDate('tanggal_lead', Carbon::today());
                    if ($selectedBrand) {
                        $query->where('brand_id', $selectedBrand);
                    }
                },
                'mitras as masuk_leads' => function ($query) use ($startDate, $endDate, $selectedBrand) {
                    $query->where('chat', 'masuk');
                    if ($startDate && $endDate) {
                        $query->whereBetween('tanggal_lead', [$startDate, $endDate]);
                    }
                    if ($selectedBrand) {
                        $query->where('brand_id', $selectedBrand);
                    }
                },
                'mitras as followup_leads' => function ($query) use ($startDate, $endDate, $selectedBrand) {
                    $query->where('chat', 'followup');
                    if ($startDate && $endDate) {
                        $query->whereBetween('tanggal_lead', [$startDate, $endDate]);
                    }
                    if ($selectedBrand) {
                        $query->where('brand_id', $selectedBrand);
                    }
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

    private function getLabelDistribution($currentUser, $request = null)
    {
        $startDate = $request ? $request->get('start_date') : null;
        $endDate = $request ? $request->get('end_date') : null;
        $selectedMarketing = $request ? $request->get('marketing') : null;
        $selectedBrand = $request ? $request->get('brand') : null;
        
        $mitraQuery = Mitra::query();
        if ($currentUser->hasLimitedAccess()) {
            $mitraQuery->where('user_id', $currentUser->id);
        }
        
        // Apply filters to get total count
        if ($startDate && $endDate) {
            $mitraQuery->whereBetween('tanggal_lead', [$startDate, $endDate]);
        }
        if ($selectedMarketing) {
            $mitraQuery->where('user_id', $selectedMarketing);
        }
        if ($selectedBrand) {
            $mitraQuery->where('brand_id', $selectedBrand);
        }
        
        $totalMitras = $mitraQuery->count();
        
        $labelQuery = Label::query();
        
        return $labelQuery->withCount(['mitras' => function ($query) use ($currentUser, $startDate, $endDate, $selectedMarketing, $selectedBrand) {
                if ($currentUser->hasLimitedAccess()) {
                    $query->where('user_id', $currentUser->id);
                }
                if ($startDate && $endDate) {
                    $query->whereBetween('tanggal_lead', [$startDate, $endDate]);
                }
                if ($selectedMarketing) {
                    $query->where('user_id', $selectedMarketing);
                }
                if ($selectedBrand) {
                    $query->where('brand_id', $selectedBrand);
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

    private function getClosingAnalysis($currentUser, $request = null)
    {
        $startDate = $request ? $request->get('start_date') : null;
        $endDate = $request ? $request->get('end_date') : null;
        $selectedMarketing = $request ? $request->get('marketing') : null;
        $selectedBrand = $request ? $request->get('brand') : null;
        
        $mitraQuery = Mitra::query();
        if ($currentUser->hasLimitedAccess()) {
            $mitraQuery->where('user_id', $currentUser->id);
        }
        
        // Apply filters
        if ($startDate && $endDate) {
            $mitraQuery->whereBetween('tanggal_lead', [$startDate, $endDate]);
        }
        if ($selectedMarketing) {
            $mitraQuery->where('user_id', $selectedMarketing);
        }
        if ($selectedBrand) {
            $mitraQuery->where('brand_id', $selectedBrand);
        }
        
        $totalLeads = $mitraQuery->count();
        
        // Get closing label ID
        $closingLabel = \App\Models\Label::where('nama', 'Closing')->first();
        $closedLeads = $closingLabel ? (clone $mitraQuery)->where('label_id', $closingLabel->id)->count() : 0;
        
        $openLeads = (clone $mitraQuery)->where('chat', 'masuk')->count();

        $marketingQuery = User::where('role', 'marketing');
        if ($currentUser->hasLimitedAccess()) {
            $marketingQuery->where('id', $currentUser->id);
        }
        if ($selectedMarketing) {
            $marketingQuery->where('id', $selectedMarketing);
        }

        return [
            'total_leads' => $totalLeads,
            'closed_leads' => $closedLeads,
            'open_leads' => $openLeads,
            'closing_rate' => $totalLeads > 0 ? round(($closedLeads / $totalLeads) * 100, 2) : 0,
            'by_marketing' => $marketingQuery->withCount([
                    'mitras as total' => function ($query) use ($startDate, $endDate, $selectedBrand) {
                        if ($startDate && $endDate) {
                            $query->whereBetween('tanggal_lead', [$startDate, $endDate]);
                        }
                        if ($selectedBrand) {
                            $query->where('brand_id', $selectedBrand);
                        }
                    },
                    'mitras as closed' => function ($query) use ($startDate, $endDate, $selectedBrand) {
                        // Get closing label ID
                        $closingLabel = \App\Models\Label::where('nama', 'Closing')->first();
                        if ($closingLabel) {
                            $query->where('label_id', $closingLabel->id);
                        } else {
                            $query->whereRaw('1 = 0'); // No results if closing label doesn't exist
                        }
                        if ($startDate && $endDate) {
                            $query->whereBetween('tanggal_lead', [$startDate, $endDate]);
                        }
                        if ($selectedBrand) {
                            $query->where('brand_id', $selectedBrand);
                        }
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

    private function getDailyTrends($currentUser, $request = null)
    {
        $startDate = $request ? $request->get('start_date') : null;
        $endDate = $request ? $request->get('end_date') : null;
        $selectedMarketing = $request ? $request->get('marketing') : null;
        $selectedBrand = $request ? $request->get('brand') : null;
        
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
        
        // Apply filters
        if ($startDate && $endDate) {
            $query->whereBetween('tanggal_lead', [$startDate, $endDate]);
        }
        if ($selectedMarketing) {
            $query->where('user_id', $selectedMarketing);
        }
        if ($selectedBrand) {
            $query->where('brand_id', $selectedBrand);
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

    private function getTopMarketing($currentUser, $request = null)
    {
        $startDate = $request ? $request->get('start_date') : null;
        $endDate = $request ? $request->get('end_date') : null;
        $selectedMarketing = $request ? $request->get('marketing') : null;
        $selectedBrand = $request ? $request->get('brand') : null;
        
        $query = User::where('role', 'marketing');
        
        if ($currentUser->hasLimitedAccess()) {
            $query->where('id', $currentUser->id);
        }
        if ($selectedMarketing) {
            $query->where('id', $selectedMarketing);
        }
        
        return $query
            ->whereHas('mitras', function ($mitraQuery) use ($startDate, $endDate, $selectedBrand) {
                if ($startDate && $endDate) {
                    $mitraQuery->whereBetween('tanggal_lead', [$startDate, $endDate]);
                }
                if ($selectedBrand) {
                    $mitraQuery->where('brand_id', $selectedBrand);
                }
            })
            ->withCount([
                'mitras as total_leads' => function ($query) use ($startDate, $endDate, $selectedBrand) {
                    if ($startDate && $endDate) {
                        $query->whereBetween('tanggal_lead', [$startDate, $endDate]);
                    }
                    if ($selectedBrand) {
                        $query->where('brand_id', $selectedBrand);
                    }
                },
                'mitras as closed_leads' => function ($query) use ($startDate, $endDate, $selectedBrand) {
                    // Get closing label ID
                    $closingLabel = \App\Models\Label::where('nama', 'Closing')->first();
                    if ($closingLabel) {
                        $query->where('label_id', $closingLabel->id);
                    } else {
                        $query->whereRaw('1 = 0'); // No results if closing label doesn't exist
                    }
                    if ($startDate && $endDate) {
                        $query->whereBetween('tanggal_lead', [$startDate, $endDate]);
                    }
                    if ($selectedBrand) {
                        $query->where('brand_id', $selectedBrand);
                    }
                }
            ])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'total_leads' => $user->total_leads,
                    'closed_leads' => $user->closed_leads,
                    'closing_rate' => $user->total_leads > 0 ? 
                        round(($user->closed_leads / $user->total_leads) * 100, 2) : 0,
                ];
            })
            ->sortByDesc('closing_rate')
            ->take(5)
            ->values();
    }

    private function getBrandPerformance($currentUser, $request = null)
    {
        $startDate = $request ? $request->get('start_date') : null;
        $endDate = $request ? $request->get('end_date') : null;
        $selectedMarketing = $request ? $request->get('marketing') : null;
        $selectedBrand = $request ? $request->get('brand') : null;
        
        $query = Brand::whereHas('mitras', function ($mitraQuery) use ($currentUser, $startDate, $endDate, $selectedMarketing) {
                if ($currentUser->hasLimitedAccess()) {
                    $mitraQuery->where('user_id', $currentUser->id);
                }
                if ($startDate && $endDate) {
                    $mitraQuery->whereBetween('tanggal_lead', [$startDate, $endDate]);
                }
                if ($selectedMarketing) {
                    $mitraQuery->where('user_id', $selectedMarketing);
                }
            });
            
        if ($selectedBrand) {
            $query->where('id', $selectedBrand);
        }
            
        return $query->withCount([
                'mitras as total_leads' => function ($query) use ($currentUser, $startDate, $endDate, $selectedMarketing) {
                    if ($currentUser->hasLimitedAccess()) {
                        $query->where('user_id', $currentUser->id);
                    }
                    if ($startDate && $endDate) {
                        $query->whereBetween('tanggal_lead', [$startDate, $endDate]);
                    }
                    if ($selectedMarketing) {
                        $query->where('user_id', $selectedMarketing);
                    }
                },
                'mitras as closed_leads' => function ($query) use ($currentUser, $startDate, $endDate, $selectedMarketing) {
                    // Get closing label ID
                    $closingLabel = \App\Models\Label::where('nama', 'Closing')->first();
                    if ($closingLabel) {
                        $query->where('label_id', $closingLabel->id);
                    } else {
                        $query->whereRaw('1 = 0'); // No results if closing label doesn't exist
                    }
                    if ($currentUser->hasLimitedAccess()) {
                        $query->where('user_id', $currentUser->id);
                    }
                    if ($startDate && $endDate) {
                        $query->whereBetween('tanggal_lead', [$startDate, $endDate]);
                    }
                    if ($selectedMarketing) {
                        $query->where('user_id', $selectedMarketing);
                    }
                }
            ])
            ->get()
            ->map(function ($brand) {
                return [
                    'id' => $brand->id,
                    'nama' => $brand->nama,
                    'logo_url' => $brand->logo_url,
                    'total_leads' => $brand->total_leads,
                    'closed_leads' => $brand->closed_leads,
                    'closing_rate' => $brand->total_leads > 0 ? 
                        round(($brand->closed_leads / $brand->total_leads) * 100, 2) : 0,
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

        // Task statistics per marketing user - using the same logic as TaskManagementController
        $marketingQuery = User::where('role', 'marketing');
        
        if ($currentUser->hasLimitedAccess()) {
            $marketingQuery->where('id', $currentUser->id);
        }

        $marketingStats = $marketingQuery->get()->map(function ($user) use ($currentUser) {
            // Build base query for this specific user with role-based access
            $userTasksQuery = TodoList::query();
            
            if ($currentUser->hasLimitedAccess()) {
                // If current user has limited access, apply the same restrictions
                $userTasksQuery->where(function ($query) use ($currentUser) {
                    $query->where('user_id', $currentUser->id)
                          ->orWhere('assigned_to', $currentUser->id);
                });
            }
            
            // Apply user filter - tasks where user is creator OR assigned to
            $userTasksQuery->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhere('assigned_to', $user->id);
            });

            // Get counts using the same logic as TaskManagementController
            $totalTasks = (clone $userTasksQuery)->count();
            $pendingTasks = (clone $userTasksQuery)->where('status', 'pending')->count();
            $inProgressTasks = (clone $userTasksQuery)->where('status', 'in_progress')->count();
            $completedTasks = (clone $userTasksQuery)->where('status', 'completed')->count();
            $overdueTasks = (clone $userTasksQuery)->where('status', '!=', 'completed')
                                                   ->where('due_date', '<', Carbon::today())->count();

            // Also get separate counts for created vs assigned for reference
            $createdTotal = TodoList::where('user_id', $user->id)->count();
            $createdPending = TodoList::where('user_id', $user->id)->where('status', 'pending')->count();
            $createdInProgress = TodoList::where('user_id', $user->id)->where('status', 'in_progress')->count();
            $createdCompleted = TodoList::where('user_id', $user->id)->where('status', 'completed')->count();
            
            $assignedTotal = TodoList::where('assigned_to', $user->id)->count();
            $assignedPending = TodoList::where('assigned_to', $user->id)->where('status', 'pending')->count();
            $assignedInProgress = TodoList::where('assigned_to', $user->id)->where('status', 'in_progress')->count();
            $assignedCompleted = TodoList::where('assigned_to', $user->id)->where('status', 'completed')->count();
            $assignedOverdue = TodoList::where('assigned_to', $user->id)
                                      ->where('status', '!=', 'completed')
                                      ->where('due_date', '<', Carbon::today())->count();
            
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_total' => $createdTotal,
                'created_pending' => $createdPending,
                'created_in_progress' => $createdInProgress,
                'created_completed' => $createdCompleted,
                'assigned_total' => $assignedTotal,
                'assigned_pending' => $assignedPending,
                'assigned_in_progress' => $assignedInProgress,
                'assigned_completed' => $assignedCompleted,
                'assigned_overdue' => $assignedOverdue,
                'total_tasks' => $totalTasks,
                'pending_tasks' => $pendingTasks,
                'in_progress_tasks' => $inProgressTasks,
                'completed_tasks' => $completedTasks,
                'overdue_tasks' => $overdueTasks, // Add this field for total overdue tasks
                'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
            ];
        });

        return [
            'overall' => $overallStats,
            'by_marketing' => $marketingStats,
        ];
    }

    private function getSummaryReport($currentUser, $request = null)
    {
        $ppnRate = (float) \App\Models\SiteSetting::get('ppn_rate', 11);
        $spentMultiplier = 1 + ($ppnRate / 100.0);
        // Get dates from request or use current month as default
        $startDate = $request && $request->get('start_date') ? $request->get('start_date') : Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $request && $request->get('end_date') ? $request->get('end_date') : Carbon::now()->endOfMonth()->format('Y-m-d');
        $selectedMarketing = $request ? $request->get('marketing') : null;
        $selectedBrand = $request ? $request->get('brand') : null;
        
        // Ensure dates are not empty
        if (empty($startDate)) {
            $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        }
        if (empty($endDate)) {
            $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        }

        // Build marketing filter condition for subqueries
        $marketingFilter = $selectedMarketing ? ' AND user_id = ' . $selectedMarketing : '';
        $userAccessFilter = $currentUser->hasLimitedAccess() ? ' AND user_id = ' . $currentUser->id : '';
        
        $query = Brand::select([
            'brands.id as brand_id',
            'brands.nama as brand_name',
            DB::raw('COALESCE(SUM(CASE WHEN iklan_budgets.tanggal BETWEEN "' . $startDate . '" AND "' . $endDate . '" THEN iklan_budgets.spent_amount END), 0) as spent'),
            DB::raw('COALESCE(SUM(CASE WHEN iklan_budgets.tanggal BETWEEN "' . $startDate . '" AND "' . $endDate . '" THEN iklan_budgets.spent_amount * ' . $spentMultiplier . ' END), 0) as spent_with_tax'),
            DB::raw('(
                SELECT COUNT(*) 
                FROM mitras 
                WHERE mitras.brand_id = brands.id 
                AND mitras.tanggal_lead BETWEEN "' . $startDate . '" AND "' . $endDate . '"
                ' . $marketingFilter . '
                ' . $userAccessFilter . '
            ) as real_lead'),
            DB::raw('(
                SELECT COUNT(*) 
                FROM transaksis 
                WHERE transaksis.lead_awal_brand_id = brands.id 
                AND transaksis.tanggal_tf BETWEEN "' . $startDate . '" AND "' . $endDate . '"
                AND transaksis.status_pembayaran = "Dp / TJ"
                ' . $marketingFilter . '
                ' . $userAccessFilter . '
            ) as closing'),
            DB::raw('(
                SELECT COALESCE(SUM(transaksis.nominal_masuk), 0) 
                FROM transaksis 
                WHERE transaksis.lead_awal_brand_id = brands.id 
                AND transaksis.tanggal_tf BETWEEN "' . $startDate . '" AND "' . $endDate . '"
                ' . $marketingFilter . '
                ' . $userAccessFilter . '
            ) as omset')
        ])
        ->leftJoin('iklan_budgets', 'brands.id', '=', 'iklan_budgets.brand_id');

        // Apply brand filter
        if ($selectedBrand) {
            $query->where('brands.id', $selectedBrand);
        }

        $results = $query->groupBy('brands.id', 'brands.nama')
            ->havingRaw('spent > 0 OR real_lead > 0 OR closing > 0')
            ->get();

        return $results->map(function ($item) {
            $costPerLead = $item->real_lead > 0 ? $item->spent / $item->real_lead : 0;
            $roas = $item->spent_with_tax > 0 ? $item->omset / $item->spent_with_tax : 0;
            
            return [
                'brand' => $item->brand_name,
                'spent' => (float) $item->spent,
                'spent_with_tax' => (float) $item->spent_with_tax,
                'real_lead' => (int) $item->real_lead,
                'cost_per_lead' => (float) $costPerLead,
                'closing' => (int) $item->closing,
                'omset' => (float) $item->omset,
                'roas' => round($roas, 2),
            ];
        });
    }
}
