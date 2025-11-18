<template>
  <Card class="border-0 shadow-md">
    <CardHeader class="pb-3">
      <CardTitle>Daftar Transaksi Mitra</CardTitle>
    </CardHeader>
    <CardContent>
      <div v-if="agg.length === 0" class="py-8 text-center text-sm text-muted-foreground">Tidak ada data transaksi.</div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left border-b">
              <th class="py-2 px-2">Nama Pelanggan</th>
              <th class="py-2 px-2">No Tlp</th>
              <th class="py-2 px-2">Total Transaksi</th>
              <th class="py-2 px-2">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in agg" :key="r.key" class="border-b">
              <td class="py-2 px-2">{{ r.nama }}</td>
              <td class="py-2 px-2">{{ r.no_tlp }}</td>
              <td class="py-2 px-2">{{ formatCurrency(r.total) }}</td>
              <td class="py-2 px-2">
                <Button variant="ghost" size="sm" @click="openChart(r)"><Eye class="h-4 w-4" /></Button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </CardContent>
  </Card>

  <Dialog :open="showChart" @update:open="(v:boolean)=> showChart = v">
    <DialogScrollContent class="sm:max-w-lg">
      <DialogHeader>
        <DialogTitle>Grafik Transaksi Pelanggan</DialogTitle>
      </DialogHeader>
      <div v-if="selected" class="mb-3 text-sm text-muted-foreground">{{ selected.nama }} · {{ selected.no_tlp }}</div>
      <div v-if="chartEmpty" class="py-8 text-center text-sm text-muted-foreground">Tidak ada transaksi untuk pelanggan ini.</div>
      <div v-else class="h-64">
        <canvas :key="canvasKey" ref="chartCanvas" :id="canvasId"></canvas>
      </div>
      <div class="flex justify-end gap-2 mt-4">
        <Button variant="outline" @click="showChart = false">Tutup</Button>
      </div>
    </DialogScrollContent>
  </Dialog>
</template>

<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Dialog, DialogHeader, DialogTitle, DialogScrollContent } from '@/components/ui/dialog'
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import ChartJS from 'chart.js/auto'
import type { ChartData, ChartOptions } from 'chart.js'
import { Eye } from 'lucide-vue-next'

interface Item { nama_pelanggan: string; no_tlp: string; transaksi: number; tanggal?: string }
const props = defineProps<{ items?: Item[] }>()

const formatCurrency = (amount: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(amount)

type AggRow = { key: string; nama: string; no_tlp: string; total: number }
const agg = computed<AggRow[]>(() => {
  const map = new Map<string, AggRow>()
  const arr = Array.isArray(props.items) ? props.items : []
  for (const it of arr) {
    const key = (it.no_tlp || it.nama_pelanggan || '').trim()
    if (!key) continue
    const exist = map.get(key)
    const total = (it.transaksi || 0) + (exist?.total || 0)
    map.set(key, { key, nama: it.nama_pelanggan || '-', no_tlp: it.no_tlp || '-', total })
  }
  return Array.from(map.values()).sort((a, b) => b.total - a.total)
})

const chartCanvas = ref<HTMLCanvasElement>()
const chartInstance = ref<ChartJS | null>(null)
const canvasKey = ref(0)
const canvasId = computed(() => `cs-repeat-partner-transaksi-${canvasKey.value}`)

const selected = ref<AggRow | null>(null)
const showChart = ref(false)

const filteredBySelected = computed(() => {
  if (!selected.value) return [] as Item[]
  const key = selected.value.no_tlp?.trim() || selected.value.nama?.trim().toLowerCase()
  const arr = Array.isArray(props.items) ? props.items : []
  return arr
    .filter(it => {
      const samePhone = (it.no_tlp || '').trim() === (selected.value!.no_tlp || '').trim()
      const sameName = (it.nama_pelanggan || '').trim().toLowerCase() === selected.value!.nama.trim().toLowerCase()
      return samePhone || sameName
    })
    .sort((a, b) => new Date(a.tanggal || '').getTime() - new Date(b.tanggal || '').getTime())
})

const chartEmpty = computed(() => filteredBySelected.value.length === 0)

const chartData = computed<ChartData<'line'>>(() => {
  const rows = filteredBySelected.value
  const labels = rows.map(r => r.tanggal ? new Date(r.tanggal).toLocaleDateString('id-ID') : '')
  const data = rows.map(r => r.transaksi || 0)
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
        fill: 'origin',
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
  if (chartInstance.value) {
    chartInstance.value.destroy()
    chartInstance.value = null
  }
  chartInstance.value = new ChartJS(chartCanvas.value, { type: 'line', data: chartData.value, options })
}

watch(chartData, () => { if (showChart.value) { canvasKey.value++; renderChart() } })
onMounted(() => { if (showChart.value) renderChart() })
onUnmounted(() => { chartInstance.value?.destroy(); chartInstance.value = null })

const openChart = (row: AggRow) => {
  selected.value = row
  showChart.value = true
  canvasKey.value++
  renderChart()
}

watch(() => showChart.value, async (v) => {
  if (v) {
    canvasKey.value++
    await nextTick()
    renderChart()
  } else {
    chartInstance.value?.destroy()
    chartInstance.value = null
  }
})
</script>

<style scoped></style>
