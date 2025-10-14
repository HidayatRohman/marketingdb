<template>
  <Card class="w-full">
    <CardHeader class="pb-3">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <CardTitle class="text-lg font-semibold text-gray-900 dark:text-white">
            Analisa Status Pembayaran
          </CardTitle>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Jumlah transaksi per bulan berdasarkan status pembayaran
          </p>
        </div>
        
        <div class="flex items-center gap-2">
          <!-- Chart Type Toggle -->
          <div class="flex items-center rounded-lg border border-gray-200 dark:border-gray-700 p-1">
            <Button
              variant="ghost"
              size="sm"
              @click="viewMode = 'line'"
              :class="[
                'h-7 px-2 text-xs transition-all duration-200',
                viewMode === 'line'
                  ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300'
                  : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
              ]"
            >
              <TrendingUp class="h-3 w-3" />
              <span class="ml-1 hidden sm:inline">Line</span>
            </Button>
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
      <div v-else-if="chartData && chartData.datasets.length > 0" class="relative">
        <div class="h-64 w-full sm:h-80">
          <canvas :key="canvasKey" ref="chartCanvas" :id="canvasId"></canvas>
        </div>
        
        <!-- Legend & Stats -->
        <div class="mt-3 grid grid-cols-1 gap-3 sm:mt-4 sm:gap-4 lg:grid-cols-2">
          <!-- Status Legend -->
          <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2 flex items-center gap-2">
              <Palette class="h-4 w-4" />
              Status Pembayaran
            </h4>
            <div class="grid grid-cols-1 gap-2">
              <div 
                v-for="(dataset, index) in chartData.datasets" 
                :key="index"
                class="flex items-center gap-2 rounded-lg bg-gray-50 dark:bg-gray-800 px-2 py-1"
              >
                <div 
                  class="w-3 h-3 rounded-full flex-shrink-0" 
                  :style="{ backgroundColor: dataset.borderColor }"
                ></div>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate flex-1">
                  {{ dataset.label }}
                </span>
                <Badge variant="secondary" class="text-xs flex-shrink-0">
                  {{ getTotalForStatus(dataset.label) }}
                </Badge>
              </div>
            </div>
          </div>

          <!-- Monthly Stats -->
          <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2 flex items-center gap-2">
              <Clock class="h-4 w-4" />
              Bulan Tertinggi
            </h4>
            <div class="space-y-1.5 sm:space-y-2">
              <div 
                v-for="peak in topMonths" 
                :key="peak.month"
                class="flex items-center justify-between rounded-lg bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950 dark:to-purple-950 px-2 py-1"
              >
                <span class="text-xs font-medium text-indigo-900 dark:text-indigo-100">
                  {{ peak.month }}
                </span>
                <div class="flex items-center gap-1">
                  <Badge variant="default" class="text-xs">
                    {{ peak.total }} transaksi
                  </Badge>
                  <TrendingUp class="h-3 w-3 text-indigo-500" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="flex flex-col items-center justify-center py-8 sm:py-12">
        <div class="rounded-full bg-gray-100 dark:bg-gray-800 p-3 mb-3 sm:p-4 sm:mb-4">
          <TrendingUp class="h-6 w-6 text-gray-400 sm:h-8 sm:w-8" />
        </div>
        <h3 class="text-base font-medium text-gray-900 dark:text-white mb-2 sm:text-lg">
          Belum Ada Data Transaksi
        </h3>
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
  PointElement,
  LineElement,
  BarElement,
  LineController,
  BarController,
  Title,
  Tooltip,
  Legend,
  Filler,
  ChartOptions,
  ChartData,
} from 'chart.js';

// Register Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  LineController,
  BarController,
  Title,
  Tooltip,
  Legend,
  Filler
);

interface PaymentStatusData {
  month: string;
  dp: number;
  tambahan_dp: number;
  pelunasan: number;
  total: number;
}

