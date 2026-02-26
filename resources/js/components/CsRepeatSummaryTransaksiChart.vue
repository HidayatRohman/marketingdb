<template>
  <Card class="border-0 shadow-md dark:bg-gray-800 dark:border-gray-700">
    <CardHeader class="pb-3">
      <CardTitle class="dark:text-gray-100">Grafik Transaksi CS</CardTitle>
    </CardHeader>
    <CardContent>
      <div v-if="loading" class="py-12 text-center text-sm text-muted-foreground dark:text-gray-400">Memuat grafik...</div>
      <div v-else-if="chartData && chartData.datasets.length > 0" class="h-64">
        <canvas :key="canvasKey" ref="chartCanvas" :id="canvasId"></canvas>
      </div>
      <div v-else class="py-8 text-center text-sm text-muted-foreground dark:text-gray-400">Tidak ada data pada periode ini.</div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import ChartJS from 'chart.js/auto'
import { ChartData, ChartOptions } from 'chart.js'

interface DailyRow { date: string; total: number }
const props = defineProps<{ data?: DailyRow[]; loading?: boolean }>()

const chartCanvas = ref<HTMLCanvasElement>()
const chartInstance = ref<ChartJS | null>(null)
const canvasKey = ref(0)
const canvasId = computed(() => `cs-repeat-summary-transaksi-${canvasKey.value}`)

const isDark = ref(false)
let observer: MutationObserver | null = null

const updateTheme = () => {
  isDark.value = document.documentElement.classList.contains('dark')
}

const chartData = computed<ChartData<'line'>>(() => {
  const rows = props.data || []
  if (!rows || rows.length === 0) return { labels: [], datasets: [] }
  const labels = rows.map(r => new Date(r.date).toLocaleDateString('id-ID'))
  const data = rows.map(r => r.total || 0)
  return {
    labels,
    datasets: [
      {
        label: 'Transaksi',
        data,
        borderColor: '#3b82f6',
        backgroundColor: '#3b82f610',
        borderWidth: 2,
        tension: 0.25,
        fill: false,
        pointRadius: 2,
        pointHoverRadius: 4,
      },
    ],
  }
})

const options = computed<ChartOptions<'line'>>(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: true,
      labels: { color: isDark.value ? '#d1d5db' : '#374151' }
    },
    tooltip: {
      enabled: true,
      backgroundColor: isDark.value ? '#1f2937' : '#ffffff',
      titleColor: isDark.value ? '#f3f4f6' : '#111827',
      bodyColor: isDark.value ? '#f3f4f6' : '#111827',
      borderColor: isDark.value ? '#374151' : '#e5e7eb',
      borderWidth: 1
    }
  },
  interaction: { intersect: false, mode: 'index' },
  scales: {
    x: {
      display: true,
      grid: { color: isDark.value ? '#374151' : '#e5e7eb' },
      ticks: { color: isDark.value ? '#9ca3af' : '#6b7280' }
    },
    y: {
      display: true,
      beginAtZero: true,
      grid: { color: isDark.value ? '#374151' : '#e5e7eb' },
      ticks: { color: isDark.value ? '#9ca3af' : '#6b7280' }
    }
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
  
  // Ensure any existing chart on this canvas is destroyed immediately before creation
  const existingChart = ChartJS.getChart(chartCanvas.value)
  if (existingChart) {
    existingChart.destroy()
  }

  chartInstance.value = new ChartJS(chartCanvas.value, { type: 'line', data: chartData.value, options: options.value })
}

watch(() => props.data, () => {
  canvasKey.value++
  renderChart()
})

watch(isDark, () => {
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
</script>

<style scoped></style>

