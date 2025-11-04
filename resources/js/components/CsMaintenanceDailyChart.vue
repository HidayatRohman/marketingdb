<template>
  <Card class="w-full">
    <CardHeader class="pb-3">
      <div class="flex items-center justify-between">
        <div>
          <CardTitle class="text-lg font-semibold">Grafik Interaksi Harian (CS Maintenance)</CardTitle>
          <p class="text-sm text-muted-foreground">Jumlah entri per hari pada periode terpilih.</p>
        </div>
        <Button variant="outline" size="sm" @click="$emit('refresh')">Refresh</Button>
      </div>
    </CardHeader>
    <CardContent>
      <div v-if="chartData && chartData.labels && chartData.labels.length" class="relative h-64 sm:h-80">
        <canvas :key="canvasKey" ref="chartCanvas"></canvas>
      </div>
      <div v-else class="flex flex-col items-center justify-center py-10">
        <div class="rounded-full bg-gray-100 dark:bg-gray-800 p-3 mb-3">
          <BarChart3 class="h-6 w-6 text-gray-400" />
        </div>
        <p class="text-sm text-muted-foreground">Tidak ada data pada periode ini.</p>
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

const chartCanvas = ref<HTMLCanvasElement>()
const chartInstance = ref<ChartJS | null>(null)
const canvasKey = ref(0)

const chartData = computed<ChartData<'line'> | null>(() => {
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

const options: ChartOptions<'line'> = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: true },
    tooltip: { enabled: true },
  },
  scales: {
    x: { display: true },
    y: { display: true, beginAtZero: true },
  },
}

const renderChart = async () => {
  if (!chartCanvas.value || !chartData.value) return
  await nextTick()
  if (chartInstance.value) {
    chartInstance.value.destroy()
    chartInstance.value = null
  }
  chartInstance.value = new ChartJS(chartCanvas.value, {
    type: 'line',
    data: chartData.value,
    options,
  })
}

watch(() => props.data, () => {
  canvasKey.value++
  renderChart()
})

onMounted(renderChart)
onUnmounted(() => {
  if (chartInstance.value) {
    chartInstance.value.destroy()
    chartInstance.value = null
  }
})
</script>

<style scoped></style>