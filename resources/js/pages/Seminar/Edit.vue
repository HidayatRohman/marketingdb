<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

const form = useForm({
  judul: props.seminar.judul || '',
  tanggal: props.seminar.tanggal || '',
  lokasi: props.seminar.lokasi || '',
  deskripsi: props.seminar.deskripsi || '',
});

const submit = () => {
  form.put(`/seminars/${props.seminar.id}`);
};

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Seminar', href: '/seminars' },
  { title: 'Edit', href: `/seminars/${props.seminar.id}/edit` },
];
</script>

<template>
  <Head :title="`Edit Seminar - ${props.seminar.judul}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-6 mt-6 space-y-6">
      <Card class="border-0 shadow-md">
        <CardHeader class="pb-2">
          <CardTitle class="flex items-center gap-2 text-lg font-semibold">
            <Calendar class="h-5 w-5" /> Edit Seminar
          </CardTitle>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div class="space-y-2">
                <Label for="judul">Judul Seminar *</Label>
                <Input id="judul" v-model="form.judul" placeholder="Judul Seminar" />
                <p v-if="form.errors.judul" class="text-sm text-destructive">{{ form.errors.judul }}</p>
              </div>

              <div class="space-y-2">
                <Label for="tanggal">Tanggal</Label>
                <Input id="tanggal" type="date" v-model="form.tanggal" />
                <p v-if="form.errors.tanggal" class="text-sm text-destructive">{{ form.errors.tanggal }}</p>
              </div>

              <div class="space-y-2">
                <Label for="lokasi">Lokasi</Label>
                <Input id="lokasi" v-model="form.lokasi" placeholder="Lokasi Seminar" />
                <p v-if="form.errors.lokasi" class="text-sm text-destructive">{{ form.errors.lokasi }}</p>
              </div>

              <div class="space-y-2 sm:col-span-2">
                <Label for="deskripsi">Deskripsi</Label>
                <textarea
                  id="deskripsi"
                  v-model="form.deskripsi"
                  class="flex h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                  placeholder="Deskripsi singkat seminar"
                />
                <p v-if="form.errors.deskripsi" class="text-sm text-destructive">{{ form.errors.deskripsi }}</p>
              </div>
            </div>

            <div class="flex justify-between">
              <Button as-child variant="outline">
                <Link href="/seminars" class="gap-2">
                  <ChevronLeft class="h-4 w-4" /> Kembali
                </Link>
              </Button>
              <Button type="submit" :disabled="form.processing">Update</Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>