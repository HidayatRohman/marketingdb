<template>
  <Card class="border-0 shadow-md dark:bg-gray-800 dark:border-gray-700">
    <CardHeader class="pb-3">
      <CardTitle class="dark:text-gray-100">Transaksi Mingguan</CardTitle>
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

interface DailyRow { date: string; total: number }
const props = defineProps<{ data?: DailyRow[]; loading?: boolean }>()

const chartCanvas = ref<HTMLCanvasElement>()
const chartInstance = ref<ChartJS | null>(null)
const canvasKey = ref(0)
const canvasId = computed(() => `cs-repeat-weekly-transaksi-${canvasKey.value}`)

const isDark = ref(false)
let observer: MutationObserver | null = null

const updateTheme = () => {
  isDark.value = document.documentElement.classList.contains('dark')
}

const toLocalYMD = (d: Date) => {
  if (isNaN(d.getTime())) return ''
  const tzOffsetMs = d.getTimezoneOffset() * 60000
  return new Date(d.getTime() - tzOffsetMs).toISOString().slice(0, 10)
}
const weekStart = (d: Date) => {
  const day = d.getDay()
  const diff = (day + 6) % 7 // Monday=0
  const start = new Date(d)
  start.setDate(d.getDate() - diff)
  return start
}

const chartData = computed<ChartData<'line'>>(() => {
  if (!props.data || props.data.length === 0) return { labels: [], datasets: [] }
  const groups: Record<string, number> = {}
  for (const r of props.data) {
    const d = new Date(r.date)
    if (isNaN(d.getTime())) continue
    const startKey = toLocalYMD(weekStart(d))
    if (!startKey) continue
    groups[startKey] = (groups[startKey] || 0) + (r.total || 0)
  }
  const keys = Object.keys(groups).sort((a,b) => a.localeCompare(b))
  const labels = keys.map(k => new Date(k).toLocaleDateString('id-ID'))
  const data = keys.map(k => groups[k] || 0)
  return {
    labels,
    datasets: [
      {
        label: 'Transaksi Mingguan',
        data,
        borderColor: '#6366f1',
        backgroundColor: '#6366f110',
        borderWidth: 2,
        tension: 0.3,
        fill: false,
        pointRadius: 3,
        pointHoverRadius: 5,
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
