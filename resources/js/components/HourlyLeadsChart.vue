<template>
  <Card class="border-0 shadow-md">
    <CardHeader class="pb-3 sm:pb-4">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2 sm:gap-3">
          <div class="rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 p-1.5 shadow-lg sm:p-2">
            <TrendingUp class="h-4 w-4 text-white sm:h-5 sm:w-5" />
          </div>
          <div class="min-w-0 flex-1">
            <CardTitle class="text-base font-semibold text-gray-900 dark:text-white sm:text-lg">
              <span class="hidden sm:inline">Analisa Lead per Jam</span>
              <span class="sm:hidden">Lead per Jam</span>
              <span class="block text-xs font-normal text-gray-600 dark:text-gray-400 sm:hidden">
                {{ selectedDate ? formatDate(selectedDate) : 'Semua Tanggal' }}
              </span>
            </CardTitle>
            <p class="hidden text-sm text-gray-600 dark:text-gray-400 mt-1 sm:block">
              {{ selectedDate ? formatDate(selectedDate) : 'Semua Tanggal' }} - Distribusi lead berdasarkan jam dan brand
            </p>
          </div>
        </div>
        
        <!-- Chart Controls - Mobile Responsive -->
        <div class="flex flex-wrap items-center gap-2">
          <!-- View Toggle -->
          <div class="flex rounded-lg border border-gray-200 dark:border-gray-700 p-1">
            <Button
              variant="ghost"
              size="sm"
              @click="viewMode = 'line'"
              :class="[
                'h-7 px-2 text-xs sm:h-8 sm:px-3',
                viewMode === 'line' 
                  ? 'bg-indigo-500 text-white shadow-sm' 
                  : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800'
              ]"
            >
              <TrendingUp class="h-3 w-3 mr-1" />
              <span class="hidden sm:inline">Line</span>
            </Button>
            <Button
              variant="ghost"
              size="sm"
              @click="viewMode = 'bar'"
              :class="[
                'h-7 px-2 text-xs sm:h-8 sm:px-3',
                viewMode === 'bar' 
                  ? 'bg-indigo-500 text-white shadow-sm' 
                  : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800'
              ]"
            >
              <BarChart3 class="h-3 w-3 mr-1" />
              <span class="hidden sm:inline">Bar</span>
            </Button>
          </div>
          
          <!-- Brand Filter Dropdown -->
          <div class="relative" ref="filterDropdown">
            <Button
              variant="outline"
              size="sm"
              @click="showBrandFilter = !showBrandFilter"
              class="h-7 px-2 text-xs border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-800 min-w-[80px] justify-between sm:h-8 sm:px-3 sm:min-w-[100px]"
            >
              <span class="truncate">
                {{ selectedBrands.length === 0 ? 'Semua' : 
                   selectedBrands.length === 1 ? (selectedBrands[0].length > 8 ? selectedBrands[0].substring(0, 8) + '...' : selectedBrands[0]) : 
                   `${selectedBrands.length} Brand` }}
              </span>
              <ChevronDown class="h-3 w-3 ml-1 flex-shrink-0" />
            </Button>
            
            <!-- Dropdown Menu -->
            <div 
              v-if="showBrandFilter"
              class="absolute right-0 mt-1 w-44 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg z-50 sm:w-48"
            >
              <div class="p-2 max-h-40 overflow-y-auto sm:max-h-48">
                <div class="mb-2">
                  <label class="flex items-center space-x-2 cursor-pointer p-1 hover:bg-gray-50 dark:hover:bg-gray-700 rounded">
                    <input 
                      type="checkbox" 
                      :checked="selectedBrands.length === 0"
                      @change="toggleAllBrands"
                      class="rounded border-gray-300 dark:border-gray-600"
                    />
                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Semua Brand</span>
                  </label>
                </div>
                <hr class="border-gray-200 dark:border-gray-600 mb-2" />
                <div v-for="brand in availableBrands" :key="brand" class="mb-1">
                  <label class="flex items-center space-x-2 cursor-pointer p-1 hover:bg-gray-50 dark:hover:bg-gray-700 rounded">
                    <input 
                      type="checkbox" 
                      :value="brand"
                      v-model="selectedBrands"
                      class="rounded border-gray-300 dark:border-gray-600"
                    />
                    <span class="text-xs text-gray-700 dark:text-gray-300 truncate">{{ brand }}</span>
                  </label>
                </div>
              </div>
            </div>
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
          <!-- Brand Legend -->
          <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2 flex items-center gap-2">
              <Palette class="h-4 w-4" />
              Brand Legend
            </h4>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-1">
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
                  {{ getTotalLeadsForBrand(dataset.label) }}
                </Badge>
              </div>
            </div>
          </div>

          <!-- Peak Hours Stats -->
          <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2 flex items-center gap-2">
              <Clock class="h-4 w-4" />
              Jam Puncak
            </h4>
            <div class="space-y-1.5 sm:space-y-2">
              <div 
                v-for="peak in peakHours" 
                :key="peak.hour"
                class="flex items-center justify-between rounded-lg bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950 dark:to-purple-950 px-2 py-1"
              >
                <span class="text-xs font-medium text-indigo-900 dark:text-indigo-100">
                  {{ formatHour(peak.hour) }}
                </span>
                <div class="flex items-center gap-1">
                  <Badge variant="default" class="text-xs">
                    {{ peak.total }} leads
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
          Belum Ada Data Lead
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 text-center max-w-sm px-4 sm:max-w-md sm:px-0">
          {{ emptyMessage || 'Tidak ada data lead untuk periode yang dipilih. Pilih tanggal atau filter yang berbeda.' }}
        </p>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { TrendingUp, BarChart3, RefreshCw, Clock, Palette, ChevronDown } from 'lucide-vue-next';
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

