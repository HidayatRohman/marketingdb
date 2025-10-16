<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import DatePicker from '@/components/ui/datepicker/DatePicker.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
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
const selectedMarketing = ref(props.filters.marketing || '');
const selectedBrand = ref(props.filters.brand || '');
const selectedYear = ref<number>(new Date().getFullYear());

// Derived labels
const brands = computed(() => props.brands || []);
const marketingUsers = computed(() => props.marketingUsers || []);
const selectedBrandName = computed(() => {
  const b = brands.value.find(x => String(x.id) === String(selectedBrand.value));
  return b ? b.nama : '';
});

// Breadcrumbs
const breadcrumbs = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Analisa Bisnis', href: '/analisa-bisnis' },
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
    if (selectedBrand.value) params.append('brand_id', String(selectedBrand.value));

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
    const params = new URLSearchParams({
      start_date: startDate.value || new Date(new Date().getFullYear(), 0, 1).toISOString().split('T')[0],
      end_date: endDate.value || new Date(new Date().getFullYear(), 11, 31).toISOString().split('T')[0],
    });
    if (selectedMarketing.value) params.append('marketing', String(selectedMarketing.value));
    if (selectedBrand.value) params.append('brand', String(selectedBrand.value));
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
    if (selectedMarketing.value) params.append('marketing', String(selectedMarketing.value));
    if (selectedBrand.value) params.append('brand', String(selectedBrand.value));
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
    if (selectedMarketing.value) params.append('marketing', String(selectedMarketing.value));
    if (selectedBrand.value) params.append('brand', String(selectedBrand.value));
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
    if (selectedMarketing.value) params.append('marketing', String(selectedMarketing.value));
    if (selectedBrand.value) params.append('brand', String(selectedBrand.value));
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
    if (selectedMarketing.value) params.append('marketing', String(selectedMarketing.value));
    if (selectedBrand.value) params.append('brand', String(selectedBrand.value));
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

// Watch filters to reload the page data (server-rendered) and charts
watch([startDate, endDate, selectedMarketing, selectedBrand], () => {
  router.get('/analisa-bisnis', {
    start_date: startDate.value || undefined,
    end_date: endDate.value || undefined,
    marketing: selectedMarketing.value || undefined,
    brand: selectedBrand.value || undefined,
  }, { preserveState: true, replace: true });

  // Refresh client-fetched charts
  fetchMonthlySpent();
  fetchPaymentStatus();
  fetchSourceAnalytics();
  fetchAgeAnalytics();
  fetchLeadAwalAnalytics();
  fetchJobAnalytics();
});

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
          <h1 class="mb-2 text-3xl font-bold tracking-tight sm:text-4xl">Wawasan Kinerja Bisnis</h1>
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
            <div>
              <label class="text-sm text-gray-600 dark:text-gray-300">Marketing</label>
              <Select v-model="selectedMarketing">
                <SelectTrigger>
                  <SelectValue placeholder="Semua Marketing" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">Semua Marketing</SelectItem>
                  <SelectItem v-for="m in marketingUsers" :key="m.id" :value="String(m.id)">{{ m.name }}</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div>
              <label class="text-sm text-gray-600 dark:text-gray-300">Brand</label>
              <Select v-model="selectedBrand">
                <SelectTrigger>
                  <SelectValue placeholder="Semua Brand" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">Semua Brand</SelectItem>
                  <SelectItem v-for="b in brands" :key="b.id" :value="String(b.id)">{{ b.nama }}</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mt-4">
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