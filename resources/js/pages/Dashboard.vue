<script setup lang="ts">
import BrandPerformanceChart from '@/components/BrandPerformanceChart.vue';
import MarketingPerformanceChart from '@/components/MarketingPerformanceChart.vue';
import CsRepeatDailyTransaksiChart from '@/components/CsRepeatDailyTransaksiChart.vue';
import CsRepeatDailyProductChart from '@/components/CsRepeatDailyProductChart.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress/index';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs/index';
import { DatePicker } from '@/components/ui/datepicker';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Activity,
    AlertCircle,
    ArrowDownRight,
    ArrowUpRight,
    Award,
    BarChart3,
    Briefcase,
    Building2,
    Calendar,
    CheckCircle,
    ChevronDown,
    Clock,
    Eye,
    Filter,
    MessageSquare,
    Phone,
    PieChart,
    RefreshCw,
    Settings,
    Shield,
    Tag,
    Target,
    TrendingUp,
    User,
    UserCheck,
    Users,
    X,
    Zap,
    DollarSign,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, Teleport, watch } from 'vue';
import { toLocalDateString } from '@/lib/utils';

interface UserStats {
    total: number;
    super_admin: number;
    admin: number;
    marketing: number;
}

interface MitraStats {
    total: number;
    masuk: number;
    followup: number;
    today: number;
    this_week: number;
    this_month: number;
}

interface BrandStats {
    total: number;
    with_logo: number;
}

interface LabelStats {
    total: number;
}

interface ChatAnalytic {
    id: number;
    name: string;
    total_leads: number;
    today_leads: number;
    masuk_leads: number;
    followup_leads: number;
    conversion_rate: number;
}

interface LabelDistribution {
    id: number;
    nama: string;
    warna: string;
    count: number;
    percentage: number;
}

interface ClosingAnalysis {
    total_leads: number;
    closed_leads: number;
    open_leads: number;
    closing_rate: number;
    by_marketing: Array<{
        name: string;
        total: number;
        closed: number;
        rate: number;
    }>;
}

interface DailyTrend {
    date: string;
    date_formatted: string;
    total: number;
    masuk: number;
    followup: number;
}

interface TopMarketing {
    id: number;
    name: string;
    email: string;
    total_leads: number;
    closed_leads: number;
    closing_rate: number;
}

// CS Repeat analytics types
interface CsRepeatDailyRow { date: string; total: number }
interface CsRepeatDailyProductRow { date: string; products: Record<string, number>; total: number }
interface CsRepeatSummary { totalOmset: number; jumlahTransaksi: number }

interface BrandPerformance {
    id: number;
    nama: string;
    logo_url: string | null;
    total_leads: number;
    closed_leads: number;
    closing_rate: number;
}

interface RecentActivity {
    id: number;
    nama: string;
    no_telp: string;
    tanggal_lead: string;
    chat: 'masuk' | 'followup';
    brand: { nama: string };
    label: { nama: string; warna: string } | null;
    user: { name: string } | null;
    created_at: string;
}

interface TaskOverallStats {
    total: number;
    pending: number;
    in_progress: number;
    completed: number;
    overdue: number;
}

interface TaskMarketingStats {
    id: number;
    name: string;
    email: string;
    created_total: number;
    created_pending: number;
    created_in_progress: number;
    created_completed: number;
    assigned_total: number;
    assigned_pending: number;
    assigned_in_progress: number;
    assigned_completed: number;
    assigned_overdue: number;
    total_tasks: number;
    pending_tasks: number;
    in_progress_tasks: number;
    completed_tasks: number;
    overdue_tasks: number;
    completion_rate: number;
}

interface TaskStats {
    overall: TaskOverallStats;
    by_marketing: TaskMarketingStats[];
}

interface MarketingUser {
    id: number;
    name: string;
}

interface Brand {
    id: number;
    nama: string;
}

interface Filters {
    start_date: string | null;
    end_date: string | null;
    marketing: string | null;
    brand: string | null;
}

interface SummaryReport {
    brand: string;
    spent: number;
    spent_with_tax: number;
    real_lead: number;
    cost_per_lead: number;
    closing: number;
    omset: number;
    roas: number;
}

interface Permissions {
    canCrud: boolean;
    canOnlyView: boolean;
    canOnlyViewOwn: boolean;
    hasFullAccess: boolean;
    hasReadOnlyAccess: boolean;
    hasLimitedAccess: boolean;
}

