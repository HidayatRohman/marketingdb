<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { Loader2, Palette, Tag } from 'lucide-vue-next';
import { watch } from 'vue';

interface LabelData {
    id?: number;
    nama: string;
    warna: string;
}

interface Props {
    open: boolean;
    mode: 'create' | 'edit' | 'view';
    label?: LabelData;
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

// Watch for label prop changes
watch(
    () => props.label,
    (newLabel) => {
        if (newLabel) {
            form.nama = newLabel.nama || '';
            form.warna = newLabel.warna || '#6B7280';
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
            form.reset();
            form.clearErrors();
            form.warna = '#6B7280';
        }
    },
);

const submit = () => {
    if (props.mode === 'create') {
        form.post('/labels', {
            onSuccess: () => {
                emit('success');
                emit('close');
            },
        });
    } else if (props.mode === 'edit' && props.label?.id) {
        form.put(`/labels/${props.label.id}`, {
            onSuccess: () => {
                emit('success');
                emit('close');
            },
        });
    }
};

const selectColor = (color: string) => {
    form.warna = color;
};
</script>

<template>
    <Dialog :open="open" @update:open="(value) => !value && $emit('close')">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-[500px]">
            <DialogHeader class="space-y-3">
                <DialogTitle class="flex items-center gap-3 text-xl">
                    <div class="rounded-lg bg-primary/10 p-2">
                        <Tag class="h-5 w-5 text-primary" />
                    </div>
                    <span v-if="mode === 'create'">Tambah Label Baru</span>
                    <span v-else-if="mode === 'edit'">Edit Label</span>
                    <span v-else>Detail Label</span>
                </DialogTitle>
                <DialogDescription class="text-base">
                    <span v-if="mode === 'create'">Buat label baru untuk kategorisasi mitra dengan warna yang menarik.</span>
                    <span v-else-if="mode === 'edit'">Perbarui informasi label yang sudah ada.</span>
                    <span v-else>Informasi lengkap tentang label ini.</span>
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-8">
                <!-- Nama Label -->
                <div class="space-y-3">
                    <Label for="nama" class="flex items-center gap-2 text-sm font-medium">
                        <Tag class="h-4 w-4" />
                        Nama Label *
                    </Label>
                    <Input
                        id="nama"
                        v-model="form.nama"
                        :disabled="mode === 'view'"
                        placeholder="Masukkan nama label"
                        class="h-11"
                        :class="{ 'border-destructive': form.errors.nama }"
                    />
                    <p v-if="form.errors.nama" class="text-sm text-destructive">
                        {{ form.errors.nama }}
                    </p>
                </div>

                <!-- Warna Label -->
                <div class="space-y-4">
                    <Label class="flex items-center gap-2 text-sm font-medium">
                        <Palette class="h-4 w-4" />
                        Warna Label *
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
                    <div v-if="mode !== 'view'" class="space-y-3">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Atau pilih dari warna yang tersedia:</p>
                        <div class="grid grid-cols-5 gap-3">
                            <button
                                v-for="color in predefinedColors"
                                :key="color"
                                type="button"
                                @click="selectColor(color)"
                                class="group relative h-12 w-12 rounded-xl transition-all hover:scale-110 focus:scale-110 focus:outline-none"
                                :class="[
                                    form.warna === color
                                        ? 'ring-2 ring-primary ring-offset-2 ring-offset-white dark:ring-offset-gray-800'
                                        : 'ring-1 ring-gray-300 hover:ring-gray-400 dark:ring-gray-600',
                                ]"
                                :style="{ backgroundColor: color }"
                                :title="color"
                            >
                                <div v-if="form.warna === color" class="absolute inset-0 flex items-center justify-center">
                                    <div class="flex h-4 w-4 items-center justify-center rounded-full bg-white">
                                        <div class="h-2 w-2 rounded-full bg-gray-800"></div>
                                    </div>
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
                    <Label class="text-sm font-medium">Preview Label:</Label>
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
                                {{ form.nama || 'Nama Label' }}
                            </div>
                        </div>
                        <p class="mt-2 text-center text-xs text-gray-500 dark:text-gray-400">Begini tampilan label di halaman mitra</p>
                    </div>
                </div>
            </form>

            <DialogFooter v-if="mode !== 'view'" class="gap-3 pt-6">
                <Button type="button" variant="outline" @click="$emit('close')" class="px-6"> Batal </Button>
                <Button @click="submit" :disabled="form.processing" class="gap-2 px-6">
                    <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                    <span v-if="mode === 'create'">Tambah Label</span>
                    <span v-else>Perbarui Label</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
