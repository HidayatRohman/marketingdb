<template>
  <Card class="w-full dark:bg-gray-800 dark:border-gray-700">
    <CardHeader class="pb-3">
      <div class="flex items-center justify-between">
        <div>
          <CardTitle class="text-lg font-semibold dark:text-gray-100">Grafik Interaksi Harian (CS Maintenance)</CardTitle>
          <p class="text-sm text-muted-foreground dark:text-gray-300">Jumlah entri per hari pada periode terpilih.</p>
        </div>
        <Button variant="outline" size="sm" @click="$emit('refresh')" class="dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Refresh</Button>
      </div>
    </CardHeader>
    <CardContent>
      <div v-if="chartData && chartData.labels && chartData.labels.length" class="relative h-64 sm:h-80">
        <canvas :key="canvasKey" ref="chartCanvas"></canvas>
      </div>
      <div v-else class="flex flex-col items-center justify-center py-10">
        <div class="rounded-full bg-gray-100 dark:bg-gray-700 p-3 mb-3">
          <BarChart3 class="h-6 w-6 text-gray-400 dark:text-gray-300" />
        </div>
        <p class="text-sm text-muted-foreground dark:text-gray-300">Tidak ada data pada periode ini.</p>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { BarChart3 } from 'lucide-vue-next'
import { nextTick, onMounted, onUnmounted, ref, watch, computed } from 'vue'
import ChartJS from 'chart.js/auto'
import { ChartOptions, ChartData } from 'chart.js'

interface DailyRow { date: string; count: number }
interface Props { data?: DailyRow[]; startDate?: string; endDate?: string }
const props = defineProps<Props>()

defineEmits(['refresh'])

const chartCanvas = ref<HTMLCanvasElement>()
const chartInstance = ref<ChartJS | null>(null)
const canvasKey = ref(0)

const isDark = ref(false)
let observer: MutationObserver | null = null

const updateTheme = () => {
  isDark.value = document.documentElement.classList.contains('dark')
}

const chartData = computed<ChartData<'line'> | null>(() => {
  // ... (keep logic)
  let rows: DailyRow[] = props.data || []

  // Fallback: jika kosong, bangun rentang tanggal dari props start/end dan isi nol
  if ((!rows || rows.length === 0) && props.startDate && props.endDate) {
    const start = new Date(props.startDate)
    const end = new Date(props.endDate)
    const dates: string[] = []
    for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
      dates.push(new Date(d).toISOString().split('T')[0])
    }
    rows = dates.map(date => ({ date, count: 0 }))
  }

  if (!rows || rows.length === 0) return null
  const labels = rows.map(r => r.date)
  const data = rows.map(r => Number(r.count || 0))
  return {
    labels,
    datasets: [
      {
        label: 'Interaksi',
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
      labels: {
        color: isDark.value ? '#f3f4f6' : '#374151'
      }
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
      grid: {
        color: isDark.value ? '#374151' : '#e5e7eb'
      },
      ticks: {
        color: isDark.value ? '#f3f4f6' : '#6b7280'
      }
    },
    y: {
      display: true,
      beginAtZero: true,
      grid: {
        color: isDark.value ? '#374151' : '#e5e7eb'
      },
      ticks: {
        color: isDark.value ? '#f3f4f6' : '#6b7280'
      }
    },
  },
}))

const destroyChart = () => {
  if (chartInstance.value) {
    chartInstance.value.destroy()
    chartInstance.value = null
  }

  // Also try to destroy by canvas ID to be safe
  if (chartCanvas.value) {
    const existing = ChartJS.getChart(chartCanvas.value as any)
    if (existing) existing.destroy()
  }
}

const renderChart = async () => {
  if (!chartData.value) return
  
  await nextTick()
  
  if (!chartCanvas.value) return

  // Ensure any existing chart on this canvas is destroyed immediately before creation
  const existingChart = ChartJS.getChart(chartCanvas.value as any);
  if (existingChart) {
    existingChart.destroy();
  }
  
  if (chartInstance.value) {
    chartInstance.value.destroy();
    chartInstance.value = null;
  }

  try {
    chartInstance.value = new ChartJS(chartCanvas.value, {
      type: 'line',
      data: chartData.value,
      options: options.value,
    })
  } catch (err) {
    console.error('Error rendering chart:', err)
  }
}

watch(isDark, async () => {
  destroyChart()
  canvasKey.value++
  await nextTick()
  renderChart()
})

watch(() => props.data, async () => {
  destroyChart()
  canvasKey.value++
  await nextTick()
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