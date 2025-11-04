<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { Edit, Plus, Trash2, Search, Repeat as RepeatIcon, Eye } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogScrollContent } from '@/components/ui/dialog'
import CsRepeatDailyTransaksiChart from '@/components/CsRepeatDailyTransaksiChart.vue'
import CsRepeatDailyProductChart from '@/components/CsRepeatDailyProductChart.vue'
import { indonesianProvinces } from '@/lib/indonesianProvinces'

interface Item {
  id: number
  nama_pelanggan: string
  no_tlp: string
  tanggal?: string
  chat?: string | null
  kota: string | null
  provinsi: string | null
  transaksi: number
  keterangan?: string
  product?: { id: number; nama: string } | null
}

interface Props {
  items: { data: Item[]; current_page: number; last_page: number; per_page: number; total: number; prev_page_url: string | null; next_page_url: string | null }
  products: Array<{ id: number; nama: string }>
  filters: { search?: string; product_id?: number | string; periode_start?: string; periode_end?: string }
  permissions: { canCrud: boolean }
  charts: { dailyTransaksi: Array<{ date: string; total: number }>; dailyByProduct: Array<{ date: string; products: Record<string, number>; total: number }> }
  summary: { totalOmset: number; jumlahTransaksi: number }
}

const props = defineProps<Props>()
const items = computed(() => props.items)
const search = ref(props.filters.search || '')
const selectedProduct = ref(props.filters.product_id || '')
const periodeStart = ref(props.filters.periode_start || '')
const periodeEnd = ref(props.filters.periode_end || '')

// Ensure clicking the date field opens the native date picker
const periodeStartRef = ref<HTMLInputElement | null>(null)
const periodeEndRef = ref<HTMLInputElement | null>(null)
const openDatePicker = (el: HTMLInputElement | null) => {
  if (!el) return
  el.focus()
  // Chromium-based browsers support showPicker for date inputs
  // Fallback: focusing still places cursor for manual entry
  ;(el as any).showPicker?.()
}

// Normalize various date formats to YYYY-MM-DD for HTML date inputs
const toYMD = (input?: string | null): string => {
  if (!input) return ''
  const raw = String(input)
  if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) return raw
  const d = new Date(raw)
  if (isNaN(d.getTime())) return ''
  // Convert to local date without time zone offset
  const tzOffsetMs = d.getTimezoneOffset() * 60000
  const local = new Date(d.getTime() - tzOffsetMs)
  return local.toISOString().slice(0, 10)
}

watch([search, selectedProduct, periodeStart, periodeEnd], () => {
  router.get(
    '/cs/repeats',
    {
      search: search.value || undefined,
      product_id: selectedProduct.value || undefined,
      periode_start: periodeStart.value || undefined,
      periode_end: periodeEnd.value || undefined,
    },
    { preserveState: true, replace: true }
  )
})

