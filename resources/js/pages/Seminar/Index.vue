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
import ParticipantsMonthlyChart from '@/components/ParticipantsMonthlyChart.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Calendar, Download } from 'lucide-vue-next';
import { ref, computed, onMounted } from 'vue';

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
  participantsMonthly?: Array<{ year: number; month: number; label: string; count: number }>;
  permissions: any;
  filters?: {
    start_date?: string | null;
    end_date?: string | null;
  };
}

const props = defineProps<Props>();

const startDate = ref<string | null>(props.filters?.start_date ?? null);
const endDate = ref<string | null>(props.filters?.end_date ?? null);

// Nilai default input: awal dan akhir bulan berjalan saat filter kosong
const toISODate = (d: Date): string => {
  const yyyy = d.getFullYear();
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  return `${yyyy}-${mm}-${dd}`;
};

const getCurrentMonthRange = () => {
  const now = new Date();
  const first = new Date(now.getFullYear(), now.getMonth(), 1);
  const last = new Date(now.getFullYear(), now.getMonth() + 1, 0);
  return { start: toISODate(first), end: toISODate(last) };
};

onMounted(() => {
  const { start, end } = getCurrentMonthRange();
  if (!startDate.value) startDate.value = start;
  if (!endDate.value) endDate.value = end;
});

// Pastikan klik di kolom input memunculkan date picker
const openPicker = (e: Event) => {
  const el = e.target as HTMLInputElement | null;
  if (!el) return;
  // showPicker tersedia di Chromium modern
  const anyEl = el as any;
  if (typeof anyEl.showPicker === 'function') {
    anyEl.showPicker();
  } else {
    el.focus();
  }
};

const openStartPicker = () => {
  const el = document.getElementById('startDate') as HTMLInputElement | null;
  if (!el) return;
  const anyEl = el as any;
  if (typeof anyEl.showPicker === 'function') anyEl.showPicker(); else el.focus();
};

const openEndPicker = () => {
  const el = document.getElementById('endDate') as HTMLInputElement | null;
  if (!el) return;
  const anyEl = el as any;
  if (typeof anyEl.showPicker === 'function') anyEl.showPicker(); else el.focus();
};

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

// Teks periode untuk header kartu: gunakan tanggal terpilih,
// jika kosong, default ke rentang bulan berjalan
const parseDate = (dateStr: string | null): Date | null => {
  if (!dateStr) return null;
  const d = new Date(dateStr);
  return isNaN(d.getTime()) ? null : d;
};

const formatDMY = (d: Date): string => {
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(d);
};

