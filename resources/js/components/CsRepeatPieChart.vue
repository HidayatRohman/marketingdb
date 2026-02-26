<template>
  <Card class="w-full dark:bg-gray-800 dark:border-gray-700">
    <CardHeader class="pb-3">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <CardTitle class="text-lg font-semibold text-foreground dark:text-gray-100">
            {{ title }}
          </CardTitle>
          <p class="text-sm text-muted-foreground dark:text-gray-300 mt-1">
            {{ subtitleText }}
          </p>
        </div>
      </div>
    </CardHeader>

  <CardContent>
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="flex items-center gap-3 text-muted-foreground dark:text-gray-300">
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
          <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 bg-white dark:bg-gray-800/50">
            <h4 class="text-sm font-medium text-foreground dark:text-gray-100 mb-2 flex items-center gap-2">
              <Palette class="h-4 w-4" />
              {{ legendTitle }}
            </h4>
            <div class="grid grid-cols-1 gap-2">
              <div
                v-for="(label, index) in chartData.labels"
                :key="index"
                class="flex items-center gap-2 rounded-lg bg-gray-50 dark:bg-gray-800 px-2 py-1 border border-gray-100 dark:border-gray-700"
              >
                <div
                  class="w-3 h-3 rounded-full flex-shrink-0"
                  :style="{ backgroundColor: backgroundColors[index] }"
                ></div>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-200 truncate flex-1">
                  {{ label || 'Unknown' }}
                </span>
                <Badge variant="secondary" class="text-xs flex-shrink-0 dark:bg-gray-700 dark:text-gray-100">
                  {{ countsByLabel[label as string] || 0 }}
                </Badge>
              </div>
            </div>
          </div>

          <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 bg-white dark:bg-gray-800/50">
            <h4 class="text-sm font-medium text-foreground dark:text-gray-100 mb-2 flex items-center gap-2">
              <Clock class="h-4 w-4" />
              Teratas
            </h4>
            <div class="space-y-1.5 sm:space-y-2">
              <div
                v-for="peak in topItems"
                :key="peak.label"
                class="flex items-center justify-between rounded-lg bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950/40 dark:to-purple-950/40 px-2 py-1 border border-indigo-100 dark:border-indigo-900/50"
              >
                <span class="text-xs font-medium text-indigo-900 dark:text-indigo-200">
                  {{ peak.label || 'Unknown' }}
                </span>
                <div class="flex items-center gap-1">
                  <Badge variant="default" class="text-xs dark:bg-indigo-600 dark:text-white">
                    {{ peak.value }} data
                  </Badge>
                  <TrendingUp class="h-3 w-3 text-indigo-500 dark:text-indigo-400" />
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="py-8 text-center text-sm text-muted-foreground dark:text-gray-400">
            Tidak ada data untuk ditampilkan.
        </div>
      </div>
  </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { TrendingUp, Clock, Palette } from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import Chart from 'chart.js/auto';
import type { ChartData, ChartOptions } from 'chart.js';

interface ChartItem {
  label: string;
  value: number; // Changed from count to value to match controller
  warna?: string;
}

interface Props {
  title: string;
  legendTitle: string;
  data?: ChartItem[];
  loading?: boolean;
  startDate?: string;
  endDate?: string;
  idPrefix: string;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
});

// Component state
const chartCanvas = ref<HTMLCanvasElement>();
const chartInstance = ref<any>(null);

// Canvas management to avoid reuse issues
const canvasKey = ref(0);
const canvasId = computed(() => `cs-repeat-pie-${props.idPrefix}-${canvasKey.value}`);
const bumpCanvas = async () => {
  canvasKey.value++;
  await nextTick();
};

const getColorForIndex = (index: number) => {
  const hues = [210, 260, 300, 180, 20, 45, 90, 120, 150, 200];
  const hue = hues[index % hues.length];
  return `hsl(${hue}, 70%, 55%)`;
};

// Computed properties
const chartData = computed<ChartData<'pie'>>(() => {
  const hasData = props.data && props.data.length > 0;
  const labels = hasData ? props.data!.map(item => item.label || 'Unknown') : ['Tidak ada data'];
  const values = hasData ? props.data!.map(item => item.value) : [1];
  const colors = hasData
    ? labels.map((_, idx) => props.data?.[idx]?.warna || getColorForIndex(idx))
    : ['#e5e7eb'];

  return {
    labels,
    datasets: [
      {
        label: 'Jumlah',
        data: values,
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
    map[key] = (map[key] || 0) + item.value;
  });
  return map;
});

const topItems = computed(() => {
  if (!props.data) return [] as { label: string; value: number }[];
  return [...props.data]
    .sort((a, b) => b.value - a.value)
    .slice(0, 3)
    .map(item => ({ label: item.label || 'Unknown', value: item.value }));
});

// Format and compute dynamic subtitle based on selected period
const formatDate = (value?: string) => {
  if (!value) return undefined;
  const d = new Date(value);
  if (isNaN(d.getTime())) return value;
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const subtitleText = computed(() => {
  // If no dates provided, assume total data
  if (!props.startDate && !props.endDate) {
      return `Berdasarkan total data yang ada`;
  }

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

const isDark = ref(false)
let observer: MutationObserver | null = null

const updateTheme = () => {
  isDark.value = document.documentElement.classList.contains('dark')
}

// Chart options
const chartOptions = computed<ChartOptions<'pie'>>(() => ({
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
      displayColors: true,
      callbacks: {
        title: (context) => `${props.legendTitle}: ${context[0].label || 'Unknown'}`,
        label: (context) => {
          const lbl = context.label || '';
          if (lbl === 'Tidak ada data') return 'Jumlah: 0';
          return `Jumlah: ${context.parsed}`;
        },
      },
    },
  },
}));

const destroyChart = () => {
  if (chartInstance.value) {
    chartInstance.value.destroy();
    chartInstance.value = null;
  }
  
  if (chartCanvas.value) {
    const existing = Chart.getChart(chartCanvas.value as any);
    if (existing) existing.destroy();
  }
};

// Chart management
const createChart = async () => {
  if (!chartCanvas.value || !chartData.value) return;

  // Wait for DOM updates first
  await nextTick();

  // Double check canvas exists after tick
  if (!chartCanvas.value) return;

  // Ensure any existing chart on this canvas is destroyed immediately before creation
  const existingChart = Chart.getChart(chartCanvas.value as any);
  if (existingChart) {
    existingChart.destroy();
  }
  
  if (chartInstance.value) {
    chartInstance.value.destroy();
    chartInstance.value = null;
  }

  const ctx = chartCanvas.value.getContext('2d');
  if (!ctx) return;

  chartInstance.value = new Chart(chartCanvas.value, {
    type: 'pie',
    data: chartData.value as any,
    options: chartOptions.value as any,
  });
};

// Watchers
watch(() => props.data, async () => {
  destroyChart();
  await bumpCanvas();
  await createChart();
}, { deep: true });

watch(isDark, async () => {
  destroyChart();
  await bumpCanvas();
  await createChart();
});

watch(() => props.loading, async (newLoading) => {
  if (!newLoading) {
    destroyChart();
    await bumpCanvas();
    await createChart();
  }
});

// Lifecycle
onMounted(async () => {
  updateTheme()
  observer = new MutationObserver(updateTheme)
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
  await createChart();
});

onUnmounted(() => {
  observer?.disconnect()
  destroyChart();
});
</script>