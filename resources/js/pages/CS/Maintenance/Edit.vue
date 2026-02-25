<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { indonesianProvinces } from '@/lib/indonesianProvinces'
import { computed, ref } from 'vue'

interface Item { id: number; nama_pelanggan: string; no_tlp: string; product?: { id: number; nama: string } | null; tanggal?: string; chat?: string; kota?: string; provinsi?: string; kendala?: string; solusi?: string }
const props = defineProps<{ 
  item: Item; 
  products: Array<{ id: number; nama: string }>,
  kendalas: Array<{ id: number; nama: string; warna?: string }>,
  solusis: Array<{ id: number; nama: string; warna?: string }>,
}>()

const normalizeDateInput = (s?: string) => {
  if (!s) return ''
  // Support "YYYY-MM-DD", "YYYY-MM-DD HH:MM:SS", or ISO strings
  if (s.includes('T')) return s.split('T')[0]
  if (s.includes(' ')) return s.split(' ')[0]
  return s
}

const form = useForm({
  nama_pelanggan: props.item.nama_pelanggan,
  no_tlp: props.item.no_tlp,
  product_id: props.item.product?.id || '',
  tanggal: normalizeDateInput(props.item.tanggal) || '',
  chat: props.item.chat || '',
  kota: props.item.kota || '',
  provinsi: props.item.provinsi || '',
  kendala: props.item.kendala || '',
  solusi: props.item.solusi || '',
})

const kendalaSearch = ref('')
const solusiSearch = ref('')

const inertiaPage = usePage() as any
const inertiaVersion = inertiaPage?.version || ''

const kendalaList = ref<Array<{ id:number; nama:string }>>(props.kendalas || [])
const solusiList = ref<Array<{ id:number; nama:string }>>(props.solusis || [])

const filteredKendalas = computed(() => {
  const q = kendalaSearch.value.toLowerCase()
  return (kendalaList.value || []).filter(k => (k?.nama || '').toLowerCase().includes(q))
})

const filteredSolusis = computed(() => {
  const q = solusiSearch.value.toLowerCase()
  return (solusiList.value || []).filter(s => (s?.nama || '').toLowerCase().includes(q))
})

const fetchKendalasIfNeeded = async () => {
  if (kendalaList.value && kendalaList.value.length > 0) return
  try {
    const res = await fetch(`/kendalas`, {
      headers: {
        'X-Inertia': 'true',
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'X-Inertia-Partial-Component': 'Kendalas/Index',
        'X-Inertia-Partial-Data': 'kendalas',
        ...(inertiaVersion ? { 'X-Inertia-Version': inertiaVersion } : {}),
      },
    })
    if (!res.ok) return
    const page = await res.json()
    const list = page?.props?.kendalas ?? page?.kendalas ?? []
    kendalaList.value = Array.isArray(list) ? list.map((k: any) => ({ id: Number(k?.id || 0), nama: String(k?.nama || '') })) : []
  } catch {}
}

const fetchSolusisIfNeeded = async () => {
  if (solusiList.value && solusiList.value.length > 0) return
  try {
    const res = await fetch(`/solusis`, {
      headers: {
        'X-Inertia': 'true',
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'X-Inertia-Partial-Component': 'Solusis/Index',
        'X-Inertia-Partial-Data': 'solusis',
        ...(inertiaVersion ? { 'X-Inertia-Version': inertiaVersion } : {}),
      },
    })
    if (!res.ok) return
    const page = await res.json()
    const list = page?.props?.solusis ?? page?.solusis ?? []
    solusiList.value = Array.isArray(list) ? list.map((s: any) => ({ id: Number(s?.id || 0), nama: String(s?.nama || '') })) : []
  } catch {}
}

fetchKendalasIfNeeded()
fetchSolusisIfNeeded()

const submit = () => {
  form.put(`/cs/maintenances/${props.item.id}`)
}

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'CS Maintenance', href: '/cs/maintenances' },
  { title: 'Edit', href: `/cs/maintenances/${props.item.id}/edit` },
]
</script>