interface Props {
    userStats: UserStats;
    mitraStats: MitraStats;
    brandStats: BrandStats;
    labelStats: LabelStats;
    taskStats: TaskStats;
    chatAnalytics: ChatAnalytic[];
    periodAnalytics: ChatAnalytic[];
    labelDistribution: LabelDistribution[];
    closingAnalysis: ClosingAnalysis;
    dailyTrends: DailyTrend[];
    topMarketing: TopMarketing[];
    brandPerformance: BrandPerformance[];
    recentActivities: RecentActivity[];
    marketingUsers: MarketingUser[];
    brands: Brand[];
    filters: Filters;
    summaryReport: SummaryReport[];
    permissions: Permissions;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

// Refs for filters
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');
const selectedMarketing = ref(props.filters.marketing || 'all');
const selectedBrand = ref(props.filters.brand || 'all');
const selectedDateRange = ref('this_month');
const nowForFilter = new Date();
const defaultMonth = String(nowForFilter.getMonth() + 1).padStart(2, '0');
const defaultYear = String(nowForFilter.getFullYear());
const selectedMonth = ref((props.filters as any)?.month || defaultMonth)
const selectedYear = ref((props.filters as any)?.year || defaultYear);
const refreshing = ref(false);
const showMarketingDropdown = ref(false);
const showBrandDropdown = ref(false);
const isFilterExpanded = ref(false);
const hoveredLabel = ref<LabelDistribution | null>(null);
const tooltipPosition = ref({ x: 0, y: 0 });
const showTooltip = ref(false);
const hoveredProgressLabel = ref<LabelDistribution | null>(null);
const showProgressTooltip = ref(false);

// Period label for subtitle
const monthNames: Record<string, string> = {
    '01': 'Januari',
    '02': 'Februari',
    '03': 'Maret',
    '04': 'April',
    '05': 'Mei',
    '06': 'Juni',
    '07': 'Juli',
    '08': 'Agustus',
    '09': 'September',
    '10': 'Oktober',
    '11': 'November',
    '12': 'Desember',
};

const reportPeriodLabel = computed(() => {
    const month = selectedMonth.value;
    const year = selectedYear.value;
    const monthLabel = month ? monthNames[month] || month : '';

    if (month && year) return `${monthLabel} ${year}`;
    if (month) return `${monthLabel}`;
    if (year) return `${year}`;
    return '';
});

// Computed values
// Conversion Rate based on Transaksis: Closing / Lead (filtered, grouped by Brand)
const conversionLeads = computed(() => {
    try {
        return (props.summaryReport || []).reduce((sum, item) => sum + (item.real_lead || 0), 0);
    } catch {
        return 0;
    }
});

const conversionClosings = computed(() => {
    try {
        return (props.summaryReport || []).reduce((sum, item) => sum + (item.closing || 0), 0);
    } catch {
        return 0;
    }
});

const totalConversionRate = computed(() => {
    return conversionLeads.value > 0
        ? Math.round((conversionClosings.value / conversionLeads.value) * 100)
        : 0;
});

const growthIndicators = computed(() => {
    return {
        daily: props.mitraStats.today,
        weekly: props.mitraStats.this_week,
        monthly: props.mitraStats.this_month,
    };
});

// ============================
// CS Repeat Analytics (Dashboard)
// ============================
const csRepeatSummary = ref<CsRepeatSummary>({ totalOmset: 0, jumlahTransaksi: 0 });
const csRepeatDailyTransaksi = ref<CsRepeatDailyRow[]>([]);
const csRepeatDailyByProduct = ref<CsRepeatDailyProductRow[]>([]);
const csRepeatLoading = ref({ summary: false, dailyTransaksi: false, dailyProduct: false });

const fetchCsRepeatSummary = async () => {
    csRepeatLoading.value.summary = true;
    try {
        const params = new URLSearchParams({
            start_date: startDate.value || toLocalDateString(new Date(new Date().getFullYear(), 0, 1)),
            end_date: endDate.value || toLocalDateString(new Date(new Date().getFullYear(), 11, 31)),
        });
        if (selectedBrand.value && selectedBrand.value !== 'all') params.append('brand', String(selectedBrand.value));
        if (selectedMarketing.value && selectedMarketing.value !== 'all') params.append('marketing', String(selectedMarketing.value));

        const res = await fetch('/cs/repeats/analytics/summary?' + params.toString());
        if (res.ok) {
            const json = await res.json();
            // Expecting { data: { totalOmset: number, jumlahTransaksi: number } }
            csRepeatSummary.value = (json.data || { totalOmset: 0, jumlahTransaksi: 0 });
        }
    } catch (e) {
        console.error('Gagal memuat summary CS Repeat:', e);
    } finally {
        csRepeatLoading.value.summary = false;
    }
};

const fetchCsRepeatDailyTransaksi = async () => {
    csRepeatLoading.value.dailyTransaksi = true;
    try {
        const params = new URLSearchParams({
            start_date: startDate.value || toLocalDateString(new Date(new Date().getFullYear(), 0, 1)),
            end_date: endDate.value || toLocalDateString(new Date(new Date().getFullYear(), 11, 31)),
        });
        if (selectedBrand.value && selectedBrand.value !== 'all') params.append('brand', String(selectedBrand.value));
        if (selectedMarketing.value && selectedMarketing.value !== 'all') params.append('marketing', String(selectedMarketing.value));

        const res = await fetch('/cs/repeats/analytics/daily-transaksi?' + params.toString());
        if (res.ok) {
            const json = await res.json();
            // Expecting { data: Array<{ date: string, total: number }> }
            csRepeatDailyTransaksi.value = (json.data || []) as CsRepeatDailyRow[];
        }
    } catch (e) {
        console.error('Gagal memuat transaksi harian CS Repeat:', e);
    } finally {
        csRepeatLoading.value.dailyTransaksi = false;
    }
};

const fetchCsRepeatDailyByProduct = async () => {
    csRepeatLoading.value.dailyProduct = true;
    try {
        const params = new URLSearchParams({
            start_date: startDate.value || toLocalDateString(new Date(new Date().getFullYear(), 0, 1)),
            end_date: endDate.value || toLocalDateString(new Date(new Date().getFullYear(), 11, 31)),
        });
        if (selectedBrand.value && selectedBrand.value !== 'all') params.append('brand', String(selectedBrand.value));
        if (selectedMarketing.value && selectedMarketing.value !== 'all') params.append('marketing', String(selectedMarketing.value));

        const res = await fetch('/cs/repeats/analytics/daily-by-product?' + params.toString());
        if (res.ok) {
            const json = await res.json();
            // Expecting { data: Array<{ date: string, products: Record<string, number>, total: number }> }
            csRepeatDailyByProduct.value = (json.data || []) as CsRepeatDailyProductRow[];
        }
    } catch (e) {
        console.error('Gagal memuat transaksi per produk harian CS Repeat:', e);
    } finally {
        csRepeatLoading.value.dailyProduct = false;
    }
};

const refreshCsRepeatAnalytics = () => {
    fetchCsRepeatSummary();
    fetchCsRepeatDailyTransaksi();
    fetchCsRepeatDailyByProduct();
};

onMounted(() => {
    // Initial load for CS Repeat analytics
    refreshCsRepeatAnalytics();
});

// Refresh when dashboard filters change
watch([startDate, endDate, selectedBrand, selectedMarketing], () => {
    refreshCsRepeatAnalytics();
});

// Functions
const refreshData = () => {
    refreshing.value = true;
    router.reload({
        only: ['chatAnalytics', 'periodAnalytics', 'dailyTrends', 'closingAnalysis', 'recentActivities'],
        onFinish: () => {
            refreshing.value = false;
        },
    });
};

const applyDateFilter = () => {
    if (startDate.value && endDate.value) {
        router.get(
            '/dashboard',
            {
                start_date: startDate.value,
                end_date: endDate.value,
                marketing: selectedMarketing.value !== 'all' ? selectedMarketing.value : null,
                brand: selectedBrand.value !== 'all' ? selectedBrand.value : null,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    }
};

const applyQuickDateFilter = (range: string) => {
    const now = new Date();
    let start, end;

    switch (range) {
        case 'today':
            start = end = toLocalDateString(now);
            break;
        case 'yesterday':
            const yesterday = new Date(now);
            yesterday.setDate(yesterday.getDate() - 1);
            start = end = toLocalDateString(yesterday);
            break;
        case 'this_week':
            const startOfWeek = new Date(now);
            startOfWeek.setDate(now.getDate() - now.getDay());
            start = toLocalDateString(startOfWeek);
            end = toLocalDateString(now);
            break;
        case 'last_week':
            const startOfLastWeek = new Date(now);
            startOfLastWeek.setDate(now.getDate() - now.getDay() - 7);
            const endOfLastWeek = new Date(startOfLastWeek);
            endOfLastWeek.setDate(startOfLastWeek.getDate() + 6);
            start = toLocalDateString(startOfLastWeek);
            end = toLocalDateString(endOfLastWeek);
            break;
        case 'this_month':
            start = toLocalDateString(new Date(now.getFullYear(), now.getMonth(), 1));
            end = toLocalDateString(new Date(now.getFullYear(), now.getMonth() + 1, 0));
            break;
        case 'last_month':
            start = toLocalDateString(new Date(now.getFullYear(), now.getMonth() - 1, 1));
            end = toLocalDateString(new Date(now.getFullYear(), now.getMonth(), 0));
            break;
        default:
            return;
    }

    startDate.value = start;
    endDate.value = end;
    selectedDateRange.value = range;
    applyDateFilter();
};

const applyMarketingFilter = (marketingId: string) => {
    selectedMarketing.value = marketingId;
    applyFilters();
};

const applyBrandFilter = (brandId: string) => {
    selectedBrand.value = brandId;
    applyFilters();
};

const applyFilters = () => {
    const params: any = {};

    if (startDate.value && endDate.value) {
        params.start_date = startDate.value;
        params.end_date = endDate.value;
    }

    if (selectedMarketing.value !== 'all') {
        params.marketing = selectedMarketing.value;
    }

    if (selectedBrand.value !== 'all') {
        params.brand = selectedBrand.value;
    }

    router.get('/dashboard', params, {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    selectedMarketing.value = 'all';
    selectedBrand.value = 'all';
    selectedDateRange.value = 'this_month';
    applyQuickDateFilter('this_month');
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getGrowthIcon = (value: number) => {
    return value > 0 ? ArrowUpRight : ArrowDownRight;
};

const getGrowthColor = (value: number) => {
    return value > 0 ? 'text-green-600' : 'text-red-600';
};

// Function to create SVG path for pie chart
const getArcPath = (label: LabelDistribution, index: number) => {
    const total = props.labelDistribution.reduce((sum, item) => sum + item.count, 0);
    if (total === 0) return '';

    const centerX = 100;
    const centerY = 100;
    const radius = 80;

    // Calculate cumulative percentage up to current index
    let cumulativePercentage = 0;
    for (let i = 0; i < index; i++) {
        cumulativePercentage += props.labelDistribution[i].percentage;
    }

    const startAngle = (cumulativePercentage / 100) * 2 * Math.PI;
    const endAngle = ((cumulativePercentage + label.percentage) / 100) * 2 * Math.PI;

    const startX = centerX + radius * Math.cos(startAngle);
    const startY = centerY + radius * Math.sin(startAngle);
    const endX = centerX + radius * Math.cos(endAngle);
    const endY = centerY + radius * Math.sin(endAngle);

    const largeArcFlag = endAngle - startAngle <= Math.PI ? '0' : '1';

    if (label.percentage === 100) {
        // Full circle
        return `M ${centerX - radius},${centerY} A ${radius},${radius} 0 1,1 ${centerX - radius},${centerY + 0.1} Z`;
    }

    return `M ${centerX},${centerY} L ${startX},${startY} A ${radius},${radius} 0 ${largeArcFlag},1 ${endX},${endY} Z`;
};

// Tooltip functions
const handleMouseEnter = (label: LabelDistribution, event: MouseEvent) => {
    hoveredLabel.value = label;
    showTooltip.value = true;
    updateTooltipPosition(event);
};

const handleMouseLeave = () => {
    hoveredLabel.value = null;
    showTooltip.value = false;
};

const handleMouseMove = (event: MouseEvent) => {
    if (showTooltip.value) {
        updateTooltipPosition(event);
    }
};

const updateTooltipPosition = (event: MouseEvent) => {
    tooltipPosition.value = {
        x: event.clientX + 10,
        y: event.clientY - 10
    };
};

// Progress bar tooltip functions
const handleProgressMouseEnter = (label: LabelDistribution, event: MouseEvent) => {
    hoveredProgressLabel.value = label;
    showProgressTooltip.value = true;
    updateTooltipPosition(event);
};

const handleProgressMouseLeave = () => {
    hoveredProgressLabel.value = null;
    showProgressTooltip.value = false;
};

const handleProgressMouseMove = (event: MouseEvent) => {
    if (showProgressTooltip.value) {
        updateTooltipPosition(event);
    }
};

// Filter by month and year function
const filterByMonthYear = () => {
    const params: any = {};
    
    if (selectedMonth.value || selectedYear.value) {
        const year = selectedYear.value ? parseInt(selectedYear.value) : new Date().getFullYear();
        const month = selectedMonth.value ? parseInt(selectedMonth.value) : null;
        
        if (month) {
            // Filter by specific month and year
            const startOfMonth = `${year}-${selectedMonth.value.padStart(2, '0')}-01`;
            const endOfMonth = toLocalDateString(new Date(year, month, 0));
            
            params.start_date = startOfMonth;
            params.end_date = endOfMonth;
        } else if (selectedYear.value) {
            // Filter by year only
            params.start_date = `${year}-01-01`;
            params.end_date = `${year}-12-31`;
        }
    }
    
    if (selectedMarketing.value !== 'all') {
        params.marketing = selectedMarketing.value;
    }
    
    if (selectedBrand.value !== 'all') {
        params.brand = selectedBrand.value;
    }
    
    router.get('/dashboard', params, {
        preserveState: true,
        replace: true,
    });
};

onMounted(() => {
    // Set default date range to current month
    const now = new Date();
    startDate.value = toLocalDateString(new Date(now.getFullYear(), now.getMonth(), 1));
    endDate.value = toLocalDateString(new Date(now.getFullYear(), now.getMonth() + 1, 0));
    selectedDateRange.value = 'this_month';

    // Close dropdowns when clicking outside
    const handleClickOutside = (event: Event) => {
        const target = event.target as Element;
        if (!target.closest('.relative')) {
            showMarketingDropdown.value = false;
            showBrandDropdown.value = false;
        }
    };

    document.addEventListener('click', handleClickOutside);

    onUnmounted(() => {
        document.removeEventListener('click', handleClickOutside);
    });

    // Apply default month/year filter to URL if none provided
    if (!(props.filters as any)?.month && !(props.filters as any)?.year) {
        filterByMonthYear();
    }
});

// PPN percentage for labeling Spent+PPN columns consistently
const page = usePage();
const ppnPercentage = computed(() => {
    const raw = (page.props as any)?.siteSettings?.ppn_rate;
    const rate = Number(raw);
    return isNaN(rate) ? 11 : rate;
});
</script>

<template>
    <Head title="Analytics Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="layout-main">
            <!-- Enhanced Welcome Section -->
            <div
                class="dashboard-welcome relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 text-white"
            >
                <div class="relative z-10">
                    <div class="responsive-gap flex flex-col lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex-1">
                            <h1 class="mb-4 flex items-center gap-3 text-3xl font-bold tracking-tight sm:text-4xl">
                                <BarChart3 class="h-8 w-8 sm:h-10 sm:w-10" />
                                Analytics Dashboard
                            </h1>
                            <p class="mb-6 max-w-2xl text-lg text-blue-100 sm:text-xl">
                                Pantau performa marketing dan analisa data lead secara real-time
                            </p>
                        </div>
                        <div class="flex flex-col gap-3 sm:flex-row sm:gap-4">
                            <Button
                                @click="refreshData"
                                :disabled="refreshing"
                                class="btn-spacing border-white/30 bg-white/20 text-white backdrop-blur-sm hover:bg-white/30"
                            >
                                <RefreshCw :class="['mr-2 h-4 w-4', refreshing && 'animate-spin']" />
                                Refresh
                            </Button>
                        </div>
                    </div>
                </div>
                <div class="absolute top-0 right-0 -mt-24 -mr-24 h-48 w-48 rounded-full bg-white/10 sm:-mt-32 sm:-mr-32 sm:h-64 sm:w-64"></div>
                <div class="absolute bottom-0 left-0 -mb-16 -ml-16 h-32 w-32 rounded-full bg-white/5 sm:-mb-24 sm:-ml-24 sm:h-48 sm:w-48"></div>
            </div>

            <!-- Report Budget Vs Omset -->
            <Card v-if="permissions.hasFullAccess || permissions.hasReadOnlyAccess || permissions.hasLimitedAccess" class="mb-6 border-gray-200 bg-gradient-to-br from-gray-50 to-gray-100 transition-all duration-200 hover:shadow-lg dark:border-gray-700 dark:from-gray-800/30 dark:to-gray-700/30 shadow-lg">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <BarChart3 class="h-6 w-6" />
                        Report Budget Vs Omset
                    </CardTitle>
                    <p class="text-sm font-medium text-blue-700 dark:text-blue-300">
                        Ringkasan performa budget marketing vs omset per brand
                        <span v-if="reportPeriodLabel" class="ml-1">— {{ reportPeriodLabel }}</span>
                    </p>
                </CardHeader>
                <CardContent class="p-6">
                    <!-- Filter Section -->
                    <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 mb-6">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                            <label for="month-filter" class="text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">Bulan:</label>
                            <select 
                                id="month-filter" 
                                v-model="selectedMonth" 
                                @change="filterByMonthYear"
                                class="w-full sm:w-auto px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">Semua Bulan</option>
                                <option value="01">Januari</option>
                                <option value="02">Februari</option>
                                <option value="03">Maret</option>
                                <option value="04">April</option>
                                <option value="05">Mei</option>
                                <option value="06">Juni</option>
                                <option value="07">Juli</option>
                                <option value="08">Agustus</option>
                                <option value="09">September</option>
                                <option value="10">Oktober</option>
                                <option value="11">November</option>
                                <option value="12">Desember</option>
                            </select>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                            <label for="year-filter" class="text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">Tahun:</label>
                            <select 
                                id="year-filter" 
                                v-model="selectedYear" 
                                @change="filterByMonthYear"
                                class="w-full sm:w-auto px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">Semua Tahun</option>
                                <option value="2023">2023</option>
                                <option value="2024">2024</option>
                                <option value="2025">2025</option>
                                <option value="2026">2026</option>
                            </select>
                        </div>
                    </div>

                    <!-- Summary Statistics Cards -->
                    <!-- Grid 1: ringkas utama (tetap responsif) -->
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-6">
                        <Card class="relative bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950 dark:to-blue-900">
                            <CardContent class="p-6">
                                <div class="absolute top-3 right-3 text-blue-500/60">
                                    <Target class="h-4 w-4" />
                                </div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-blue-700 dark:text-blue-300">Total Spent</p>
                                        <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                                            Rp {{ summaryReport.reduce((sum, item) => sum + item.spent, 0).toLocaleString('id-ID') }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card class="relative bg-gradient-to-br from-red-50 to-red-100 dark:from-red-950 dark:to-red-900">
                            <CardContent class="p-6">
                                <div class="absolute top-3 right-3 text-red-500/60">
                                    <DollarSign class="h-4 w-4" />
                                </div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-red-700 dark:text-red-300">Total Spent+PPN ({{ ppnPercentage }}%)</p>
                                        <p class="text-2xl font-bold text-red-900 dark:text-red-100">
                                            Rp {{ summaryReport.reduce((sum, item) => sum + item.spent_with_tax, 0).toLocaleString('id-ID') }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card class="relative bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950 dark:to-green-900">
                            <CardContent class="p-6">
                                <div class="absolute top-3 right-3 text-green-500/60">
                                    <TrendingUp class="h-4 w-4" />
                                </div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-green-700 dark:text-green-300">Total Omset</p>
                                        <p class="text-2xl font-bold text-green-900 dark:text-green-100">
                                            Rp {{ summaryReport.reduce((sum, item) => sum + item.omset, 0).toLocaleString('id-ID') }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Cost Per Acquisition dipindah setelah Total Omset -->
                        <Card class="relative bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-950 dark:to-indigo-900">
                            <CardContent class="p-6">
                                <div class="absolute top-3 right-3 text-indigo-500/60">
                                    <DollarSign class="h-4 w-4" />
                                </div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-indigo-700 dark:text-indigo-300">Cost Per Acquisition</p>
                                        <p class="text-2xl font-bold text-indigo-900 dark:text-indigo-100">
                                            {{
                                                (() => {
                                                    const totalSpent = summaryReport.reduce((sum, item) => sum + item.spent, 0)
                                                    const totalClosing = summaryReport.reduce((sum, item) => sum + item.closing, 0)
                                                    return totalClosing > 0 ? Math.round(totalSpent / totalClosing).toLocaleString('id-ID') : '0'
                                                })()
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Grid 2: empat kartu dalam 2 kolom (50% : 50%) -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <!-- ROAS Keseluruhan (baru) -->
                        <Card class="relative bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-950 dark:to-emerald-900">
                            <CardContent class="p-6">
                                <div class="absolute top-3 right-3 text-emerald-500/60">
                                    <TrendingUp class="h-4 w-4" />
                                </div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs sm:text-sm font-medium text-emerald-700 dark:text-emerald-300">ROAS Keseluruhan</p>
                                        <p class="text-lg sm:text-2xl font-bold leading-tight text-emerald-900 dark:text-emerald-100">
                                            {{
                                                (() => {
                                                    const totalSpentWithTax = summaryReport.reduce((sum, item) => sum + item.spent_with_tax, 0)
                                                    const totalOmset = summaryReport.reduce((sum, item) => sum + item.omset, 0)
                                                    return totalSpentWithTax > 0 ? (totalOmset / totalSpentWithTax).toFixed(2) + 'x' : '0.00x'
                                                })()
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card class="relative bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950 dark:to-purple-900">
                            <CardContent class="p-6">
                                <div class="absolute top-3 right-3 text-purple-500/60">
                                    <Users class="h-4 w-4" />
                                </div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs sm:text-sm font-medium text-purple-700 dark:text-purple-300">Total Leads</p>
                                        <p class="text-lg sm:text-2xl font-bold leading-tight text-purple-900 dark:text-purple-100">
                                            {{ summaryReport.reduce((sum, item) => sum + item.real_lead, 0).toLocaleString('id-ID') }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card class="relative bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-950 dark:to-orange-900">
                            <CardContent class="p-6">
                                <div class="absolute top-3 right-3 text-orange-500/60">
                                    <Award class="h-4 w-4" />
                                </div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs sm:text-sm font-medium text-orange-700 dark:text-orange-300">Total Closing</p>
                                        <p class="text-lg sm:text-2xl font-bold leading-tight text-orange-900 dark:text-orange-100">
                                            {{ summaryReport.reduce((sum, item) => sum + item.closing, 0).toLocaleString('id-ID') }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Cost Per Lead (baru) -->
                        <Card class="relative bg-gradient-to-br from-teal-50 to-teal-100 dark:from-teal-950 dark:to-teal-900">
                            <CardContent class="p-6">
                                <div class="absolute top-3 right-3 text-teal-500/60">
                                    <Target class="h-4 w-4" />
                                </div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs sm:text-sm font-medium text-teal-700 dark:text-teal-300">Cost Per Lead</p>
                                        <p class="text-lg sm:text-2xl font-bold leading-tight text-teal-900 dark:text-teal-100">
                                            {{
                                                (() => {
                                                    const totalSpent = summaryReport.reduce((sum, item) => sum + item.spent, 0)
                                                    const totalLead = summaryReport.reduce((sum, item) => sum + item.real_lead, 0)
                                                    return totalLead > 0 ? Math.round(totalSpent / totalLead).toLocaleString('id-ID') : '0'
                                                })()
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Summary Report Table -->
                    <div v-if="summaryReport.length > 0" class="overflow-x-auto">
                        <table class="w-full border-collapse text-xs sm:text-sm">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left p-2 sm:p-3 font-semibold sticky left-0 z-30 bg-background min-w-[140px] sm:min-w-[180px] border-r border-border">Brand</th>
                                    <th class="text-right p-3 font-semibold">Spent</th>
                                    <th class="text-right p-3 font-semibold">Spent+PPN ({{ ppnPercentage }}%)</th>
                                    <th class="text-right p-3 font-semibold">Real Lead</th>
                                    <th class="text-right p-3 font-semibold">Cost/Lead</th>
                                    <th class="text-right p-3 font-semibold">Closing</th>
                                    <th class="text-right p-3 font-semibold">Omset</th>
                                    <th class="text-right p-3 font-semibold">ROAS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in summaryReport" :key="item.brand" class="border-b hover:bg-muted/50">
                                    <td class="p-2 sm:p-3 text-xs sm:text-sm font-medium text-blue-600 sticky left-0 z-20 bg-background min-w-[140px] sm:min-w-[180px] border-r border-border">{{ item.brand }}</td>
                                    <td class="p-3 text-right text-red-600">Rp {{ item.spent.toLocaleString('id-ID') }}</td>
                                    <td class="p-3 text-right text-red-600">Rp {{ item.spent_with_tax.toLocaleString('id-ID') }}</td>
                                    <td class="p-3 text-right">{{ item.real_lead }}</td>
                                    <td class="p-3 text-right text-orange-600">
                                        <span v-if="item.real_lead > 0">Rp {{ item.cost_per_lead.toLocaleString('id-ID') }}</span>
                                        <span v-else class="text-red-500">#DIV/0!</span>
                                    </td>
                                    <td class="p-3 text-right text-purple-600">{{ item.closing }}</td>
                                    <td class="p-3 text-right text-green-600">Rp {{ item.omset.toLocaleString('id-ID') }}</td>
                                    <td class="p-3 text-right" :class="item.roas >= 1 ? 'text-green-600 font-semibold' : 'text-red-500'">
                                        {{ item.roas.toFixed(2) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="flex h-32 flex-col items-center justify-center text-muted-foreground">
                        <BarChart3 class="mb-4 h-12 w-12 opacity-50" />
                        <p class="text-lg font-medium">Tidak ada data summary report</p>
                        <p class="text-sm">Data akan muncul setelah ada aktivitas marketing</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Main KPI Cards -->
            <div class="stats-grid stats-grid-mobile-2">
                <!-- Total Leads -->
                <Card class="relative stats-card stats-card-blue stats-card-mobile">
                    <CardContent class="stats-card-content">
                        <!-- Icon ala ROAS: kecil, transparan, di pojok kanan atas -->
                        <div class="absolute top-3 right-3 text-blue-500/60">
                            <Users class="h-4 w-4" />
                        </div>
                        <div class="stats-card-layout">
                            <div class="stats-card-text">
                                <p class="stats-card-label stats-label-blue">Total Leads</p>
                                <p class="stats-card-value stats-value-blue">{{ mitraStats.total }}</p>
                                <p class="stats-card-subtitle stats-subtitle-blue">+{{ mitraStats.today }} hari ini</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Conversion Rate -->
                <Card class="relative stats-card stats-card-green stats-card-mobile">
                    <CardContent class="stats-card-content">
                        <!-- Icon ala ROAS: kecil, transparan, di pojok kanan atas -->
                        <div class="absolute top-3 right-3 text-green-500/60">
                            <Target class="h-4 w-4" />
                        </div>
                        <div class="stats-card-layout">
                            <div class="stats-card-text">
                                <p class="stats-card-label stats-label-green">Conversion Rate</p>
                                <p class="stats-card-value-with-icon stats-value-green">
                                    {{ totalConversionRate }}%
                                    <component
                                        :is="getGrowthIcon(totalConversionRate)"
                                        :class="['stats-growth-icon', getGrowthColor(totalConversionRate)]"
                                    />
                                </p>
                                <p class="stats-card-subtitle stats-subtitle-green">{{ conversionClosings }} dari {{ conversionLeads }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Active Chats -->
                <Card class="relative stats-card stats-card-orange stats-card-mobile">
                    <CardContent class="stats-card-content">
                        <!-- Icon ala ROAS: kecil, transparan, di pojok kanan atas -->
                        <div class="absolute top-3 right-3 text-orange-500/60">
                            <MessageSquare class="h-4 w-4" />
                        </div>
                        <div class="stats-card-layout">
                            <div class="stats-card-text">
                                <p class="stats-card-label stats-label-orange">Chat Baru</p>
                                <p class="stats-card-value stats-value-orange">{{ mitraStats.masuk }}</p>
                                <p class="stats-card-subtitle stats-subtitle-orange">{{ mitraStats.this_week }} minggu ini</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Follow Ups -->
                <Card class="relative stats-card stats-card-purple stats-card-mobile">
                    <CardContent class="stats-card-content">
                        <!-- Icon ala ROAS: kecil, transparan, di pojok kanan atas -->
                        <div class="absolute top-3 right-3 text-purple-500/60">
                            <Phone class="h-4 w-4" />
                        </div>
                        <div class="stats-card-layout">
                            <div class="stats-card-text">
                                <p class="stats-card-label stats-label-purple">Follow Up</p>
                                <p class="stats-card-value stats-value-purple">{{ mitraStats.followup }}</p>
                                <p class="stats-card-subtitle stats-subtitle-purple">{{ mitraStats.this_month }} bulan ini</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Enhanced Collapsible Filter Section -->
            <div class="rounded-lg bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 p-0.5 shadow-lg">
                <Card class="overflow-visible rounded-lg border-0 bg-white dark:bg-gray-900">
                    <CardContent class="layout-content py-6">
                        <!-- Filter Toggle Header -->
                        <div class="group flex cursor-pointer items-center justify-between" @click="isFilterExpanded = !isFilterExpanded">
                            <div class="flex items-center gap-3">
                                <div
                                    class="rounded-lg bg-gradient-to-r from-indigo-500 to-purple-500 p-2 shadow-lg transition-all duration-300 group-hover:shadow-xl"
                                >
                                    <Filter class="h-5 w-5 text-white" />
                                </div>
                                <div>
                                    <h3
                                        class="text-lg font-bold text-gray-900 transition-colors group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-400"
                                    >
                                        Filter Dashboard
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ isFilterExpanded ? 'Klik untuk menyembunyikan filter' : 'Klik untuk menampilkan opsi filter' }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <!-- Active Filters Count -->
                                <div
                                    v-if="selectedMarketing !== 'all' || selectedBrand !== 'all'"
                                    class="rounded-full bg-indigo-100 px-3 py-1.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300"
                                >
                                    {{ (selectedMarketing !== 'all' ? 1 : 0) + (selectedBrand !== 'all' ? 1 : 0) }} filter aktif
                                </div>
                                <ChevronDown
                                    :class="[
                                        'h-5 w-5 text-gray-500 transition-transform duration-300 dark:text-gray-400',
                                        isFilterExpanded ? 'rotate-180 transform' : '',
                                    ]"
                                />
                            </div>
                        </div>

                        <!-- Expandable Filter Content -->
                        <Transition
                            enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="max-h-0 opacity-0"
                            enter-to-class="max-h-[800px] opacity-100"
                            leave-active-class="transition-all duration-300 ease-in"
                            leave-from-class="max-h-[800px] opacity-100"
                            leave-to-class="max-h-0 opacity-0"
                        >
                            <div v-if="isFilterExpanded" class="filter-content-section">
                                <!-- Quick Date Range Filters -->
                                <div class="filter-group">
                                    <div class="filter-header">
                                        <Calendar class="h-4 w-4 text-indigo-500" />
                                        <Label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Periode Waktu:</Label>
                                    </div>
                                    <div class="responsive-grid-6">
                                        <Button
                                            v-for="range in [
                                                { key: 'today', label: 'Hari Ini', color: 'emerald' },
                                                { key: 'yesterday', label: 'Kemarin', color: 'blue' },
                                                { key: 'this_week', label: 'Minggu Ini', color: 'purple' },
                                                { key: 'last_week', label: 'Minggu Lalu', color: 'pink' },
                                                { key: 'this_month', label: 'Bulan Ini', color: 'indigo' },
                                                { key: 'last_month', label: 'Bulan Lalu', color: 'orange' },
                                            ]"
                                            :key="range.key"
                                            :class="[
                                                'btn-filter-range',
                                                selectedDateRange === range.key ? 'btn-filter-active' : 'btn-filter-inactive',
                                            ]"
                                            size="sm"
                                            @click="applyQuickDateFilter(range.key)"
                                        >
                                            {{ range.label }}
                                        </Button>
                                    </div>
                                </div>

                                <!-- Custom Date Range & Advanced Filters -->
                                <div class="filter-advanced-section">
                                    <div class="filter-header">
                                        <Settings class="h-4 w-4 text-gray-600 dark:text-gray-400" />
                                        <Label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Filter Lanjutan:</Label>
                                    </div>

                                    <div class="responsive-grid-4">
                                        <!-- Custom Start Date -->
                                        <div class="form-group">
                                            <Label class="form-label"> Tanggal Mulai: </Label>
                                            <DatePicker
                                                v-model="startDate"
                                                placeholder="Pilih tanggal mulai"
                                                :max-date="endDate || undefined"
                                            />
                                        </div>

                                        <!-- Custom End Date -->
                                        <div class="form-group">
                                            <Label class="form-label"> Tanggal Akhir: </Label>
                                            <DatePicker
                                                v-model="endDate"
                                                placeholder="Pilih tanggal akhir"
                                                :min-date="startDate || undefined"
                                            />
                                        </div>

                                        <!-- Marketing Filter Dropdown -->
                                        <div class="form-group dropdown-wrapper">
                                            <Label class="form-label">Marketing:</Label>
                                            <Button
                                                variant="outline"
                                                class="dropdown-trigger"
                                                @click="showMarketingDropdown = !showMarketingDropdown"
                                            >
                                                <span class="dropdown-content">
                                                    <Users class="h-4 w-4 text-emerald-500" />
                                                    <span class="truncate">
                                                        {{
                                                            selectedMarketing === 'all'
                                                                ? 'Semua Marketing'
                                                                : topMarketing.find((m) => m.id.toString() === selectedMarketing)?.name
                                                        }}
                                                    </span>
                                                </span>
                                                <ChevronDown class="h-4 w-4 flex-shrink-0 text-gray-400" />
                                            </Button>
                                            <div v-if="showMarketingDropdown" class="dropdown-menu">
                                                <div
                                                    class="dropdown-item dropdown-item-border"
                                                    @click="
                                                        applyMarketingFilter('all');
                                                        showMarketingDropdown = false;
                                                    "
                                                >
                                                    <div class="dropdown-item-content">
                                                        <Target class="h-4 w-4 text-emerald-500" />
                                                        <span class="font-medium text-gray-700 dark:text-gray-300">Semua Marketing</span>
                                                    </div>
                                                </div>
                                                <div
                                                    v-for="marketing in topMarketing"
                                                    :key="marketing.id"
                                                    class="dropdown-item"
                                                    @click="
                                                        applyMarketingFilter(marketing.id.toString());
                                                        showMarketingDropdown = false;
                                                    "
                                                >
                                                    <div class="dropdown-item-with-badge">
                                                        <div class="dropdown-item-content">
                                                            <Users class="h-4 w-4 text-emerald-500" />
                                                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ marketing.name }}</span>
                                                        </div>
                                                        <Badge class="dropdown-badge-emerald"> {{ marketing.total_leads }} leads </Badge>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Brand Filter Dropdown -->
                                        <div class="form-group dropdown-wrapper">
                                            <Label class="form-label">Brand:</Label>
                                            <Button
                                                variant="outline"
                                                class="dropdown-trigger dropdown-trigger-purple"
                                                @click="showBrandDropdown = !showBrandDropdown"
                                            >
                                                <span class="dropdown-content">
                                                    <Building2 class="h-4 w-4 text-purple-500" />
                                                    <span class="truncate">
                                                        {{
                                                            selectedBrand === 'all'
                                                                ? 'Semua Brand'
                                                                : brandPerformance.find((b) => b.id.toString() === selectedBrand)?.nama
                                                        }}
                                                    </span>
                                                </span>
                                                <ChevronDown class="h-4 w-4 flex-shrink-0 text-gray-400" />
                                            </Button>
                                            <div v-if="showBrandDropdown" class="dropdown-menu">
                                                <div
                                                    class="dropdown-item-purple dropdown-item-border"
                                                    @click="
                                                        applyBrandFilter('all');
                                                        showBrandDropdown = false;
                                                    "
                                                >
                                                    <div class="dropdown-item-content">
                                                        <Target class="h-4 w-4 text-purple-500" />
                                                        <span class="font-medium text-gray-700 dark:text-gray-300">Semua Brand</span>
                                                    </div>
                                                </div>
                                                <div
                                                    v-for="brand in brandPerformance"
                                                    :key="brand.id"
                                                    class="dropdown-item-purple"
                                                    @click="
                                                        applyBrandFilter(brand.id.toString());
                                                        showBrandDropdown = false;
                                                    "
                                                >
                                                    <div class="dropdown-item-with-badge">
                                                        <div class="dropdown-item-content">
                                                            <Building2 class="h-4 w-4 text-purple-500" />
                                                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ brand.nama }}</span>
                                                        </div>
                                                        <Badge class="dropdown-badge-purple"> {{ brand.total_leads }} leads </Badge>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="action-buttons">
                                    <Button @click="applyFilters" class="btn-primary-gradient">
                                        <Filter class="mr-2 h-4 w-4" />
                                        Terapkan Filter
                                    </Button>
                                    <Button variant="outline" @click="resetFilters" class="btn-reset">
                                        <RefreshCw class="mr-2 h-4 w-4" />
                                        Reset
                                    </Button>
                                </div>

                                <!-- Active Filters Display -->
                                <div v-if="selectedMarketing !== 'all' || selectedBrand !== 'all'" class="active-filters-container">
                                    <span class="active-filters-label">Filter Aktif:</span>
                                    <Badge v-if="selectedMarketing !== 'all'" class="active-filter-badge active-filter-emerald">
                                        <Users class="h-3 w-3" />
                                        <span>{{ topMarketing.find((m) => m.id.toString() === selectedMarketing)?.name }}</span>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="filter-remove-btn filter-remove-emerald"
                                            @click="applyMarketingFilter('all')"
                                        >
                                            <X class="h-3 w-3" />
                                        </Button>
                                    </Badge>
                                    <Badge v-if="selectedBrand !== 'all'" class="active-filter-badge active-filter-purple">
                                        <Building2 class="h-3 w-3" />
                                        <span>{{ brandPerformance.find((b) => b.id.toString() === selectedBrand)?.nama }}</span>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="filter-remove-btn filter-remove-purple"
                                            @click="applyBrandFilter('all')"
                                        >
                                            <X class="h-3 w-3" />
                                        </Button>
                                    </Badge>
                                </div>
                            </div>
                        </Transition>
                    </CardContent>
                </Card>
            </div>

            <!-- CS-only: CS Repeat Analytics outside Tabs -->
            <div v-if="permissions.role === 'cs'">
                <Card class="border-0 shadow-lg">
                    <CardHeader class="relative overflow-hidden rounded-t-xl bg-gradient-to-r from-indigo-600 via-sky-600 to-cyan-600 text-white dark:from-indigo-700 dark:via-sky-700 dark:to-cyan-700">
                        <CardTitle class="flex items-center gap-2 text-white">
                            CS Repeat Analytics
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Summary Cards -->
                        <div class="flex flex-nowrap gap-4 sm:grid sm:grid-cols-2">
                            <Card class="border border-indigo-100 basis-[65%] sm:basis-auto sm:col-span-1">
                                <CardHeader class="pb-2 bg-gradient-to-r from-indigo-50 to-blue-50">
                                    <CardTitle class="text-sm sm:text-base">Total Omset</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-xl sm:text-2xl font-bold text-indigo-700">
                                        {{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(csRepeatSummary.totalOmset || 0) }}
                                    </div>
                                </CardContent>
                            </Card>
                            <Card class="border border-indigo-100 basis-[35%] sm:basis-auto sm:col-span-1">
                                <CardHeader class="pb-2 bg-gradient-to-r from-indigo-50 to-blue-50">
                                    <CardTitle class="text-sm sm:text-base">Jumlah Transaksi</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-xl sm:text-2xl font-bold text-indigo-700">
                                        {{ new Intl.NumberFormat('id-ID').format(csRepeatSummary.jumlahTransaksi || 0) }}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Charts Grid -->
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <CsRepeatDailyTransaksiChart :data="csRepeatDailyTransaksi" :loading="csRepeatLoading.dailyTransaksi" />
                            <CsRepeatDailyProductChart :data="csRepeatDailyByProduct" :loading="csRepeatLoading.dailyProduct" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Tabs for Different Analytics Views -->
            <Tabs v-if="permissions.role !== 'cs'" default-value="overview" class="w-full">
                <div class="w-full overflow-x-auto pb-2">
                    <TabsList class="inline-flex h-10 items-center justify-start rounded-md bg-muted p-1 text-muted-foreground min-w-max">
                        <TabsTrigger value="overview" class="text-xs md:text-sm whitespace-nowrap px-3 py-2 min-w-[80px] md:min-w-[100px]">
                            <span class="hidden sm:inline">Overview</span>
                            <span class="sm:hidden">Overview</span>
                        </TabsTrigger>
                        <TabsTrigger value="marketing" class="text-xs md:text-sm whitespace-nowrap px-3 py-2 min-w-[80px] md:min-w-[120px]">
                            <span class="hidden sm:inline">Per Marketing</span>
                            <span class="sm:hidden">Marketing</span>
                        </TabsTrigger>
                        <TabsTrigger value="brands" class="text-xs md:text-sm whitespace-nowrap px-3 py-2 min-w-[80px] md:min-w-[100px]">
                            <span class="hidden sm:inline">Per Brand</span>
                            <span class="sm:hidden">Brands</span>
                        </TabsTrigger>
                        <TabsTrigger value="labels" class="text-xs md:text-sm whitespace-nowrap px-3 py-2 min-w-[80px] md:min-w-[120px]">
                            <span class="hidden sm:inline">Label Analysis</span>
                            <span class="sm:hidden">Labels</span>
                        </TabsTrigger>
                        <TabsTrigger value="trends" class="text-xs md:text-sm whitespace-nowrap px-3 py-2 min-w-[80px] md:min-w-[100px]">
                            <span class="hidden sm:inline">Trends</span>
                            <span class="sm:hidden">Trends</span>
                        </TabsTrigger>

                    </TabsList>
                </div>

                <!-- Overview Tab -->
                <TabsContent value="overview" class="space-y-6">
                    <div class="grid gap-6 md:grid-cols-2">
                        <!-- Closing Analysis -->
                        <Card v-if="permissions.role !== 'cs'" class="border-0 shadow-lg">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Award class="h-5 w-5" />
                                    Analisa Closing Rate
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="flex items-center justify-between rounded-lg bg-muted/50 p-4">
                                    <div>
                                        <p class="text-sm text-muted-foreground">Overall Closing Rate</p>
                                        <p class="text-2xl font-bold">{{ closingAnalysis.closing_rate }}%</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-muted-foreground">{{ closingAnalysis.closed_leads }} closed</p>
                                        <p class="text-sm text-muted-foreground">dari {{ closingAnalysis.total_leads }} leads</p>
                                    </div>
                                </div>
                                <Progress :value="closingAnalysis.closing_rate" class="h-3" />
                            </CardContent>
                        </Card>

                        <!-- System Overview -->
                        <Card v-if="permissions.role !== 'cs'" class="border-0 shadow-lg">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Activity class="h-5 w-5" />
                                    System Overview
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="rounded-lg bg-blue-50 p-3 text-center dark:bg-blue-900/20">
                                        <Users class="mx-auto mb-2 h-6 w-6 text-blue-600" />
                                        <p class="text-sm text-muted-foreground">Total Users</p>
                                        <p class="text-xl font-bold">{{ userStats.total }}</p>
                                    </div>
                                    <div class="rounded-lg bg-green-50 p-3 text-center dark:bg-green-900/20">
                                        <Briefcase class="mx-auto mb-2 h-6 w-6 text-green-600" />
                                        <p class="text-sm text-muted-foreground">Marketing</p>
                                        <p class="text-xl font-bold">{{ userStats.marketing }}</p>
                                    </div>
                                    <div class="rounded-lg bg-purple-50 p-3 text-center dark:bg-purple-900/20">
                                        <Zap class="mx-auto mb-2 h-6 w-6 text-purple-600" />
                                        <p class="text-sm text-muted-foreground">Brands</p>
                                        <p class="text-xl font-bold">{{ brandStats.total }}</p>
                                    </div>
                                    <div class="rounded-lg bg-orange-50 p-3 text-center dark:bg-orange-900/20">
                                        <Tag class="mx-auto mb-2 h-6 w-6 text-orange-600" />
                                        <p class="text-sm text-muted-foreground">Labels</p>
                                        <p class="text-xl font-bold">{{ labelStats.total }}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Marketing Performance Chart Section -->
                    <Card v-if="permissions.role !== 'cs'" class="analytics-card">
                        <CardHeader class="analytics-card-header">
                            <CardTitle class="analytics-card-title">
                                <User class="h-6 w-6" />
                                Performa Marketing
                            </CardTitle>
                            <p class="analytics-card-subtitle">Analisis performa tim marketing berdasarkan total leads dan conversion rate</p>
                        </CardHeader>
                        <CardContent class="analytics-card-content">
                            <div class="chart-wrapper">
                                <MarketingPerformanceChart
                                    v-if="topMarketing.length > 0"
                                    :data="topMarketing"
                                    :title="`Performa Marketing ${selectedMarketing !== 'all' ? '- ' + (marketingUsers.find((m) => m.id.toString() === selectedMarketing)?.name || '') : ''}`"
                                />
                                <div v-else class="flex h-96 flex-col items-center justify-center text-muted-foreground">
                                    <User class="mb-4 h-16 w-16 opacity-50" />
                                    <p class="text-lg font-medium">Tidak ada data marketing</p>
                                    <p class="text-sm">Data performa marketing akan muncul di sini</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Brand Performance Chart Section -->
                    <Card v-if="permissions.role !== 'cs'" class="analytics-card">
                        <CardHeader class="analytics-card-header">
                            <CardTitle class="analytics-card-title">
                                <Tag class="h-6 w-6" />
                                Performa Brand
                            </CardTitle>
                            <p class="analytics-card-subtitle">Analisis performa setiap brand berdasarkan total leads dan tingkat konversi</p>
                        </CardHeader>
                        <CardContent class="analytics-card-content">
                            <div class="chart-wrapper">
                                <BrandPerformanceChart
                                    v-if="brandPerformance.length > 0"
                                    :data="brandPerformance"
                                    :title="`Performa Brand ${selectedBrand !== 'all' ? '- ' + (brands.find((b) => b.id.toString() === selectedBrand)?.nama || '') : ''}`"
                                />
                                <div v-else class="flex h-96 flex-col items-center justify-center text-muted-foreground">
                                    <Tag class="mb-4 h-16 w-16 opacity-50" />
                                    <p class="text-lg font-medium">Tidak ada data brand</p>
                                    <p class="text-sm">Data performa brand akan muncul di sini</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Recent Activities -->
                    <Card v-if="permissions.role !== 'cs'" class="border-0 shadow-lg">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Clock class="h-5 w-5" />
                                Aktivitas Terbaru
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div
                                    v-for="activity in recentActivities.slice(0, 5)"
                                    :key="activity.id"
                                    class="flex items-center justify-between rounded-lg border p-3 transition-colors hover:bg-muted/50"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-900/30">
                                            <Users class="h-4 w-4 text-blue-600" />
                                        </div>
                                        <div>
                                            <p class="font-medium">{{ activity.nama }}</p>
                                            <p class="text-sm text-muted-foreground">
                                                {{ activity.brand.nama }} • {{ activity.user?.name || 'No Marketing' }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <Badge :variant="activity.chat === 'followup' ? 'default' : 'secondary'" class="mb-1">
                                            {{ activity.chat }}
                                        </Badge>
                                        <p class="text-xs text-muted-foreground">
                                            {{ formatDate(activity.created_at) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 text-center">
                                <Link href="/mitras">
                                    <Button variant="outline" size="sm">
                                        <Eye class="mr-2 h-4 w-4" />
                                        Lihat Semua Lead
                                    </Button>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Task Management Report -->
                    <Card v-if="permissions.role !== 'cs'" class="border-0 bg-gradient-to-br from-indigo-50 to-purple-50 shadow-xl dark:from-indigo-950/50 dark:to-purple-950/50">
                        <CardHeader class="pb-3 sm:pb-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <CardTitle class="flex items-center gap-3 text-lg font-bold sm:text-xl">
                                    <div class="rounded-lg bg-gradient-to-r from-indigo-500 to-purple-500 p-2 shadow-lg">
                                        <CheckCircle class="h-5 w-5 text-white sm:h-6 sm:w-6" />
                                    </div>
                                    Report Task Management
                                </CardTitle>
                                <Badge class="self-start bg-gradient-to-r from-indigo-500 to-purple-500 px-3 py-1 text-white sm:self-center">
                                    Real-time Data
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-6 p-4 sm:space-y-8 sm:p-6">
                            <!-- Overall Task Statistics -->
                            <div class="rounded-xl bg-white p-4 shadow-md sm:p-6 dark:bg-gray-800">
                                <h3 class="mb-4 flex items-center gap-2 text-base font-semibold text-gray-900 sm:text-lg dark:text-white">
                                    <BarChart3 class="h-4 w-4 text-indigo-600 sm:h-5 sm:w-5" />
                                    Total Keseluruhan Task
                                </h3>
                                <div class="grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-5">
                                    <!-- Total Tasks -->
                                    <div
                                        class="rounded-lg bg-gradient-to-br from-gray-50 to-gray-100 p-3 text-center sm:p-4 dark:from-gray-700 dark:to-gray-800"
                                    >
                                        <div
                                            class="mx-auto mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-gray-500 to-gray-600 sm:h-10 sm:w-10"
                                        >
                                            <Clock class="h-4 w-4 text-white sm:h-5 sm:w-5" />
                                        </div>
                                        <p class="mb-1 text-xs text-gray-600 sm:text-sm dark:text-gray-400">Total Task</p>
                                        <p class="text-lg font-bold text-gray-900 sm:text-xl dark:text-white">{{ taskStats.overall.total }}</p>
                                    </div>

                                    <!-- Pending Tasks -->
                                    <div
                                        class="rounded-lg bg-gradient-to-br from-yellow-50 to-orange-50 p-3 text-center sm:p-4 dark:from-yellow-900/30 dark:to-orange-900/30"
                                    >
                                        <div
                                            class="mx-auto mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-yellow-500 to-orange-500 sm:h-10 sm:w-10"
                                        >
                                            <Clock class="h-4 w-4 text-white sm:h-5 sm:w-5" />
                                        </div>
                                        <p class="mb-1 text-xs text-yellow-700 sm:text-sm dark:text-yellow-400">Rencana</p>
                                        <p class="text-lg font-bold text-yellow-900 sm:text-xl dark:text-yellow-100">
                                            {{ taskStats.overall.pending }}
                                        </p>
                                        <p class="text-xs text-yellow-600 dark:text-yellow-400">
                                            {{
                                                taskStats.overall.total > 0
                                                    ? Math.round((taskStats.overall.pending / taskStats.overall.total) * 100)
                                                    : 0
                                            }}%
                                        </p>
                                    </div>

                                    <!-- In Progress Tasks -->
                                    <div
                                        class="rounded-lg bg-gradient-to-br from-blue-50 to-indigo-50 p-3 text-center sm:p-4 dark:from-blue-900/30 dark:to-indigo-900/30"
                                    >
                                        <div
                                            class="mx-auto mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-blue-500 to-indigo-500 sm:h-10 sm:w-10"
                                        >
                                            <Activity class="h-4 w-4 text-white sm:h-5 sm:w-5" />
                                        </div>
                                        <p class="mb-1 text-xs text-blue-700 sm:text-sm dark:text-blue-400">Dikerjakan</p>
                                        <p class="text-lg font-bold text-blue-900 sm:text-xl dark:text-blue-100">
                                            {{ taskStats.overall.in_progress }}
                                        </p>
                                        <p class="text-xs text-blue-600 dark:text-blue-400">
                                            {{
                                                taskStats.overall.total > 0
                                                    ? Math.round((taskStats.overall.in_progress / taskStats.overall.total) * 100)
                                                    : 0
                                            }}%
                                        </p>
                                    </div>

                                    <!-- Completed Tasks -->
                                    <div
                                        class="rounded-lg bg-gradient-to-br from-green-50 to-emerald-50 p-3 text-center sm:p-4 dark:from-green-900/30 dark:to-emerald-900/30"
                                    >
                                        <div
                                            class="mx-auto mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-green-500 to-emerald-500 sm:h-10 sm:w-10"
                                        >
                                            <CheckCircle class="h-4 w-4 text-white sm:h-5 sm:w-5" />
                                        </div>
                                        <p class="mb-1 text-xs text-green-700 sm:text-sm dark:text-green-400">Selesai</p>
                                        <p class="text-lg font-bold text-green-900 sm:text-xl dark:text-green-100">
                                            {{ taskStats.overall.completed }}
                                        </p>
                                        <p class="text-xs text-green-600 dark:text-green-400">
                                            {{
                                                taskStats.overall.total > 0
                                                    ? Math.round((taskStats.overall.completed / taskStats.overall.total) * 100)
                                                    : 0
                                            }}%
                                        </p>
                                    </div>

                                    <!-- Overdue Tasks -->
                                    <div
                                        class="rounded-lg bg-gradient-to-br from-red-50 to-pink-50 p-3 text-center sm:p-4 dark:from-red-900/30 dark:to-pink-900/30"
                                    >
                                        <div
                                            class="mx-auto mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-red-500 to-pink-500 sm:h-10 sm:w-10"
                                        >
                                            <AlertCircle class="h-4 w-4 text-white sm:h-5 sm:w-5" />
                                        </div>
                                        <p class="mb-1 text-xs text-red-700 sm:text-sm dark:text-red-400">Terlambat</p>
                                        <p class="text-lg font-bold text-red-900 sm:text-xl dark:text-red-100">{{ taskStats.overall.overdue }}</p>
                                        <p class="text-xs text-red-600 dark:text-red-400">
                                            {{
                                                taskStats.overall.total > 0
                                                    ? Math.round((taskStats.overall.overdue / taskStats.overall.total) * 100)
                                                    : 0
                                            }}%
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Task Statistics by Marketing -->
                            <div class="rounded-xl bg-white p-4 shadow-md sm:p-6 dark:bg-gray-800">
                                <h3 class="mb-4 flex items-center gap-2 text-base font-semibold text-gray-900 sm:text-lg dark:text-white">
                                    <Users class="h-4 w-4 text-purple-600 sm:h-5 sm:w-5" />
                                    Report Task per Marketing
                                </h3>

                                <!-- Table Header -->
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs sm:text-sm">
                                        <thead>
                                            <tr class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/30 dark:to-purple-900/30">
                                                <th class="p-3 text-left font-semibold text-gray-700 dark:text-gray-300">Marketing</th>
                                                <th class="p-3 text-center font-semibold text-yellow-700 dark:text-yellow-400">Rencana</th>
                                                <th class="p-3 text-center font-semibold text-blue-700 dark:text-blue-400">Dikerjakan</th>
                                                <th class="p-3 text-center font-semibold text-green-700 dark:text-green-400">Selesai</th>
                                                <th class="p-3 text-center font-semibold text-red-700 dark:text-red-400">Terlambat</th>
                                                <th class="p-3 text-center font-semibold text-indigo-700 dark:text-indigo-400">Total</th>
                                                <th class="p-3 text-center font-semibold text-purple-700 dark:text-purple-400">Completion %</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="marketing in taskStats.by_marketing"
                                                :key="marketing.id"
                                                class="border-b border-gray-200 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700"
                                            >
                                                <td class="p-3">
                                                    <div class="flex items-center gap-2">
                                                        <div
                                                            class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-indigo-500 to-purple-500"
                                                        >
                                                            <User class="h-4 w-4 text-white" />
                                                        </div>
                                                        <div>
                                                            <p class="text-xs sm:text-sm font-medium text-gray-900 dark:text-white">{{ marketing.name }}</p>
                                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ marketing.email }}</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="p-3 text-center">
                                                    <Badge class="bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 text-xs sm:text-sm">
                                                        {{ marketing.pending_tasks }}
                                                    </Badge>
                                                </td>
                                                <td class="p-3 text-center">
                                                    <Badge class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 text-xs sm:text-sm">
                                                        {{ marketing.in_progress_tasks }}
                                                    </Badge>
                                                </td>
                                                <td class="p-3 text-center">
                                                    <Badge class="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 text-xs sm:text-sm">
                                                        {{ marketing.completed_tasks }}
                                                    </Badge>
                                                </td>
                                                <td class="p-3 text-center">
                                                    <Badge class="bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 text-xs sm:text-sm">
                                                        {{ marketing.overdue_tasks }}
                                                    </Badge>
                                                </td>
                                                <td class="p-3 text-center">
                                                    <Badge class="bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200 text-xs sm:text-sm">
                                                        {{ marketing.total_tasks }}
                                                    </Badge>
                                                </td>
                                                <td class="p-3 text-center">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <div class="h-2 w-12 rounded-full bg-gray-200 dark:bg-gray-700">
                                                            <div
                                                                class="h-2 rounded-full bg-gradient-to-r from-purple-500 to-indigo-500 transition-all duration-300"
                                                                :style="{ width: `${marketing.completion_rate}%` }"
                                                            ></div>
                                                        </div>
                                                        <span class="text-xs font-medium text-purple-600 dark:text-purple-400">
                                                            {{ marketing.completion_rate }}%
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Summary Row -->
                                <div
                                    class="mt-4 rounded-lg bg-gradient-to-r from-indigo-50 to-purple-50 p-4 dark:from-indigo-900/20 dark:to-purple-900/20"
                                >
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between text-xs sm:text-sm">
                                        <div class="flex items-center gap-2">
                                            <Award class="h-5 w-5 text-purple-600" />
                                            <span class="font-semibold text-gray-900 dark:text-white">Total Keseluruhan:</span>
                                        </div>
                                        <div class="flex flex-wrap gap-4">
                                            <span class="text-yellow-700 dark:text-yellow-400">
                                                Rencana: <strong>{{ taskStats.overall.pending }}</strong>
                                            </span>
                                            <span class="text-blue-700 dark:text-blue-400">
                                                Dikerjakan: <strong>{{ taskStats.overall.in_progress }}</strong>
                                            </span>
                                            <span class="text-green-700 dark:text-green-400">
                                                Selesai: <strong>{{ taskStats.overall.completed }}</strong>
                                            </span>
                                            <span class="text-purple-700 dark:text-purple-400">
                                                Total: <strong>{{ taskStats.overall.total }}</strong>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- CS Repeat Analytics Section -->
                    <Card class="border-0 shadow-lg">
                        <CardHeader class="relative overflow-hidden rounded-t-xl bg-gradient-to-r from-indigo-600 via-sky-600 to-cyan-600 text-white dark:from-indigo-700 dark:via-sky-700 dark:to-cyan-700">
                            <CardTitle class="flex items-center gap-2 text-white">
                                CS Repeat Analytics
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Summary Cards -->
                            <div class="flex flex-nowrap gap-4 sm:grid sm:grid-cols-2">
                                <Card class="border border-indigo-100 basis-[65%] sm:basis-auto sm:col-span-1">
                                    <CardHeader class="pb-2 bg-gradient-to-r from-indigo-50 to-blue-50">
                                        <CardTitle class="text-sm sm:text-base">Total Omset</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div class="text-xl sm:text-2xl font-bold text-indigo-700">
                                            {{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(csRepeatSummary.totalOmset || 0) }}
                                        </div>
                                    </CardContent>
                                </Card>
                                <Card class="border border-indigo-100 basis-[35%] sm:basis-auto sm:col-span-1">
                                    <CardHeader class="pb-2 bg-gradient-to-r from-indigo-50 to-blue-50">
                                        <CardTitle class="text-sm sm:text-base">Jumlah Transaksi</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div class="text-xl sm:text-2xl font-bold text-indigo-700">
                                            {{ new Intl.NumberFormat('id-ID').format(csRepeatSummary.jumlahTransaksi || 0) }}
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            <!-- Charts Grid -->
                            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                <CsRepeatDailyTransaksiChart :data="csRepeatDailyTransaksi" :loading="csRepeatLoading.dailyTransaksi" />
                                <CsRepeatDailyProductChart :data="csRepeatDailyByProduct" :loading="csRepeatLoading.dailyProduct" />
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Labels Tab -->
                <TabsContent value="labels" class="space-y-6">
                    <div class="grid gap-6 md:grid-cols-2">
                        <!-- Label Distribution Pie Chart -->
                        <Card class="border-0 shadow-lg">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <PieChart class="h-5 w-5" />
                                    Distribusi Label
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <!-- Simple Pie Chart Visualization -->
                                    <div v-if="labelDistribution.length > 0" class="relative mx-auto h-64 w-64">
                                        <svg viewBox="0 0 200 200" class="h-full w-full -rotate-90 transform">
                                            <template v-for="(label, index) in labelDistribution" :key="label.id">
                                                <path
                                                    :d="getArcPath(label, index)"
                                                    :fill="label.warna"
                                                    :stroke="label.warna"
                                                    stroke-width="2"
                                                    class="cursor-pointer drop-shadow-sm transition-all duration-200 hover:brightness-110 hover:scale-105"
                                                    @mouseenter="handleMouseEnter(label, $event)"
                                                    @mouseleave="handleMouseLeave"
                                                    @mousemove="handleMouseMove($event)"
                                                />
                                            </template>
                                        </svg>
                                        <!-- Center text -->
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <div class="rounded-full bg-white/90 p-4 text-center backdrop-blur-sm dark:bg-gray-900/90">
                                                <p class="text-2xl font-bold">{{ labelStats.total }}</p>
                                                <p class="text-sm text-muted-foreground">Total Labels</p>
                                            </div>
                                        </div>
                                        
                                        <!-- Custom Tooltip -->
                                        <Teleport to="body">
                                            <div
                                                v-if="showTooltip && hoveredLabel"
                                                class="pointer-events-none fixed z-50 rounded-lg bg-gray-900 px-3 py-2 text-sm text-white shadow-lg dark:bg-gray-100 dark:text-gray-900"
                                                :style="{
                                                    left: tooltipPosition.x + 'px',
                                                    top: tooltipPosition.y + 'px'
                                                }"
                                            >
                                                <div class="font-semibold">{{ hoveredLabel.nama }}</div>
                                                <div class="text-xs opacity-90">
                                                    Jumlah: <span class="font-medium">{{ hoveredLabel.count }}</span>
                                                </div>
                                                <div class="text-xs opacity-90">
                                                    Persentase: <span class="font-medium">{{ hoveredLabel.percentage }}%</span>
                                                </div>
                                            </div>
                                        </Teleport>
                                        
                                        <!-- Progress Bar Tooltip -->
                                        <Teleport to="body">
                                            <div
                                                v-if="showProgressTooltip && hoveredProgressLabel"
                                                class="pointer-events-none fixed z-50 rounded-lg bg-gray-900 px-3 py-2 text-sm text-white shadow-lg dark:bg-gray-100 dark:text-gray-900"
                                                :style="{
                                                    left: tooltipPosition.x + 'px',
                                                    top: tooltipPosition.y + 'px'
                                                }"
                                            >
                                                <div class="font-semibold">{{ hoveredProgressLabel.nama }}</div>
                                                <div class="text-xs opacity-90">
                                                    Total Leads: <span class="font-medium">{{ hoveredProgressLabel.count }}</span>
                                                </div>
                                                <div class="text-xs opacity-90">
                                                    Persentase: <span class="font-medium">{{ hoveredProgressLabel.percentage }}%</span>
                                                </div>
                                                <div class="text-xs opacity-90">
                                                    Dari Total: <span class="font-medium">{{ labelDistribution.reduce((sum, item) => sum + item.count, 0) }}</span>
                                                </div>
                                            </div>
                                        </Teleport>
                                    </div>
                                    
                                    <!-- No Data Message -->
                                    <div v-else class="flex h-64 flex-col items-center justify-center text-muted-foreground">
                                        <PieChart class="mb-4 h-16 w-16 opacity-50" />
                                        <p class="text-lg font-medium">Tidak ada data label</p>
                                        <p class="text-sm">Data distribusi label akan muncul di sini</p>
                                    </div>

                                    <!-- Legend -->
                                    <div v-if="labelDistribution.length > 0" class="space-y-2">
                                        <div
                                            v-for="label in labelDistribution"
                                            :key="label.id"
                                            class="flex items-center justify-between rounded-lg p-2 transition-colors hover:bg-muted/50"
                                        >
                                            <div class="flex items-center gap-3">
                                                <div class="h-4 w-4 rounded-full" :style="{ backgroundColor: label.warna }"></div>
                                                <span class="font-medium">{{ label.nama }}</span>
                                            </div>
                                            <div class="text-right">
                                                <span class="font-bold">{{ label.count }}</span>
                                                <span class="ml-1 text-sm text-muted-foreground">({{ label.percentage }}%)</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Label Statistics -->
                        <Card class="border-0 shadow-lg">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Tag class="h-5 w-5" />
                                    Label Statistics
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div v-if="labelDistribution.length > 0" class="grid gap-4">
                                    <div v-for="label in labelDistribution.slice(0, 5)" :key="label.id" class="rounded-lg border p-4">
                                        <div class="mb-2 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <div class="h-3 w-3 rounded-full" :style="{ backgroundColor: label.warna }"></div>
                                                <span class="font-medium">{{ label.nama }}</span>
                                            </div>
                                            <span class="text-sm font-bold">{{ label.count }} leads</span>
                                        </div>
                                        <div 
                                            class="cursor-pointer"
                                            @mouseenter="handleProgressMouseEnter(label, $event)"
                                            @mouseleave="handleProgressMouseLeave"
                                            @mousemove="handleProgressMouseMove($event)"
                                        >
                                            <Progress :value="label.percentage" class="h-2 transition-all duration-200 hover:h-3" />
                                        </div>
                                        <p class="mt-1 text-xs text-muted-foreground">{{ label.percentage }}% dari total leads</p>
                                    </div>
                                </div>
                                
                                <!-- No Data Message for Label Statistics -->
                                <div v-else class="flex h-32 flex-col items-center justify-center text-muted-foreground">
                                    <Tag class="mb-2 h-8 w-8 opacity-50" />
                                    <p class="text-sm">Tidak ada data statistik label</p>
                                </div>

                                <div v-if="labelDistribution.length > 0" class="mt-4 rounded-lg bg-muted/50 p-4">
                                    <h4 class="mb-2 font-medium">Summary</h4>
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <p class="text-muted-foreground">Total Labels</p>
                                            <p class="font-bold">{{ labelStats.total }}</p>
                                        </div>
                                        <div>
                                            <p class="text-muted-foreground">Most Used</p>
                                            <p class="font-bold">{{ labelDistribution[0]?.nama || 'N/A' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <!-- Marketing Tab -->
                <TabsContent value="marketing" class="space-y-6">
                    <div class="grid gap-6 md:grid-cols-2">
                        <!-- Top Marketing Performance -->
                        <Card class="border-0 shadow-lg">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Briefcase class="h-5 w-5" />
                                    Top Marketing Performance
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <div
                                        v-for="marketing in topMarketing.slice(0, 5)"
                                        :key="marketing.id"
                                        class="flex items-center justify-between rounded-lg border p-4 transition-colors hover:bg-muted/50"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-900/30">
                                                <Users class="h-4 w-4 text-blue-600" />
                                            </div>
                                            <div>
                                                <p class="font-medium">{{ marketing.name }}</p>
                                                <p class="text-sm text-muted-foreground">{{ marketing.email }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold">{{ marketing.total_leads }} leads</p>
                                            <p class="text-sm text-green-600">{{ marketing.closing_rate }}% closed</p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Marketing Statistics -->
                        <Card class="border-0 shadow-lg">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <BarChart3 class="h-5 w-5" />
                                    Marketing Overview
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid gap-4">
                                    <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm text-muted-foreground">Total Marketing</p>
                                                <p class="text-2xl font-bold">{{ userStats.marketing }}</p>
                                            </div>
                                            <Briefcase class="h-8 w-8 text-blue-600" />
                                        </div>
                                    </div>

                                    <div class="rounded-lg bg-green-50 p-4 dark:bg-green-900/20">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm text-muted-foreground">Avg. Closing Rate</p>
                                                <p class="text-2xl font-bold">
                                                    {{
                                                        Math.round(
                                                            topMarketing.reduce((sum, m) => sum + m.closing_rate, 0) /
                                                                Math.max(topMarketing.length, 1),
                                                        )
                                                    }}%
                                                </p>
                                            </div>
                                            <Target class="h-8 w-8 text-green-600" />
                                        </div>
                                    </div>

                                    <div class="rounded-lg bg-purple-50 p-4 dark:bg-purple-900/20">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm text-muted-foreground">Total Leads</p>
                                                <p class="text-2xl font-bold">{{ topMarketing.reduce((sum, m) => sum + m.total_leads, 0) }}</p>
                                            </div>
                                            <Users class="h-8 w-8 text-purple-600" />
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <!-- Brands Tab -->
                <TabsContent value="brands" class="space-y-6">
                    <div class="grid gap-6 md:grid-cols-2">
                        <!-- Brand Performance -->
                        <Card class="border-0 shadow-lg">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Building2 class="h-5 w-5" />
                                    Brand Performance
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <div
                                        v-for="brand in brandPerformance.slice(0, 5)"
                                        :key="brand.id"
                                        class="flex items-center justify-between rounded-lg border p-4 transition-colors hover:bg-muted/50"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800">
                                                <img v-if="brand.logo_url" :src="brand.logo_url" :alt="brand.nama" class="h-8 w-8 object-contain" />
                                                <Building2 v-else class="h-5 w-5 text-muted-foreground" />
                                            </div>
                                            <div>
                                                <p class="font-medium">{{ brand.nama }}</p>
                                                <p class="text-sm text-muted-foreground">{{ brand.total_leads }} leads</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold">{{ brand.closed_leads }} closed</p>
                                            <p class="text-sm text-green-600">{{ brand.closing_rate }}%</p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Brand Statistics -->
                        <Card class="border-0 shadow-lg">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Zap class="h-5 w-5" />
                                    Brand Overview
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid gap-4">
                                    <div class="rounded-lg bg-purple-50 p-4 dark:bg-purple-900/20">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm text-muted-foreground">Total Brands</p>
                                                <p class="text-2xl font-bold">{{ brandStats.total }}</p>
                                            </div>
                                            <Building2 class="h-8 w-8 text-purple-600" />
                                        </div>
                                    </div>

                                    <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm text-muted-foreground">With Logo</p>
                                                <p class="text-2xl font-bold">{{ brandStats.with_logo }}</p>
                                            </div>
                                            <Zap class="h-8 w-8 text-blue-600" />
                                        </div>
                                    </div>

                                    <div class="rounded-lg bg-green-50 p-4 dark:bg-green-900/20">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm text-muted-foreground">Best Performer</p>
                                                <p class="text-lg font-bold">{{ brandPerformance[0]?.nama || 'N/A' }}</p>
                                            </div>
                                            <Award class="h-8 w-8 text-green-600" />
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <!-- Trends Tab -->
                <TabsContent value="trends" class="space-y-6">
                    <div class="grid gap-6">
                        <!-- Daily Trends Chart -->
                        <Card class="border-0 shadow-lg">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <TrendingUp class="h-5 w-5" />
                                    Daily Trends
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <!-- Simple Line Chart Visualization -->
                                    <div class="flex h-64 items-end justify-between gap-2 rounded-lg bg-muted/20 p-4">
                                        <div
                                            v-for="(trend, index) in dailyTrends.slice(-10)"
                                            :key="trend.date"
                                            class="flex flex-1 flex-col items-center gap-2"
                                        >
                                            <div class="flex flex-col items-center gap-1">
                                                <!-- Total bar -->
                                                <div
                                                    class="w-4 rounded-t bg-blue-500 transition-all duration-300 hover:bg-blue-600"
                                                    :style="{
                                                        height: `${Math.max((trend.total / Math.max(...dailyTrends.map((d) => d.total))) * 150, 4)}px`,
                                                    }"
                                                    :title="`Total: ${trend.total}`"
                                                ></div>
                                                <!-- Follow up bar -->
                                                <div
                                                    class="w-4 rounded-t bg-green-500 transition-all duration-300 hover:bg-green-600"
                                                    :style="{
                                                        height: `${Math.max((trend.followup / Math.max(...dailyTrends.map((d) => d.followup))) * 100, 2)}px`,
                                                    }"
                                                    :title="`Follow up: ${trend.followup}`"
                                                ></div>
                                            </div>
                                            <div class="text-center text-xs text-muted-foreground">
                                                {{ trend.date_formatted }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Legend -->
                                    <div class="flex justify-center gap-6">
                                        <div class="flex items-center gap-2">
                                            <div class="h-3 w-3 rounded bg-blue-500"></div>
                                            <span class="text-sm">Total Leads</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="h-3 w-3 rounded bg-green-500"></div>
                                            <span class="text-sm">Follow Up</span>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Trend Statistics -->
                        <div class="grid gap-6 md:grid-cols-3">
                            <Card class="border-0 shadow-lg">
                                <CardContent class="p-6">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm text-muted-foreground">Today's Growth</p>
                                            <p class="flex items-center gap-2 text-2xl font-bold">
                                                +{{ mitraStats.today }}
                                                <TrendingUp class="h-5 w-5 text-green-600" />
                                            </p>
                                        </div>
                                        <Calendar class="h-8 w-8 text-blue-600" />
                                    </div>
                                </CardContent>
                            </Card>

                            <Card class="border-0 shadow-lg">
                                <CardContent class="p-6">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm text-muted-foreground">This Week</p>
                                            <p class="text-2xl font-bold">+{{ mitraStats.this_week }}</p>
                                        </div>
                                        <Activity class="h-8 w-8 text-green-600" />
                                    </div>
                                </CardContent>
                            </Card>

                            <Card class="border-0 shadow-lg">
                                <CardContent class="p-6">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm text-muted-foreground">This Month</p>
                                            <p class="text-2xl font-bold">+{{ mitraStats.this_month }}</p>
                                        </div>
                                        <BarChart3 class="h-8 w-8 text-purple-600" />
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </TabsContent>

                <!-- Summary Report Tab -->

            </Tabs>



            <!-- Statistics Cards -->
            <div class="grid grid-cols-2 gap-6 lg:grid-cols-4">
                <Card class="relative overflow-hidden border-0 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950 dark:to-blue-900">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-blue-700 dark:text-blue-300">Total Users</CardTitle>
                    </CardHeader>
                    <div class="absolute top-3 right-3 text-blue-500/60">
                        <Users class="h-4 w-4" />
                    </div>
                    <CardContent>
                        <div class="text-3xl font-bold text-blue-900 dark:text-blue-100">{{ userStats.total }}</div>
                        <p class="mt-1 flex items-center text-xs text-blue-600 dark:text-blue-400">
                            <TrendingUp class="mr-1 h-3 w-3" />
                            Total pengguna aktif
                        </p>
                    </CardContent>
                    <div class="absolute right-0 bottom-0 -mr-8 -mb-8 h-16 w-16 rounded-full bg-blue-200/30"></div>
                </Card>

                <Card class="relative overflow-hidden border-0 bg-gradient-to-br from-red-50 to-red-100 dark:from-red-950 dark:to-red-900">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-red-700 dark:text-red-300">Super Admin</CardTitle>
                    </CardHeader>
                    <div class="absolute top-3 right-3 text-red-500/60">
                        <Shield class="h-4 w-4" />
                    </div>
                    <CardContent>
                        <div class="text-3xl font-bold text-red-900 dark:text-red-100">{{ userStats.super_admin }}</div>
                        <p class="mt-1 flex items-center text-xs text-red-600 dark:text-red-400">
                            <Activity class="mr-1 h-3 w-3" />
                            Akses penuh sistem
                        </p>
                    </CardContent>
                    <div class="absolute right-0 bottom-0 -mr-8 -mb-8 h-16 w-16 rounded-full bg-red-200/30"></div>
                </Card>

                <Card class="relative overflow-hidden border-0 bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-950 dark:to-amber-900">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-amber-700 dark:text-amber-300">Admin</CardTitle>
                    </CardHeader>
                    <div class="absolute top-3 right-3 text-amber-500/60">
                        <UserCheck class="h-4 w-4" />
                    </div>
                    <CardContent>
                        <div class="text-3xl font-bold text-amber-900 dark:text-amber-100">{{ userStats.admin }}</div>
                        <p class="mt-1 flex items-center text-xs text-amber-600 dark:text-amber-400">
                            <BarChart3 class="mr-1 h-3 w-3" />
                            Kelola operasional
                        </p>
                    </CardContent>
                    <div class="absolute right-0 bottom-0 -mr-8 -mb-8 h-16 w-16 rounded-full bg-amber-200/30"></div>
                </Card>

                <Card class="relative overflow-hidden border-0 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950 dark:to-green-900">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-green-700 dark:text-green-300">Marketing</CardTitle>
                    </CardHeader>
                    <div class="absolute top-3 right-3 text-green-500/60">
                        <Briefcase class="h-4 w-4" />
                    </div>
                    <CardContent>
                        <div class="text-3xl font-bold text-green-900 dark:text-green-100">{{ userStats.marketing }}</div>
                        <p class="mt-1 flex items-center text-xs text-green-600 dark:text-green-400">
                            <Calendar class="mr-1 h-3 w-3" />
                            Tim pemasaran
                        </p>
                    </CardContent>
                    <div class="absolute right-0 bottom-0 -mr-8 -mb-8 h-16 w-16 rounded-full bg-green-200/30"></div>
                </Card>
            </div>

            <!-- Quick Actions - Full Width -->
            <Card class="border-0 shadow-lg">
                <CardHeader>
                    <CardTitle class="flex items-center text-xl">
                        <Activity class="mr-3 h-6 w-6 text-blue-500" />
                        Aksi Cepat
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Link href="/users" class="group">
                            <div
                                class="relative overflow-hidden rounded-xl bg-gradient-to-r from-blue-500 to-blue-600 p-6 text-white transition-all duration-300 hover:scale-105 hover:from-blue-600 hover:to-blue-700 hover:shadow-xl"
                            >
                                <div class="flex items-center">
                                    <div class="mr-4 rounded-lg bg-white/20 p-2">
                                        <Users class="h-6 w-6" />
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold">Tambah User</h3>
                                        <p class="text-sm text-blue-100">Buat pengguna baru</p>
                                    </div>
                                </div>
                                <div class="absolute top-0 right-0 -mt-10 -mr-10 h-20 w-20 rounded-full bg-white/10"></div>
                            </div>
                        </Link>

                        <Link href="/brands" class="group">
                            <div
                                class="relative overflow-hidden rounded-xl bg-gradient-to-r from-purple-500 to-purple-600 p-6 text-white transition-all duration-300 hover:scale-105 hover:from-purple-600 hover:to-purple-700 hover:shadow-xl"
                            >
                                <div class="flex items-center">
                                    <div class="mr-4 rounded-lg bg-white/20 p-2">
                                        <Tag class="h-6 w-6" />
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold">Tambah Brand</h3>
                                        <p class="text-sm text-purple-100">Buat brand baru</p>
                                    </div>
                                </div>
                                <div class="absolute top-0 right-0 -mt-10 -mr-10 h-20 w-20 rounded-full bg-white/10"></div>
                            </div>
                        </Link>

                        <Link href="/labels" class="group">
                            <div
                                class="relative overflow-hidden rounded-xl bg-gradient-to-r from-orange-500 to-orange-600 p-6 text-white transition-all duration-300 hover:scale-105 hover:from-orange-600 hover:to-orange-700 hover:shadow-xl"
                            >
                                <div class="flex items-center">
                                    <div class="mr-4 rounded-lg bg-white/20 p-2">
                                        <Target class="h-6 w-6" />
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold">Tambah Label</h3>
                                        <p class="text-sm text-orange-100">Buat label baru</p>
                                    </div>
                                </div>
                                <div class="absolute top-0 right-0 -mt-10 -mr-10 h-20 w-20 rounded-full bg-white/10"></div>
                            </div>
                        </Link>

                        <Link href="/users" class="group">
                            <div
                                class="relative overflow-hidden rounded-xl bg-gradient-to-r from-green-500 to-green-600 p-6 text-white transition-all duration-300 hover:scale-105 hover:from-green-600 hover:to-green-700 hover:shadow-xl"
                            >
                                <div class="flex items-center">
                                    <div class="mr-4 rounded-lg bg-white/20 p-2">
                                        <Users class="h-6 w-6" />
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold">Kelola Users</h3>
                                        <p class="text-sm text-green-100">Lihat dan edit semua pengguna</p>
                                    </div>
                                </div>
                                <div class="absolute top-0 right-0 -mt-10 -mr-10 h-20 w-20 rounded-full bg-white/10"></div>
                            </div>
                        </Link>
                    </div>
                </CardContent>
            </Card>

            <!-- Main Content Grid -->
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- System Status -->
                <Card class="hidden border-0 shadow-lg">
                    <CardHeader>
                        <CardTitle class="flex items-center text-xl">
                            <Clock class="mr-3 h-6 w-6 text-green-500" />
                            Status Sistem
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div class="flex items-center rounded-lg bg-green-50 p-4 dark:bg-green-950/20">
                                <div class="mr-3 h-3 w-3 animate-pulse rounded-full bg-green-500"></div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-green-800 dark:text-green-200">Server Online</p>
                                    <p class="text-xs text-green-600 dark:text-green-400">Semua sistem berjalan normal</p>
                                </div>
                            </div>

                            <div class="flex items-center rounded-lg bg-blue-50 p-4 dark:bg-blue-950/20">
                                <div class="mr-3 h-3 w-3 animate-pulse rounded-full bg-blue-500"></div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Database Aktif</p>
                                    <p class="text-xs text-blue-600 dark:text-blue-400">Koneksi database stabil</p>
                                </div>
                            </div>

                            <div class="flex items-center rounded-lg bg-purple-50 p-4 dark:bg-purple-950/20">
                                <div class="mr-3 h-3 w-3 animate-pulse rounded-full bg-purple-500"></div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-purple-800 dark:text-purple-200">CRUD Tersedia</p>
                                    <p class="text-xs text-purple-600 dark:text-purple-400">Operasi user management aktif</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
