<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { Loader2, Lightbulb, Palette } from 'lucide-vue-next';
import { watch } from 'vue';

interface SolusiData {
    id?: number;
    nama: string;
    warna: string;
}

interface Props {
    open: boolean;
    mode: 'create' | 'edit' | 'view';
    solusi?: SolusiData;
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

// Palet warna cepat
const predefinedColors = [
    '#EF4444', // Red
    '#F59E0B', // Amber
    '#F97316', // Orange
    '#10B981', // Green
    '#3B82F6', // Blue
    '#8B5CF6', // Purple
    '#06B6D4', // Cyan
    '#84CC16', // Lime
    '#EC4899', // Pink
    '#6B7280', // Gray
];

// Sinkronisasi data saat solusi berubah
watch(
    () => props.solusi,
    (newSolusi) => {
        if (newSolusi) {
            form.nama = newSolusi.nama || '';
            form.warna = newSolusi.warna || '#6B7280';
        } else {
            form.reset();
            form.warna = '#6B7280';
        }
    },
    { immediate: true },
);

// Reset saat modal ditutup
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
        form.post('/solusis', {
            onSuccess: () => {
                emit('success');
                emit('close');
            },
        });
    } else if (props.mode === 'edit' && props.solusi?.id) {
        form.put(`/solusis/${props.solusi.id}`, {
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
        <DialogContent class="sm:max-w-[525px]">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-xl">
                    <Lightbulb class="h-5 w-5" />
                    <span v-if="mode === 'create'">Tambah Solusi</span>
                    <span v-else-if="mode === 'edit'">Edit Solusi</span>
                    <span v-else>Detail Solusi</span>
                </DialogTitle>
                <DialogDescription>
                    Kelola daftar solusi dengan nama dan warna untuk kategorisasi.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Nama -->
                <div class="space-y-2">
                    <Label for="nama">Nama Solusi</Label>
                    <Input id="nama" v-model="form.nama" :disabled="mode === 'view'" placeholder="Masukkan nama solusi"
                           :class="{ 'border-red-500': form.errors.nama }" />
                    <p v-if="form.errors.nama" class="text-sm text-red-500">{{ form.errors.nama }}</p>
                </div>

                <!-- Warna -->
                <div class="space-y-2">
                    <Label for="warna" class="flex items-center gap-2">
                        <Palette class="h-4 w-4" />
                        Warna
                    </Label>
                    <div class="flex items-center gap-3">
                        <input id="warna" type="color" v-model="form.warna" :disabled="mode === 'view'" class="h-10 w-16 cursor-pointer rounded border" />
                        <div class="flex items-center gap-2 rounded-lg border p-2">
                            <div class="h-6 w-6 rounded-full border" :style="{ backgroundColor: form.warna }" />
                            <span class="text-sm text-muted-foreground">{{ form.warna.toUpperCase() }}</span>
                        </div>
                    </div>
                    <div v-if="mode !== 'view'" class="mt-3 grid grid-cols-10 gap-2">
                        <button
                            v-for="color in predefinedColors"
                            :key="color"
                            type="button"
                            class="h-6 w-6 rounded-full border"
                            :style="{ backgroundColor: color }"
                            @click="form.warna = color"
                        />
                    </div>
                    <p v-if="form.errors.warna" class="text-sm text-red-500">{{ form.errors.warna }}</p>
                </div>

                <DialogFooter v-if="mode !== 'view'" class="gap-3">
                    <Button type="button" variant="outline" @click="$emit('close')">Batal</Button>
                    <Button type="submit" :disabled="form.processing" class="gap-2">
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <span v-if="mode === 'create'">Tambah Solusi</span>
                        <span v-else>Perbarui Solusi</span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>