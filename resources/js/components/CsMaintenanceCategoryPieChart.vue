<template>
  <Card class="w-full">
    <CardHeader class="pb-3">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <CardTitle class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ title }}
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
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="flex items-center gap-3 text-gray-500 dark:text-gray-400">
          <div class="animate-spin rounded-full h-6 w-6 border-2 border-indigo-500 border-t-transparent"></div>
          <span class="text-sm">Memuat data analisa...</span>
        </div>
      </div>

      <div v-else class="relative">
        <div class="h-56 w-full sm:h-72">
          <canvas :key="canvasKey" ref="chartCanvas" :id="canvasId"></canvas>
        </div>

        <!-- Legend & Stats -->
        <div v-if="props.data && props.data.length > 0" class="mt-3 grid grid-cols-1 gap-3 sm:mt-4 sm:gap-4 lg:grid-cols-2">
          <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2 flex items-center gap-2">
              <Palette class="h-4 w-4" />
              {{ legendTitle }}
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

          <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2 flex items-center gap-2">
              <Clock class="h-4 w-4" />
              Teratas
            </h4>
            <div class="space-y-1.5 sm:space-y-2">
              <div
                v-for="peak in topItems"
                :key="peak.label"
                class="flex items-center justify-between rounded-lg bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950 dark:to-purple-950 px-2 py-1"
              >
                <span class="text-xs font-medium text-indigo-900 dark:text-indigo-100">
                  {{ peak.label || 'Unknown' }}
                </span>
                <div class="flex items-center gap-1">
                  <Badge variant="default" class="text-xs">
                    {{ peak.count }} data
                  </Badge>
                  <TrendingUp class="h-3 w-3 text-indigo-500" />
                </div>
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
import { TrendingUp, RefreshCw, Clock, Palette } from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import Chart from 'chart.js/auto';
import type { ChartData, ChartOptions } from 'chart.js';

interface CategoryItem {
  label: string;
  count: number;
  warna?: string;
}

interface Props {
  title: string;
  legendTitle: string;
  data?: CategoryItem[];
  loading?: boolean;
  emptyMessage?: string;
  startDate?: string;
  endDate?: string;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  emptyMessage: '',
});

const emit = defineEmits<{ refresh: [] }>();

// Component state
const chartCanvas = ref<HTMLCanvasElement>();
const chartInstance = ref<any>(null);

// Canvas management to avoid reuse issues
const canvasKey = ref(0);
const canvasId = computed(() => `cs-maintenance-category-pie-${canvasKey.value}`);

const getColorForIndex = (index: number) => {
  const hues = [210, 260, 300, 180, 20, 45, 90, 120, 150, 200];
  const hue = hues[index % hues.length];
  return `hsl(${hue}, 70%, 55%)`;
};

// Computed properties
const chartData = computed<ChartData<'pie'>>(() => {
  const hasData = props.data && props.data.length > 0;
  const labels = hasData ? props.data!.map(item => item.label || 'Unknown') : ['Tidak ada data'];
  const counts = hasData ? props.data!.map(item => item.count) : [1];
  const colors = hasData
    ? labels.map((_, idx) => props.data?.[idx]?.warna || getColorForIndex(idx))
    : ['#e5e7eb'];

  return {
    labels,
    datasets: [
      {
        label: 'Jumlah',
        data: counts,
        backgroundColor: colors,
        borderColor: '#ffffff',
        borderWidth: 1,
      },
    ],
  } as ChartData<'pie'>;
});

// Expose colors for legend
const backgroundColors = computed(() => {
  if (!props.data || props.data.length === 0) return [] as string[];
  return props.data.map((item, idx) => item.warna || getColorForIndex(idx));
});

const countsByLabel = computed<Record<string, number>>(() => {
  const map: Record<string, number> = {};
  (props.data || []).forEach(item => {
    const key = item.label || 'Unknown';
    map[key] = (map[key] || 0) + item.count;
  });
  return map;
});

// HTML fallback pie dihapus agar hanya satu grafik (canvas) ditampilkan

const topItems = computed(() => {
  if (!props.data) return [] as { label: string; count: number }[];
  return [...props.data]
    .sort((a, b) => b.count - a.count)
    .slice(0, 3)
    .map(item => ({ label: item.label || 'Unknown', count: item.count }));
});

// Format and compute dynamic subtitle based on selected period
const formatDate = (value?: string) => {
  if (!value) return undefined;
  const d = new Date(value);
  if (isNaN(d.getTime())) return value;
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const subtitleText = computed(() => {
  const start = formatDate(props.startDate);
  const end = formatDate(props.endDate);

  let periodLabel = 'periode terpilih';
  if (start && end) {
    periodLabel = start === end ? `tanggal ${start}` : `periode ${start} – ${end}`;
  } else if (start && !end) {
    periodLabel = `periode mulai ${start}`;
  } else if (!start && end) {
    periodLabel = `periode hingga ${end}`;
  }

  return `Distribusi jumlah per ${props.legendTitle.toLowerCase()} dalam ${periodLabel}`;
});

// Chart options
const chartOptions = computed<ChartOptions<'pie'>>(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      titleColor: '#ffffff',
      bodyColor: '#ffffff',
      borderColor: 'rgba(255, 255, 255, 0.1)',
      borderWidth: 1,
      cornerRadius: 8,
      displayColors: true,
      callbacks: {
        title: (context) => `Kategori: ${context[0].label || 'Unknown'}`,
        label: (context) => {
          const lbl = context.label || '';
          if (lbl === 'Tidak ada data') return 'Jumlah: 0';
          return `Jumlah: ${context.parsed}`;
        },
      },
    },
  },
}));

// Chart management
const createChart = async () => {
  if (!chartCanvas.value || !chartData.value) return;

  // Destroy existing chart instance and any globally registered chart on this canvas id
  try {
    if (chartInstance.value) {
      chartInstance.value.destroy();
      chartInstance.value = null;
    }
    const existing = Chart.getChart(canvasId.value as any);
    if (existing) existing.destroy();
  } catch (_e) {}

  await nextTick();

  const ctx = chartCanvas.value.getContext('2d');
  if (!ctx) return;

  chartInstance.value = new Chart(ctx, {
    type: 'pie',
    data: chartData.value as any,
    options: chartOptions.value as any,
  });
};

const updateChart = async () => {
  if (!chartInstance.value || !chartData.value) {
    await createChart();
    return;
  }
  chartInstance.value.data = chartData.value as any;
  chartInstance.value.options = chartOptions.value as any;
  chartInstance.value.update('none');
};

// Watchers
watch(() => props.data, async () => {
  await updateChart();
}, { deep: true });

watch(() => props.loading, async (newLoading) => {
  if (!newLoading) {
    await updateChart();
  }
});

// Lifecycle
onMounted(async () => {
  await createChart();
});

onUnmounted(() => {
  if (chartInstance.value) {
    chartInstance.value.destroy();
    chartInstance.value = null;
  }
});
</script>