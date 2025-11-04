<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { Edit, Plus, Trash2, Search, Package as PackageIcon } from 'lucide-vue-next'
import { ref, watch, computed } from 'vue'

interface ProductItem {
  id: number
  nama: string
  logo: string | null
  logo_url: string | null
  created_at: string
}

interface Props {
  products: {
    data: ProductItem[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    prev_page_url: string | null
    next_page_url: string | null
  }
  filters: { search?: string }
  permissions: { canCrud: boolean }
}

const props = defineProps<Props>()
// Make products reactive to Inertia prop updates
const products = computed(() => props.products)
const search = ref(props.filters.search || '')

watch(search, (val) => {
  router.get('/products', { search: val }, { preserveState: true, replace: true })
})

const createUrl = '/products/create'
const editUrl = (id: number) => `/products/${id}/edit`
const deleteItem = (item: ProductItem) => {
  if (!confirm('Yakin hapus produk ini?')) return
  router.delete(`/products/${item.id}`, { preserveScroll: true })
}

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Produk', href: '/products' },
]
</script>

<template>
  <Head title="Produk" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-6 mt-6 space-y-6">
      <div class="relative overflow-hidden rounded-xl bg-gradient-to-r from-indigo-600 via-sky-600 to-cyan-600 p-4 text-white sm:p-6">
        <div class="relative z-10">
          <div class="flex flex-col space-y-4 lg:flex-row lg:items-center lg:justify-between lg:space-y-0">
            <div class="flex-1">
              <h1 class="mb-2 flex items-center gap-2 text-xl font-bold tracking-tight sm:gap-3 sm:text-2xl lg:text-3xl">
                <PackageIcon class="h-5 w-5 sm:h-6 sm:w-6 lg:h-8 lg:w-8" />
                Manajemen Produk
              </h1>
              <p class="text-sm opacity-90">Kelola data produk secara konsisten dan rapi.</p>
            </div>
            <div class="flex items-center gap-2">
              <Button v-if="props.permissions.canCrud" as-child variant="secondary" class="bg-white/20 hover:bg-white/30">
                <a :href="createUrl"><Plus class="h-4 w-4 mr-2" />Tambah</a>
              </Button>
            </div>
          </div>
        </div>
      </div>

      <Card>
      <CardHeader class="flex items-center justify-between">
        <CardTitle>Produk</CardTitle>
        <div class="flex items-center gap-2">
          <div class="relative">
            <Search class="absolute left-2 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
            <Input v-model="search" placeholder="Cari produk..." class="pl-8 w-56" />
          </div>
        </div>
      </CardHeader>
      <CardContent>
        <div class="overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Logo</TableHead>
                <TableHead>Nama</TableHead>
                <TableHead class="text-center">Aksi</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in products.data" :key="item.id">
                <TableCell>
                  <img v-if="item.logo_url" :src="item.logo_url" alt="Logo" class="h-8 w-8 rounded" />
                </TableCell>
                <TableCell>{{ item.nama }}</TableCell>
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

        <div class="mt-4 flex items-center justify-between text-sm text-muted-foreground">
          <div>Menampilkan {{ products.data.length }} dari {{ products.total }} produk</div>
          <div class="flex items-center gap-2">
            <Button variant="outline" size="sm" :disabled="!products.prev_page_url" @click="router.visit(products.prev_page_url || '#', { preserveState: true })">Prev</Button>
            <span>Hal {{ products.current_page }} / {{ products.last_page }}</span>
            <Button variant="outline" size="sm" :disabled="!products.next_page_url" @click="router.visit(products.next_page_url || '#', { preserveState: true })">Next</Button>
          </div>
        </div>
      </CardContent>
      </Card>
    </div>
  </AppLayout>
  </template>