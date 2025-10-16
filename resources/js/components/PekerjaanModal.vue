<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { Loader2, Palette, Briefcase } from 'lucide-vue-next';
import { watch } from 'vue';

interface PekerjaanData {
    id?: number;
    nama: string;
    warna: string;
}

interface Props {
    open: boolean;
    mode: 'create' | 'edit' | 'view';
    pekerjaan?: PekerjaanData;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
    success: [];
}>();

const form = useForm({
    nama: '',
    warna: '#6B7280',
});

// Predefined colors
const predefinedColors = [
    '#10B981', // Green
    '#3B82F6', // Blue
    '#F59E0B', // Amber
    '#EF4444', // Red
    '#6B7280', // Gray
    '#8B5CF6', // Purple
    '#F97316', // Orange
    '#06B6D4', // Cyan
    '#84CC16', // Lime
    '#EC4899', // Pink
];

// Watch for pekerjaan prop changes
watch(
    () => props.pekerjaan,
    (newPekerjaan) => {
        if (newPekerjaan) {
            form.nama = newPekerjaan.nama || '';
            form.warna = newPekerjaan.warna || '#6B7280';
        } else {
            form.reset();
            form.warna = '#6B7280';
        }
    },
    { immediate: true },
);

// Reset form when modal closes
watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            form.clearErrors();
        }
    },
);

const submit = () => {
    if (props.mode === 'view') return;

    if (props.mode === 'create') {
        form.post('/pekerjaans', {
            onSuccess: () => {
                emit('success');
                emit('close');
            },
        });
    } else if (props.mode === 'edit' && props.pekerjaan?.id) {
        form.put(`/pekerjaans/${props.pekerjaan.id}`, {
            onSuccess: () => {
                emit('success');
                emit('close');
            },
        });
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="(value) => !value && $emit('close')">
        <DialogContent class="sm:max-w-[560px]">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-xl">
                    <Briefcase class="h-5 w-5" />
                    <span v-if="mode === 'create'">Tambah Pekerjaan Baru</span>
                    <span v-else-if="mode === 'edit'">Edit Pekerjaan</span>
                    <span v-else>Detail Pekerjaan</span>
                </DialogTitle>
                <DialogDescription>
                    <span v-if="mode === 'create'">Buat pekerjaan baru untuk kebutuhan kategorisasi atau referensi.</span>
                    <span v-else-if="mode === 'edit'">Perbarui informasi pekerjaan yang sudah ada.</span>
                    <span v-else>Informasi lengkap tentang pekerjaan ini.</span>
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-8">
                <!-- Nama Pekerjaan -->
                <div class="space-y-3">
                    <Label for="nama" class="flex items-center gap-2 text-sm font-medium">
                        <Briefcase class="h-4 w-4" />
                        Nama Pekerjaan *
                    </Label>
                    <Input
                        id="nama"
                        v-model="form.nama"
                        :disabled="mode === 'view'"
                        placeholder="Masukkan nama pekerjaan"
                        class="h-11"
                        :class="{ 'border-destructive': form.errors.nama }"
                    />
                    <p v-if="form.errors.nama" class="text-sm text-destructive">
                        {{ form.errors.nama }}
                    </p>
                </div>

                <!-- Warna Pekerjaan -->
                <div class="space-y-4">
                    <Label class="flex items-center gap-2 text-sm font-medium">
                        <Palette class="h-4 w-4" />
                        Warna Pekerjaan *
                    </Label>

                    <!-- Color Preview & Picker -->
                    <div class="flex items-center gap-4 rounded-lg border bg-gray-50 p-4 dark:bg-gray-800/50">
                        <div class="flex items-center gap-3">
                            <div
                                class="h-10 w-10 rounded-full border-2 border-gray-300 shadow-sm ring-2 ring-white dark:border-gray-600 dark:ring-gray-800"
                                :style="{ backgroundColor: form.warna }"
                            ></div>
                            <Input
                                v-model="form.warna"
                                :disabled="mode === 'view'"
                                type="color"
                                class="h-10 w-20 cursor-pointer p-1"
                                :class="{ 'border-destructive': form.errors.warna }"
                            />
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Kode Warna</p>
                            <p class="font-mono text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ form.warna.toUpperCase() }}
                            </p>
                        </div>
                    </div>

                    <!-- Predefined Colors -->
                    <div class="space-y-2">
                        <p class="text-sm text-muted-foreground">Pilih dari warna populer:</p>
                        <div class="grid grid-cols-5 gap-3">
                            <button
                                v-for="color in predefinedColors"
                                :key="color"
                                type="button"
                                class="group flex items-center gap-3 rounded-lg border bg-white p-3 transition hover:bg-gray-50 dark:bg-gray-900/30 dark:hover:bg-gray-900/50"
                                @click="form.warna = color"
                            >
                                <div
                                    class="h-6 w-6 rounded-full border-2 border-gray-300 shadow-sm ring-2 ring-white dark:border-gray-600 dark:ring-gray-800"
                                    :style="{ backgroundColor: color }"
                                ></div>
                                <div class="flex-1 text-left">
                                    <p class="text-sm font-medium">{{ color.toUpperCase() }}</p>
                                    <p class="text-xs text-muted-foreground">Klik untuk memilih</p>
                                </div>
                            </button>
                        </div>
                    </div>

                    <p v-if="form.errors.warna" class="text-sm text-destructive">
                        {{ form.errors.warna }}
                    </p>
                </div>

                <!-- Preview -->
                <div class="space-y-3">
                    <Label class="text-sm font-medium">Preview Pekerjaan:</Label>
                    <div class="rounded-lg border-2 border-dashed border-gray-200 bg-gray-50/50 p-6 dark:border-gray-700 dark:bg-gray-800/50">
                        <div class="flex justify-center">
                            <div
                                class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition-all"
                                :style="{
                                    backgroundColor: form.warna + '15',
                                    color: form.warna,
                                    border: `2px solid ${form.warna}30`,
                                }"
                            >
                                <div class="h-3 w-3 rounded-full" :style="{ backgroundColor: form.warna }"></div>
                                {{ form.nama || 'Nama Pekerjaan' }}
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <Button type="button" variant="outline" @click="$emit('close')">Batal</Button>
                    <Button
                        v-if="mode !== 'view'"
                        type="submit"
                        :disabled="form.processing"
                        class="gap-2"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <span>{{ mode === 'create' ? 'Simpan' : 'Update' }}</span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>