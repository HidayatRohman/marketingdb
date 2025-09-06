<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    Users, UserCheck, Shield, Briefcase, Plus, BarChart3, TrendingUp, Activity, 
    Clock, Calendar, MessageSquare, Target, Award, ChevronUp, ChevronDown,
    Phone, Mail, MapPin, Building2, Zap, Eye, Filter, RefreshCw,
    TrendingDown, ArrowUpRight, ArrowDownRight, Percent
} from 'lucide-vue-next';
import { ref, computed, onMounted } from 'vue';

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

interface Props {
    userStats: UserStats;
    mitraStats: MitraStats;
    brandStats: BrandStats;
    labelStats: LabelStats;
    chatAnalytics: ChatAnalytic[];
    periodAnalytics: ChatAnalytic[];
    labelDistribution: LabelDistribution[];
    closingAnalysis: ClosingAnalysis;
    dailyTrends: DailyTrend[];
    topMarketing: TopMarketing[];
    brandPerformance: BrandPerformance[];
    recentActivities: RecentActivity[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

// Refs for filters
const startDate = ref('');
const endDate = ref('');
const refreshing = ref(false);

// Computed values
const totalConversionRate = computed(() => {
    return props.mitraStats.total > 0 ? 
        Math.round((props.mitraStats.followup / props.mitraStats.total) * 100) : 0;
});

const growthIndicators = computed(() => {
    return {
        daily: props.mitraStats.today,
        weekly: props.mitraStats.this_week,
        monthly: props.mitraStats.this_month,
    };
});

// Functions
const refreshData = () => {
    refreshing.value = true;
    router.reload({ 
        only: ['chatAnalytics', 'periodAnalytics', 'dailyTrends', 'closingAnalysis', 'recentActivities'],
        onFinish: () => {
            refreshing.value = false;
        }
    });
};

const applyDateFilter = () => {
    if (startDate.value && endDate.value) {
        router.get('/dashboard', {
            start_date: startDate.value,
            end_date: endDate.value,
        }, {
            preserveState: true,
            replace: true,
        });
    }
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

onMounted(() => {
    // Set default date range to current month
    const now = new Date();
    startDate.value = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
    endDate.value = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];
});
</script>

<template>
    <Head title="Analytics Dashboard" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-8">
            <!-- Enhanced Welcome Section -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 p-8 text-white">
                <div class="relative z-10">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-4xl font-bold tracking-tight mb-2 flex items-center gap-3">
                                <BarChart3 class="h-10 w-10" />
                                Analytics Dashboard
                            </h1>
                            <p class="text-xl text-blue-100 mb-6">
                                Pantau performa marketing dan analisa data lead secara real-time
                            </p>
                        </div>
                        <div class="flex gap-4">
                            <Button 
                                @click="refreshData"
                                :disabled="refreshing"
                                class="bg-white/20 backdrop-blur-sm text-white hover:bg-white/30 border-white/30"
                            >
                                <RefreshCw :class="['mr-2 h-4 w-4', refreshing && 'animate-spin']" />
                                Refresh
                            </Button>
                            <Link href="/mitras/create">
                                <Button class="bg-white text-blue-600 hover:bg-blue-50 font-semibold">
                                    <Plus class="mr-2 h-5 w-5" />
                                    Lead Baru
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/5 rounded-full -ml-24 -mb-24"></div>
            </div>

            <!-- Date Filter Section -->
            <Card class="border-0 shadow-lg">
                <CardContent class="p-6">
                    <div class="flex flex-col sm:flex-row gap-4 items-center">
                        <div class="flex items-center gap-2">
                            <Calendar class="h-5 w-5 text-muted-foreground" />
                            <span class="font-medium">Filter Periode:</span>
                        </div>
                        <div class="flex gap-2 items-center">
                            <Label for="start-date" class="text-sm">Dari:</Label>
                            <Input
                                id="start-date"
                                v-model="startDate"
                                type="date"
                                class="w-auto"
                            />
                            <Label for="end-date" class="text-sm">Sampai:</Label>
                            <Input
                                id="end-date"
                                v-model="endDate"
                                type="date"
                                class="w-auto"
                            />
                            <Button @click="applyDateFilter" size="sm">
                                <Filter class="h-4 w-4 mr-2" />
                                Terapkan
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Main KPI Cards -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <!-- Total Leads -->
                <Card class="border-0 shadow-lg bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950 dark:to-blue-900">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-blue-700 dark:text-blue-300">Total Leads</p>
                                <p class="text-3xl font-bold text-blue-900 dark:text-blue-100">{{ mitraStats.total }}</p>
                                <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                    +{{ mitraStats.today }} hari ini
                                </p>
                            </div>
                            <div class="p-3 bg-blue-500 rounded-lg">
                                <Users class="h-6 w-6 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Conversion Rate -->
                <Card class="border-0 shadow-lg bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950 dark:to-green-900">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-green-700 dark:text-green-300">Conversion Rate</p>
                                <p class="text-3xl font-bold text-green-900 dark:text-green-100 flex items-center gap-2">
                                    {{ totalConversionRate }}%
                                    <component 
                                        :is="getGrowthIcon(totalConversionRate)" 
                                        :class="['h-5 w-5', getGrowthColor(totalConversionRate)]"
                                    />
                                </p>
                                <p class="text-xs text-green-600 dark:text-green-400 mt-1">
                                    {{ mitraStats.followup }} dari {{ mitraStats.total }}
                                </p>
                            </div>
                            <div class="p-3 bg-green-500 rounded-lg">
                                <Target class="h-6 w-6 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Active Chats -->
                <Card class="border-0 shadow-lg bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-950 dark:to-orange-900">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-orange-700 dark:text-orange-300">Chat Masuk</p>
                                <p class="text-3xl font-bold text-orange-900 dark:text-orange-100">{{ mitraStats.masuk }}</p>
                                <p class="text-xs text-orange-600 dark:text-orange-400 mt-1">
                                    {{ mitraStats.this_week }} minggu ini
                                </p>
                            </div>
                            <div class="p-3 bg-orange-500 rounded-lg">
                                <MessageSquare class="h-6 w-6 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Follow Ups -->
                <Card class="border-0 shadow-lg bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950 dark:to-purple-900">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-purple-700 dark:text-purple-300">Follow Up</p>
                                <p class="text-3xl font-bold text-purple-900 dark:text-purple-100">{{ mitraStats.followup }}</p>
                                <p class="text-xs text-purple-600 dark:text-purple-400 mt-1">
                                    {{ mitraStats.this_month }} bulan ini
                                </p>
                            </div>
                            <div class="p-3 bg-purple-500 rounded-lg">
                                <Phone class="h-6 w-6 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Tabs for Different Analytics Views -->
            <Tabs default-value="overview" class="w-full">
                <TabsList class="grid w-full grid-cols-5">
                    <TabsTrigger value="overview">Overview</TabsTrigger>
                    <TabsTrigger value="marketing">Per Marketing</TabsTrigger>
                    <TabsTrigger value="brands">Per Brand</TabsTrigger>
                    <TabsTrigger value="labels">Label Analysis</TabsTrigger>
                    <TabsTrigger value="trends">Trends</TabsTrigger>
                </TabsList>

                <!-- Overview Tab -->
                <TabsContent value="overview" class="space-y-6">
                    <div class="grid gap-6 md:grid-cols-2">
                        <!-- Closing Analysis -->
                        <Card class="border-0 shadow-lg">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Award class="h-5 w-5" />
                                    Analisa Closing Rate
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="flex items-center justify-between p-4 bg-muted/50 rounded-lg">
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
                        <Card class="border-0 shadow-lg">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Activity class="h-5 w-5" />
                                    System Overview
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                        <Users class="h-6 w-6 mx-auto mb-2 text-blue-600" />
                                        <p class="text-sm text-muted-foreground">Total Users</p>
                                        <p class="text-xl font-bold">{{ userStats.total }}</p>
                                    </div>
                                    <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                        <Briefcase class="h-6 w-6 mx-auto mb-2 text-green-600" />
                                        <p class="text-sm text-muted-foreground">Marketing</p>
                                        <p class="text-xl font-bold">{{ userStats.marketing }}</p>
                                    </div>
                                    <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                                        <Zap class="h-6 w-6 mx-auto mb-2 text-purple-600" />
                                        <p class="text-sm text-muted-foreground">Brands</p>
                                        <p class="text-xl font-bold">{{ brandStats.total }}</p>
                                    </div>
                                    <div class="text-center p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                                        <Tag class="h-6 w-6 mx-auto mb-2 text-orange-600" />
                                        <p class="text-sm text-muted-foreground">Labels</p>
                                        <p class="text-xl font-bold">{{ labelStats.total }}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Recent Activities -->
                    <Card class="border-0 shadow-lg">
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
                                    class="flex items-center justify-between p-3 border rounded-lg hover:bg-muted/50 transition-colors"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
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
                                        <Badge 
                                            :variant="activity.chat === 'followup' ? 'default' : 'secondary'"
                                            class="mb-1"
                                        >
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
                </TabsContent>
                        </Link>
                        <Link href="/users">
                            <Button variant="outline" class="border-white text-white hover:bg-white/10">
                                <Users class="mr-2 h-5 w-5" />
                                Kelola Users
                            </Button>
                        </Link>
                    </div>
                </div>
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/5 rounded-full -ml-24 -mb-24"></div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <Card class="relative overflow-hidden border-0 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950 dark:to-blue-900">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-blue-700 dark:text-blue-300">Total Users</CardTitle>
                        <div class="p-2 bg-blue-500 rounded-lg">
                            <Users class="h-5 w-5 text-white" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-blue-900 dark:text-blue-100">{{ userStats.total }}</div>
                        <p class="text-xs text-blue-600 dark:text-blue-400 flex items-center mt-1">
                            <TrendingUp class="h-3 w-3 mr-1" />
                            Total pengguna aktif
                        </p>
                    </CardContent>
                    <div class="absolute bottom-0 right-0 w-16 h-16 bg-blue-200/30 rounded-full -mr-8 -mb-8"></div>
                </Card>

                <Card class="relative overflow-hidden border-0 bg-gradient-to-br from-red-50 to-red-100 dark:from-red-950 dark:to-red-900">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-red-700 dark:text-red-300">Super Admin</CardTitle>
                        <div class="p-2 bg-red-500 rounded-lg">
                            <Shield class="h-5 w-5 text-white" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-red-900 dark:text-red-100">{{ userStats.super_admin }}</div>
                        <p class="text-xs text-red-600 dark:text-red-400 flex items-center mt-1">
                            <Activity class="h-3 w-3 mr-1" />
                            Akses penuh sistem
                        </p>
                    </CardContent>
                    <div class="absolute bottom-0 right-0 w-16 h-16 bg-red-200/30 rounded-full -mr-8 -mb-8"></div>
                </Card>

                <Card class="relative overflow-hidden border-0 bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-950 dark:to-amber-900">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-amber-700 dark:text-amber-300">Admin</CardTitle>
                        <div class="p-2 bg-amber-500 rounded-lg">
                            <UserCheck class="h-5 w-5 text-white" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-amber-900 dark:text-amber-100">{{ userStats.admin }}</div>
                        <p class="text-xs text-amber-600 dark:text-amber-400 flex items-center mt-1">
                            <BarChart3 class="h-3 w-3 mr-1" />
                            Kelola operasional
                        </p>
                    </CardContent>
                    <div class="absolute bottom-0 right-0 w-16 h-16 bg-amber-200/30 rounded-full -mr-8 -mb-8"></div>
                </Card>

                <Card class="relative overflow-hidden border-0 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950 dark:to-green-900">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-green-700 dark:text-green-300">Marketing</CardTitle>
                        <div class="p-2 bg-green-500 rounded-lg">
                            <Briefcase class="h-5 w-5 text-white" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-green-900 dark:text-green-100">{{ userStats.marketing }}</div>
                        <p class="text-xs text-green-600 dark:text-green-400 flex items-center mt-1">
                            <Calendar class="h-3 w-3 mr-1" />
                            Tim pemasaran
                        </p>
                    </CardContent>
                    <div class="absolute bottom-0 right-0 w-16 h-16 bg-green-200/30 rounded-full -mr-8 -mb-8"></div>
                </Card>
            </div>

            <!-- Main Content Grid -->
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Quick Actions -->
                <div class="lg:col-span-2">
                    <Card class="border-0 shadow-lg">
                        <CardHeader>
                            <CardTitle class="flex items-center text-xl">
                                <Activity class="mr-3 h-6 w-6 text-blue-500" />
                                Aksi Cepat
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <Link href="/users" class="group">
                                    <div class="relative overflow-hidden rounded-xl bg-gradient-to-r from-blue-500 to-blue-600 p-6 text-white transition-all duration-300 hover:from-blue-600 hover:to-blue-700 hover:scale-105 hover:shadow-xl">
                                        <div class="flex items-center">
                                            <div class="p-2 bg-white/20 rounded-lg mr-4">
                                                <Users class="h-6 w-6" />
                                            </div>
                                            <div>
                                                <h3 class="font-semibold text-lg">Kelola Users</h3>
                                                <p class="text-blue-100 text-sm">Lihat dan edit semua pengguna</p>
                                            </div>
                                        </div>
                                        <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -mr-10 -mt-10"></div>
                                    </div>
                                </Link>
                                
                                <Link href="/users/create" class="group">
                                    <div class="relative overflow-hidden rounded-xl bg-gradient-to-r from-green-500 to-green-600 p-6 text-white transition-all duration-300 hover:from-green-600 hover:to-green-700 hover:scale-105 hover:shadow-xl">
                                        <div class="flex items-center">
                                            <div class="p-2 bg-white/20 rounded-lg mr-4">
                                                <Plus class="h-6 w-6" />
                                            </div>
                                            <div>
                                                <h3 class="font-semibold text-lg">Tambah User</h3>
                                                <p class="text-green-100 text-sm">Buat pengguna baru</p>
                                            </div>
                                        </div>
                                        <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -mr-10 -mt-10"></div>
                                    </div>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- System Status -->
                <Card class="border-0 shadow-lg">
                    <CardHeader>
                        <CardTitle class="flex items-center text-xl">
                            <Clock class="mr-3 h-6 w-6 text-green-500" />
                            Status Sistem
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div class="flex items-center p-4 bg-green-50 dark:bg-green-950/20 rounded-lg">
                                <div class="w-3 h-3 bg-green-500 rounded-full mr-3 animate-pulse"></div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-green-800 dark:text-green-200">Server Online</p>
                                    <p class="text-xs text-green-600 dark:text-green-400">Semua sistem berjalan normal</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center p-4 bg-blue-50 dark:bg-blue-950/20 rounded-lg">
                                <div class="w-3 h-3 bg-blue-500 rounded-full mr-3 animate-pulse"></div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Database Aktif</p>
                                    <p class="text-xs text-blue-600 dark:text-blue-400">Koneksi database stabil</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center p-4 bg-purple-50 dark:bg-purple-950/20 rounded-lg">
                                <div class="w-3 h-3 bg-purple-500 rounded-full mr-3 animate-pulse"></div>
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
