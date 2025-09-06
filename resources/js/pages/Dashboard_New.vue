<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    Users, UserCheck, Shield, Briefcase, Plus, BarChart3, TrendingUp, Activity, 
    Clock, Calendar, MessageSquare, Target, Award, ChevronUp, ChevronDown,
    Phone, Mail, MapPin, Building2, Zap, Eye, Filter, RefreshCw,
    TrendingDown, ArrowUpRight, ArrowDownRight, Percent, Tag
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

            <!-- Marketing Performance Overview -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Chat Analytics per Marketing -->
                <Card class="border-0 shadow-lg">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Users class="h-5 w-5" />
                            Performa Marketing Hari Ini
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div 
                                v-for="marketing in chatAnalytics.slice(0, 5)" 
                                :key="marketing.id"
                                class="flex items-center justify-between p-4 border rounded-lg"
                            >
                                <div>
                                    <p class="font-medium">{{ marketing.name }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ marketing.today_leads }} leads hari ini
                                    </p>
                                </div>
                                <div class="text-right">
                                    <Badge :variant="marketing.conversion_rate >= 50 ? 'default' : 'secondary'">
                                        {{ marketing.conversion_rate }}%
                                    </Badge>
                                    <p class="text-xs text-muted-foreground mt-1">
                                        {{ marketing.followup_leads }}/{{ marketing.total_leads }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Top Performing Marketing -->
                <Card class="border-0 shadow-lg">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Award class="h-5 w-5" />
                            Top Performing Marketing
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div 
                                v-for="(marketing, index) in topMarketing.slice(0, 5)" 
                                :key="marketing.id"
                                class="flex items-center justify-between p-4 border rounded-lg"
                            >
                                <div class="flex items-center gap-3">
                                    <div 
                                        :class="[
                                            'w-8 h-8 rounded-full flex items-center justify-center text-white font-bold',
                                            index === 0 ? 'bg-yellow-500' : 
                                            index === 1 ? 'bg-gray-400' : 
                                            index === 2 ? 'bg-orange-500' : 'bg-blue-500'
                                        ]"
                                    >
                                        {{ index + 1 }}
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ marketing.name }}</p>
                                        <p class="text-sm text-muted-foreground">
                                            {{ marketing.total_leads }} total leads
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <Badge variant="default">
                                        {{ marketing.closing_rate }}%
                                    </Badge>
                                    <p class="text-xs text-muted-foreground mt-1">
                                        {{ marketing.closed_leads }} closed
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Label Distribution -->
            <Card class="border-0 shadow-lg">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Tag class="h-5 w-5" />
                        Distribusi Label (Yang Paling Banyak)
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div 
                            v-for="label in labelDistribution.slice(0, 6)" 
                            :key="label.id"
                            class="p-4 border rounded-lg"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <div 
                                        class="w-4 h-4 rounded-full"
                                        :style="{ backgroundColor: label.warna }"
                                    ></div>
                                    <span class="font-medium">{{ label.nama }}</span>
                                </div>
                                <Badge variant="outline">{{ label.percentage }}%</Badge>
                            </div>
                            <Progress :value="label.percentage" class="h-2" />
                            <p class="text-sm text-muted-foreground mt-2">
                                {{ label.count }} dari {{ mitraStats.total }} leads
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Brand Performance -->
            <Card class="border-0 shadow-lg">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Zap class="h-5 w-5" />
                        Performa per Brand & Persentase Closing
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div 
                            v-for="brand in brandPerformance.slice(0, 8)" 
                            :key="brand.id"
                            class="flex items-center justify-between p-4 border rounded-lg"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded-lg flex items-center justify-center">
                                    <img 
                                        v-if="brand.logo_url" 
                                        :src="brand.logo_url" 
                                        :alt="brand.nama" 
                                        class="w-6 h-6 object-contain"
                                    />
                                    <Zap v-else class="h-5 w-5 text-gray-400" />
                                </div>
                                <div>
                                    <p class="font-medium">{{ brand.nama }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ brand.total_leads }} total leads
                                    </p>
                                </div>
                            </div>
                            <div class="text-right flex items-center gap-4">
                                <div>
                                    <p class="text-lg font-bold">{{ brand.closing_rate }}%</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ brand.closed_leads }}/{{ brand.total_leads }}
                                    </p>
                                </div>
                                <Progress :value="brand.closing_rate" class="w-16 h-2" />
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Closing Analysis -->
            <Card class="border-0 shadow-lg">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Award class="h-5 w-5" />
                        Analisa Closing Rate Overall
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
                            v-for="activity in recentActivities.slice(0, 8)" 
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
                                        <span v-if="activity.label" class="ml-2">
                                            • <span :style="{ color: activity.label.warna }">{{ activity.label.nama }}</span>
                                        </span>
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
        </div>
    </AppLayout>
</template>

<style scoped>
/* Custom scrollbar for trends */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.3);
}
</style>
