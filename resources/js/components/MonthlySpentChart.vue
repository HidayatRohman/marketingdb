<template>
  <Card class="w-full dark:bg-gray-800 dark:border-gray-700">
    <CardHeader class="pb-3">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <CardTitle class="text-lg font-semibold text-gray-900 dark:text-white">
            Grafik Spent per Bulan
          </CardTitle>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            {{ subtitleText }}
          </p>
        </div>

        <div class="flex items-center gap-2">
          <Button
            variant="outline"
            size="sm"
            @click="$emit('refresh')"
            class="h-7 px-2 text-xs border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700 dark:text-gray-200 sm:h-8 sm:px-3"
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
          <span class="text-sm">Memuat data grafik...</span>
        </div>
      </div>

      <!-- Chart Container -->
      <div v-else-if="chartData && chartData.labels.length > 0" class="relative">
        <div class="h-64 w-full sm:h-80">
          <canvas :key="canvasKey" ref="chartCanvas" :id="canvasId"></canvas>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="flex flex-col items-center justify-center py-8 sm:py-12">
        <div class="rounded-full bg-gray-100 dark:bg-gray-800 p-3 mb-3 sm:p-4 sm:mb-4">
          <BarChart3 class="h-6 w-6 text-gray-400 sm:h-8 sm:w-8" />
        </div>
        <h3 class="text-base font-medium text-gray-900 dark:text-white mb-2 sm:text-lg">
          Belum Ada Data Spent
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 text-center max-w-sm px-4 sm:max-w-md sm:px-0">
          Tidak ada data untuk periode yang dipilih. Silakan ubah filter.
        </p>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { BarChart3, RefreshCw } from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
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
  BarElement,
  BarController,
  Title,
  Tooltip,
  Legend,
  Filler,
);

interface MonthlySpentRow {
  month: number;
  label: string;
  spent: number;
}

interface Props {
  data?: MonthlySpentRow[];
  loading?: boolean;
  year?: number | string;
  brandName?: string;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
});

const emit = defineEmits<{ refresh: [] }>();

// Component state
const chartCanvas = ref<HTMLCanvasElement>();
const chartInstance = ref<ChartJS | null>(null);

// Canvas management to avoid reuse issues
const canvasKey = ref(0);
const canvasId = computed(() => `monthly-spent-chart-${canvasKey.value}`);

const chartData = computed<ChartData<'bar'> | null>(() => {
  if (!props.data || props.data.length === 0) return null;

  const labels = props.data.map(item => item.label);
  const values = props.data.map(item => Number(item.spent || 0));

  return {
    labels,
    datasets: [
      {
        label: 'Spent',
        data: values,
        backgroundColor: 'rgba(99, 102, 241, 0.2)', // Indigo soft
        borderColor: '#6366f1',
        borderWidth: 2,
        borderRadius: 6,
        barThickness: 'flex',
      },
    ],
  };
});

const subtitleText = computed(() => {
  const yearLabel = props.year ? String(props.year) : 'tahun ini';
  const brandLabel = props.brandName ? ` • Brand: ${props.brandName}` : '';
  return `Perbandingan spent per bulan untuk ${yearLabel}${brandLabel}`;
});

const isDark = ref(false)
let observer: MutationObserver | null = null

const updateTheme = () => {
  isDark.value = document.documentElement.classList.contains('dark')
}

const chartOptions = computed<ChartOptions<'bar'>>(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: isDark.value ? '#1f2937' : 'rgba(0, 0, 0, 0.8)',
      titleColor: isDark.value ? '#f3f4f6' : '#ffffff',
      bodyColor: isDark.value ? '#f3f4f6' : '#ffffff',
      borderColor: isDark.value ? '#374151' : 'rgba(255, 255, 255, 0.1)',
      borderWidth: 1,
      cornerRadius: 8,
      callbacks: {
        title: (context) => `Bulan: ${context[0].label}`,
        label: (context) => `Spent: Rp${Number(context.parsed.y || 0).toLocaleString('id-ID')}`,
      },
    },
  },
  scales: {
    x: {
      grid: { color: isDark.value ? '#374151' : 'rgba(107, 114, 128, 0.1)' },
      ticks: { color: isDark.value ? '#9ca3af' : '#6b7280', font: { size: 11 } },
    },
    y: {
      beginAtZero: true,
      grid: { color: isDark.value ? '#374151' : 'rgba(107, 114, 128, 0.1)' },
      ticks: {
        color: isDark.value ? '#9ca3af' : '#6b7280',
        font: { size: 11 },
        callback: (value) => `Rp${Number(value).toLocaleString('id-ID')}`,
      },
    },
  },
}));

const destroyChart = () => {
  if (chartInstance.value) {
    chartInstance.value.destroy();
    chartInstance.value = null;
  }
};

const renderChart = async () => {
  destroyChart();
  await nextTick();
  if (!chartCanvas.value || !chartData.value) return;
  
  chartInstance.value = new ChartJS(chartCanvas.value.getContext('2d') as CanvasRenderingContext2D, {
    type: 'bar',
    data: chartData.value,
    options: chartOptions.value,
  });
};

watch(isDark, () => {
  canvasKey.value++
  renderChart()
})

onMounted(() => {
  updateTheme()
  observer = new MutationObserver(updateTheme)
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
  renderChart()
})

onUnmounted(() => {
  observer?.disconnect()
  destroyChart()
})

watch(() => props.data, () => {
  canvasKey.value++;
  renderChart();
});
</script>

<style scoped>
</style>