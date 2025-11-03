<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { Edit, Plus, Trash2, Search, Repeat as RepeatIcon } from 'lucide-vue-next'
import { ref, watch } from 'vue'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'

interface Item {
  id: number
  nama_pelanggan: string
  no_tlp: string
  tanggal?: string
  kota: string | null
  provinsi: string | null
  transaksi: number
  product?: { id: number; nama: string } | null
}

interface Props {
  items: { data: Item[]; current_page: number; last_page: number; per_page: number; total: number; prev_page_url: string | null; next_page_url: string | null }
  products: Array<{ id: number; nama: string }>
  filters: { search?: string; product_id?: number | string }
  permissions: { canCrud: boolean }
}

const props = defineProps<Props>()
const items = props.items
const search = ref(props.filters.search || '')
const selectedProduct = ref(props.filters.product_id || '')

watch([search, selectedProduct], () => {
  router.get('/cs/repeats', { search: search.value, product_id: selectedProduct.value }, { preserveState: true, replace: true })
})

const showCreate = ref(false)
const createForm = useForm({
  tanggal: '',
  nama_pelanggan: '',
  no_tlp: '',
  product_id: '',
  chat: '',
  kota: '',
  provinsi: '',
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
const editUrl = (id: number) => `/cs/repeats/${id}/edit`
const deleteItem = (item: Item) => {
  if (!confirm('Yakin hapus data ini?')) return
  router.delete(`/cs/repeats/${item.id}`, { preserveScroll: true })
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

      <Card>
      <CardHeader class="flex items-center justify-between border-b border-indigo-100/50">
        <CardTitle>CS Repeat</CardTitle>
        <div class="flex items-center gap-2">
          <div class="relative">
            <Search class="absolute left-2 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
            <Input v-model="search" placeholder="Cari..." class="pl-8 w-56" />
          </div>
          <select v-model="selectedProduct" class="h-9 rounded border px-2">
            <option value="">Semua Produk</option>
            <option v-for="p in props.products" :key="p.id" :value="p.id">{{ p.nama }}</option>
          </select>
          <Button v-if="props.permissions.canCrud" @click="showCreate = true">
            <Plus class="h-4 w-4 mr-2" />Tambah
          </Button>
        </div>
      </CardHeader>
      <CardContent class="pt-4">
        <div class="overflow-x-auto hidden md:block">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Nama Pelanggan</TableHead>
                <TableHead>No Tlp</TableHead>
                <TableHead>Tanggal</TableHead>
                <TableHead>Produk</TableHead>
                <TableHead>Kota</TableHead>
                <TableHead>Provinsi</TableHead>
                <TableHead>Transaksi</TableHead>
                <TableHead class="text-center">Aksi</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in items.data" :key="item.id">
                <TableCell>{{ item.nama_pelanggan }}</TableCell>
                <TableCell>{{ item.no_tlp }}</TableCell>
                <TableCell>{{ formatDate(item.tanggal) }}</TableCell>
                <TableCell>{{ item.product?.nama || '-' }}</TableCell>
                <TableCell>{{ item.kota || '-' }}</TableCell>
                <TableCell>{{ item.provinsi || '-' }}</TableCell>
                <TableCell>{{ formatCurrency(item.transaksi || 0) }}</TableCell>
                <TableCell class="text-center">
                  <div class="flex justify-center gap-2" v-if="props.permissions.canCrud">
                    <Button variant="ghost" size="sm" as-child>
                      <a :href="editUrl(item.id)"><Edit class="h-4 w-4" /></a>
                    </Button>
                    <Button variant="ghost" size="sm" class="hover:text-red-600" @click="deleteItem(item)">
                      <Trash2 class="h-4 w-4" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <!-- Kartu responsif untuk mobile -->
        <div class="md:hidden space-y-3">
          <div v-for="item in items.data" :key="item.id" class="rounded-lg border border-indigo-100 bg-white/60 shadow-sm">
            <div class="p-3">
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-sm font-semibold text-indigo-700">{{ item.nama_pelanggan }}</div>
                  <div class="text-xs text-gray-500">{{ item.no_tlp }}</div>
                </div>
                <div class="text-xs text-indigo-600">{{ formatDate(item.tanggal) }}</div>
              </div>
              <div class="mt-2 grid grid-cols-2 gap-2 text-xs">
                <div>
                  <div class="text-gray-500">Produk</div>
                  <div class="text-gray-800">{{ item.product?.nama || '-' }}</div>
                </div>
                <div>
                  <div class="text-gray-500">Transaksi</div>
                  <div class="text-gray-800">{{ formatCurrency(item.transaksi || 0) }}</div>
                </div>
                <div>
                  <div class="text-gray-500">Kota</div>
                  <div class="text-gray-800">{{ item.kota || '-' }}</div>
                </div>
                <div>
                  <div class="text-gray-500">Provinsi</div>
                  <div class="text-gray-800">{{ item.provinsi || '-' }}</div>
                </div>
              </div>
              <div class="mt-3 flex justify-end gap-2" v-if="props.permissions.canCrud">
                <Button variant="outline" size="sm" as-child>
                  <a :href="editUrl(item.id)">Edit</a>
                </Button>
                <Button variant="ghost" size="sm" class="text-red-600" @click="deleteItem(item)">Hapus</Button>
              </div>
            </div>
          </div>
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
      <DialogContent class="sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Tambah CS Repeat</DialogTitle>
        </DialogHeader>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Tanggal</label>
            <Input v-model="createForm.tanggal" type="date" />
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
            <textarea v-model="createForm.chat" class="w-full rounded border p-2" rows="3"></textarea>
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
              <Input v-model="createForm.provinsi" />
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
      </DialogContent>
    </Dialog>

  </AppLayout>
  </template>