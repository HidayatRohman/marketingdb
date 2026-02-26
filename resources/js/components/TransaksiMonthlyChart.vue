<template>
  <Card class="w-full dark:bg-gray-800 dark:border-gray-700">
    <CardHeader class="pb-3">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <CardTitle class="text-lg font-semibold text-gray-900 dark:text-white">
            Analisa Transaksi Bulanan
          </CardTitle>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Jumlah transaksi dan total nominal per bulan
          </p>
        </div>
        
        <div class="flex items-center gap-2">
          <!-- Compare Checkbox -->
          <div class="flex items-center gap-2 mr-2">
             <input 
               type="checkbox" 
               id="compare-year" 
               :checked="compare"
               @change="(e) => $emit('update:compare', (e.target as HTMLInputElement).checked)"
               class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700"
             />
             <label for="compare-year" class="text-xs text-gray-700 dark:text-gray-300 select-none">
               Bandingkan {{ year - 1 }}
             </label>
          </div>

          <!-- Year Selector -->
          <Select :model-value="String(year)" @update:model-value="(v) => $emit('update:year', Number(v))">
            <SelectTrigger class="w-[100px] h-8 text-xs">
              <SelectValue placeholder="Tahun" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="y in years" :key="y" :value="String(y)">
                {{ y }}
              </SelectItem>
            </SelectContent>
          </Select>

          <!-- Refresh Button -->
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
          <span class="text-sm">Memuat data analisa...</span>
        </div>
      </div>

      <!-- Chart Container -->
      <div v-else class="relative">
        <div class="h-64 w-full sm:h-80">
          <canvas :key="canvasKey" ref="chartCanvas" :id="canvasId"></canvas>
        </div>
        
        <!-- Summary Cards -->
        <div class="mt-3 grid grid-cols-1 gap-3 sm:mt-4 sm:gap-4 lg:grid-cols-2">
           <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
              <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                 <TrendingUp class="h-4 w-4 text-indigo-500" />
                 Total Transaksi Tahun Ini
              </h4>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ totalCount }}</p>
           </div>
           <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
              <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                 <DollarSign class="h-4 w-4 text-green-500" />
                 Total Omset Tahun Ini
              </h4>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(totalNominal) }}</p>
           </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { RefreshCw, TrendingUp, DollarSign } from 'lucide-vue-next';
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

interface MonthlyData {
  month: number;
  count: number;
  total_nominal: number;
  count_prev?: number;
  total_nominal_prev?: number;
}

interface Props {
  data?: MonthlyData[];
  year?: number;
  compare?: boolean;
  loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  data: () => [],
  year: new Date().getFullYear(),
  compare: false,
  loading: false,
});

const emit = defineEmits<{
  refresh: [];
  'update:year': [year: number];
  'update:compare': [value: boolean];
}>();

// Generate years list (current year - 4 to current year)
const currentYear = new Date().getFullYear();
const years = computed(() => {
  const yrs = [];
  for (let i = 0; i < 5; i++) {
    yrs.push(currentYear - i);
  }
  return yrs;
});

// Component state
const chartCanvas = ref<HTMLCanvasElement>();
const chartInstance = ref<ChartJS | null>(null);

// Canvas management
const canvasKey = ref(0);
const canvasId = computed(() => `transaksi-monthly-chart-${canvasKey.value}`);

// Helpers
const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value);
};

const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

// Computed totals
const totalCount = computed(() => props.data.reduce((sum, item) => sum + Number(item.count), 0));
const totalNominal = computed(() => props.data.reduce((sum, item) => sum + Number(item.total_nominal), 0));

