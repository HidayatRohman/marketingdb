<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Wrench as WrenchIcon } from 'lucide-vue-next'
import { Head, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { ref, watch, onMounted, computed } from 'vue'
import { Dialog, DialogHeader, DialogTitle, DialogScrollContent } from '@/components/ui/dialog'
import CsMaintenanceDailyChart from '@/components/CsMaintenanceDailyChart.vue'
import CsMaintenanceCategoryPieChart from '@/components/CsMaintenanceCategoryPieChart.vue'

interface Item {
  id: number
  nama_pelanggan: string
  no_tlp: string
  tanggal?: string
  product?: { id: number; nama: string } | null
  chat?: string
  kota?: string
  provinsi?: string
  kendala?: string
  solusi?: string
}

const props = defineProps<{
  items: { data: Item[]; total: number; per_page: number; current_page: number }
  filters: { q?: string | null; product_id?: number | string | null }
  products: Array<{ id: number; nama: string }>
}>()

const q = ref(props.filters.q || '')
const productId = ref(props.filters.product_id || '')

// Grafik: tanggal awal/akhir default bulan berjalan
const today = new Date()
const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1)
const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0)
const startDate = ref(startOfMonth.toISOString().split('T')[0])
const endDate = ref(endOfMonth.toISOString().split('T')[0])
const chartLoading = ref(false)
const dailyData = ref<{ date: string; count: number }[]>([])

const fetchDaily = async () => {
  chartLoading.value = true
  try {
    const params = new URLSearchParams({
      start_date: startDate.value,
      end_date: endDate.value,
      product_id: productId.value ? String(productId.value) : ''
    })
    const res = await fetch(`/cs/maintenances/analytics/daily-count?${params}`)
    if (res.ok) {
      const json = await res.json()
      dailyData.value = Array.isArray(json.data) ? json.data : []
    }
  } catch (e) {
    // noop
  } finally {
    chartLoading.value = false
  }
}

// Grafik Kendala & Solusi
const kendalaLoading = ref(false)
const solusiLoading = ref(false)
const kendalaData = ref<Array<{ label: string; count: number; warna?: string }>>([])
const solusiData = ref<Array<{ label: string; count: number; warna?: string }>>([])

const fetchKendala = async () => {
  kendalaLoading.value = true
  try {
    const params = new URLSearchParams({
      start_date: startDate.value,
      end_date: endDate.value,
      product_id: productId.value ? String(productId.value) : ''
    })
    const res = await fetch(`/cs/maintenances/analytics/kendala?${params}`)
    if (res.ok) {
      const json = await res.json()
      kendalaData.value = Array.isArray(json.data) ? json.data : []
    }
  } catch (e) {
    // noop
  } finally {
    kendalaLoading.value = false
  }
}

const fetchSolusi = async () => {
  solusiLoading.value = true
  try {
    const params = new URLSearchParams({
      start_date: startDate.value,
      end_date: endDate.value,
      product_id: productId.value ? String(productId.value) : ''
    })
    const res = await fetch(`/cs/maintenances/analytics/solusi?${params}`)
    if (res.ok) {
      const json = await res.json()
      solusiData.value = Array.isArray(json.data) ? json.data : []
    }
  } catch (e) {
    // noop
  } finally {
    solusiLoading.value = false
  }
}

watch([q, productId], () => {
  const params: Record<string, any> = {}
  if (q.value) params.q = q.value
  if (productId.value) params.product_id = productId.value
  router.get('/cs/maintenances', params, { preserveState: true, preserveScroll: true })
})

watch([startDate, endDate, productId], () => {
  fetchDaily()
  fetchKendala()
  fetchSolusi()
})

onMounted(() => {
  fetchDaily()
  fetchKendala()
  fetchSolusi()
})

const destroyItem = (id: number) => {
  if (!confirm('Hapus data ini?')) return
  router.delete(`/cs/maintenances/${id}`)
}

const formatDate = (d?: string) => (d ? new Date(d).toLocaleDateString('id-ID') : '-')

// View modal state & handlers (meniru Daftar Repeat Order)
const showView = ref(false)
const viewItem = ref<Item | null>(null)
const openView = (item: Item) => {
  viewItem.value = item
  showView.value = true
}
const closeView = () => {
  showView.value = false
  viewItem.value = null
}

