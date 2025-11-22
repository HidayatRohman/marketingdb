<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import DatePicker from '@/components/ui/datepicker/DatePicker.vue';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { ChevronDown, Users, Building2, Filter, RefreshCw, X, Target } from 'lucide-vue-next';
import BrandPerformanceChart from '@/components/BrandPerformanceChart.vue';
import MarketingPerformanceChart from '@/components/MarketingPerformanceChart.vue';
import MonthlySpentChart from '@/components/MonthlySpentChart.vue';
import PaymentStatusChart from '@/components/PaymentStatusChart.vue';
import SourceAnalyticsChart from '@/components/SourceAnalyticsChart.vue';
import AgeAnalyticsChart from '@/components/AgeAnalyticsChart.vue';
import LeadAwalAnalyticsChart from '@/components/LeadAwalAnalyticsChart.vue';
import PekerjaanAnalyticsChart from '@/components/PekerjaanAnalyticsChart.vue';
import { TrendingUp } from 'lucide-vue-next';

interface BrandOption { id: number; nama: string }
interface MarketingOption { id: number; name: string }

interface BrandData {
  id: number;
  nama: string;
  logo_url?: string;
  total_leads: number;
  closed_leads: number;
  closing_rate: number;
}

interface MarketingData {
  id: number;
  name: string;
  email: string;
  total_leads: number;
  closed_leads: number;
  closing_rate: number;
}

interface Props {
  brandPerformance: BrandData[];
  topMarketing: MarketingData[];
  brands: BrandOption[];
  marketingUsers: MarketingOption[];
  filters: { start_date?: string; end_date?: string; marketing?: string; brand?: string };
  permissions: any;
}

const props = defineProps<Props>();

// Filters
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');
const selectedMarketing = ref(props.filters.marketing || 'all');
const selectedBrand = ref(props.filters.brand || 'all');
const selectedYear = ref<number>(new Date().getFullYear());
const showMarketingDropdown = ref(false);
const showBrandDropdown = ref(false);

// Derived labels
const brands = computed(() => props.brands || []);
const marketingUsers = computed(() => props.marketingUsers || []);
const selectedBrandName = computed(() => {
  if (selectedBrand.value === 'all' || !selectedBrand.value) return '';
  const b = brands.value.find(x => String(x.id) === String(selectedBrand.value));
  return b ? b.nama : '';
});

// Breadcrumbs
const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Analisa Bisnis', href: '/analisa-bisnis' },
];

// Charts state
const monthlySpentData = ref<any[]>([]);
const monthlySpentLoading = ref(false);

const paymentStatusData = ref<any[]>([]);
const paymentStatusLoading = ref(false);

const sourceChartData = ref<any[]>([]);
const sourceChartLoading = ref(false);

const ageChartData = ref<any[]>([]);
const ageChartLoading = ref(false);

const leadAwalChartData = ref<any[]>([]);
const leadAwalChartLoading = ref(false);

const jobChartData = ref<any[]>([]);
const jobChartLoading = ref(false);

// Fetch functions
const fetchMonthlySpent = async () => {
  monthlySpentLoading.value = true;
  try {
    const params = new URLSearchParams({
      year: String(selectedYear.value || new Date().getFullYear()),
    });
    if (selectedBrand.value && selectedBrand.value !== 'all') params.append('brand_id', String(selectedBrand.value));

    const res = await fetch('/iklan-budgets/analytics/monthly-spent?' + params.toString());
    if (res.ok) {
      const json = await res.json();
      monthlySpentData.value = json.data || [];
    }
  } catch (e) {
    console.error('Error fetching monthly spent:', e);
  } finally {
    monthlySpentLoading.value = false;
  }
};

const fetchPaymentStatus = async () => {
  paymentStatusLoading.value = true;
  try {
    const now = new Date();
    const defaultStart = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
    const defaultEnd = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];
    const params = new URLSearchParams({
      start_date: startDate.value || defaultStart,
      end_date: endDate.value || defaultEnd,
    });
    if (selectedMarketing.value && selectedMarketing.value !== 'all') params.append('marketing', String(selectedMarketing.value));
    if (selectedBrand.value && selectedBrand.value !== 'all') params.append('brand', String(selectedBrand.value));
    const res = await fetch('/transaksis/analytics/payment-status?' + params.toString());
    if (res.ok) {
      const json = await res.json();
      paymentStatusData.value = json.data || [];
    }
  } catch (e) {
    console.error('Error fetching payment status:', e);
  } finally {
    paymentStatusLoading.value = false;
  }
};

