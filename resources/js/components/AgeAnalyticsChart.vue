<template>
  <Card class="w-full">
    <CardHeader class="pb-3">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <CardTitle class="text-lg font-semibold text-gray-900 dark:text-white">
            Analisa Berdasarkan Usia
          </CardTitle>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Distribusi transaksi berdasarkan rentang usia
          </p>
        </div>

        <div class="flex items-center gap-2">
          <!-- Chart Type Toggle -->
          <div class="flex items-center rounded-lg border border-gray-200 dark:border-gray-700 p-1">
            <Button
              variant="ghost"
              size="sm"
              @click="viewMode = 'bar'"
              :class="[
                'h-7 px-2 text-xs transition-all duration-200',
                viewMode === 'bar'
                  ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300'
                  : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
              ]"
            >
              <BarChart3 class="h-3 w-3" />
              <span class="ml-1 hidden sm:inline">Bar</span>
            </Button>
            <Button
              variant="ghost"
              size="sm"
              @click="viewMode = 'doughnut'"
              :class="[
                'h-7 px-2 text-xs transition-all duration-200',
                viewMode === 'doughnut'
                  ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300'
                  : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
              ]"
            >
              <TrendingUp class="h-3 w-3" />
              <span class="ml-1 hidden sm:inline">Pie</span>
            </Button>
          </div>

          <!-- Refresh Button -->
          <Button
            variant="outline"
            size="sm"
            @click="$emit('refresh')"
            class="h-7 px-2 text-xs border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-800 sm:h-8 sm:px-3"
            title="Refresh Data"
          >
            <RefreshCw class="h-3 w-3" />
            <span class="ml-1 hidden sm:inline">Refresh</span>
          </Button>
        </div>
      </div>
    </CardHeader>

    <CardContent>
      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="flex items-center gap-3 text-gray-500 dark:text-gray-400">
          <div class="animate-spin rounded-full h-6 w-6 border-2 border-indigo-500 border-t-transparent"></div>
          <span class="text-sm">Memuat data analisa...</span>
        </div>
      </div>

      <!-- Chart Container -->
      <div v-else class="relative">
        <div class="h-64 w-full sm:h-80">
          <canvas :key="canvasKey" ref="chartCanvas" :id="canvasId"></canvas>
        </div>

        <!-- Legend & Stats -->
        <div class="mt-3 grid grid-cols-1 gap-3 sm:mt-4 sm:gap-4 lg:grid-cols-2">
          <!-- Age Legend -->
          <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2 flex items-center gap-2">
              <Palette class="h-4 w-4" />
              Rentang Usia
            </h4>
            <div class="grid grid-cols-1 gap-2">
              <div 
                v-for="(label, index) in chartData.labels" 
                :key="index"
                class="flex items-center gap-2 rounded-lg bg-gray-50 dark:bg-gray-800 px-2 py-1"
              >
                <div 
                  class="w-3 h-3 rounded-full flex-shrink-0" 
                  :style="{ backgroundColor: backgroundColors[index] }"
                ></div>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate flex-1">
                  {{ label || 'Unknown' }}
                </span>
                <Badge variant="secondary" class="text-xs flex-shrink-0">
                  {{ countsByLabel[label] || 0 }}
                </Badge>
              </div>
            </div>
          </div>

          <!-- Top Age Buckets -->
          <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2 flex items-center gap-2">
              <Clock class="h-4 w-4" />
              Rentang Usia Terbanyak
            </h4>
            <div class="grid grid-cols-1 gap-2">
              <div
                v-for="(item, idx) in topBuckets"
                :key="idx"
                class="flex items-center justify-between rounded-lg bg-gray-50 dark:bg-gray-800 px-2 py-1"
              >
                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ item.label }}</span>
                <Badge variant="secondary" class="text-xs">{{ item.count }}</Badge>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="flex flex-col items-center justify-center py-10">
        <div class="h-10 w-10 text-gray-300 dark:text-gray-600">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <path d="M19 11H5v2h14v-2z" />
          </svg>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 text-center max-w-sm px-4 sm:max-w-md sm:px-0">
          {{ emptyMessage || 'Tidak ada data transaksi untuk periode yang dipilih. Pilih tanggal atau filter yang berbeda.' }}
        </p>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { TrendingUp, BarChart3, RefreshCw, Clock, Palette } from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  BarController,
  DoughnutController,
  Title,
  Tooltip,
  Legend,
  Filler,
  ArcElement,
  ChartOptions,
  ChartData,
} from 'chart.js';

// Register Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  BarController,
  DoughnutController,
  Title,
  Tooltip,
  Legend,
  Filler,
  ArcElement,
);

interface AgeAnalyticsData {
  usia_bucket: string;
  count: number;
  total_nominal: number;
}

interface Props {
  data?: AgeAnalyticsData[];
  loading?: boolean;
  emptyMessage?: string;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  emptyMessage: '',
});

const emit = defineEmits<{
  refresh: [];
}>();

// Component state
const chartCanvas = ref<HTMLCanvasElement>();
const chartInstance = ref<ChartJS | null>(null);
const viewMode = ref<'bar' | 'doughnut'>('bar');

