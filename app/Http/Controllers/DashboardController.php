<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Mitra;
use App\Models\Brand;
use App\Models\Label;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Validate date inputs
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Basic Statistics
        $userStats = [
            'total' => User::count(),
            'super_admin' => User::where('role', 'super_admin')->count(),
            'admin' => User::where('role', 'admin')->count(),
            'marketing' => User::where('role', 'marketing')->count(),
        ];

        $mitraStats = [
            'total' => Mitra::count(),
            'masuk' => Mitra::where('chat', 'masuk')->count(),
            'followup' => Mitra::where('chat', 'followup')->count(),
            'today' => Mitra::whereDate('tanggal_lead', Carbon::today())->count(),
            'this_week' => Mitra::whereBetween('tanggal_lead', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])->count(),
            'this_month' => Mitra::whereMonth('tanggal_lead', Carbon::now()->month)
                           ->whereYear('tanggal_lead', Carbon::now()->year)->count(),
        ];

        $brandStats = [
            'total' => Brand::count(),
            'with_logo' => Brand::whereNotNull('logo')->count(),
        ];

        $labelStats = [
            'total' => Label::count(),
        ];

        // Chat Analytics per Marketing (Harian)
        $chatAnalytics = $this->getChatAnalytics();

        // Chat Analytics per Marketing (Periode)
        $periodAnalytics = $this->getPeriodAnalytics($request);

        // Label Distribution
        $labelDistribution = $this->getLabelDistribution();

        // Closing Rate Analysis
        $closingAnalysis = $this->getClosingAnalysis();

        // Daily Chat Trends (Last 30 days)
        $dailyTrends = $this->getDailyTrends();

        // Top Performing Marketing
        $topMarketing = $this->getTopMarketing();

        // Brand Performance
        $brandPerformance = $this->getBrandPerformance();

        // Recent Activities
        $recentActivities = Mitra::with(['brand', 'label', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

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
        ]);
    }

    private function getChatAnalytics()
    {
        return User::where('role', 'marketing')
            ->withCount([
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

    private function getPeriodAnalytics($request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        return User::where('role', 'marketing')
            ->withCount([
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

    private function getLabelDistribution()
    {
        $totalMitras = Mitra::count();
        
        return Label::withCount('mitras')
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

    private function getClosingAnalysis()
    {
        $totalLeads = Mitra::count();
        $closedLeads = Mitra::where('chat', 'followup')->count();
        $openLeads = Mitra::where('chat', 'masuk')->count();

        return [
            'total_leads' => $totalLeads,
            'closed_leads' => $closedLeads,
            'open_leads' => $openLeads,
            'closing_rate' => $totalLeads > 0 ? round(($closedLeads / $totalLeads) * 100, 2) : 0,
            'by_marketing' => User::where('role', 'marketing')
                ->withCount([
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

    private function getDailyTrends()
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        
        return Mitra::select(
                DB::raw('DATE(tanggal_lead) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN chat = "masuk" THEN 1 ELSE 0 END) as masuk'),
                DB::raw('SUM(CASE WHEN chat = "followup" THEN 1 ELSE 0 END) as followup')
            )
            ->where('tanggal_lead', '>=', $thirtyDaysAgo)
            ->groupBy('date')
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

    private function getTopMarketing()
    {
        return User::where('role', 'marketing')
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

    private function getBrandPerformance()
    {
        return Brand::withCount([
                'mitras as total_leads',
                'mitras as closed_leads' => function ($query) {
                    $query->where('chat', 'followup');
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
}