const fetchSourceAnalytics = async () => {
  sourceChartLoading.value = true;
  try {
    const params = new URLSearchParams({
      start_date: startDate.value || new Date(new Date().getFullYear(), 0, 1).toISOString().split('T')[0],
      end_date: endDate.value || new Date(new Date().getFullYear(), 11, 31).toISOString().split('T')[0],
    });
    if (selectedMarketing.value && selectedMarketing.value !== 'all') params.append('marketing', String(selectedMarketing.value));
    if (selectedBrand.value && selectedBrand.value !== 'all') params.append('brand', String(selectedBrand.value));
    const res = await fetch('/transaksis/analytics/sumber?' + params.toString());
    if (res.ok) {
      const json = await res.json();
      sourceChartData.value = json.data || [];
    }
  } catch (e) {
    console.error('Error fetching source analytics:', e);
  } finally {
    sourceChartLoading.value = false;
  }
};

const fetchAgeAnalytics = async () => {
  ageChartLoading.value = true;
  try {
    const params = new URLSearchParams({
      start_date: startDate.value || new Date(new Date().getFullYear(), 0, 1).toISOString().split('T')[0],
      end_date: endDate.value || new Date(new Date().getFullYear(), 11, 31).toISOString().split('T')[0],
    });
    if (selectedMarketing.value && selectedMarketing.value !== 'all') params.append('marketing', String(selectedMarketing.value));
    if (selectedBrand.value && selectedBrand.value !== 'all') params.append('brand', String(selectedBrand.value));
    const res = await fetch('/transaksis/analytics/usia?' + params.toString());
    if (res.ok) {
      const json = await res.json();
      ageChartData.value = json.data || [];
    }
  } catch (e) {
    console.error('Error fetching age analytics:', e);
  } finally {
    ageChartLoading.value = false;
  }
};

const fetchLeadAwalAnalytics = async () => {
  leadAwalChartLoading.value = true;
  try {
    const params = new URLSearchParams({
      start_date: startDate.value || new Date(new Date().getFullYear(), 0, 1).toISOString().split('T')[0],
      end_date: endDate.value || new Date(new Date().getFullYear(), 11, 31).toISOString().split('T')[0],
    });
    if (selectedMarketing.value && selectedMarketing.value !== 'all') params.append('marketing', String(selectedMarketing.value));
    if (selectedBrand.value && selectedBrand.value !== 'all') params.append('brand', String(selectedBrand.value));
    const res = await fetch('/transaksis/analytics/lead-awal?' + params.toString());
    if (res.ok) {
      const json = await res.json();
      leadAwalChartData.value = json.data || [];
    }
  } catch (e) {
    console.error('Error fetching lead awal analytics:', e);
  } finally {
    leadAwalChartLoading.value = false;
  }
};

const fetchJobAnalytics = async () => {
  jobChartLoading.value = true;
  try {
    const params = new URLSearchParams({
      start_date: startDate.value || new Date(new Date().getFullYear(), 0, 1).toISOString().split('T')[0],
      end_date: endDate.value || new Date(new Date().getFullYear(), 11, 31).toISOString().split('T')[0],
    });
    if (selectedMarketing.value && selectedMarketing.value !== 'all') params.append('marketing', String(selectedMarketing.value));
    if (selectedBrand.value && selectedBrand.value !== 'all') params.append('brand', String(selectedBrand.value));
    const res = await fetch('/transaksis/analytics/pekerjaan?' + params.toString());
    if (res.ok) {
      const json = await res.json();
      jobChartData.value = json.data || [];
    }
  } catch (e) {
    console.error('Error fetching pekerjaan analytics:', e);
  } finally {
    jobChartLoading.value = false;
  }
};

// Apply/Reset filter helpers (mirroring Dashboard behavior)
const applyMarketingFilter = (value: string) => {
  selectedMarketing.value = value;
};
const applyBrandFilter = (value: string) => {
  selectedBrand.value = value;
};
const applyFilters = () => {
  router.get(
    '/analisa-bisnis',
    {
      start_date: startDate.value || undefined,
      end_date: endDate.value || undefined,
      marketing: selectedMarketing.value !== 'all' ? selectedMarketing.value : undefined,
      brand: selectedBrand.value !== 'all' ? selectedBrand.value : undefined,
    },
    { preserveState: true, replace: true },
  );
  fetchMonthlySpent();
  fetchPaymentStatus();
  fetchSourceAnalytics();
  fetchAgeAnalytics();
  fetchLeadAwalAnalytics();
  fetchJobAnalytics();
};
const resetFilters = () => {
  startDate.value = '';
  endDate.value = '';
  selectedMarketing.value = 'all';
  selectedBrand.value = 'all';
  applyFilters();
};

// Watch year for monthly spent
watch(selectedYear, () => {
  fetchMonthlySpent();
});

