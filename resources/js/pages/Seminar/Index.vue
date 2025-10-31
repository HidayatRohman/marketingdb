<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Table from '@/components/ui/table/Table.vue';
import TableBody from '@/components/ui/table/TableBody.vue';
import TableCell from '@/components/ui/table/TableCell.vue';
import TableHead from '@/components/ui/table/TableHead.vue';
import TableHeader from '@/components/ui/table/TableHeader.vue';
import TableRow from '@/components/ui/table/TableRow.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Calendar, Download } from 'lucide-vue-next';
import { ref } from 'vue';

interface Brand { id: number; nama: string }
interface Label { id: number; nama: string; warna: string }
interface User { id: number; name: string }

interface MitraParticipant {
  id: number;
  nama: string;
  no_telp: string;
  tanggal_lead: string;
  user_id: number | null;
  user?: User | null;
  brand_id: number;
  brand?: Brand | null;
  label_id?: number | null;
  label?: Label | null;
  chat: 'masuk' | 'followup' | 'followup_2' | 'followup_3';
  kota: string;
  provinsi: string;
  komentar?: string | null;
  webinar: 'Tidak' | 'Ikut';
}

interface SeminarItem {
  id: number;
  judul: string;
  tanggal?: string | null;
  lokasi?: string | null;
  deskripsi?: string | null;
}

interface Paginated<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  prev_page_url?: string | null;
  next_page_url?: string | null;
}

interface Props {
  seminars: Paginated<SeminarItem>;
  participants: Paginated<MitraParticipant>;
  permissions: any;
  filters?: {
    start_date?: string | null;
    end_date?: string | null;
  };
}

const props = defineProps<Props>();

const startDate = ref<string | null>(props.filters?.start_date ?? null);
const endDate = ref<string | null>(props.filters?.end_date ?? null);

const applyFilters = () => {
  const params: Record<string, string> = {};
  if (startDate.value) params.start_date = startDate.value;
  if (endDate.value) params.end_date = endDate.value;
  router.get('/seminars', params, { preserveState: true, replace: true });
};

const exportXlsx = () => {
  const url = new URL('/seminars/export', window.location.origin);
  if (startDate.value) url.searchParams.set('start_date', startDate.value);
  if (endDate.value) url.searchParams.set('end_date', endDate.value);
  window.open(url.toString(), '_blank');
};

const chatLabels: Record<string, string> = {
  masuk: 'Baru',
  followup: 'Follow Up',
  followup_2: 'Follow Up 2',
  followup_3: 'Follow Up 3',
};

// Breadcrumbs
const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Seminar', href: '/seminars' },
];
</script>

