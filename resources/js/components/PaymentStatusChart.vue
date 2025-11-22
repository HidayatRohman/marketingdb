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
      <div v-else class="relative">
        <div class="h-64 w-full sm:h-80">
          <canvas :key="canvasKey" ref="chartCanvas" :id="canvasId"></canvas>
        </div>
        <div class="mt-3 grid grid-cols-1 gap-3 sm:mt-4 sm:gap-4 lg:grid-cols-2">
          <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2 flex items-center gap-2">
              <Clock class="h-4 w-4" />
              Top 3 Tanggal Tertinggi
            </h4>
            <div class="space-y-1.5 sm:space-y-2">
              <div
                v-for="peak in topDays"
                :key="peak.date"
                class="flex items-center justify-between rounded-lg bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950 dark:to-purple-950 px-2 py-1"
              >
                <span class="text-xs font-medium text-indigo-900 dark:text-indigo-100">
                  {{ peak.date }}
                </span>
                <div class="flex items-center gap-2">
                  <Badge variant="default" class="text-xs">{{ peak.total }} transaksi</Badge>
                  <TrendingUp class="h-3 w-3 text-indigo-500" />
                </div>
              </div>
            </div>
          </div>

          <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2 flex items-center gap-2">
              <Palette class="h-4 w-4" />
              Ringkasan Status Bulan Ini
            </h4>
            <div class="grid grid-cols-1 gap-2">
              <div class="flex items-center gap-2 rounded-lg bg-gray-50 dark:bg-gray-800 px-2 py-1">
                <div class="w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: statusColors['DP'] }"></div>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-300 flex-1">DP/TJ</span>
                <Badge variant="secondary" class="text-xs">{{ monthTotals.dp }}</Badge>
              </div>
              <div class="flex items-center gap-2 rounded-lg bg-gray-50 dark:bg-gray-800 px-2 py-1">
                <div class="w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: statusColors['Tambahan DP'] }"></div>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-300 flex-1">Tambahan DP</span>
                <Badge variant="secondary" class="text-xs">{{ monthTotals.tambahan_dp }}</Badge>
              </div>
              <div class="flex items-center gap-2 rounded-lg bg-gray-50 dark:bg-gray-800 px-2 py-1">
                <div class="w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: statusColors['Pelunasan'] }"></div>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-300 flex-1">Pelunasan</span>
                <Badge variant="secondary" class="text-xs">{{ monthTotals.pelunasan }}</Badge>
              </div>
            </div>
          </div>
        </div>
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
  date?: string;
  status_pembayaran?: string;
  count?: number;
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
const isCreating = ref(false);
const viewMode = ref<'line' | 'bar'>('line');

// Canvas management to avoid reuse issues
const canvasKey = ref(0);
const canvasId = computed(() => `payment-status-chart-${canvasKey.value}`);
const bumpCanvas = async () => {
  canvasKey.value++;
  await nextTick();
};

// Status colors
const statusColors = {
  'DP': '#10b981', // Emerald
  'Tambahan DP': '#f59e0b', // Amber
  'Pelunasan': '#6366f1', // Indigo
};

// Helpers
const monthLabel = (m: number | string | undefined) => {
  const map = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
  const n = Number(m);
  if (!isNaN(n) && n >= 1 && n <= 12) return map[n-1];
  return String(m ?? '');
};

const statusMap: Record<string, 'DP' | 'Tambahan DP' | 'Pelunasan'> = {
  'Dp / TJ': 'DP',
  'DP': 'DP',
  'Tambahan Dp': 'Tambahan DP',
  'Tambahan DP': 'Tambahan DP',
  'Pelunasan': 'Pelunasan',
};

const processed = computed(() => {
  const rows = (props.data || []) as PaymentStatusData[];
  const map: Record<string, { dp: number; tambahan_dp: number; pelunasan: number; total: number }> = {};
  for (const r of rows) {
    const d = String(r.date || '');
    if (!map[d]) map[d] = { dp: 0, tambahan_dp: 0, pelunasan: 0, total: 0 };
    const key = statusMap[String(r.status_pembayaran || '').trim()] || 'DP';
    const cnt = Number(r.count || 0);
    if (key === 'DP') map[d].dp += cnt;
    else if (key === 'Tambahan DP') map[d].tambahan_dp += cnt;
    else if (key === 'Pelunasan') map[d].pelunasan += cnt;
    map[d].total += cnt;
  }
  return map;
});