const periodText = computed(() => {
  const s = parseDate(startDate.value);
  const e = parseDate(endDate.value);
  if (s && e) {
    return `${formatDMY(s)} – ${formatDMY(e)}`;
  }
  if (s && !e) {
    return `${formatDMY(s)} – sekarang`;
  }
  if (!s && e) {
    return `hingga ${formatDMY(e)}`;
  }
  const now = new Date();
  const first = new Date(now.getFullYear(), now.getMonth(), 1);
  const last = new Date(now.getFullYear(), now.getMonth() + 1, 0);
  return `${formatDMY(first)} – ${formatDMY(last)}`;
});

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
              <h1 class="mb-2 flex items-center gap-2 text-base font-bold tracking-tight sm:gap-3 sm:text-2xl lg:text-3xl">
                <Calendar class="h-5 w-5 sm:h-6 sm:w-6 lg:h-8 lg:w-8" />
                Manajemen Seminar
              </h1>
              <p class="text-xs opacity-90 sm:text-sm">Kelola seminar dan lihat daftar peserta dari Mitra (Periode: {{ periodText }})</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:gap-3">
              <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <div class="flex flex-col gap-1" @click="openStartPicker">
                  <Label for="startDate" class="text-white/90 text-xs sm:text-sm">Tanggal Awal</Label>
                  <div class="relative">
                    <Input id="startDate" type="date" v-model="startDate" class="bg-white/95 text-foreground cursor-pointer pr-9 date-input--no-native-icon text-[11px] sm:text-sm" @click.stop="openPicker" />
                    <Calendar class="absolute top-2 right-2 h-4 w-4 text-muted-foreground sm:hidden pointer-events-none" />
                  </div>
                </div>
                <div class="flex flex-col gap-1" @click="openEndPicker">
                  <Label for="endDate" class="text-white/90 text-xs sm:text-sm">Tanggal Akhir</Label>
                  <div class="relative">
                    <Input id="endDate" type="date" v-model="endDate" class="bg-white/95 text-foreground cursor-pointer pr-9 date-input--no-native-icon text-[11px] sm:text-sm" @click.stop="openPicker" />
                    <Calendar class="absolute top-2 right-2 h-4 w-4 text-muted-foreground sm:hidden pointer-events-none" />
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <Button variant="secondary" class="shadow-sm text-xs sm:text-sm" @click="applyFilters">Terapkan Filter</Button>
                <Button variant="default" class="shadow-sm text-xs sm:text-sm" @click="exportXlsx">
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
        <CardHeader class="pb-2 bg-gray-50 dark:bg-gray-800/70 rounded-md px-3 py-2">
          <CardTitle class="text-lg font-semibold text-gray-900 dark:text-white">Daftar Peserta</CardTitle>
        </CardHeader>
        <CardContent class="p-0">
          <div class="relative overflow-hidden">
            <div class="overflow-x-auto table-compact-mobile">
              <Table>
                <TableHeader>
                  <TableRow class="border-b border-border hover:bg-transparent">
                    <TableHead class="font-semibold text-foreground sticky left-0 bg-background z-30 w-28 border-r border-border text-[11px] sm:static sm:left-auto sm:w-auto sm:text-sm">Nama</TableHead>
                    <TableHead class="font-semibold text-foreground w-28 px-2 py-2 sm:w-auto sm:px-3 sm:py-3"><span class="text-[11px] sm:text-sm">Kontak</span></TableHead>
                    <TableHead class="font-semibold text-foreground w-28 px-2 py-2 sm:w-auto sm:px-3 sm:py-3"><span class="text-[11px] sm:text-sm">Tanggal Lead</span></TableHead>
                    <TableHead class="font-semibold text-foreground w-28 px-2 py-2 sm:w-auto sm:px-3 sm:py-3"><span class="text-[11px] sm:text-sm">Marketing</span></TableHead>
                    <TableHead class="font-semibold text-foreground w-24 px-2 py-2 sm:w-auto sm:px-3 sm:py-3"><span class="text-[11px] sm:text-sm">Brand</span></TableHead>
                    <TableHead class="font-semibold text-foreground w-20 px-2 py-2 sm:w-auto sm:px-3 sm:py-3"><span class="text-[11px] sm:text-sm">Chat</span></TableHead>
                    <TableHead class="font-semibold text-foreground w-32 px-2 py-2 sm:w-auto sm:px-3 sm:py-3"><span class="text-[11px] sm:text-sm">Lokasi</span></TableHead>
                    <TableHead class="font-semibold text-foreground w-24 px-2 py-2 sm:w-auto sm:px-3 sm:py-3"><span class="text-[11px] sm:text-sm">Label</span></TableHead>
                    <TableHead class="font-semibold text-foreground w-24 px-2 py-2 sm:w-auto sm:px-3 sm:py-3"><span class="text-[11px] sm:text-sm">Webinar</span></TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <template v-if="props.participants.data.length">
                    <TableRow v-for="item in props.participants.data" :key="item.id" class="border-b border-border">
                      <TableCell class="px-2 py-2 sm:p-3 text-foreground sticky left-0 bg-background z-20 w-28 border-r border-border sm:static sm:left-auto sm:w-auto">
                        <div class="font-medium truncate text-[11px] leading-tight max-w-[100px] sm:text-sm sm:leading-normal sm:max-w-none">{{ item.nama }}</div>
                        <div v-if="item.komentar" class="truncate text-[10px] text-muted-foreground sm:text-xs">{{ item.komentar }}</div>
                      </TableCell>
                      <TableCell class="w-28 px-2 py-2 text-muted-foreground truncate sm:w-auto sm:p-3">
                        <span class="text-[11px] sm:text-sm truncate">{{ item.no_telp }}</span>
                      </TableCell>
                      <TableCell class="w-28 px-2 py-2 text-muted-foreground sm:w-auto sm:p-3">
                        <span class="text-[11px] sm:text-sm whitespace-nowrap">{{ item.tanggal_lead }}</span>
                      </TableCell>
                      <TableCell class="w-28 px-2 py-2 text-muted-foreground sm:w-auto sm:p-3">
                        <div class="text-[11px] sm:text-sm truncate max-w-[120px] sm:max-w-none">{{ item.user?.name || '-' }}</div>
                      </TableCell>
                      <TableCell class="w-24 px-2 py-2 text-muted-foreground sm:w-auto sm:p-3">
                        <div class="text-[11px] sm:text-sm truncate max-w-[110px] sm:max-w-none">{{ item.brand?.nama || '-' }}</div>
                      </TableCell>
                      <TableCell class="w-20 px-2 py-2 sm:w-auto sm:p-3">
                        <Badge variant="outline" class="px-2 py-1 text-[11px] sm:text-sm">{{ chatLabels[item.chat] || item.chat }}</Badge>
                      </TableCell>
                      <TableCell class="w-32 px-2 py-2 text-muted-foreground sm:w-auto sm:p-3">
                        <div class="text-[11px] sm:text-sm truncate max-w-[140px] sm:max-w-none">{{ item.kota }}, {{ item.provinsi }}</div>
                      </TableCell>
                      <TableCell class="w-24 px-2 py-2 sm:w-auto sm:p-3">
                        <Badge v-if="item.label" :style="{ backgroundColor: item.label.warna, color: '#fff' }" class="px-2 py-1 text-[11px] sm:text-sm">
                          {{ item.label.nama }}
                        </Badge>
                        <span v-else class="text-muted-foreground text-[11px] sm:text-sm">-</span>
                      </TableCell>
                      <TableCell class="w-24 px-2 py-2 sm:w-auto sm:p-3">
                        <Badge :variant="item.webinar === 'Ikut' ? 'default' : 'secondary'" class="px-2 py-1 text-[11px] sm:text-sm">
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

      <!-- Analisa Daftar Peserta (Grafik Garis 12 Bulan) -->
      <ParticipantsMonthlyChart :data="props.participantsMonthly || []" />
    </div>
  </AppLayout>
</template>
<style>
/* Sembunyikan ikon kalender native hanya di tampilan mobile untuk input filter tanggal */
@media (max-width: 640px) {
  input[type="date"].date-input--no-native-icon::-webkit-calendar-picker-indicator {
    display: none !important;
    -webkit-appearance: none;
    appearance: none;
    opacity: 0;
  }
}
</style>