interface Props {
  data?: PaymentStatusData[];
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
const viewMode = ref<'line' | 'bar'>('bar');

// Canvas management to avoid reuse issues
const canvasKey = ref(0);
const canvasId = computed(() => `payment-status-chart-${canvasKey.value}`);

// Status colors
const statusColors = {
  'DP': '#10b981', // Emerald
  'Tambahan DP': '#f59e0b', // Amber
  'Pelunasan': '#6366f1', // Indigo
};

// Computed properties
const chartData = computed(() => {
  if (!props.data || props.data.length === 0) {
    return null;
  }

  const labels = props.data.map(item => item.month);
  
  const datasets = [
    {
      label: 'DP',
      data: props.data.map(item => item.dp),
      borderColor: statusColors['DP'],
      backgroundColor: viewMode.value === 'bar' ? statusColors['DP'] + '20' : statusColors['DP'] + '10',
      borderWidth: 2,
      tension: 0.4,
      pointBackgroundColor: statusColors['DP'],
      pointBorderColor: '#ffffff',
      pointBorderWidth: 2,
      pointRadius: 4,
      pointHoverRadius: 6,
    },
    {
      label: 'Tambahan DP',
      data: props.data.map(item => item.tambahan_dp),
      borderColor: statusColors['Tambahan DP'],
      backgroundColor: viewMode.value === 'bar' ? statusColors['Tambahan DP'] + '20' : statusColors['Tambahan DP'] + '10',
      borderWidth: 2,
      tension: 0.4,
      pointBackgroundColor: statusColors['Tambahan DP'],
      pointBorderColor: '#ffffff',
      pointBorderWidth: 2,
      pointRadius: 4,
      pointHoverRadius: 6,
    },
    {
      label: 'Pelunasan',
      data: props.data.map(item => item.pelunasan),
      borderColor: statusColors['Pelunasan'],
      backgroundColor: viewMode.value === 'bar' ? statusColors['Pelunasan'] + '20' : statusColors['Pelunasan'] + '10',
      borderWidth: 2,
      tension: 0.4,
      pointBackgroundColor: statusColors['Pelunasan'],
      pointBorderColor: '#ffffff',
      pointBorderWidth: 2,
      pointRadius: 4,
      pointHoverRadius: 6,
    },
  ];

  return {
    labels,
    datasets,
  } as ChartData<'line' | 'bar'>;
});

// Chart options
const chartOptions = computed<ChartOptions<'line' | 'bar'>>(() => ({
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    intersect: false,
    mode: 'index',
  },
  plugins: {
    legend: {
      display: false, // We'll use custom legend
    },
    tooltip: {
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      titleColor: '#ffffff',
      bodyColor: '#ffffff',
      borderColor: 'rgba(255, 255, 255, 0.1)',
      borderWidth: 1,
      cornerRadius: 8,
      displayColors: true,
      callbacks: {
        title: (context) => {
          return `Bulan: ${context[0].label}`;
        },
        label: (context) => {
          return `${context.dataset.label}: ${context.parsed.y} transaksi`;
        },
      },
    },
  },
  scales: {
    x: {
      grid: {
        display: false,
      },
      ticks: {
        color: '#6b7280',
        font: {
          size: 11,
        },
      },
    },
    y: {
      beginAtZero: true,
      grid: {
        color: 'rgba(107, 114, 128, 0.1)',
      },
      ticks: {
        color: '#6b7280',
        font: {
          size: 11,
        },
        callback: function(value) {
          return Number.isInteger(value) ? value : '';
        },
      },
    },
  },
}));

// Helper functions
const getTotalForStatus = (status: string) => {
  if (!props.data) return 0;
  
  switch (status) {
    case 'DP':
      return props.data.reduce((sum, item) => sum + item.dp, 0);
    case 'Tambahan DP':
      return props.data.reduce((sum, item) => sum + item.tambahan_dp, 0);
    case 'Pelunasan':
      return props.data.reduce((sum, item) => sum + item.pelunasan, 0);
    default:
      return 0;
  }
};

const topMonths = computed(() => {
  if (!props.data) return [];
  
  return props.data
    .map(item => ({
      month: item.month,
      total: item.total,
    }))
    .sort((a, b) => b.total - a.total)
    .slice(0, 3);
});

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
    data: chartData.value,
    options: chartOptions.value,
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
  chartInstance.value.data = chartData.value;
  chartInstance.value.options = chartOptions.value;
  chartInstance.value.update('none');
};

// Watchers
watch([() => props.data, viewMode], async () => {
  if (props.data && props.data.length > 0) {
    canvasKey.value++;
    await nextTick();
    await createChart();
  }
}, { deep: true });

watch(() => props.loading, (newLoading) => {
  if (!newLoading && props.data && props.data.length > 0) {
    nextTick(() => createChart());
  }
});

// Lifecycle
onMounted(() => {
  if (props.data && props.data.length > 0) {
    nextTick(() => createChart());
  }
});

onUnmounted(() => {
  if (chartInstance.value) {
    chartInstance.value.destroy();
    chartInstance.value = null;
  }
});
</script>