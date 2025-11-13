<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Wrench as WrenchIcon } from 'lucide-vue-next'
import { Head, router, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { ref, watch, onMounted, computed } from 'vue'
import { indonesianProvinces } from '@/lib/indonesianProvinces'
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

const toYMD = (input?: string | null): string => {
  if (!input) return ''
  const raw = String(input)
  if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) return raw
  const d = new Date(raw)
  if (isNaN(d.getTime())) return ''
  const tzOffsetMs = d.getTimezoneOffset() * 60000
  const local = new Date(d.getTime() - tzOffsetMs)
  return local.toISOString().slice(0, 10)
}
const getTodayYMD = () => {
  const now = new Date()
  const tzOffsetMs = now.getTimezoneOffset() * 60000
  return new Date(now.getTime() - tzOffsetMs).toISOString().slice(0, 10)
}

const showEdit = ref(false)
const editForm = useForm({
  id: 0,
  nama_pelanggan: '',
  no_tlp: '',
  product_id: '',
  tanggal: '',
  chat: '',
  kota: '',
  provinsi: 'Unknown',
  kendala: '',
  solusi: '',
})
const openEdit = (item: Item) => {
  editForm.id = item.id
  editForm.nama_pelanggan = item.nama_pelanggan || ''
  editForm.no_tlp = item.no_tlp || ''
  editForm.product_id = item.product?.id ? String(item.product.id) : ''
  editForm.tanggal = toYMD(item.tanggal) || ''
  editForm.chat = item.chat || ''
  editForm.kota = item.kota || ''
  editForm.provinsi = item.provinsi || 'Unknown'
  editForm.kendala = item.kendala || ''
  editForm.solusi = item.solusi || ''
  showEdit.value = true
}
const submitEdit = () => {
  editForm.put(`/cs/maintenances/${editForm.id}` as any, {
    preserveScroll: true,
    onSuccess: () => {
      showEdit.value = false
    },
  })
}

