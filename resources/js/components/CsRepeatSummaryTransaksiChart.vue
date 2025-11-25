<template>
  <Card class="border-0 shadow-md">
    <CardHeader class="pb-3">
      <CardTitle>Grafik Transaksi CS</CardTitle>
    </CardHeader>
    <CardContent>
      <div v-if="loading" class="py-12 text-center text-sm text-muted-foreground">Memuat grafik...</div>
      <div v-else-if="chartData && chartData.datasets.length > 0" class="h-64">
        <canvas :key="canvasKey" ref="chartCanvas" :id="canvasId"></canvas>
      </div>
      <div v-else class="py-8 text-center text-sm text-muted-foreground">Tidak ada data pada periode ini.</div>
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

const options: ChartOptions<'line'> = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: true }, tooltip: { enabled: true } },
  interaction: { intersect: false, mode: 'index' },
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

