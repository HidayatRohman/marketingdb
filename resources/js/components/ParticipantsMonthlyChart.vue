<template>
  <Card class="border-0 shadow-md dark:bg-gray-800 dark:border dark:border-gray-700">
    <CardHeader class="pb-2 bg-gray-50 dark:bg-gray-800/70 rounded-md px-3 py-2">
      <div class="flex items-center justify-between">
        <CardTitle class="text-lg font-semibold text-gray-900 dark:text-white">Analisa Daftar Peserta (12 Bulan)</CardTitle>
      </div>
    </CardHeader>
    <CardContent>
      <div v-if="chartReady" class="chart-container">
        <canvas :key="canvasKey" ref="chartCanvas" :id="canvasId"></canvas>
      </div>
      <div v-else class="flex flex-col items-center justify-center py-8 sm:py-12">
        <div class="rounded-full bg-gray-100 dark:bg-gray-700 p-3 mb-3 sm:p-4 sm:mb-4">
          <TrendingUp class="h-6 w-6 text-gray-400 dark:text-gray-300 sm:h-8 sm:w-8" />
        </div>
        <h3 class="text-base font-medium text-gray-900 dark:text-white mb-2 sm:text-lg">
          Belum Ada Data Peserta
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 text-center max-w-sm px-4 sm:max-w-md sm:px-0">
          Tidak ada data peserta pada 12 bulan terakhir.
        </p>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { TrendingUp } from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  LineController,
  Title,
  Tooltip,
  Legend,
  Filler,
  ChartOptions,
  ChartData,
} from 'chart.js';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  LineController,
  Title,
  Tooltip,
  Legend,
  Filler,
);

interface MonthlyRow {
  year: number;
  month: number; // 1..12
  label: string; // e.g. "Okt 2025"
  count: number;
}

interface Props {
  data?: MonthlyRow[];
}

const props = withDefaults(defineProps<Props>(), {
  data: () => [],
});

const chartCanvas = ref<HTMLCanvasElement>();
const chartInstance = ref<ChartJS | null>(null);

const canvasKey = ref(0);
const canvasId = computed(() => `participants-monthly-chart-${canvasKey.value}`);

const chartReady = computed(() => props.data && props.data.length > 0);

const isDark = ref(false)
let observer: MutationObserver | null = null

const updateTheme = () => {
  isDark.value = document.documentElement.classList.contains('dark')
}

const chartData = computed<ChartData<'line'>>(() => {
  const labels = (props.data || []).map(r => r.label);
  const values = (props.data || []).map(r => r.count);
  return {
    labels,
    datasets: [
      {
        label: 'Peserta',
        data: values,
        borderColor: '#10b981', // Emerald
        backgroundColor: '#10b98110',
        borderWidth: 2,
        tension: 0.4,
        pointBackgroundColor: '#10b981',
        pointBorderColor: '#ffffff',
        pointBorderWidth: 2,
        pointRadius: 3,
        pointHoverRadius: 5,
        fill: {
          target: 'origin',
          above: '#10b98108',
        },
      },
    ],
  };
});

const chartOptions = computed<ChartOptions<'line'>>(() => ({
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index', intersect: false },
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: isDark.value ? '#1f2937' : 'rgba(17, 24, 39, 0.95)',
      titleColor: isDark.value ? '#f9fafb' : '#f9fafb',
      bodyColor: isDark.value ? '#f9fafb' : '#f9fafb',
      borderColor: isDark.value ? '#374151' : '#10b981',
      borderWidth: 1,
      cornerRadius: 8,
      displayColors: false,
      callbacks: {
        label: (context) => ` ${context.parsed.y} peserta`,
      },
    },
  },
  scales: {
    x: {
      grid: { display: false, color: isDark.value ? '#374151' : '#e5e7eb' },
      ticks: {
        color: isDark.value ? '#9ca3af' : '#6b7280',
        maxTicksLimit: window.innerWidth >= 640 ? 12 : 6,
        callback: (v: any, i: number, ticks: any[]) => {
          // Kurangi kepadatan label di mobile
          if (window.innerWidth < 640 && i % 2 !== 0) return '';
          return (chartData.value.labels || [])[i] || '';
        },
      },
    },
    y: {
      beginAtZero: true,
      grid: { color: isDark.value ? '#374151' : '#e5e7eb' },
      ticks: {
        color: isDark.value ? '#9ca3af' : '#6b7280',
        precision: 0,
        stepSize: 1,
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

const createChart = async () => {
  if (!chartData.value) return;

  destroyChart();
  
  canvasKey.value += 1;
  await nextTick();

  if (!chartCanvas.value) return;

  if (!chartCanvas.value.id) {
    chartCanvas.value.id = canvasId.value;
  }

  const ctx = chartCanvas.value.getContext('2d');
  if (!ctx) return;

  chartInstance.value = new ChartJS(ctx, {
    type: 'line',
    data: chartData.value,
    options: chartOptions.value,
  });
};

watch(isDark, () => {
  createChart()
})

watch(() => props.data, async () => {
  if (props.data && props.data.length > 0) {
    await nextTick();
    createChart();
  }
}, { deep: true });

onMounted(() => {
  updateTheme()
  observer = new MutationObserver(updateTheme)
  observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class'],
  })
  createChart()
})

onUnmounted(() => {
  if (observer) {
    observer.disconnect()
  }
  destroyChart()
})
</script>

<style scoped>
.chart-container {
  position: relative;
  height: 280px;
  width: 100%;
}
@media (min-width: 640px) {
  .chart-container { height: 320px; }
}
</style>