const showMaintenance = ref(false)
const maintenanceForm = useForm({
  nama_pelanggan: '',
  no_tlp: '',
  product_id: '',
  tanggal: '',
  chat: '',
  kota: '',
  provinsi: 'Unknown',
  kendala: '',
  solusi: '',
})
const openMaintenanceFromView = (item: Item) => {
  maintenanceForm.nama_pelanggan = item.nama_pelanggan || ''
  maintenanceForm.no_tlp = item.no_tlp || ''
  maintenanceForm.product_id = item.product?.id ? String(item.product.id) : ''
  maintenanceForm.tanggal = getTodayYMD()
  maintenanceForm.chat = item.chat || ''
  maintenanceForm.kota = item.kota || ''
  maintenanceForm.provinsi = item.provinsi || 'Unknown'
  maintenanceForm.kendala = ''
  maintenanceForm.solusi = ''
  showMaintenance.value = true
}
const submitMaintenance = () => {
  maintenanceForm.post('/cs/maintenances', {
    preserveScroll: true,
    onSuccess: () => {
      showMaintenance.value = false
      maintenanceForm.reset()
    },
  })
}

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
                    <Button variant="outline" @click="openEdit(it)">Edit</Button>
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
          <Button v-if="viewItem" @click="openEdit(viewItem as any)">Edit</Button>
          <Button v-if="viewItem" variant="secondary" @click="openMaintenanceFromView(viewItem as any)">Maintenance</Button>
        </div>
      </DialogScrollContent>
    </Dialog>

    <!-- Dialog Edit CS Maintenance -->
    <Dialog :open="showEdit" @update:open="(v:boolean)=> showEdit = v">
      <DialogScrollContent class="sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Edit CS Maintenance</DialogTitle>
        </DialogHeader>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Tanggal</label>
            <Input v-model="editForm.tanggal" type="date" />
            <div v-if="editForm.errors.tanggal" class="text-sm text-red-600 mt-1">{{ editForm.errors.tanggal }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Nama Pelanggan</label>
            <Input v-model="editForm.nama_pelanggan" />
            <div v-if="editForm.errors.nama_pelanggan" class="text-sm text-red-600 mt-1">{{ editForm.errors.nama_pelanggan }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">No Tlp</label>
            <Input v-model="editForm.no_tlp" />
            <div v-if="editForm.errors.no_tlp" class="text-sm text-red-600 mt-1">{{ editForm.errors.no_tlp }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Produk</label>
            <select v-model="editForm.product_id" class="h-9 rounded border px-2 w-full">
              <option value="">-- Pilih Produk --</option>
              <option v-for="p in props.products" :key="p.id" :value="p.id">{{ p.nama }}</option>
            </select>
            <div v-if="editForm.errors.product_id" class="text-sm text-red-600 mt-1">{{ editForm.errors.product_id }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Chat</label>
            <select v-model="editForm.chat" class="h-9 rounded border px-2 w-full">
              <option value="">-- Pilih Status Chat --</option>
              <option value="Baru">Baru</option>
              <option value="Follow Up">Follow Up</option>
              <option value="Follow Up 2">Follow Up 2</option>
              <option value="Followup 3">Followup 3</option>
            </select>
            <div v-if="editForm.errors.chat" class="text-sm text-red-600 mt-1">{{ editForm.errors.chat }}</div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Kota</label>
              <Input v-model="editForm.kota" />
              <div v-if="editForm.errors.kota" class="text-sm text-red-600 mt-1">{{ editForm.errors.kota }}</div>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Provinsi</label>
              <select v-model="editForm.provinsi" class="h-9 rounded border px-2 w-full">
                <option v-for="province in indonesianProvinces" :key="province" :value="province">{{ province }}</option>
              </select>
              <div v-if="editForm.errors.provinsi" class="text-sm text-red-600 mt-1">{{ editForm.errors.provinsi }}</div>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Kendala</label>
            <select v-model="editForm.kendala" class="h-9 rounded border px-2 w-full">
              <option value="">-- Pilih Kendala --</option>
              <option v-for="k in kendalaData" :key="k.label" :value="k.label">{{ k.label }}</option>
            </select>
            <div v-if="editForm.errors.kendala" class="text-sm text-red-600 mt-1">{{ editForm.errors.kendala }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Solusi</label>
            <select v-model="editForm.solusi" class="h-9 rounded border px-2 w-full">
              <option value="">-- Pilih Solusi --</option>
              <option v-for="s in solusiData" :key="s.label" :value="s.label">{{ s.label }}</option>
            </select>
            <div v-if="editForm.errors.solusi" class="text-sm text-red-600 mt-1">{{ editForm.errors.solusi }}</div>
          </div>
          <div class="flex justify-end gap-2">
            <Button variant="outline" @click="showEdit = false">Batal</Button>
            <Button :disabled="editForm.processing" @click="submitEdit">Simpan</Button>
          </div>
        </div>
      </DialogScrollContent>
    </Dialog>

    <!-- Dialog Tambah Maintenance -->
    <Dialog :open="showMaintenance" @update:open="(v:boolean)=> showMaintenance = v">
      <DialogScrollContent class="sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Tambah Histori Maintenance</DialogTitle>
        </DialogHeader>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Tanggal</label>
            <Input v-model="maintenanceForm.tanggal" type="date" />
            <div v-if="maintenanceForm.errors.tanggal" class="text-sm text-red-600 mt-1">{{ maintenanceForm.errors.tanggal }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Nama Pelanggan</label>
            <Input v-model="maintenanceForm.nama_pelanggan" />
            <div v-if="maintenanceForm.errors.nama_pelanggan" class="text-sm text-red-600 mt-1">{{ maintenanceForm.errors.nama_pelanggan }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">No Tlp</label>
            <Input v-model="maintenanceForm.no_tlp" />
            <div v-if="maintenanceForm.errors.no_tlp" class="text-sm text-red-600 mt-1">{{ maintenanceForm.errors.no_tlp }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Produk</label>
            <select v-model="maintenanceForm.product_id" class="h-9 rounded border px-2 w-full">
              <option value="">-- Pilih Produk --</option>
              <option v-for="p in props.products" :key="p.id" :value="p.id">{{ p.nama }}</option>
            </select>
            <div v-if="maintenanceForm.errors.product_id" class="text-sm text-red-600 mt-1">{{ maintenanceForm.errors.product_id }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Chat</label>
            <select v-model="maintenanceForm.chat" class="h-9 rounded border px-2 w-full">
              <option value="">-- Pilih Status Chat --</option>
              <option value="Baru">Baru</option>
              <option value="Follow Up">Follow Up</option>
              <option value="Follow Up 2">Follow Up 2</option>
              <option value="Followup 3">Followup 3</option>
            </select>
            <div v-if="maintenanceForm.errors.chat" class="text-sm text-red-600 mt-1">{{ maintenanceForm.errors.chat }}</div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Kota</label>
              <Input v-model="maintenanceForm.kota" />
              <div v-if="maintenanceForm.errors.kota" class="text-sm text-red-600 mt-1">{{ maintenanceForm.errors.kota }}</div>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Provinsi</label>
              <select v-model="maintenanceForm.provinsi" class="h-9 rounded border px-2 w-full">
                <option v-for="province in indonesianProvinces" :key="province" :value="province">{{ province }}</option>
              </select>
              <div v-if="maintenanceForm.errors.provinsi" class="text-sm text-red-600 mt-1">{{ maintenanceForm.errors.provinsi }}</div>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Kendala</label>
            <select v-model="maintenanceForm.kendala" class="h-9 rounded border px-2 w-full">
              <option value="">-- Pilih Kendala --</option>
              <option v-for="k in kendalaData" :key="k.label" :value="k.label">{{ k.label }}</option>
            </select>
            <div v-if="maintenanceForm.errors.kendala" class="text-sm text-red-600 mt-1">{{ maintenanceForm.errors.kendala }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Solusi</label>
            <select v-model="maintenanceForm.solusi" class="h-9 rounded border px-2 w-full">
              <option value="">-- Pilih Solusi --</option>
              <option v-for="s in solusiData" :key="s.label" :value="s.label">{{ s.label }}</option>
            </select>
            <div v-if="maintenanceForm.errors.solusi" class="text-sm text-red-600 mt-1">{{ maintenanceForm.errors.solusi }}</div>
          </div>
          <div class="flex justify-end gap-2">
            <Button variant="outline" @click="showMaintenance = false">Batal</Button>
            <Button :disabled="maintenanceForm.processing" @click="submitMaintenance">Simpan</Button>
          </div>
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
