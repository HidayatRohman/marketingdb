<template>
  <Card class="border-0 shadow-md dark:bg-gray-800 dark:border-gray-700">
    <CardHeader class="pb-3 border-b border-indigo-100/50 bg-gradient-to-br from-indigo-50 via-sky-50 to-cyan-50 dark:from-gray-800 dark:via-gray-800 dark:to-gray-800 dark:border-gray-700">
      <CardTitle class="dark:text-gray-100">Daftar Transaksi Mitra</CardTitle>
    </CardHeader>
    <CardContent>
      <div v-if="agg.length === 0" class="py-8 text-center text-sm text-muted-foreground dark:text-gray-400">Tidak ada data transaksi.</div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left border-b dark:border-gray-700">
              <th class="py-2 px-2 dark:text-gray-300">Nama Pelanggan</th>
              <th class="py-2 px-2 dark:text-gray-300">No Tlp</th>
              <th class="py-2 px-2 dark:text-gray-300">Total Transaksi</th>
              <th class="py-2 px-2 dark:text-gray-300">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(r, idx) in pagedAgg" :key="r.key" class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
              <td class="py-2 px-2">
                <div class="flex items-center gap-2">
                  <div v-if="idx < 3" class="medal-wrap" :class="[rankMedalClass(idx), { 'dark-medal': isDark }]">
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
                    <span class="medal-number">{{ idx + 1 + (currentPage - 1) * perPage }}</span>
                  </div>
                  <span class="font-medium dark:text-gray-200">{{ r.nama }}</span>
                </div>
              </td>
              <td class="py-2 px-2">
                <div class="flex items-center gap-2">
                  <div class="rounded bg-green-100 p-1 dark:bg-green-900/50">
                    <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"
                      />
                    </svg>
                  </div>
                  <button
                    @click="openWhatsApp(r.no_tlp, r.nama)"
                    class="font-medium text-green-600 transition-colors duration-200 hover:text-green-800 hover:underline dark:text-green-400 dark:hover:text-green-300"
                    :title="`Hubungi ${r.nama} via WhatsApp`"
                  >
                    {{ r.no_tlp }}
                  </button>
                </div>
              </td>
              <td class="py-2 px-2 dark:text-gray-300">{{ formatCurrency(r.total) }}</td>
              <td class="py-2 px-2">
                <Button variant="ghost" size="sm" @click="openChart(r)" class="dark:text-gray-300 dark:hover:bg-gray-700"><Eye class="h-4 w-4" /></Button>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="mt-4 flex flex-col items-center justify-between gap-3 rounded-lg bg-muted/20 dark:bg-gray-800 p-3 sm:flex-row">
          <div class="text-sm text-foreground/80 dark:text-gray-400">
            Menampilkan <span class="font-medium text-foreground dark:text-gray-200">{{ pagedAgg.length }}</span> dari
            <span class="font-medium text-foreground dark:text-gray-200">{{ agg.length }}</span> pelanggan
            <span v-if="agg.length > 0" class="text-foreground/70 dark:text-gray-500">
              ({{ (currentPage - 1) * perPage + 1 }} - {{ Math.min(currentPage * perPage, agg.length) }})
            </span>
          </div>
          <div class="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              :disabled="currentPage <= 1"
              @click="prevPage"
              class="h-9 border-gray-300 bg-gradient-to-r from-gray-100 to-gray-200 px-3 text-gray-800 transition-all duration-200 hover:from-gray-200 hover:to-gray-300 dark:border-gray-600 dark:from-gray-700 dark:to-gray-800 dark:text-gray-200 dark:hover:from-gray-600 dark:hover:to-gray-700"
            >
              ← Prev
            </Button>
            <Button
              variant="default"
              size="sm"
              class="h-9 w-9 border-blue-500 bg-gradient-to-r from-blue-500 to-blue-600 p-0 text-white shadow-md"
              disabled
            >
              {{ currentPage }}
            </Button>
            <span class="text-sm text-foreground/70">/ {{ totalPages }}</span>
            <Button
              variant="outline"
              size="sm"
              :disabled="currentPage >= totalPages"
              @click="nextPage"
              class="h-9 border-gray-300 bg-gradient-to-r from-gray-100 to-gray-200 px-3 text-gray-800 transition-all duration-200 hover:from-gray-200 hover:to-gray-300 dark:border-gray-600 dark:from-gray-700 dark:to-gray-800 dark:text-gray-200 dark:hover:from-gray-600 dark:hover:to-gray-700"
            >
              Next →
            </Button>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>

  <Dialog :open="showChart" @update:open="(v:boolean)=> showChart = v">
    <DialogScrollContent class="sm:max-w-lg dark:bg-gray-800 dark:border-gray-700">
      <DialogHeader>
        <DialogTitle class="dark:text-gray-100">Grafik Transaksi Pelanggan</DialogTitle>
      </DialogHeader>
      <div v-if="selected" class="mb-1 text-sm text-muted-foreground dark:text-gray-400">{{ selected.nama }} · {{ selected.no_tlp }}</div>
      <div v-if="selected" class="mb-3 text-sm dark:text-gray-300"><span class="font-semibold text-black dark:text-gray-100">Bio Pelanggan</span>: {{ selectedBio || '-' }}</div>
      <div v-if="chartEmpty" class="py-8 text-center text-sm text-muted-foreground dark:text-gray-400">Tidak ada transaksi untuk pelanggan ini.</div>
      <div v-else class="h-64">
        <canvas :key="canvasKey" ref="chartCanvas" :id="canvasId"></canvas>
      </div>
      <div class="flex justify-end gap-2 mt-4">
        <Button variant="outline" @click="showChart = false" class="dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Tutup</Button>
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
const toNumber = (x: unknown): number => {
  if (typeof x === 'number') return x || 0
  if (typeof x === 'string') {
    // Handle decimal strings from DB (e.g. "150000.00")
    if (x.includes('.')) {
      return parseFloat(x) || 0
    }
    const n = parseInt(x.replace(/[^0-9]/g, ''))
    return isNaN(n) ? 0 : n
  }
  return 0
}
const normalizePhone = (s: unknown): string => String(s || '').replace(/\D/g, '')
const normalizeName = (s: unknown): string => String(s || '').trim().toLowerCase()
const agg = computed<AggRow[]>(() => {
  const map = new Map<string, AggRow>()
  const arr = Array.isArray(props.items) ? props.items : []
  for (const it of arr) {
    const phoneKey = normalizePhone(it.no_tlp)
    const nameKey = normalizeName(it.nama_pelanggan)
    const key = phoneKey || nameKey
    if (!key) continue
    const exist = map.get(key)
    const total = toNumber(it.transaksi) + (exist?.total || 0)
    map.set(key, { key, nama: it.nama_pelanggan || '-', no_tlp: it.no_tlp || '-', total })
  }
  return Array.from(map.values()).sort((a, b) => b.total - a.total)
})