interface HourlyLeadData {
  hour: number;
  brands: Record<string, number>;
  total: number;
}

interface Props {
  data?: HourlyLeadData[];
  loading?: boolean;
  selectedDate?: string;
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
const viewMode = ref<'line' | 'bar'>('line');

// Brand filtering state
const selectedBrands = ref<string[]>([]);
const showBrandFilter = ref(false);
const filterDropdown = ref<HTMLElement>();

// Canvas management to avoid reuse issues
const canvasKey = ref(0);
const canvasId = computed(() => `hourly-leads-chart-${canvasKey.value}`);

// Available brands computed from data
const availableBrands = computed(() => {
  if (!props.data || !Array.isArray(props.data)) return [];
  
  const brands = new Set<string>();
  props.data.forEach(item => {
    if (item.brands) {
      Object.keys(item.brands).forEach(brandName => {
        if (brandName && brandName.trim()) {
          brands.add(brandName);
        }
      });
    }
  });
  
  return Array.from(brands).sort();
});

// Brand colors - consistent palette
const brandColors = [
  '#6366f1', // Indigo
  '#8b5cf6', // Purple  
  '#06b6d4', // Cyan
  '#10b981', // Emerald
  '#f59e0b', // Amber
  '#ef4444', // Red
  '#ec4899', // Pink
  '#84cc16', // Lime
  '#f97316', // Orange
  '#6b7280', // Gray
];

// Computed properties
const chartData = computed(() => {
  if (!props.data || props.data.length === 0) {
    return null;
  }

  // Get all unique brands
  const brands = new Set<string>();
  props.data.forEach(hourData => {
    Object.keys(hourData.brands).forEach(brand => brands.add(brand));
  });

  let brandList = Array.from(brands).sort();
  
  // Apply brand filtering if any brands are selected
  if (selectedBrands.value.length > 0) {
    brandList = brandList.filter(brand => selectedBrands.value.includes(brand));
  }
  
  // Create datasets for each brand
  const datasets = brandList.map((brand, index) => {
    const color = brandColors[index % brandColors.length];
    
    const data = props.data!.map(hourData => hourData.brands[brand] || 0);
    
    const baseConfig = {
      label: brand,
      data: data,
      borderColor: color,
      backgroundColor: viewMode.value === 'bar' ? color + '20' : color + '10',
      borderWidth: 2,
      tension: 0.4,
      pointBackgroundColor: color,
      pointBorderColor: '#ffffff',
      pointBorderWidth: 2,
      pointRadius: 4,
      pointHoverRadius: 6,
    };

    // Add fill only for line charts with proper configuration
    if (viewMode.value === 'line') {
      return {
        ...baseConfig,
        fill: {
          target: 'origin',
          above: color + '08',
        },
      };
    }

    return baseConfig;
  });

  // Create labels (hours)
  const labels = props.data.map(hourData => formatHour(hourData.hour));

  return {
    labels,
    datasets,
  } as ChartData<'line' | 'bar'>;
});

const peakHours = computed(() => {
  if (!props.data || props.data.length === 0) return [];
  
  return props.data
    .filter(hourData => hourData.total > 0)
    .sort((a, b) => b.total - a.total)
    .slice(0, 3); // Top 3 peak hours
});

// Methods
const formatHour = (hour: number): string => {
  return `${String(hour).padStart(2, '0')}:00`;
};

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long', 
    day: 'numeric',
  });
};

const getTotalLeadsForBrand = (brandName: string): number => {
  if (!props.data) return 0;
  
  return props.data.reduce((total, hourData) => {
    return total + (hourData.brands[brandName] || 0);
  }, 0);
};

// Brand filtering methods
const toggleAllBrands = () => {
  if (selectedBrands.value.length === 0) {
    // If none selected, don't change (keep showing all)
    return;
  } else {
    // If some selected, clear selection (show all)
    selectedBrands.value = [];
  }
};

const closeBrandFilter = () => {
  showBrandFilter.value = false;
};