const asTime = (d?: string) => {
  if (!d) return 0
  const x = new Date(d)
  return isNaN(x.getTime()) ? 0 : x.getTime()
}
const timelineEvents = computed<Item[]>(() => {
  const v = viewItem.value
  if (!v) return []
  const keyPhone = (v.no_tlp || '').trim()
  const keyName = (v.nama_pelanggan || '').trim().toLowerCase()
  return [...props.items.data]
    .filter((i) => {
      const samePhone = keyPhone && (i.no_tlp || '').trim() === keyPhone
      const sameName = keyName && (i.nama_pelanggan || '').trim().toLowerCase() === keyName
      return samePhone || sameName
    })
    .sort((a, b) => asTime(a.tanggal) - asTime(b.tanggal))
})

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'CS Maintenance', href: '/cs/maintenances' },
]
</script>

<template>
  <Head title="CS Maintenance" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-6 mt-6 space-y-6">
      <div class="relative overflow-hidden rounded-xl border border-indigo-100 bg-gradient-to-r from-indigo-50 via-sky-50 to-cyan-50 p-4 text-indigo-700 sm:p-6">
        <div class="relative z-10">
          <div class="flex flex-col space-y-4 lg:flex-row lg:items-center lg:justify-between lg:space-y-0">
            <div class="flex-1">
              <h1 class="mb-2 flex items-center gap-2 text-xl font-bold tracking-tight sm:gap-3 sm:text-2xl lg:text-3xl">
                <WrenchIcon class="h-5 w-5 sm:h-6 sm:w-6 lg:h-8 lg:w-8" />
                Manajemen CS Maintenance
              </h1>
              <p class="text-sm text-indigo-700/80">Kelola data CS Maintenance secara konsisten dan rapi.</p>
            </div>
            <div class="flex items-center gap-2">
              <Button as-child variant="secondary" class="bg-white/60 hover:bg-white/70 text-indigo-700">
                <a href="/cs/maintenances/create">Tambah</a>
              </Button>
            </div>
          </div>
        </div>
      </div>

    <Card>
      <CardHeader>
        <CardTitle>CS Maintenance</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="flex flex-col sm:flex-row gap-2 sm:items-center sm:justify-between mb-4">
          <div class="flex gap-2 items-center">
            <Input v-model="q" placeholder="Cari nama/chat/kota/provinsi/kendala/solusi" class="w-64" />
            <select v-model="productId" class="h-9 rounded border px-2">
              <option value="">Semua Produk</option>
              <option v-for="p in props.products" :key="p.id" :value="p.id">{{ p.nama }}</option>
            </select>
          </div>
          <Button as-child>
            <a href="/cs/maintenances/create">Tambah</a>
          </Button>
        </div>
        <div class="overflow-x-auto responsive-table">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left border-b">
                <th class="py-2 px-2 sticky left-0 z-30 bg-background min-w-[120px] sm:min-w-[200px] border-r border-border">
                  <span class="sm:hidden">Nama</span>
                  <span class="hidden sm:inline">Nama Pelanggan</span>
                </th>
                <th class="py-2 px-2">No Tlp</th>
                <th class="py-2 px-2">Tanggal</th>
                <th class="py-2 px-2">Produk</th>
                <th class="py-2 px-2">Chat</th>
                <th class="py-2 px-2">Kota</th>
                <th class="py-2 px-2">Provinsi</th>
                <th class="py-2 px-2">Kendala</th>
                <th class="py-2 px-2">Solusi</th>
                <th class="py-2 px-2">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="it in props.items.data" :key="it.id" class="border-b">
                <td class="sticky left-0 z-20 bg-background p-2 sm:p-3 font-medium text-xs sm:text-base min-w-[120px] sm:min-w-[200px] border-r border-border">{{ it.nama_pelanggan }}</td>
                <td class="py-2 px-2">{{ it.no_tlp }}</td>
                <td class="py-2 px-2">{{ formatDate(it.tanggal) }}</td>
                <td class="py-2 px-2">{{ it.product?.nama || '-' }}</td>
                <td class="py-2 px-2">{{ it.chat || '-' }}</td>
                <td class="py-2 px-2">{{ it.kota || '-' }}</td>
                <td class="py-2 px-2">{{ it.provinsi || '-' }}</td>
                <td class="py-2 px-2">{{ it.kendala || '-' }}</td>
                <td class="py-2 px-2">{{ it.solusi || '-' }}</td>
                <td class="py-2 px-2">
                  <div class="flex gap-2">
                    <Button variant="secondary" @click="openView(it)">View</Button>
                    <Button variant="outline" as-child><a :href="`/cs/maintenances/${it.id}/edit`">Edit</a></Button>
                    <Button variant="destructive" @click="destroyItem(it.id)">Hapus</Button>
                  </div>
                </td>
              </tr>
              <tr v-if="props.items.data.length === 0">
                <td colspan="9" class="text-center py-6 text-muted-foreground">Tidak ada data</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex justify-between items-center mt-4 text-sm">
          <div>Total: {{ props.items.total }}</div>
          <div>Halaman: {{ props.items.current_page }}</div>
        </div>
      </CardContent>
    </Card>

    <!-- Report Grafik: ditempatkan di bawah CS Maintenance -->
    <Card>
      <CardHeader>
        <CardTitle>Report Grafik (CS Maintenance)</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between mb-4">
          <div class="flex items-center gap-2">
            <Input type="date" v-model="startDate" class="w-40" />
            <span class="text-sm text-muted-foreground">s/d</span>
            <Input type="date" v-model="endDate" class="w-40" />
            <Button variant="outline" @click="fetchDaily">Terapkan</Button>
          </div>
          <div class="text-sm text-muted-foreground" v-if="chartLoading">Memuat grafik…</div>
        </div>
        <CsMaintenanceDailyChart :data="dailyData" :startDate="startDate" :endDate="endDate" @refresh="fetchDaily" />
      </CardContent>
    </Card>

    <!-- Grafik Kendala & Solusi dalam dua kolom pada desktop -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <CsMaintenanceCategoryPieChart
        :title="'Grafik Kendala'"
        :legendTitle="'Kendala'"
        :data="kendalaData"
        :loading="kendalaLoading"
        :startDate="startDate"
        :endDate="endDate"
        emptyMessage="Tidak ada data kendala untuk periode ini."
        @refresh="fetchKendala"
      />

      <CsMaintenanceCategoryPieChart
        :title="'Grafik Solusi'"
        :legendTitle="'Solusi'"
        :data="solusiData"
        :loading="solusiLoading"
        :startDate="startDate"
        :endDate="endDate"
        emptyMessage="Tidak ada data solusi untuk periode ini."
        @refresh="fetchSolusi"
      />
    </div>
    </div>

    <!-- Dialog View Detail CS Maintenance -->
    <Dialog :open="showView" @update:open="(v:boolean)=> showView = v">
      <DialogScrollContent class="sm:max-w-md">
        <DialogHeader class="bg-gradient-to-r from-indigo-600 via-sky-600 to-cyan-600 text-white rounded-t-md -mx-6 -mt-6 px-6 py-3">
          <DialogTitle>Detail CS Maintenance</DialogTitle>
        </DialogHeader>
        <div v-if="viewItem" class="space-y-3 text-sm">
          <div class="grid grid-cols-3 gap-2">
            <div class="font-semibold text-black">Nama</div>
            <div class="col-span-2 font-medium">{{ viewItem.nama_pelanggan }}</div>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <div class="font-semibold text-black">No Tlp</div>
            <div class="col-span-2">{{ viewItem.no_tlp }}</div>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <div class="font-semibold text-black">Kota</div>
            <div class="col-span-2">{{ viewItem.kota || '-' }}</div>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <div class="font-semibold text-black">Provinsi</div>
            <div class="col-span-2">{{ viewItem.provinsi || '-' }}</div>
          </div>

          <div v-if="timelineEvents.length >= 1" class="mt-6">
            <div class="text-sm font-semibold text-indigo-700 border border-indigo-100/50 bg-gradient-to-r from-indigo-50 via-sky-50 to-cyan-50 dark:from-indigo-900/40 dark:via-sky-900/30 dark:to-cyan-900/30 rounded-md px-3 py-2">Histori Maintenance</div>
            <div class="relative mt-3 pl-8 pr-2 max-h-64 overflow-y-auto">
              <div class="absolute left-3 top-0 h-full w-0.5 bg-indigo-200 dark:bg-indigo-800"></div>
              <div v-for="e in timelineEvents" :key="e.id" class="relative mb-4">
                <div class="absolute -left-4 top-1 h-3 w-3 rounded-full border-2 border-indigo-500 bg-white dark:bg-gray-900"></div>
                <div class="grid grid-cols-[120px_1fr] gap-3">
                  <div class="text-indigo-700 dark:text-indigo-300 font-semibold">{{ formatDate(e.tanggal) }}</div>
                  <div class="col-span-2">
                    <div class="text-xs"><span class="font-semibold text-black">Kendala:</span> <span class="text-gray-600 dark:text-gray-400">{{ e.kendala || '-' }}</span></div>
                    <div class="text-xs"><span class="font-semibold text-black">Solusi:</span> <span class="text-gray-600 dark:text-gray-400">{{ e.solusi || '-' }}</span></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="flex justify-end gap-2 mt-4">
          <Button variant="outline" @click="closeView">Tutup</Button>
          <Button v-if="viewItem" as-child><a :href="`/cs/maintenances/${viewItem.id}/edit`">Edit</a></Button>
        </div>
      </DialogScrollContent>
    </Dialog>
  </AppLayout>
  </template>

<style scoped>
.responsive-table {
  -webkit-overflow-scrolling: touch;
}
</style>
