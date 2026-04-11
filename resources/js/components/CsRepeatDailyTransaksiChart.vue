<template>
  <Card class="border-0 shadow-md dark:bg-gray-800 dark:border-gray-700">
    <CardHeader class="pb-3">
      <CardTitle class="dark:text-gray-100">Transaksi Harian</CardTitle>
    </CardHeader>
    <CardContent>
      <div v-if="loading" class="py-12 text-center text-sm text-muted-foreground dark:text-gray-400">Memuat grafik...</div>
      <div v-else-if="chartData && chartData.datasets.length > 0" class="h-64">
        <canvas :key="canvasKey" ref="chartCanvas" :id="canvasId"></canvas>
      </div>
      <div v-else class="py-8 text-center text-sm text-muted-foreground dark:text-gray-400">Tidak ada data dalam 30 hari terakhir.</div>
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
const canvasId = computed(() => `cs-repeat-daily-transaksi-${canvasKey.value}`)
const isCreating = ref(false)

const isDark = ref(false)
let observer: MutationObserver | null = null

const updateTheme = () => {
  isDark.value = document.documentElement.classList.contains('dark')
}

const chartData = computed<ChartData<'line'>>(() => {
  if (!props.data || props.data.length === 0) return { labels: [], datasets: [] }
  const labels = props.data.map(r => new Date(r.date).toLocaleDateString('id-ID'))
  const data = props.data.map(r => r.total)
  return {
    labels,
    datasets: [
      {
        label: 'Transaksi',
        data,
        borderColor: '#6366f1',
        backgroundColor: '#6366f110',
        borderWidth: 2,
        tension: 0.3,
        fill: {
          target: 'origin',
          above: '#6366f108',
        },
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
    },
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
  const existingById = ChartJS.getChart(canvasId.value)
  if (existingById) existingById.destroy()
}

const renderChart = async () => {
  if (isCreating.value) return
  isCreating.value = true

  try {
    // Important: wait for DOM to be ready
    await nextTick()
    
    if (!chartCanvas.value) {
      console.warn('Chart canvas ref not available')
      return
    }

    // Destroy existing chart before re-creating
    destroyChart()

    const ctx = chartCanvas.value.getContext('2d')
    if (!ctx) {
      console.error('Could not get canvas context')
      return
    }

    chartInstance.value = new ChartJS(chartCanvas.value, {
      type: 'line',
      data: chartData.value,
      options: options.value,
    })
  } catch (err) {
    console.error('Error rendering chart:', err)
  } finally {
    isCreating.value = false
  }
}

watch(() => props.data, () => {
  canvasKey.value++
  renderChart()
}, { deep: true })

watch(() => props.loading, (newLoading) => {
  if (!newLoading) {
    canvasKey.value++
    renderChart()
  }
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