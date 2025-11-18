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
            <tr v-for="(r, idx) in agg" :key="r.key" class="border-b">
              <td class="py-2 px-2">
                <div class="flex items-center gap-2">
                  <div v-if="idx < 3" class="medal-wrap" :class="rankMedalClass(idx)">
                    <svg class="medal-svg" viewBox="0 0 40 48" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                      <circle class="medal-circle" cx="20" cy="14" r="12" />
                      <circle class="medal-inner" cx="20" cy="14" r="8.5" />
                      <g class="medal-laurel">
                        <path d="M11 14c0-2 2-4 4-5-1 2-1 3 0 5-1 1-2 2-4 0z" />
                        <path d="M29 14c0-2-2-4-4-5 1 2 1 3 0 5 1 1 2 2 4 0z" />
                      </g>
                      <g class="medal-ribbon">
                        <path class="ribbon-left" d="M14 24 L14 40 L18 36 L18 24 Z" />
                        <path class="ribbon-right" d="M26 24 L26 40 L22 36 L22 24 Z" />
                        <rect class="ribbon-center" x="18" y="24" width="4" height="12" />
                      </g>
                    </svg>
                    <span class="medal-number">{{ idx + 1 }}</span>
                  </div>
                  <span class="font-medium">{{ r.nama }}</span>
                </div>
              </td>
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
      <div v-if="selected" class="mb-1 text-sm text-muted-foreground">{{ selected.nama }} · {{ selected.no_tlp }}</div>
      <div v-if="selected" class="mb-3 text-sm"><span class="font-semibold text-black">Bio Pelanggan</span>: {{ selectedBio || '-' }}</div>
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

interface Item { nama_pelanggan: string; no_tlp: string; transaksi: number; tanggal?: string; bio_pelanggan?: string | null }
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

const rankMedalClass = (idx: number) => {
  if (idx === 0) return 'medal-gold'
  if (idx === 1) return 'medal-silver'
  if (idx === 2) return 'medal-bronze'
  return ''
}

const selectedBio = computed(() => {
  if (!selected.value) return null
  const arr = Array.isArray(props.items) ? props.items : []
  const match = arr.find(it => {
    const samePhone = (it.no_tlp || '').trim() === (selected.value!.no_tlp || '').trim()
    const sameName = (it.nama_pelanggan || '').trim().toLowerCase() === selected.value!.nama.trim().toLowerCase()
    return samePhone || sameName
  })
  return match?.bio_pelanggan ?? null
})

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

<style scoped>
.medal-wrap { width: 28px; height: 34px; position: relative; background: rgba(255,255,255,0.9); border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
.medal-svg { width: 100%; height: 100%; display: block; }
.medal-number { position: absolute; top: 6px; left: 0; width: 28px; text-align: center; font-weight: 800; font-size: 12px; color: #fff; text-shadow: 0 1px 2px rgba(0,0,0,0.2); }
.medal-circle { stroke-width: 2; }
.medal-inner { fill: #ffffff33; }
.medal-laurel path { fill: #ffffffcc; }
.medal-ribbon .ribbon-left { fill: #d32f2f; }
.medal-ribbon .ribbon-right { fill: #d32f2f; }
.medal-ribbon .ribbon-center { fill: #ffffff; }
.medal-gold .medal-circle { fill: #f5c33b; stroke: #e6a700; }
.medal-silver .medal-circle { fill: #b0bec5; stroke: #90a4ae; }
.medal-bronze .medal-circle { fill: #d27c2c; stroke: #b4651f; }
</style>