// Chart Data
const chartData = computed<ChartData>(() => {
  const labels = monthNames;
  const counts = new Array(12).fill(0);
  const nominals = new Array(12).fill(0);
  const countsPrev = new Array(12).fill(0);
  const nominalsPrev = new Array(12).fill(0);

  props.data.forEach(item => {
    const idx = item.month - 1;
    if (idx >= 0 && idx < 12) {
      counts[idx] = Number(item.count);
      nominals[idx] = Number(item.total_nominal);
      
      if (props.compare) {
        countsPrev[idx] = Number(item.count_prev || 0);
        nominalsPrev[idx] = Number(item.total_nominal_prev || 0);
      }
    }
  });

  const datasets: any[] = [
    {
      type: 'bar',
      label: `Jumlah Transaksi ${props.year}`,
      data: counts,
      backgroundColor: 'rgba(59, 130, 246, 0.7)', // Blue
      borderColor: 'rgba(59, 130, 246, 1)',
      borderWidth: 1,
      yAxisID: 'y',
      order: 2,
    },
    {
      type: 'line',
      label: `Total Nominal ${props.year}`,
      data: nominals,
      borderColor: 'rgba(16, 185, 129, 1)', // Emerald
      backgroundColor: 'rgba(16, 185, 129, 0.1)',
      borderWidth: 2,
      pointBackgroundColor: 'rgba(16, 185, 129, 1)',
      tension: 0.3,
      fill: true,
      yAxisID: 'y1',
      order: 1,
    }
  ];

  if (props.compare) {
    datasets.push({
      type: 'bar',
      label: `Jumlah Transaksi ${props.year - 1}`,
      data: countsPrev,
      backgroundColor: 'rgba(156, 163, 175, 0.5)', // Gray
      borderColor: 'rgba(156, 163, 175, 1)',
      borderWidth: 1,
      yAxisID: 'y',
      order: 3,
    });
    datasets.push({
      type: 'line',
      label: `Total Nominal ${props.year - 1}`,
      data: nominalsPrev,
      borderColor: 'rgba(245, 158, 11, 1)', // Amber/Orange
      backgroundColor: 'rgba(245, 158, 11, 0.1)',
      borderWidth: 2,
      borderDash: [5, 5], // Dashed line
      pointBackgroundColor: 'rgba(245, 158, 11, 1)',
      tension: 0.3,
      fill: false,
      yAxisID: 'y1',
      order: 0,
    });
  }

  return {
    labels,
    datasets
  };
});

const chartOptions = computed<ChartOptions>(() => {
  const isDark = document.documentElement.classList.contains('dark');
  const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
  const textColor = isDark ? '#e5e7eb' : '#374151';

  return {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
      mode: 'index',
      intersect: false,
    },
    plugins: {
      legend: {
        position: 'top',
        labels: {
          color: textColor,
          usePointStyle: true,
        },
      },
      tooltip: {
        callbacks: {
          label: function(context) {
            let label = context.dataset.label || '';
            if (label) {
              label += ': ';
            }
            if (context.dataset.yAxisID === 'y1') {
              label += formatCurrency(context.parsed.y);
            } else {
              label += context.parsed.y;
            }
            return label;
          }
        }
      }
    },
    scales: {
      x: {
        grid: {
          color: gridColor,
          display: false,
        },
        ticks: {
          color: textColor,
        },
      },
      y: {
        type: 'linear',
        display: true,
        position: 'left',
        title: {
          display: true,
          text: 'Jumlah Transaksi',
          color: textColor,
        },
        grid: {
          color: gridColor,
        },
        ticks: {
          color: textColor,
          precision: 0,
        },
      },
      y1: {
        type: 'linear',
        display: true,
        position: 'right',
        title: {
          display: true,
          text: 'Total Nominal (Rp)',
          color: textColor,
        },
        grid: {
          drawOnChartArea: false,
        },
        ticks: {
          color: textColor,
          callback: function(value) {
            // Shorten large numbers
            const val = Number(value);
            if (val >= 1000000000) return (val / 1000000000).toFixed(1) + 'M';
            if (val >= 1000000) return (val / 1000000).toFixed(1) + 'Jt';
            if (val >= 1000) return (val / 1000).toFixed(0) + 'k';
            return val;
          }
        },
      },
    },
  };
});

// Render Chart
const renderChart = () => {
  if (!chartCanvas.value) return;
  
  // Destroy existing chart with robust check
  if (chartInstance.value) {
    chartInstance.value.destroy();
    chartInstance.value = null;
  }
  
  // Double check global registry
  const existingChart = ChartJS.getChart(chartCanvas.value);
  if (existingChart) {
    existingChart.destroy();
  }

  chartInstance.value = new ChartJS(chartCanvas.value, {
    type: 'bar', // Base type
    data: chartData.value,
    options: chartOptions.value,
  });
};

// Watchers
watch(() => props.data, () => {
  if (!props.loading) {
    nextTick(() => {
      renderChart();
    });
  }
}, { deep: true });

// Theme observer
let observer: MutationObserver | null = null;

onMounted(() => {
  renderChart();
  
  // Watch for dark mode changes
  observer = new MutationObserver(() => {
    renderChart();
  });
  
  observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class'],
  });
});

onUnmounted(() => {
  if (chartInstance.value) {
    chartInstance.value.destroy();
  }
  if (observer) {
    observer.disconnect();
  }
});
</script>