const showCreate = ref(false)
const createDateRef = ref<HTMLInputElement | null>(null)
const createForm = useForm({
  tanggal: '',
  nama_pelanggan: '',
  no_tlp: '',
  product_id: '',
  chat: '',
  kota: '',
  provinsi: 'Unknown',
  transaksi: 0,
  keterangan: '',
})
const transaksiFormatted = ref(new Intl.NumberFormat('id-ID').format(createForm.transaksi))
const parseRupiah = (formatted: string): number => parseInt(formatted.replace(/[^0-9]/g, '')) || 0
const handleTransaksiInput = (e: Event) => {
  const val = (e.target as HTMLInputElement).value
  const num = parseRupiah(val)
  createForm.transaksi = num
  transaksiFormatted.value = new Intl.NumberFormat('id-ID').format(num)
}
watch(() => createForm.transaksi, (n) => {
  const current = parseRupiah(transaksiFormatted.value)
  if (current !== n) transaksiFormatted.value = new Intl.NumberFormat('id-ID').format(n)
})
const submitCreate = () => {
  createForm.post('/cs/repeats', {
    preserveScroll: true,
    onSuccess: () => {
      showCreate.value = false
      createForm.reset()
      transaksiFormatted.value = '0'
    },
  })
}
const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'CS Repeat', href: '/cs/repeats' },
]
// Edit modal state and form
const showEdit = ref(false)
const editDateRef = ref<HTMLInputElement | null>(null)
const editForm = useForm({
  id: 0,
  tanggal: '',
  nama_pelanggan: '',
  no_tlp: '',
  product_id: '',
  chat: '',
  kota: '',
  provinsi: 'Unknown',
  transaksi: 0,
  keterangan: '',
})
const editTransaksiFormatted = ref(new Intl.NumberFormat('id-ID').format(editForm.transaksi))
const handleEditTransaksiInput = (e: Event) => {
  const val = (e.target as HTMLInputElement).value
  const num = parseRupiah(val)
  editForm.transaksi = num
  editTransaksiFormatted.value = new Intl.NumberFormat('id-ID').format(num)
}
watch(() => editForm.transaksi, (n) => {
  const current = parseRupiah(editTransaksiFormatted.value)
  if (current !== n) editTransaksiFormatted.value = new Intl.NumberFormat('id-ID').format(n)
})
const openEdit = (item: Item) => {
  editForm.id = item.id
  editForm.tanggal = toYMD(item.tanggal) || ''
  editForm.nama_pelanggan = item.nama_pelanggan || ''
  editForm.no_tlp = item.no_tlp || ''
  editForm.product_id = item.product?.id ? String(item.product.id) : ''
  editForm.chat = item.chat || ''
  editForm.kota = item.kota || ''
  editForm.provinsi = item.provinsi || 'Unknown'
  editForm.transaksi = item.transaksi || 0
  editTransaksiFormatted.value = new Intl.NumberFormat('id-ID').format(editForm.transaksi)
  editForm.keterangan = item.keterangan || ''
  showEdit.value = true
}
const submitEdit = () => {
  editForm.put(`/cs/repeats/${editForm.id}` as any, {
    preserveScroll: true,
    onSuccess: () => {
      showEdit.value = false
    },
  })
}
const editUrl = (id: number) => `/cs/repeats/${id}/edit`
const deleteItem = (item: Item) => {
  if (!confirm('Yakin hapus data ini?')) return
  router.delete(`/cs/repeats/${item.id}`, { preserveScroll: true })
}

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

