<template>
  <Card class="border-0 shadow-md dark:bg-gray-800 dark:border-gray-700">
    <CardHeader class="pb-3">
      <CardTitle class="dark:text-gray-100">Transaksi Per Produk (Harian)</CardTitle>
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

interface DailyProductRow { date: string; products: Record<string, number>; total: number }
const props = defineProps<{ data?: DailyProductRow[]; loading?: boolean }>()

const chartCanvas = ref<HTMLCanvasElement>()
const chartInstance = ref<ChartJS | null>(null)
const canvasKey = ref(0)
const canvasId = computed(() => `cs-repeat-daily-product-${canvasKey.value}`)

const isDark = ref(false)
let observer: MutationObserver | null = null

const updateTheme = () => {
  isDark.value = document.documentElement.classList.contains('dark')
}

const palette = [
  '#6366f1', '#8b5cf6', '#06b6d4', '#10b981', '#f59e0b', '#ef4444', '#ec4899', '#84cc16', '#f97316', '#6b7280'
]

// Use raw date string to avoid any Date API issues
const labels = computed(() => {
  const arr = Array.isArray(props.data) ? props.data : []
  return arr.map(r => r?.date ?? '')
})

const productList = computed(() => {
  const set = new Set<string>()
  const arr = Array.isArray(props.data) ? props.data : []
  for (const row of arr) {
    const prod = row && row.products ? row.products : {}
    for (const key of Object.keys(prod)) set.add(key)
  }
  // Return as-is to avoid sort-related runtime issues
  return Array.from(set)
})

const chartData = computed<ChartData<'line'>>(() => {
  const arr = Array.isArray(props.data) ? props.data : []
  if (arr.length === 0) return { labels: [], datasets: [] }
  const datasets = productList.value.map((product, idx) => {
    const color = palette[idx % palette.length]
    const data = arr.map(row => (row.products && typeof row.products[product] === 'number' ? row.products[product] : 0))
    return {
      label: product,
      data,
      borderColor: color,
      backgroundColor: color,
      borderWidth: 2,
      tension: 0.3,
      pointRadius: 3,
      pointHoverRadius: 5,
      fill: 'origin',
    }
  })
  return { labels: labels.value, datasets }
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
}

const renderChart = async () => {
  destroyChart()
  await nextTick()
  if (!chartCanvas.value) return
  
  chartInstance.value = new ChartJS(chartCanvas.value, {
    type: 'line',
    data: chartData.value,
    options: options.value,
  })
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