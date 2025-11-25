<template>
  <Card class="border-0 shadow-md">
    <CardHeader class="pb-3">
      <CardTitle>Transaksi Bulanan</CardTitle>
    </CardHeader>
    <CardContent>
      <div v-if="loading" class="py-12 text-center text-sm text-muted-foreground">Memuat grafik...</div>
      <div v-else-if="chartData && chartData.datasets.length > 0" class="h-64">
        <canvas :key="canvasKey" ref="chartCanvas" :id="canvasId"></canvas>
      </div>
      <div v-else class="py-8 text-center text-sm text-muted-foreground">Tidak ada data.</div>
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
const canvasId = computed(() => `cs-repeat-monthly-transaksi-${canvasKey.value}`)

const monthKey = (d: Date) => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`
const monthLabel = (k: string) => {
  const [y,m] = k.split('-')
  const id = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'][Number(m)-1]
  return `${id} ${y}`
}

const chartData = computed<ChartData<'line'>>(() => {
  if (!props.data || props.data.length === 0) return { labels: [], datasets: [] }
  const groups: Record<string, number> = {}
  for (const r of props.data) {
    const d = new Date(r.date)
    const k = monthKey(d)
    groups[k] = (groups[k] || 0) + (r.total || 0)
  }
  const keys = Object.keys(groups).sort()
  const labels = keys.map(monthLabel)
  const data = keys.map(k => groups[k] || 0)
  return {
    labels,
    datasets: [
      {
        label: 'Transaksi Bulanan',
        data,
        borderColor: '#10b981',
        backgroundColor: '#10b98110',
        borderWidth: 2,
        tension: 0.3,
        fill: false,
        pointRadius: 3,
        pointHoverRadius: 5,
      },
    ],
  }
})

const options: ChartOptions<'line'> = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: true }, tooltip: { enabled: true } },
  scales: { x: { display: true }, y: { display: true, beginAtZero: true } },
}

const renderChart = async () => {
  if (!chartCanvas.value) return
  await nextTick()
  if (chartInstance.value) { chartInstance.value.destroy(); chartInstance.value = null }
  chartInstance.value = new ChartJS(chartCanvas.value, { type: 'line', data: chartData.value, options })
}

watch(() => props.data, () => { canvasKey.value++; renderChart() })
onMounted(renderChart)
onUnmounted(() => { if (chartInstance.value) { chartInstance.value.destroy(); chartInstance.value = null } })
</script>

<style scoped></style>