const formatCurrency = (amount: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(amount)
const formatDate = (d?: string) => (d ? new Date(d).toLocaleDateString('id-ID') : '-')
</script>

<template>
  <Head title="CS Repeat" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-6 mt-6 space-y-6">
      <!-- Header Section (match Mitra style) -->
      <div class="relative overflow-hidden rounded-xl bg-gradient-to-r from-indigo-600 via-sky-600 to-cyan-600 p-4 text-white sm:p-6">
        <div class="relative z-10">
          <div class="flex flex-col space-y-4 lg:flex-row lg:items-center lg:justify-between lg:space-y-0">
            <div class="flex-1">
              <h1 class="mb-2 flex items-center gap-2 text-xl font-bold tracking-tight sm:gap-3 sm:text-2xl lg:text-3xl">
                <RepeatIcon class="h-5 w-5 sm:h-6 sm:w-6 lg:h-8 lg:w-8" />
                Manajemen CS Repeat
              </h1>
              <p class="text-sm opacity-90">Kelola data CS Repeat dengan mudah dan cepat.</p>
            </div>
            <div class="flex items-center gap-2">
              <Button v-if="props.permissions.canCrud" variant="secondary" @click="showCreate = true" class="bg-white/20 hover:bg-white/30">
                <Plus class="h-4 w-4 mr-2" />Tambah
              </Button>
            </div>
          </div>
        </div>
      </div>

      <!-- Filter Bar (mobile-friendly) -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 rounded-lg border border-indigo-100 bg-white/70 p-3">
        <!-- Cari -->
        <div class="col-span-2 sm:col-span-1">
          <div class="relative">
            <Search class="absolute left-2 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
            <Input v-model="search" placeholder="Cari..." class="pl-8 w-full" />
          </div>
        </div>
        <!-- Periode -->
        <div class="col-span-2 sm:col-span-2 flex items-center gap-2">
          <label class="text-sm text-gray-600">Periode:</label>
          <input type="date" v-model="periodeStart" ref="periodeStartRef" class="h-9 w-full sm:w-40 rounded border px-2" @click="openDatePicker(periodeStartRef)" @focus="openDatePicker(periodeStartRef)" />
          <span class="text-gray-500 text-xs sm:text-base">s/d</span>
          <input type="date" v-model="periodeEnd" ref="periodeEndRef" class="h-9 w-full sm:w-40 rounded border px-2" @click="openDatePicker(periodeEndRef)" @focus="openDatePicker(periodeEndRef)" />
        </div>
        <!-- Produk -->
        <div class="col-span-2 sm:col-span-1">
          <select v-model="selectedProduct" class="h-9 w-full rounded border px-2">
            <option value="">Semua Produk</option>
            <option v-for="p in props.products" :key="p.id" :value="p.id">{{ p.nama }}</option>
          </select>
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="flex flex-nowrap gap-4 sm:grid sm:grid-cols-2">
        <Card class="border border-indigo-100 basis-[65%] sm:basis-auto sm:col-span-1">
          <CardHeader class="pb-2 bg-gradient-to-r from-indigo-50 to-blue-50">
            <CardTitle class="text-sm sm:text-base">Total Omset</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-xl sm:text-2xl font-bold text-indigo-700">
              {{ formatCurrency(props.summary?.totalOmset || 0) }}
            </div>
          </CardContent>
        </Card>
        <Card class="border border-indigo-100 basis-[35%] sm:basis-auto sm:col-span-1">
          <CardHeader class="pb-2 bg-gradient-to-r from-indigo-50 to-blue-50">
            <CardTitle class="text-sm sm:text-base">Jumlah Transaksi</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-xl sm:text-2xl font-bold text-indigo-700">
              {{ new Intl.NumberFormat('id-ID').format(props.summary?.jumlahTransaksi || 0) }}
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Charts Grid -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <CsRepeatDailyTransaksiChart :data="props.charts?.dailyTransaksi" />
        <CsRepeatDailyProductChart :data="props.charts?.dailyByProduct" />
      </div>

      <Card>
      <CardHeader class="flex items-center justify-between border-b border-indigo-100/50 bg-gradient-to-br from-indigo-50 via-sky-50 to-cyan-50">
        <CardTitle>Daftar Repeat Order</CardTitle>
        <div class="flex items-center gap-2">
          <Button v-if="props.permissions.canCrud" @click="showCreate = true">
            <Plus class="h-4 w-4 mr-2" />Tambah
          </Button>
        </div>
      </CardHeader>
      <CardContent class="pt-4">
        <div class="overflow-x-auto responsive-table">
          <Table class="min-w-[900px]">
            <TableHeader>
              <TableRow>
                <TableHead class="sticky left-0 z-30 bg-background min-w-[120px] sm:min-w-[200px] border-r border-border">
                  <span class="sm:hidden">Nama</span>
                  <span class="hidden sm:inline">Nama Pelanggan</span>
                </TableHead>
                <TableHead>No Tlp</TableHead>
                <TableHead>Tanggal</TableHead>
                <TableHead>Produk</TableHead>
                <TableHead>Kota</TableHead>
                <TableHead>Provinsi</TableHead>
                <TableHead>Transaksi</TableHead>
                <TableHead>Keterangan</TableHead>
                <TableHead class="text-center">Aksi</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in items.data" :key="item.id">
                <TableCell class="sticky left-0 z-20 bg-background p-2 sm:p-3 font-medium text-xs sm:text-base min-w-[120px] sm:min-w-[200px] border-r border-border">{{ item.nama_pelanggan }}</TableCell>
                <TableCell>{{ item.no_tlp }}</TableCell>
                <TableCell>{{ formatDate(item.tanggal) }}</TableCell>
                <TableCell>{{ item.product?.nama || '-' }}</TableCell>
                <TableCell>{{ item.kota || '-' }}</TableCell>
                <TableCell>{{ item.provinsi || '-' }}</TableCell>
                <TableCell>{{ formatCurrency(item.transaksi || 0) }}</TableCell>
                <TableCell>{{ item.keterangan || '-' }}</TableCell>
                <TableCell class="text-center">
                  <div class="flex justify-center gap-2">
                    <Button variant="ghost" size="sm" @click="openView(item)">
                      <Eye class="h-4 w-4" />
                    </Button>
                    <template v-if="props.permissions.canCrud">
                      <Button variant="ghost" size="sm" @click="openEdit(item)">
                        <Edit class="h-4 w-4" />
                      </Button>
                      <Button variant="ghost" size="sm" class="hover:text-red-600" @click="deleteItem(item)">
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    </template>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <div class="mt-4 flex items-center justify-between text-sm text-muted-foreground">
          <div>Menampilkan {{ items.data.length }} dari {{ items.total }} data</div>
          <div class="flex items-center gap-2">
            <Button variant="outline" size="sm" :disabled="!items.prev_page_url" @click="router.visit(items.prev_page_url || '#', { preserveState: true })">Prev</Button>
            <span>Hal {{ items.current_page }} / {{ items.last_page }}</span>
            <Button variant="outline" size="sm" :disabled="!items.next_page_url" @click="router.visit(items.next_page_url || '#', { preserveState: true })">Next</Button>
          </div>
        </div>
      </CardContent>
      </Card>
    </div>

    <!-- Dialog Create -->
    <Dialog :open="showCreate" @update:open="(v:boolean)=> showCreate = v">
      <DialogScrollContent class="sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Tambah CS Repeat</DialogTitle>
        </DialogHeader>
        <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1">Tanggal</label>
            <input
              ref="createDateRef"
              v-model="createForm.tanggal"
              type="date"
              class="h-9 rounded border px-2 w-full"
              @focus="openDatePicker(createDateRef)"
              @click="openDatePicker(createDateRef)"
            />
          <div v-if="createForm.errors.tanggal" class="text-sm text-red-600 mt-1">{{ createForm.errors.tanggal }}</div>
        </div>
          <div>
            <label class="block text-sm font-medium mb-1">Nama Pelanggan</label>
            <Input v-model="createForm.nama_pelanggan" />
            <div v-if="createForm.errors.nama_pelanggan" class="text-sm text-red-600 mt-1">{{ createForm.errors.nama_pelanggan }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">No Tlp</label>
            <Input v-model="createForm.no_tlp" />
            <div v-if="createForm.errors.no_tlp" class="text-sm text-red-600 mt-1">{{ createForm.errors.no_tlp }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Produk</label>
            <select v-model="createForm.product_id" class="h-9 rounded border px-2 w-full">
              <option value="">-- Pilih Produk --</option>
              <option v-for="p in props.products" :key="p.id" :value="p.id">{{ p.nama }}</option>
            </select>
            <div v-if="createForm.errors.product_id" class="text-sm text-red-600 mt-1">{{ createForm.errors.product_id }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Chat</label>
            <select v-model="createForm.chat" class="h-9 rounded border px-2 w-full">
              <option value="">-- Pilih Status Chat --</option>
              <option value="Baru">Baru</option>
              <option value="Follow Up">Follow Up</option>
              <option value="Follow Up 2">Follow Up 2</option>
              <option value="Followup 3">Followup 3</option>
            </select>
            <div v-if="createForm.errors.chat" class="text-sm text-red-600 mt-1">{{ createForm.errors.chat }}</div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Kota</label>
              <Input v-model="createForm.kota" />
              <div v-if="createForm.errors.kota" class="text-sm text-red-600 mt-1">{{ createForm.errors.kota }}</div>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Provinsi</label>
              <select v-model="createForm.provinsi" class="h-9 rounded border px-2 w-full">
                <option v-for="province in indonesianProvinces" :key="province" :value="province">{{ province }}</option>
              </select>
              <div v-if="createForm.errors.provinsi" class="text-sm text-red-600 mt-1">{{ createForm.errors.provinsi }}</div>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Transaksi (Rupiah)</label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">Rp</span>
              <input type="text" class="pl-8 h-9 w-full rounded border px-2" :value="transaksiFormatted" @input="handleTransaksiInput" placeholder="0" />
            </div>
            <div v-if="createForm.errors.transaksi" class="text-sm text-red-600 mt-1">{{ createForm.errors.transaksi }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Keterangan</label>
            <textarea v-model="createForm.keterangan" class="w-full rounded border p-2" rows="3"></textarea>
            <div v-if="createForm.errors.keterangan" class="text-sm text-red-600 mt-1">{{ createForm.errors.keterangan }}</div>
          </div>
          <div class="flex justify-end gap-2">
            <Button variant="outline" @click="showCreate = false">Batal</Button>
            <Button :disabled="createForm.processing" @click="submitCreate">Simpan</Button>
          </div>
        </div>
      </DialogScrollContent>
    </Dialog>

    <!-- Dialog View Detail -->
    <Dialog :open="showView" @update:open="(v:boolean)=> showView = v">
      <DialogScrollContent class="sm:max-w-md">
        <DialogHeader class="border-b border-indigo-100/50 bg-gradient-to-r from-indigo-50 via-sky-50 to-cyan-50 dark:from-indigo-900/40 dark:via-sky-900/30 dark:to-cyan-900/30 rounded-t-md -mx-6 -mt-6 px-6 py-3">
          <DialogTitle class="text-indigo-700 dark:text-indigo-200">Detail Repeat Order</DialogTitle>
        </DialogHeader>
        <div v-if="viewItem" class="space-y-3 text-sm">
          <div class="grid grid-cols-3 gap-2">
            <div class="text-gray-500">Nama</div>
            <div class="col-span-2 font-medium">{{ viewItem.nama_pelanggan }}</div>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <div class="text-gray-500">No Tlp</div>
            <div class="col-span-2">{{ viewItem.no_tlp }}</div>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <div class="text-gray-500">Tanggal</div>
            <div class="col-span-2">{{ formatDate(viewItem.tanggal) }}</div>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <div class="text-gray-500">Produk</div>
            <div class="col-span-2">{{ viewItem.product?.nama || '-' }}</div>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <div class="text-gray-500">Kota</div>
            <div class="col-span-2">{{ viewItem.kota || '-' }}</div>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <div class="text-gray-500">Provinsi</div>
            <div class="col-span-2">{{ viewItem.provinsi || '-' }}</div>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <div class="text-gray-500">Transaksi</div>
            <div class="col-span-2 font-semibold">{{ formatCurrency(viewItem.transaksi || 0) }}</div>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <div class="text-gray-500">Keterangan</div>
            <div class="col-span-2">{{ viewItem.keterangan || '-' }}</div>
          </div>
        </div>
        <div class="flex justify-end gap-2 mt-4">
          <Button variant="outline" @click="closeView">Tutup</Button>
          <Button v-if="viewItem" @click="openEdit(viewItem)">Edit</Button>
        </div>
      </DialogScrollContent>
    </Dialog>

    <!-- Dialog Edit -->
    <Dialog :open="showEdit" @update:open="(v:boolean)=> showEdit = v">
      <DialogScrollContent class="sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Edit CS Repeat</DialogTitle>
        </DialogHeader>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Tanggal</label>
            <input
              ref="editDateRef"
              v-model="editForm.tanggal"
              type="date"
              class="h-9 rounded border px-2 w-full"
              @click="openDatePicker(editDateRef)"
            />
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
            <label class="block text-sm font-medium mb-1">Transaksi (Rupiah)</label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">Rp</span>
              <input type="text" class="pl-8 h-9 w-full rounded border px-2" :value="editTransaksiFormatted" @input="handleEditTransaksiInput" placeholder="0" />
            </div>
            <div v-if="editForm.errors.transaksi" class="text-sm text-red-600 mt-1">{{ editForm.errors.transaksi }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Keterangan</label>
            <textarea v-model="editForm.keterangan" class="w-full rounded border p-2" rows="3"></textarea>
            <div v-if="editForm.errors.keterangan" class="text-sm text-red-600 mt-1">{{ editForm.errors.keterangan }}</div>
          </div>
          <div class="flex justify-end gap-2">
            <Button variant="outline" @click="showEdit = false">Batal</Button>
            <Button :disabled="editForm.processing" @click="submitEdit">Simpan</Button>
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

<!-- View Detail Modal moved inside main template -->