<template>
  <Head title="Seminar" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-6 mt-6 space-y-6">
      <!-- Header Section -->
      <div class="relative overflow-hidden rounded-xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 p-4 text-white sm:p-6">
        <div class="relative z-10">
          <div class="flex flex-col space-y-4 lg:flex-row lg:items-center lg:justify-between lg:space-y-0">
            <div class="flex-1">
              <h1 class="mb-2 flex items-center gap-2 text-xl font-bold tracking-tight sm:gap-3 sm:text-2xl lg:text-3xl">
                <Calendar class="h-5 w-5 sm:h-6 sm:w-6 lg:h-8 lg:w-8" />
                Manajemen Seminar
              </h1>
              <p class="text-sm opacity-90">Kelola seminar dan lihat daftar peserta dari Mitra (Webinar = Ikut).</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:gap-3">
              <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <div class="flex flex-col gap-1">
                  <Label for="startDate" class="text-white/90">Tanggal Awal</Label>
                  <Input id="startDate" type="date" v-model="startDate" class="bg-white/95 text-foreground" />
                </div>
                <div class="flex flex-col gap-1">
                  <Label for="endDate" class="text-white/90">Tanggal Akhir</Label>
                  <Input id="endDate" type="date" v-model="endDate" class="bg-white/95 text-foreground" />
                </div>
              </div>
              <div class="flex items-center gap-2">
                <Button variant="secondary" class="shadow-sm" @click="applyFilters">Terapkan Filter</Button>
                <Button variant="default" class="shadow-sm" @click="exportXlsx">
                  <Download class="mr-2 h-4 w-4" /> Export XLSX
                </Button>
              </div>
            </div>
          </div>
        </div>
        <!-- Decorative circles -->
        <div class="absolute -top-10 -right-10 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
        <div class="absolute -bottom-8 -left-8 h-28 w-28 rounded-full bg-white/10 blur-xl"></div>
      </div>

      <!-- Daftar Peserta -->
      <Card class="border-0 shadow-md">
        <CardHeader class="pb-2">
          <CardTitle class="text-lg font-semibold">Daftar Peserta</CardTitle>
        </CardHeader>
        <CardContent class="p-0">
          <div class="relative overflow-hidden">
            <div class="overflow-x-auto table-compact-mobile">
              <Table>
                <TableHeader>
                  <TableRow class="border-b border-border hover:bg-transparent">
                    <TableHead class="font-semibold text-foreground">Nama</TableHead>
                    <TableHead class="font-semibold text-foreground">Kontak</TableHead>
                    <TableHead class="font-semibold text-foreground">Tanggal Lead</TableHead>
                    <TableHead class="font-semibold text-foreground">Marketing</TableHead>
                    <TableHead class="font-semibold text-foreground">Brand</TableHead>
                    <TableHead class="font-semibold text-foreground">Chat</TableHead>
                    <TableHead class="font-semibold text-foreground">Lokasi</TableHead>
                    <TableHead class="font-semibold text-foreground">Label</TableHead>
                    <TableHead class="font-semibold text-foreground">Webinar</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <template v-if="props.participants.data.length">
                    <TableRow v-for="item in props.participants.data" :key="item.id" class="border-b border-border">
                      <TableCell class="p-3 text-foreground">
                        <div class="font-medium">{{ item.nama }}</div>
                        <div v-if="item.komentar" class="text-xs text-muted-foreground">{{ item.komentar }}</div>
                      </TableCell>
                      <TableCell class="p-3 text-muted-foreground">{{ item.no_telp }}</TableCell>
                      <TableCell class="p-3 text-muted-foreground">{{ item.tanggal_lead }}</TableCell>
                      <TableCell class="p-3 text-muted-foreground">{{ item.user?.name || '-' }}</TableCell>
                      <TableCell class="p-3 text-muted-foreground">{{ item.brand?.nama || '-' }}</TableCell>
                      <TableCell class="p-3">
                        <Badge variant="outline" class="px-2 py-1 text-xs">{{ chatLabels[item.chat] || item.chat }}</Badge>
                      </TableCell>
                      <TableCell class="p-3 text-muted-foreground">{{ item.kota }}, {{ item.provinsi }}</TableCell>
                      <TableCell class="p-3">
                        <Badge v-if="item.label" :style="{ backgroundColor: item.label.warna, color: '#fff' }" class="px-2 py-1 text-xs">
                          {{ item.label.nama }}
                        </Badge>
                        <span v-else class="text-muted-foreground">-</span>
                      </TableCell>
                      <TableCell class="p-3">
                        <Badge :variant="item.webinar === 'Ikut' ? 'default' : 'secondary'" class="px-2 py-1 text-xs">
                          {{ item.webinar }}
                        </Badge>
                      </TableCell>
                    </TableRow>
                  </template>
                  <template v-else>
                    <TableRow>
                      <TableCell :colspan="9" class="p-8 text-center text-muted-foreground">
                        Tidak ada peserta untuk periode yang dipilih. Silakan ubah rentang tanggal dan coba lagi.
                      </TableCell>
                    </TableRow>
                  </template>
                </TableBody>
              </Table>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>