onMounted(() => {
  // Initial chart loads
  fetchMonthlySpent();
  fetchPaymentStatus();
  fetchSourceAnalytics();
  fetchAgeAnalytics();
  fetchLeadAwalAnalytics();
  fetchJobAnalytics();
});

// Helpers
const refreshMonthlySpent = () => fetchMonthlySpent();
const refreshPaymentStatus = () => fetchPaymentStatus();
const refreshSourceAnalytics = () => fetchSourceAnalytics();
const refreshAgeAnalytics = () => fetchAgeAnalytics();
const refreshLeadAwalAnalytics = () => fetchLeadAwalAnalytics();
const refreshJobAnalytics = () => fetchJobAnalytics();

</script>

<template>
  <Head title="Analisa Bisnis" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-6 mt-6 space-y-6">
      <!-- Header Section -->
      <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-purple-600 to-fuchsia-700 p-6 text-white shadow-2xl sm:p-8">
        <div class="relative z-10">
          <div class="flex items-center gap-2 mb-4">
            <TrendingUp class="h-5 w-5" />
            <span class="text-sm font-medium">Analisa Bisnis</span>
          </div>
          <h1 class="mb-2 text-3xl font-bold tracking-tight sm:text-4xl">Perkembangan Bisnis</h1>
          <p class="text-indigo-100 opacity-90 sm:text-lg">Pantau performa brand, marketing, dan tren transaksi</p>
        </div>
        <div class="absolute top-0 right-0 -mt-32 -mr-32 h-64 w-64 rounded-full bg-gradient-to-br from-white/20 to-white/5 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 -mb-24 -ml-24 h-48 w-48 rounded-full bg-gradient-to-tr from-purple-400/20 to-indigo-400/20 blur-2xl"></div>
      </div>

      <!-- Filters -->
      <Card>
        <CardHeader class="pb-3">
          <CardTitle>Filter Analisis</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
              <label class="text-sm text-gray-600 dark:text-gray-300">Tanggal Mulai</label>
              <DatePicker v-model="startDate" />
            </div>
            <div>
              <label class="text-sm text-gray-600 dark:text-gray-300">Tanggal Akhir</label>
              <DatePicker v-model="endDate" />
            </div>
            <!-- Marketing Dropdown -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Marketing</label>
              <div class="relative">
                <Button
                  variant="outline"
                  class="flex w-full items-center justify-between"
                  @click="showMarketingDropdown = !showMarketingDropdown"
                >
                  <span class="flex items-center gap-2 truncate">
                    <Users class="h-4 w-4 text-emerald-500" />
                    <span>
                      {{
                        selectedMarketing === 'all'
                          ? 'Semua Marketing'
                          : props.topMarketing.find((m) => m.id.toString() === selectedMarketing)?.name ||
                            marketingUsers.find((m) => m.id.toString() === selectedMarketing)?.name || 'Marketing'
                      }}
                    </span>
                  </span>
                  <ChevronDown class="h-4 w-4 text-gray-400" />
                </Button>
                <div
                  v-if="showMarketingDropdown"
                  class="absolute z-50 mt-2 w-full rounded-md border bg-white p-2 shadow-lg dark:border-gray-700 dark:bg-gray-800"
                >
                  <div
                    class="flex cursor-pointer items-center gap-2 rounded-md px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700"
                    @click="applyMarketingFilter('all'); showMarketingDropdown = false;"
                  >
                    <Target class="h-4 w-4 text-emerald-500" />
                    <span class="font-medium">Semua Marketing</span>
                  </div>
                  <div
                    v-for="marketing in props.topMarketing"
                    :key="marketing.id"
                    class="flex cursor-pointer items-center justify-between rounded-md px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700"
                    @click="applyMarketingFilter(marketing.id.toString()); showMarketingDropdown = false; applyFilters();"
                  >
                    <div class="flex items-center gap-2">
                      <Users class="h-4 w-4 text-emerald-500" />
                      <span class="font-medium">{{ marketing.name }}</span>
                    </div>
                    <Badge class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-200">{{ marketing.total_leads }} leads</Badge>
                  </div>
                </div>
              </div>
            </div>
            <!-- Brand Dropdown -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Brand</label>
              <div class="relative">
                <Button
                  variant="outline"
                  class="flex w-full items-center justify-between"
                  @click="showBrandDropdown = !showBrandDropdown"
                >
                  <span class="flex items-center gap-2 truncate">
                    <Building2 class="h-4 w-4 text-purple-500" />
                    <span>
                      {{
                        selectedBrand === 'all'
                          ? 'Semua Brand'
                          : props.brandPerformance.find((b) => b.id.toString() === selectedBrand)?.nama ||
                            brands.find((b) => b.id.toString() === selectedBrand)?.nama || 'Brand'
                      }}
                    </span>
                  </span>
                  <ChevronDown class="h-4 w-4 text-gray-400" />
                </Button>
                <div
                  v-if="showBrandDropdown"
                  class="absolute z-50 mt-2 w-full rounded-md border bg-white p-2 shadow-lg dark:border-gray-700 dark:bg-gray-800"
                >
                  <div
                    class="flex cursor-pointer items-center gap-2 rounded-md px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700"
                    @click="applyBrandFilter('all'); showBrandDropdown = false;"
                  >
                    <Target class="h-4 w-4 text-purple-500" />
                    <span class="font-medium">Semua Brand</span>
                  </div>
                  <div
                    v-for="brand in props.brandPerformance"
                    :key="brand.id"
                    class="flex cursor-pointer items-center justify-between rounded-md px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700"
                    @click="applyBrandFilter(brand.id.toString()); showBrandDropdown = false; applyFilters();"
                  >
                    <div class="flex items-center gap-2">
                      <Building2 class="h-4 w-4 text-purple-500" />
                      <span class="font-medium">{{ brand.nama }}</span>
                    </div>
                    <Badge class="bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-200">{{ brand.total_leads }} leads</Badge>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="mt-4 flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-3">
              <Button @click="applyFilters" class="bg-indigo-600 text-white hover:bg-indigo-700">
                <Filter class="mr-2 h-4 w-4" />
                Terapkan Filter
              </Button>
              <Button variant="outline" @click="resetFilters" class="">
                <RefreshCw class="mr-2 h-4 w-4" />
                Reset
              </Button>
            </div>

            <div v-if="selectedMarketing !== 'all' || selectedBrand !== 'all'" class="ml-auto flex flex-wrap items-center gap-2">
              <span class="text-xs text-gray-500 dark:text-gray-400">Filter Aktif:</span>
              <Badge v-if="selectedMarketing !== 'all'" class="flex items-center gap-1 bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-200">
                <Users class="h-3 w-3" />
                <span>{{ props.topMarketing.find((m) => m.id.toString() === selectedMarketing)?.name }}</span>
                <Button variant="ghost" size="sm" class="h-6 px-1" @click="applyMarketingFilter('all')">
                  <X class="h-3 w-3" />
                </Button>
              </Badge>
              <Badge v-if="selectedBrand !== 'all'" class="flex items-center gap-1 bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-200">
                <Building2 class="h-3 w-3" />
                <span>{{ props.brandPerformance.find((b) => b.id.toString() === selectedBrand)?.nama }}</span>
                <Button variant="ghost" size="sm" class="h-6 px-1" @click="applyBrandFilter('all')">
                  <X class="h-3 w-3" />
                </Button>
              </Badge>
            </div>
          </div>

          <!-- Monthly Spent Year Control -->
          <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
              <label class="text-sm text-gray-600 dark:text-gray-300">Tahun (Monthly Spent)</label>
              <Input type="number" v-model.number="selectedYear" min="2020" max="2030" />
            </div>
            <div class="flex items-end">
              <Button variant="outline" @click="fetchMonthlySpent">Refresh Spent</Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Charts Grid -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <BrandPerformanceChart :data="props.brandPerformance" title="Performa per Brand" />
        <MarketingPerformanceChart :data="props.topMarketing" title="Performa Marketing" />
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <MonthlySpentChart :data="monthlySpentData" :loading="monthlySpentLoading" :year="selectedYear" :brandName="selectedBrandName" @refresh="refreshMonthlySpent" />
        <PaymentStatusChart :data="paymentStatusData" :loading="paymentStatusLoading" emptyMessage="Tidak ada data pada periode ini." @refresh="refreshPaymentStatus" />
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <SourceAnalyticsChart :data="sourceChartData" :loading="sourceChartLoading" :startDate="startDate" :endDate="endDate" emptyMessage="Tidak ada data sumber transaksi pada periode ini." @refresh="refreshSourceAnalytics" />
        <AgeAnalyticsChart :data="ageChartData" :loading="ageChartLoading" emptyMessage="Tidak ada data usia pada periode ini." @refresh="refreshAgeAnalytics" />
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <LeadAwalAnalyticsChart :data="leadAwalChartData" :loading="leadAwalChartLoading" :startDate="startDate" :endDate="endDate" @refresh="refreshLeadAwalAnalytics" />
        <PekerjaanAnalyticsChart :data="jobChartData" :loading="jobChartLoading" :startDate="startDate" :endDate="endDate" emptyMessage="Tidak ada data pekerjaan pada periode ini." @refresh="refreshJobAnalytics" />
      </div>
    </div>
  </AppLayout>
  </template>

<style scoped></style>