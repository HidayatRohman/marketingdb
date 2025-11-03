<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'

const form = useForm<{ nama: string; logo: File | null }>({
  nama: '',
  logo: null,
})

const submit = () => {
  form.post('/products', {
    forceFormData: true,
  })
}
</script>

<template>
  <Head title="Tambah Produk" />
  <AppLayout>
    <Card>
      <CardHeader>
        <CardTitle>Tambah Produk</CardTitle>
      </CardHeader>
      <CardContent class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1">Nama</label>
          <Input v-model="form.nama" placeholder="Nama produk" />
          <div v-if="form.errors.nama" class="text-sm text-red-600 mt-1">{{ form.errors.nama }}</div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Logo (opsional)</label>
          <input type="file" accept="image/*" @change="(e:any)=>form.logo=e.target.files[0]" />
          <div v-if="form.errors.logo" class="text-sm text-red-600 mt-1">{{ form.errors.logo }}</div>
        </div>
        <div class="flex justify-end gap-2">
          <Button variant="outline" as-child>
            <a href="/products">Batal</a>
          </Button>
          <Button @click="submit">Simpan</Button>
        </div>
      </CardContent>
    </Card>
  </AppLayout>
  </template>