// Computed properties
const chartData = computed(() => {
  const rows = processed.value as Record<string, { dp: number; tambahan_dp: number; pelunasan: number; total: number }>;
  let baseDate = new Date();
  const firstKey = Object.keys(rows)[0];
  if (firstKey) {
    const parsed = new Date(firstKey);
    if (!isNaN(parsed.getTime())) baseDate = parsed;
  }
  const year = baseDate.getFullYear();
  const month = baseDate.getMonth();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const labels: string[] = [];
  for (let day = 1; day <= daysInMonth; day++) {
    const d = new Date(year, month, day);
    labels.push(d.toISOString().split('T')[0]);
  }

  const dpData = labels.map(d => rows[d]?.dp ?? 0);
  const tambahanData = labels.map(d => rows[d]?.tambahan_dp ?? 0);
  const pelunasanData = labels.map(d => rows[d]?.pelunasan ?? 0);

  const datasets = [
    {
      label: 'DP/TJ',
      data: dpData,
      borderColor: statusColors['DP'],
      backgroundColor: '#10b98120',
      borderWidth: 2,
      tension: 0.3,
      fill: viewMode.value === 'line' ? { target: 'origin', above: '#10b98110' } : undefined,
      pointBackgroundColor: statusColors['DP'],
      pointBorderColor: '#ffffff',
      pointBorderWidth: 2,
      pointRadius: 3,
      pointHoverRadius: 5,
    },
    {
      label: 'Tambahan DP',
      data: tambahanData,
      borderColor: statusColors['Tambahan DP'],
      backgroundColor: '#f59e0b20',
      borderWidth: 2,
      tension: 0.3,
      fill: viewMode.value === 'line' ? { target: 'origin', above: '#f59e0b10' } : undefined,
      pointBackgroundColor: statusColors['Tambahan DP'],
      pointBorderColor: '#ffffff',
      pointBorderWidth: 2,
      pointRadius: 3,
      pointHoverRadius: 5,
    },
    {
      label: 'Pelunasan',
      data: pelunasanData,
      borderColor: statusColors['Pelunasan'],
      backgroundColor: '#6366f120',
      borderWidth: 2,
      tension: 0.3,
      fill: viewMode.value === 'line' ? { target: 'origin', above: '#6366f110' } : undefined,
      pointBackgroundColor: statusColors['Pelunasan'],
      pointBorderColor: '#ffffff',
      pointBorderWidth: 2,
      pointRadius: 3,
      pointHoverRadius: 5,
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
  animation: false,
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
          return `Tanggal: ${context[0].label}`;
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
const getTotalForMonth = (monthLabelStr: string) => 0;

const topMonths = computed(() => []);

const topDays = computed(() => {
  const map = processed.value as Record<string, { dp: number; tambahan_dp: number; pelunasan: number; total: number }>;
  return Object.entries(map)
    .map(([date, v]) => ({ date, total: Number(v.total || v.dp + v.tambahan_dp + v.pelunasan || 0) }))
    .sort((a, b) => b.total - a.total)
    .slice(0, 3);
});

const monthTotals = computed(() => {
  const map = processed.value as Record<string, { dp: number; tambahan_dp: number; pelunasan: number; total: number }>;
  const acc = { dp: 0, tambahan_dp: 0, pelunasan: 0 };
  for (const v of Object.values(map)) {
    acc.dp += v.dp || 0;
    acc.tambahan_dp += v.tambahan_dp || 0;
    acc.pelunasan += v.pelunasan || 0;
  }
  return acc;
});

// Chart management
const createChart = async () => {
  if (!chartCanvas.value || !chartData.value) return;
  if (isCreating.value) return;
  isCreating.value = true;
  
  // Destroy existing chart
  if (chartInstance.value) {
    chartInstance.value.destroy();
    chartInstance.value = null;
  }
  
  // Ensure no lingering chart bound to the same canvas
  const existing = ChartJS.getChart(chartCanvas.value as any);
  if (existing) existing.destroy();
  const existingById = ChartJS.getChart(canvasId.value as any);
  if (existingById) existingById.destroy();
  
  await nextTick();
  
  const ctx = chartCanvas.value.getContext('2d');
  if (!ctx) return;
  
  chartInstance.value = new ChartJS(ctx, {
    type: viewMode.value,
    data: chartData.value,
    options: chartOptions.value,
  });
  isCreating.value = false;
};

const updateChart = async () => {
  if (!chartData.value) return;
  if (!chartInstance.value) {
    await createChart();
    return;
  }
  // If type changed, recreate
  if (chartInstance.value.config.type !== viewMode.value) {
    await bumpCanvas();
    await createChart();
    return;
  }
  chartInstance.value.data = chartData.value;
  chartInstance.value.options = chartOptions.value;
  chartInstance.value.update('none');
};

// Watchers
watch([() => props.data, viewMode], async () => {
  await bumpCanvas();
  await createChart();
}, { deep: true });

watch(() => props.loading, async (newLoading) => {
  if (!newLoading) {
    await bumpCanvas();
    await createChart();
  }
});

// Lifecycle
onMounted(async () => {
  await bumpCanvas();
  await createChart();
});

onUnmounted(() => {
  if (chartInstance.value) {
    chartInstance.value.destroy();
    chartInstance.value = null;
  }
  const existing = ChartJS.getChart(chartCanvas.value as any);
  if (existing) existing.destroy();
  const existingById = ChartJS.getChart(canvasId.value as any);
  if (existingById) existingById.destroy();
});
</script>