const createChart = async () => {
  if (!chartCanvas.value || !chartData.value) return;

  // Destroy existing chart safely
  if (chartInstance.value) {
    try {
      chartInstance.value.stop();
      
      // Clear Chart.js instances registry
      const canvasId = chartCanvas.value?.id;
      if (canvasId && (ChartJS as any).instances) {
        delete (ChartJS as any).instances[canvasId];
      }
      
      chartInstance.value.destroy();
    } catch (error) {
      console.warn('Error destroying previous chart:', error);
    }
    chartInstance.value = null;
    
    // Force canvas recreation to avoid reuse issues
    canvasKey.value += 1;
    await nextTick();
    await new Promise(resolve => setTimeout(resolve, 50));
  }

  // Ensure canvas has unique ID
  if (chartCanvas.value && !chartCanvas.value.id) {
    chartCanvas.value.id = canvasId.value;
  }

  const ctx = chartCanvas.value?.getContext('2d');
  if (!ctx) return;

  const config: ChartOptions<'line' | 'bar'> = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
      mode: 'index',
      intersect: false,
    },
    plugins: {
      legend: {
        display: false, // We show custom legend
      },
      filler: {
        propagate: false,
        drawTime: 'beforeDatasetsDraw'
      },
      tooltip: {
        backgroundColor: 'rgba(17, 24, 39, 0.95)',
        titleColor: '#f9fafb',
        bodyColor: '#f9fafb',
        borderColor: '#6366f1',
        borderWidth: 1,
        cornerRadius: 8,
        displayColors: true,
        callbacks: {
          title: (context) => {
            return `Jam ${context[0].label}`;
          },
          label: (context) => {
            return ` ${context.dataset.label}: ${context.parsed.y} leads`;
          },
          footer: (context) => {
            const total = context.reduce((sum, item) => sum + item.parsed.y, 0);
            return `Total: ${total} leads`;
          }
        }
      },
    },
    scales: {
      x: {
        display: true,
        title: {
          display: window.innerWidth >= 640, // Hide title on mobile
          text: 'Jam (24 Format)',
          color: '#6b7280',
          font: {
            family: 'Inter',
            size: window.innerWidth >= 640 ? 12 : 10,
            weight: '500',
          }
        },
        grid: {
          color: 'rgba(107, 114, 128, 0.1)',
        },
        ticks: {
          color: '#6b7280',
          font: {
            family: 'Inter',
            size: window.innerWidth >= 640 ? 11 : 9,
          },
          maxRotation: 0, // Keep labels horizontal
          minRotation: 0,
          maxTicksLimit: window.innerWidth >= 640 ? 24 : 12, // Reduce ticks on mobile
        }
      },
      y: {
        display: true,
        beginAtZero: true,
        title: {
          display: window.innerWidth >= 640, // Hide title on mobile
          text: 'Jumlah Lead',
          color: '#6b7280',
          font: {
            family: 'Inter',
            size: window.innerWidth >= 640 ? 12 : 10,
            weight: '500',
          }
        },
        grid: {
          color: 'rgba(107, 114, 128, 0.1)',
        },
        ticks: {
          color: '#6b7280',
          font: {
            family: 'Inter',
            size: window.innerWidth >= 640 ? 11 : 9,
          },
          stepSize: 1,
          maxTicksLimit: window.innerWidth >= 640 ? 10 : 6, // Reduce ticks on mobile
        }
      },
    },
  };

  try {
    chartInstance.value = new ChartJS(ctx, {
      type: viewMode.value,
      data: chartData.value,
      options: config,
    });
  } catch (error) {
    console.error('Error creating chart:', error);
    if (chartInstance.value) {
      chartInstance.value.destroy();
      chartInstance.value = null;
    }
  }
};

// Watch for data or view mode changes
watch([() => props.data, viewMode, selectedBrands], async () => {
  await nextTick();
  createChart();
}, { deep: true });

// Click outside handler for brand filter
const handleClickOutside = (event: Event) => {
  if (filterDropdown.value && !filterDropdown.value.contains(event.target as Node)) {
    showBrandFilter.value = false;
  }
};

// Resize handler for responsive chart
const handleResize = async () => {
  if (chartInstance.value) {
    await nextTick();
    createChart(); // Recreate chart with new responsive settings
  }
};

// Initialize chart on mount
onMounted(async () => {
  await nextTick();
  createChart();
  
  // Add click outside listener
  document.addEventListener('click', handleClickOutside);
  
  // Add resize listener for responsive updates
  window.addEventListener('resize', handleResize);
});

// Cleanup on unmount
onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
  window.removeEventListener('resize', handleResize);
  
  if (chartInstance.value) {
    chartInstance.value.destroy();
    chartInstance.value = null;
  }
});
</script>

<style scoped>
/* Custom styles for the chart component */
.chart-container {
  position: relative;
  height: 320px;
  width: 100%;
}
</style>