<template>
  <Card class="border-0 shadow-md dark:bg-gray-800 dark:border-gray-700">
    <CardHeader class="pb-3">
      <CardTitle class="dark:text-gray-100">{{ title }}</CardTitle>
    </CardHeader>
    <CardContent>
      <div v-if="loading" class="py-12 text-center text-sm text-muted-foreground dark:text-gray-400">Memuat grafik...</div>
      <div v-else-if="chartData && chartData.datasets.length > 0" class="h-64">
        <canvas :key="canvasKey" ref="chartCanvas" :id="canvasId"></canvas>
      </div>
      <div v-else class="py-8 text-center text-sm text-muted-foreground dark:text-gray-400">Tidak ada data.</div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import ChartJS from 'chart.js/auto'
import { ChartData, ChartOptions } from 'chart.js'

interface TopRow { label: string; value: number }
const props = defineProps<{ 
  data?: TopRow[]; 
  title: string;
  loading?: boolean;
  color?: string;
  idPrefix: string;
}>()

const chartCanvas = ref<HTMLCanvasElement>()
const chartInstance = ref<ChartJS | null>(null)
const canvasKey = ref(0)
const canvasId = computed(() => `cs-repeat-top-${props.idPrefix}-${canvasKey.value}`)

const isDark = ref(false)
let observer: MutationObserver | null = null

const updateTheme = () => {
  isDark.value = document.documentElement.classList.contains('dark')
}

const chartData = computed<ChartData<'bar'>>(() => {
  if (!props.data || props.data.length === 0) return { labels: [], datasets: [] }
  const labels = props.data.map(r => r.label)
  const data = props.data.map(r => r.value)
  const baseColor = props.color || '#6366f1'
  
  return {
    labels,
    datasets: [
      {
        label: props.title,
        data,
        borderColor: baseColor,
        backgroundColor: baseColor + 'CC', // slightly transparent
        borderWidth: 1,
        borderRadius: 4,
      },
    ],
  }
})

const options = computed<ChartOptions<'bar'>>(() => ({
  indexAxis: 'y', // Horizontal bar chart
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false, // Hide legend since there's only one dataset
    },
    tooltip: {
      enabled: true,
      backgroundColor: isDark.value ? '#1f2937' : '#ffffff',
      titleColor: isDark.value ? '#f3f4f6' : '#111827',
      bodyColor: isDark.value ? '#f3f4f6' : '#111827',
      borderColor: isDark.value ? '#374151' : '#e5e7eb',
      borderWidth: 1,
      callbacks: {
        label: (context) => {
           let label = context.dataset.label || '';
           if (label) {
               label += ': ';
           }
           if (context.parsed.x !== null) {
               label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(context.parsed.x);
           }
           return label;
        }
      }
    },
  },
  scales: {
    x: {
      display: true,
      grid: { color: isDark.value ? '#374151' : '#e5e7eb' },
      ticks: { 
          color: isDark.value ? '#9ca3af' : '#6b7280',
          callback: (value) => {
              if (typeof value === 'number') {
                  // Shorten large numbers
                  if (value >= 1000000000) return (value / 1000000000).toFixed(1) + 'M';
                  if (value >= 1000000) return (value / 1000000).toFixed(1) + 'jt';
                  if (value >= 1000) return (value / 1000).toFixed(0) + 'k';
              }
              return value;
          }
      }
    },
    y: {
      display: true,
      grid: { display: false }, // Cleaner look for horizontal bars
      ticks: { color: isDark.value ? '#9ca3af' : '#6b7280' }
    },
  },
}))

const destroyChart = () => {
  if (chartInstance.value) {
    chartInstance.value.destroy()
    chartInstance.value = null
  }
  if (chartCanvas.value) {
    const existing = ChartJS.getChart(chartCanvas.value)
    if (existing) existing.destroy()
  }
}

const renderChart = async () => {
  destroyChart()
  await nextTick()
  if (!chartCanvas.value) return

  const ctx = chartCanvas.value.getContext('2d')
  if (!ctx) return

  // Ensure any existing chart on this canvas is destroyed immediately before creation
  const existingChart = ChartJS.getChart(chartCanvas.value)
  if (existingChart) {
    existingChart.destroy()
  }

  chartInstance.value = new ChartJS(ctx, {
    type: 'bar',
    data: chartData.value,
    options: options.value,
  })
}

watch([chartData, options], () => {
    renderChart()
})

watch(isDark, () => {
    renderChart()
})

onMounted(() => {
  updateTheme()
  observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.attributeName === 'class') {
        updateTheme()
      }
    })
  })
  observer.observe(document.documentElement, { attributes: true })
  
  nextTick(() => {
    renderChart()
  })
})

onUnmounted(() => {
  destroyChart()
  if (observer) observer.disconnect()
})
</script>