// Canvas management to avoid reuse issues
const canvasKey = ref(0);
const canvasId = computed(() => `age-analytics-chart-${canvasKey.value}`);

// Colors
const palette = [
  '#6366f1', // Indigo
  '#10b981', // Emerald
  '#f59e0b', // Amber
  '#ef4444', // Red
  '#3b82f6', // Blue
  '#14b8a6', // Teal
  '#a78bfa', // Violet
  '#f97316', // Orange
];

const backgroundColors = computed(() => {
  const labels = chartData.value?.labels || [];
  return labels.map((_, idx) => palette[idx % palette.length]);
});

// Aggregations
const countsByLabel = computed<Record<string, number>>(() => {
  const result: Record<string, number> = {};
  if (!props.data) return result;
  for (const item of props.data) {
    result[item.usia_bucket || 'Unknown'] = item.count || 0;
  }
  return result;
});

const topBuckets = computed(() => {
  if (!props.data) return [];
  return props.data
    .map(item => ({ label: item.usia_bucket || 'Unknown', count: item.count || 0 }))
    .sort((a, b) => b.count - a.count)
    .slice(0, 3);
});

// Computed chart data
const chartData = computed<ChartData<'bar' | 'doughnut'> | null>(() => {
  if (!props.data) {
    return null;
  }

  const defaultLabels = ['Unknown', '17-24', '25-34', '35-44', '45-54', '55+'];
  const labels = (props.data.length > 0)
    ? props.data.map(item => item.usia_bucket || 'Unknown')
    : defaultLabels;
  const counts = (props.data.length > 0)
    ? props.data.map(item => item.count || 0)
    : new Array(labels.length).fill(0);

  if (viewMode.value === 'bar') {
    return {
      labels,
      datasets: [
        {
          label: 'Jumlah Transaksi',
          data: counts,
          borderColor: '#6366f1',
          backgroundColor: '#6366f120',
          borderWidth: 2,
        },
      ],
    } as ChartData<'bar'>;
  }

  // Doughnut
  return {
    labels,
    datasets: [
      {
        label: 'Jumlah Transaksi',
        data: counts,
        backgroundColor: backgroundColors.value,
        borderColor: backgroundColors.value,
        borderWidth: 1,
      },
    ],
  } as ChartData<'doughnut'>;
});

// Chart options
const chartOptions = computed<ChartOptions<'bar' | 'doughnut'>>(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: viewMode.value === 'doughnut',
      position: 'bottom',
      labels: {
        color: '#6b7280',
        font: { size: 11 },
        usePointStyle: true,
        pointStyle: 'circle',
      },
    },
    tooltip: {
      enabled: true,
      backgroundColor: 'rgba(17, 24, 39, 0.9)',
      titleColor: '#fff',
      bodyColor: '#e5e7eb',
      borderColor: 'rgba(255,255,255,0.15)',
      borderWidth: 1,
      padding: 10,
      cornerRadius: 8,
      displayColors: true,
      callbacks: {
        title: (context) => `Usia: ${context[0].label || 'Unknown'}`,
        label: (context) => `Jumlah: ${context.parsed}`,
      },
    },
  },
  indexAxis: viewMode.value === 'bar' ? 'y' : undefined,
  scales: viewMode.value === 'bar' ? {
    x: {
      beginAtZero: true,
      grid: { color: 'rgba(107, 114, 128, 0.1)' },
      ticks: { color: '#6b7280', font: { size: 11 } },
    },
    y: {
      grid: { display: false },
      ticks: { color: '#6b7280', font: { size: 11 } },
    },
  } : undefined,
}));

// Chart management
const createChart = async () => {
  if (!chartCanvas.value || !chartData.value) return;

  // Destroy existing chart
  if (chartInstance.value) {
    chartInstance.value.destroy();
    chartInstance.value = null;
  }

  await nextTick();

  const ctx = chartCanvas.value.getContext('2d');
  if (!ctx) return;

  chartInstance.value = new ChartJS(ctx, {
    type: viewMode.value,
    data: chartData.value as any,
    options: chartOptions.value as any,
  });
};

const updateChart = async () => {
  if (!chartInstance.value || !chartData.value) {
    await createChart();
    return;
  }

  // Update chart type if changed
  if (chartInstance.value.config.type !== viewMode.value) {
    await createChart();
    return;
  }

  // Update data
  chartInstance.value.data = chartData.value as any;
  chartInstance.value.options = chartOptions.value as any;
  chartInstance.value.update('none');
};

// Watchers
watch([() => chartData.value, viewMode], async () => {
  canvasKey.value++;
  await nextTick();
  await createChart();
}, { deep: true });

watch(() => props.loading, (newLoading) => {
  if (!newLoading) {
    nextTick(() => createChart());
  }
});

// Lifecycle
onMounted(() => {
  nextTick(() => createChart());
});

onUnmounted(() => {
  if (chartInstance.value) {
    chartInstance.value.destroy();
    chartInstance.value = null;
  }
});
</script>