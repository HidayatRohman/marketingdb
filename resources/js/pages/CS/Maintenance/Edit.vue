<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'

interface Item { id: number; nama_pelanggan: string; no_tlp: string; product?: { id: number; nama: string } | null; tanggal?: string; chat?: string; kota?: string; provinsi?: string; kendala?: string; solusi?: string }
const props = defineProps<{ item: Item; products: Array<{ id: number; nama: string }> }>()

const form = useForm({
  nama_pelanggan: props.item.nama_pelanggan,
  no_tlp: props.item.no_tlp,
  product_id: props.item.product?.id || '',
  tanggal: props.item.tanggal || '',
  chat: props.item.chat || '',
  kota: props.item.kota || '',
  provinsi: props.item.provinsi || '',
  kendala: props.item.kendala || '',
  solusi: props.item.solusi || '',
})

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
      <CardHeader>
        <CardTitle>Edit CS Maintenance</CardTitle>
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
            <Input v-model="form.provinsi" />
            <div v-if="form.errors.provinsi" class="text-sm text-red-600 mt-1">{{ form.errors.provinsi }}</div>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Kendala</label>
          <textarea v-model="form.kendala" class="w-full rounded border p-2" rows="3"></textarea>
          <div v-if="form.errors.kendala" class="text-sm text-red-600 mt-1">{{ form.errors.kendala }}</div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Solusi</label>
          <textarea v-model="form.solusi" class="w-full rounded border p-2" rows="3"></textarea>
          <div v-if="form.errors.solusi" class="text-sm text-red-600 mt-1">{{ form.errors.solusi }}</div>
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