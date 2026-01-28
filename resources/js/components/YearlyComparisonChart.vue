<template>
  <Card class="w-full mt-6">
    <CardHeader class="pb-3 bg-gradient-to-r from-purple-50 via-pink-50 to-red-50 dark:from-purple-900 dark:via-pink-900 dark:to-red-900 rounded-t-lg border-b border-purple-100 dark:border-purple-800">
      <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
          <CardTitle class="flex items-center gap-2 text-purple-800 dark:text-purple-100">
            <BarChart3 class="h-6 w-6" />
            Grafis Perbandingan Tahunan
          </CardTitle>
          
          <Button
            variant="outline"
            size="sm"
            @click="fetchData"
            class="h-8 px-3 text-xs border-purple-200 hover:bg-purple-100 dark:border-purple-700 dark:hover:bg-purple-800"
          >
            <RefreshCw class="h-3 w-3 mr-2" :class="{ 'animate-spin': loading }" />
            Refresh
          </Button>
        </div>

        <!-- Filters -->
        <div class="flex flex-col sm:flex-row gap-4 items-end sm:items-center bg-white/50 dark:bg-black/20 p-3 rounded-lg backdrop-blur-sm">
            <!-- Years Filter -->
            <div class="flex-1 w-full sm:w-auto">
                <label class="text-xs font-medium text-gray-500 mb-1 block">Pilih Tahun (Bandingkan)</label>
                <div class="flex flex-wrap gap-2">
                    <label 
                        v-for="year in availableYears" 
                        :key="year" 
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-sm cursor-pointer transition-colors"
                        :class="selectedYears.includes(year) ? 'bg-purple-600 border-purple-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'"
                    >
                        <input 
                            type="checkbox" 
                            :value="year" 
                            v-model="selectedYears" 
                            class="hidden"
                            @change="handleYearChange"
                        />
                        <span>{{ year }}</span>
                        <Check v-if="selectedYears.includes(year)" class="h-3 w-3" />
                    </label>
                </div>
            </div>

            <!-- Brand Filter -->
            <div class="w-full sm:w-48">
                <label class="text-xs font-medium text-gray-500 mb-1 block">Brand</label>
                <select 
                    v-model="selectedBrand" 
                    class="w-full h-9 rounded-md border border-gray-300 bg-white px-3 py-1 text-sm shadow-sm transition-colors focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800"
                    @change="fetchData"
                >
                    <option :value="null">Semua Brand</option>
                    <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                        {{ brand.nama }}
                    </option>
                </select>
            </div>

            <!-- Metric Filter -->
            <div class="w-full sm:w-48">
                <label class="text-xs font-medium text-gray-500 mb-1 block">Metrik</label>
                <select 
                    v-model="selectedMetric" 
                    class="w-full h-9 rounded-md border border-gray-300 bg-white px-3 py-1 text-sm shadow-sm transition-colors focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800"
                    @change="updateChart"
                >
                    <option value="spent">Spent Bulanan</option>
                    <option value="leads">Total Leads</option>
                    <option value="omset">Total Omset</option>
                </select>
            </div>
        </div>
      </div>
    </CardHeader>

    <CardContent class="p-6">
      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="flex items-center gap-3 text-gray-500 dark:text-gray-400">
          <div class="animate-spin rounded-full h-6 w-6 border-2 border-purple-500 border-t-transparent"></div>
          <span class="text-sm">Memuat data perbandingan...</span>
        </div>
      </div>

      <!-- Chart Container -->
      <div v-else-if="hasData" class="relative">
        <div class="h-80 w-full">
          <canvas ref="chartCanvas"></canvas>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="flex flex-col items-center justify-center py-12">
        <div class="rounded-full bg-gray-100 dark:bg-gray-800 p-4 mb-4">
          <BarChart3 class="h-8 w-8 text-gray-400" />
        </div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
          Belum Ada Data
        </h3>
        <p class="text-gray-500 dark:text-gray-400 text-center max-w-sm">
          Silakan pilih tahun untuk melihat perbandingan.
        </p>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch, nextTick } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { BarChart3, RefreshCw, Check } from 'lucide-vue-next';
import axios from 'axios';
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
} from 'chart.js';

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  BarController,
  Title,
  Tooltip,
  Legend,
  Filler
);

const props = defineProps<{
    brands: Array<{ id: number; nama: string }>;
    initialBrandId?: number | string;
}>();

