<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { indonesianProvinces } from '@/lib/indonesianProvinces'

const props = defineProps<{ products: Array<{ id: number; nama: string }> }>()

const getTodayYMD = () => {
  const now = new Date()
  const tzOffsetMs = now.getTimezoneOffset() * 60000
  return new Date(now.getTime() - tzOffsetMs).toISOString().slice(0, 10)
}

const form = useForm({
  nama_pelanggan: '',
  no_tlp: '',
  product_id: '',
  tanggal: getTodayYMD(),
  chat: '',
  kota: '',
  provinsi: 'Unknown',
  transaksi: 0,
  keterangan: '',
})

const transaksiFormatted = ref('0')
const formatRupiah = (amount: number): string => new Intl.NumberFormat('id-ID').format(amount)
const parseRupiah = (formatted: string): number => parseInt(formatted.replace(/[^0-9]/g, '')) || 0
const handleTransaksiInput = (e: Event) => {
  const val = (e.target as HTMLInputElement).value
  const num = parseRupiah(val)
  form.transaksi = num
  transaksiFormatted.value = formatRupiah(num)
}
watch(() => form.transaksi, (n) => {
  if (parseRupiah(transaksiFormatted.value) !== n) transaksiFormatted.value = formatRupiah(n)
})

const submit = () => {
  form.post('/cs/repeats')
}

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'CS Repeat', href: '/cs/repeats' },
  { title: 'Tambah', href: '/cs/repeats/create' },
]
</script>

<template>
  <Head title="Tambah CS Repeat" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-6 mt-6 space-y-6">
    <Card>
      <CardHeader>
        <CardTitle>Tambah CS Repeat</CardTitle>
      </CardHeader>
      <CardContent class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1">Tanggal</label>
          <Input v-model="form.tanggal" type="date" />
          <div v-if="form.errors.tanggal" class="text-sm text-red-600 mt-1">{{ form.errors.tanggal }}</div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Nama Pelanggan</label>
          <Input v-model="form.nama_pelanggan" />
          <div v-if="form.errors.nama_pelanggan" class="text-sm text-red-600 mt-1">{{ form.errors.nama_pelanggan }}</div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">No Tlp</label>
          <Input v-model="form.no_tlp" />
          <div v-if="form.errors.no_tlp" class="text-sm text-red-600 mt-1">{{ form.errors.no_tlp }}</div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Produk</label>
          <select v-model="form.product_id" class="h-9 rounded border px-2">
            <option value="">-- Pilih Produk --</option>
            <option v-for="p in props.products" :key="p.id" :value="p.id">{{ p.nama }}</option>
          </select>
          <div v-if="form.errors.product_id" class="text-sm text-red-600 mt-1">{{ form.errors.product_id }}</div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Chat</label>
          <select v-model="form.chat" class="h-9 rounded border px-2 w-full">
            <option value="">-- Pilih Status Chat --</option>
            <option value="Baru">Baru</option>
            <option value="Follow Up">Follow Up</option>
            <option value="Follow Up 2">Follow Up 2</option>
            <option value="Followup 3">Followup 3</option>
          </select>
          <div v-if="form.errors.chat" class="text-sm text-red-600 mt-1">{{ form.errors.chat }}</div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Kota</label>
            <Input v-model="form.kota" />
            <div v-if="form.errors.kota" class="text-sm text-red-600 mt-1">{{ form.errors.kota }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Provinsi</label>
            <select v-model="form.provinsi" class="h-9 rounded border px-2 w-full">
              <option v-for="province in indonesianProvinces" :key="province" :value="province">{{ province }}</option>
            </select>
            <div v-if="form.errors.provinsi" class="text-sm text-red-600 mt-1">{{ form.errors.provinsi }}</div>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Transaksi (Rupiah)</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">Rp</span>
            <input type="text" class="pl-8 h-9 w-full rounded border px-2" :value="transaksiFormatted" @input="handleTransaksiInput" placeholder="0" />
          </div>
          <div v-if="form.errors.transaksi" class="text-sm text-red-600 mt-1">{{ form.errors.transaksi }}</div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Keterangan</label>
          <textarea v-model="form.keterangan" class="w-full rounded border p-2" rows="3"></textarea>
          <div v-if="form.errors.keterangan" class="text-sm text-red-600 mt-1">{{ form.errors.keterangan }}</div>
        </div>
        <div class="flex justify-end gap-2">
          <Button variant="outline" as-child>
            <a href="/cs/repeats">Batal</a>
          </Button>
          <Button @click="submit">Simpan</Button>
        </div>
      </CardContent>
    </Card>
    </div>
  </AppLayout>
  </template>