<template>
  <Head title="Edit CS Maintenance" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-6 mt-6 space-y-6">
    <Card>
      <CardHeader class="border-b border-indigo-100/50 dark:border-indigo-900/50 bg-gradient-to-r from-indigo-50 via-sky-50 to-cyan-50 dark:from-indigo-950/50 dark:via-sky-950/50 dark:to-cyan-950/50">
        <CardTitle class="dark:text-indigo-100">Edit CS Maintenance</CardTitle>
      </CardHeader>
      <CardContent class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1 dark:text-gray-200">Tanggal</label>
          <Input v-model="form.tanggal" type="date" />
          <div v-if="form.errors.tanggal" class="text-sm text-red-600 dark:text-red-400 mt-1">{{ form.errors.tanggal }}</div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1 dark:text-gray-200">Nama Pelanggan</label>
          <Input v-model="form.nama_pelanggan" />
          <div v-if="form.errors.nama_pelanggan" class="text-sm text-red-600 dark:text-red-400 mt-1">{{ form.errors.nama_pelanggan }}</div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1 dark:text-gray-200">No Tlp</label>
          <Input v-model="form.no_tlp" />
          <div v-if="form.errors.no_tlp" class="text-sm text-red-600 dark:text-red-400 mt-1">{{ form.errors.no_tlp }}</div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1 dark:text-gray-200">Produk</label>
          <select v-model="form.product_id" class="h-9 rounded border px-2 w-full bg-background dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
            <option value="" class="dark:bg-gray-800">-- Pilih Produk --</option>
            <option v-for="p in props.products" :key="p.id" :value="p.id" class="dark:bg-gray-800">{{ p.nama }}</option>
          </select>
          <div v-if="form.errors.product_id" class="text-sm text-red-600 dark:text-red-400 mt-1">{{ form.errors.product_id }}</div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1 dark:text-gray-200">Chat</label>
          <select v-model="form.chat" class="h-9 rounded border px-2 w-full bg-background dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
            <option value="" class="dark:bg-gray-800">-- Pilih Status Chat --</option>
            <option value="Baru" class="dark:bg-gray-800">Baru</option>
            <option value="Follow Up" class="dark:bg-gray-800">Follow Up</option>
            <option value="Follow Up 2" class="dark:bg-gray-800">Follow Up 2</option>
            <option value="Followup 3" class="dark:bg-gray-800">Followup 3</option>
          </select>
          <div v-if="form.errors.chat" class="text-sm text-red-600 dark:text-red-400 mt-1">{{ form.errors.chat }}</div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1 dark:text-gray-200">Kota</label>
            <Input v-model="form.kota" />
            <div v-if="form.errors.kota" class="text-sm text-red-600 dark:text-red-400 mt-1">{{ form.errors.kota }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1 dark:text-gray-200">Provinsi</label>
            <select v-model="form.provinsi" class="h-9 rounded border px-2 w-full bg-background dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
              <option v-for="province in indonesianProvinces" :key="province" :value="province" class="dark:bg-gray-800">{{ province }}</option>
            </select>
            <div v-if="form.errors.provinsi" class="text-sm text-red-600 dark:text-red-400 mt-1">{{ form.errors.provinsi }}</div>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1 dark:text-gray-200">Kendala</label>
          <select v-model="form.kendala" class="h-9 rounded border px-2 w-full bg-background dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
            <option value="" class="dark:bg-gray-800">-- Pilih Kendala --</option>
            <option v-for="k in filteredKendalas" :key="k.id" :value="k.nama" class="dark:bg-gray-800">{{ k.nama }}</option>
          </select>
          <div class="mt-2">
            <Input v-model="kendalaSearch" placeholder="Cari kendala..." />
          </div>
          <div v-if="form.errors.kendala" class="text-sm text-red-600 dark:text-red-400 mt-1">{{ form.errors.kendala }}</div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1 dark:text-gray-200">Solusi</label>
          <select v-model="form.solusi" class="h-9 rounded border px-2 w-full bg-background dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
            <option value="" class="dark:bg-gray-800">-- Pilih Solusi --</option>
            <option v-for="s in filteredSolusis" :key="s.id" :value="s.nama" class="dark:bg-gray-800">{{ s.nama }}</option>
          </select>
          <div class="mt-2">
            <Input v-model="solusiSearch" placeholder="Cari solusi..." />
          </div>
          <div v-if="form.errors.solusi" class="text-sm text-red-600 dark:text-red-400 mt-1">{{ form.errors.solusi }}</div>
        </div>
        <div class="flex justify-end gap-2">
          <Button variant="outline" as-child>
            <a href="/cs/maintenances">Batal</a>
          </Button>
          <Button @click="submit">Update</Button>
        </div>
      </CardContent>
    </Card>
    </div>
  </AppLayout>
  </template>