const loading = ref(false);
const chartCanvas = ref<HTMLCanvasElement | null>(null);
const chartInstance = ref<ChartJS | null>(null);

const currentYear = new Date().getFullYear();
const availableYears = [currentYear - 2, currentYear - 1, currentYear, currentYear + 1];
const selectedYears = ref<number[]>([currentYear - 1, currentYear]);
const selectedBrand = ref<number | null>(props.initialBrandId ? Number(props.initialBrandId) : null);
const selectedMetric = ref<'spent' | 'leads' | 'omset'>('spent');

const apiData = ref<Record<number, { spent: number[], leads: number[], omset: number[] }>>({});

const hasData = computed(() => Object.keys(apiData.value).length > 0 && selectedYears.value.length > 0);

// Colors for different years
const colors = [
    { bg: 'rgba(99, 102, 241, 0.7)', border: 'rgb(99, 102, 241)' },   // Indigo
    { bg: 'rgba(236, 72, 153, 0.7)', border: 'rgb(236, 72, 153)' },   // Pink
    { bg: 'rgba(16, 185, 129, 0.7)', border: 'rgb(16, 185, 129)' },   // Emerald
    { bg: 'rgba(245, 158, 11, 0.7)', border: 'rgb(245, 158, 11)' },   // Amber
];

const handleYearChange = () => {
    // Ensure at least one year is selected if user unchecks all (optional, or let it show empty)
    if (selectedYears.value.length === 0) {
        // Maybe warn or just let it be empty
    }
    fetchData();
};

const fetchData = async () => {
    if (selectedYears.value.length === 0) {
        apiData.value = {};
        return;
    }

    loading.value = true;
    try {
        const response = await axios.get('/iklan-budgets/yearly-comparison', {
            params: {
                years: selectedYears.value,
                brand_id: selectedBrand.value
            }
        });
        apiData.value = response.data;
        updateChart();
    } catch (error) {
        console.error('Failed to fetch comparison data:', error);
    } finally {
        loading.value = false;
    }
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};

const formatNumber = (value: number) => {
    return new Intl.NumberFormat('id-ID').format(value);
};

const updateChart = () => {
    if (!chartCanvas.value || !hasData.value) return;

    if (chartInstance.value) {
        chartInstance.value.destroy();
    }

    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
    
    const datasets = selectedYears.value.map((year, index) => {
        const yearData = apiData.value[year];
        const color = colors[index % colors.length];
        
        return {
            label: `Tahun ${year}`,
            data: yearData ? yearData[selectedMetric.value] : Array(12).fill(0),
            backgroundColor: color.bg,
            borderColor: color.border,
            borderWidth: 1,
            borderRadius: 4,
            barPercentage: 0.7,
            categoryPercentage: 0.8
        };
    }).sort((a, b) => {
        // Sort datasets by year label to keep order consistent
        return a.label.localeCompare(b.label);
    });

    const ctx = chartCanvas.value.getContext('2d');
    if (!ctx) return;

    chartInstance.value = new ChartJS(ctx, {
        type: 'bar',
        data: {
            labels: months,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 8,
                        padding: 20,
                        font: {
                            family: "'Inter', sans-serif",
                            size: 12
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    titleColor: '#1e293b',
                    bodyColor: '#475569',
                    borderColor: '#e2e8f0',
                    borderWidth: 1,
                    padding: 10,
                    boxPadding: 4,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                if (selectedMetric.value === 'spent' || selectedMetric.value === 'omset') {
                                    label += formatCurrency(context.parsed.y);
                                } else {
                                    label += formatNumber(context.parsed.y);
                                }
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                    },
                    ticks: {
                        font: {
                            size: 11
                        },
                        callback: function(value) {
                            if (typeof value === 'number') {
                                if (selectedMetric.value === 'spent' || selectedMetric.value === 'omset') {
                                    // Shorten large numbers
                                    if (value >= 1000000000) return (value / 1000000000).toFixed(1) + 'M';
                                    if (value >= 1000000) return (value / 1000000).toFixed(0) + 'jt';
                                    if (value >= 1000) return (value / 1000).toFixed(0) + 'rb';
                                }
                            }
                            return value;
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    });
};

watch(() => props.initialBrandId, (newVal) => {
    if (newVal) {
        selectedBrand.value = Number(newVal);
        fetchData();
    }
});

onMounted(() => {
    fetchData();
});
</script>