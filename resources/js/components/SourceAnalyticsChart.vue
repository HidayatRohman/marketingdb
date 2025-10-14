<template>
  <Card class="w-full">
    <CardHeader class="pb-3">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <CardTitle class="text-lg font-semibold text-gray-900 dark:text-white">
            Analisa Sumber Transaksi
          </CardTitle>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Distribusi jumlah transaksi per sumber dalam periode terpilih
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
      <div v-else-if="chartData && (chartData.labels.length > 0)" class="relative">
        <div class="h-64 w-full sm:h-80">
          <canvas :key="canvasKey" ref="chartCanvas" :id="canvasId"></canvas>
        </div>

        <!-- Legend & Stats -->
        <div class="mt-3 grid grid-cols-1 gap-3 sm:mt-4 sm:gap-4 lg:grid-cols-2">
          <!-- Source Legend -->
          <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2 flex items-center gap-2">
              <Palette class="h-4 w-4" />
              Sumber Transaksi
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

          <!-- Top Sources -->
          <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2 flex items-center gap-2">
              <Clock class="h-4 w-4" />
              Sumber Tertinggi
            </h4>
            <div class="space-y-1.5 sm:space-y-2">
              <div 
                v-for="peak in topSources" 
                :key="peak.sumber"
                class="flex items-center justify-between rounded-lg bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950 dark:to-purple-950 px-2 py-1"
              >
                <span class="text-xs font-medium text-indigo-900 dark:text-indigo-100">
                  {{ peak.sumber || 'Unknown' }}
                </span>
                <div class="flex items-center gap-1">
                  <Badge variant="default" class="text-xs">
                    {{ peak.count }} transaksi
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

interface SourceAnalyticsData {
  sumber: string;
  count: number;
  total_nominal: number;
}

interface Props {
  data?: SourceAnalyticsData[];
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
const viewMode = ref<'bar' | 'doughnut'>('doughnut');

// Canvas management to avoid reuse issues
const canvasKey = ref(0);
const canvasId = computed(() => `source-analytics-chart-${canvasKey.value}`);

// Colors per known sources
const sourceColors: Record<string, string> = {
  'IG': '#6366f1',
  'Instagram': '#6366f1',
  'FB': '#3b82f6',
  'Facebook': '#3b82f6',
  'WA': '#10b981',
  'WhatsApp': '#10b981',
  'Tiktok': '#0f172a',
  'Google': '#ef4444',
  'Web': '#14b8a6',
  'Organik': '#6b7280',
  'Teman': '#f472b6',
  'Unknown': '#9ca3af',
};

const getColorForLabel = (label: string | undefined, index: number) => {
  if (!label) return sourceColors['Unknown'];
  const base = sourceColors[label] || undefined;
  if (base) return base;
  // Fallback deterministic color
  const hues = [210, 260, 300, 180, 20, 45, 90, 120, 150, 200];
  const hue = hues[index % hues.length];
  return `hsl(${hue}, 70%, 55%)`;
};

// Computed properties
const chartData = computed(() => {
  if (!props.data || props.data.length === 0) {
    return null;
  }

  const labels = props.data.map(item => item.sumber || 'Unknown');
  const counts = props.data.map(item => item.count);
  const backgroundColors = labels.map((l, idx) => getColorForLabel(l, idx));

  if (viewMode.value === 'bar') {
    return {
      labels,
      datasets: [
        {
          label: 'Jumlah Transaksi',
          data: counts,
          backgroundColor: backgroundColors.map(c => c + '33'),
          borderColor: backgroundColors,
          borderWidth: 2,
        },
      ],
    } as ChartData<'bar'>;
  } else {
    return {
      labels,
      datasets: [
        {
          label: 'Jumlah Transaksi',
          data: counts,
          backgroundColor: backgroundColors,
          borderColor: '#ffffff',
          borderWidth: 1,
        },
      ],
    } as ChartData<'doughnut'>;
  }
});

// Expose colors for legend
const backgroundColors = computed(() => {
  if (!props.data || props.data.length === 0) return [] as string[];
  const labels = props.data.map(item => item.sumber || 'Unknown');
  return labels.map((l, idx) => getColorForLabel(l, idx));
});

const countsByLabel = computed<Record<string, number>>(() => {
  const map: Record<string, number> = {};
  (props.data || []).forEach(item => {
    const key = item.sumber || 'Unknown';
    map[key] = (map[key] || 0) + item.count;
  });
  return map;
});

const topSources = computed(() => {
  if (!props.data) return [] as { sumber: string; count: number }[];
  return [...props.data]
    .sort((a, b) => b.count - a.count)
    .slice(0, 3)
    .map(item => ({ sumber: item.sumber || 'Unknown', count: item.count }));
});

// Chart options
const chartOptions = computed<ChartOptions<'bar' | 'doughnut'>>(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false,
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
          return `Sumber: ${context[0].label || 'Unknown'}`;
        },
        label: (context) => {
          return `Jumlah: ${context.parsed}`;
        },
      },
    },
  },
  // Horizontal bars for readability
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