const perPage = ref(20)
const currentPage = ref(1)
const totalPages = computed(() => Math.max(1, Math.ceil(agg.value.length / perPage.value)))
const pagedAgg = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return agg.value.slice(start, end)
})
const prevPage = () => { if (currentPage.value > 1) currentPage.value -= 1 }
const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value += 1 }
watch(() => props.items, () => { currentPage.value = 1 })

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
    const samePhone = normalizePhone(it.no_tlp) === normalizePhone(selected.value!.no_tlp)
    const sameName = normalizeName(it.nama_pelanggan) === normalizeName(selected.value!.nama)
    return samePhone || sameName
  })
  return match?.bio_pelanggan ?? null
})

const filteredBySelected = computed(() => {
  if (!selected.value) return [] as Item[]
  const arr = Array.isArray(props.items) ? props.items : []
  return arr
    .filter(it => {
      const samePhone = normalizePhone(it.no_tlp) === normalizePhone(selected.value!.no_tlp)
      const sameName = normalizeName(it.nama_pelanggan) === normalizeName(selected.value!.nama)
      return samePhone || sameName
    })
    .map(it => ({ ...it, transaksi: toNumber(it.transaksi) }))
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

const isDark = ref(false)
let observer: MutationObserver | null = null

const updateTheme = () => {
  isDark.value = document.documentElement.classList.contains('dark')
}

const options = computed<ChartOptions<'line'>>(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: true,
      labels: {
        color: isDark.value ? '#d1d5db' : '#374151'
      }
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
      grid: {
        color: isDark.value ? '#374151' : '#e5e7eb'
      },
      ticks: {
        color: isDark.value ? '#9ca3af' : '#6b7280'
      }
    },
    y: {
      display: true,
      beginAtZero: true,
      grid: {
        color: isDark.value ? '#374151' : '#e5e7eb'
      },
      ticks: {
        color: isDark.value ? '#9ca3af' : '#6b7280'
      }
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
  if (!chartCanvas.value) return
  await nextTick()
  if (!chartCanvas.value) return
  chartInstance.value = new ChartJS(chartCanvas.value, { type: 'line', data: chartData.value, options: options.value })
}

watch(isDark, () => {
  if (showChart.value) {
    renderChart()
  }
})

watch(chartData, () => {
  if (showChart.value) {
    canvasKey.value++
    renderChart()
  }
})

onMounted(() => {
  updateTheme()
  observer = new MutationObserver(updateTheme)
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
  if (showChart.value) renderChart()
})

onUnmounted(() => {
  observer?.disconnect()
  destroyChart()
})

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
    destroyChart()
  }
})

const formatWhatsAppNumber = (phoneNumber: string) => {
  let cleaned = String(phoneNumber).replace(/\D/g, '')
  if (cleaned.startsWith('0')) cleaned = '62' + cleaned.substring(1)
  if (!cleaned.startsWith('62')) cleaned = '62' + cleaned
  return cleaned
}
const createWhatsAppUrl = (phoneNumber: string, message: string = '') => {
  const formattedNumber = formatWhatsAppNumber(phoneNumber)
  const encodedMessage = encodeURIComponent(message)
  return `https://wa.me/${formattedNumber}${message ? `?text=${encodedMessage}` : ''}`
}
const openWhatsApp = (phoneNumber: string, customerName: string) => {
  const message = `Halo ${customerName}, saya ingin menindaklanjuti mengenai inquiry Anda.`
  const url = createWhatsAppUrl(phoneNumber, message)
  window.open(url, '_blank')
}
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
.dark-medal { background: rgba(31, 41, 55, 0.9); border-color: #374151; }
</style>
