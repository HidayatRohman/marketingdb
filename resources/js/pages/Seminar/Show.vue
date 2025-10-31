<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Calendar, ChevronLeft } from 'lucide-vue-next';

interface SeminarItem {
  id: number;
  judul: string;
  tanggal?: string | null;
  lokasi?: string | null;
  deskripsi?: string | null;
}

interface Props { seminar: SeminarItem; permissions: any }
const props = defineProps<Props>();

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Seminar', href: '/seminars' },
  { title: 'Detail', href: `/seminars/${props.seminar.id}` },
];
</script>

<template>
  <Head :title="`Detail Seminar - ${props.seminar.judul}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-6 mt-6 space-y-6">
      <Card class="border-0 shadow-md">
        <CardHeader class="pb-2">
          <CardTitle class="flex items-center gap-2 text-lg font-semibold">
            <Calendar class="h-5 w-5" /> Detail Seminar
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-3">
            <div>
              <div class="text-sm text-muted-foreground">Judul</div>
              <div class="text-lg font-medium text-foreground">{{ props.seminar.judul }}</div>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <div class="text-sm text-muted-foreground">Tanggal</div>
                <div class="text-foreground">{{ props.seminar.tanggal || '-' }}</div>
              </div>
              <div>
                <div class="text-sm text-muted-foreground">Lokasi</div>
                <div class="text-foreground">{{ props.seminar.lokasi || '-' }}</div>
              </div>
            </div>
            <div>
              <div class="text-sm text-muted-foreground">Deskripsi</div>
              <div class="text-foreground">{{ props.seminar.deskripsi || '-' }}</div>
            </div>
          </div>
          <div class="mt-6 flex justify-between">
            <Button as-child variant="outline">
              <Link href="/seminars" class="gap-2">
                <ChevronLeft class="h-4 w-4" /> Kembali
              </Link>
            </Button>
            <Button as-child>
              <Link :href="`/seminars/${props.seminar.id}/edit`">Edit</Link>
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
  
</template>