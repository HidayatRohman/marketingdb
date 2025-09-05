<script setup lang="ts">
import { ref, watch } from 'vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { Loader2, Tag, Palette } from 'lucide-vue-next';

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
watch(() => props.label, (newLabel) => {
    if (newLabel) {
        form.nama = newLabel.nama || '';
        form.warna = newLabel.warna || '#6B7280';
    } else {
        form.reset();
        form.warna = '#6B7280';
    }
}, { immediate: true });

// Reset form when modal closes
watch(() => props.open, (isOpen) => {
    if (!isOpen) {
        form.reset();
        form.clearErrors();
        form.warna = '#6B7280';
    }
});

const submit = () => {
    if (props.mode === 'create') {
        form.post('/labels', {
            onSuccess: () => {
                emit('success');
                emit('close');
            }
        });
    } else if (props.mode === 'edit' && props.label?.id) {
        form.put(`/labels/${props.label.id}`, {
            onSuccess: () => {
                emit('success');
                emit('close');
            }
        });
    }
};

const selectColor = (color: string) => {
    form.warna = color;
};
</script>

<template>
    <Dialog :open="open" @update:open="(value) => !value && $emit('close')">
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-xl">
                    <Tag class="h-5 w-5" />
                    <span v-if="mode === 'create'">Tambah Label Baru</span>
                    <span v-else-if="mode === 'edit'">Edit Label</span>
                    <span v-else>Detail Label</span>
                </DialogTitle>
                <DialogDescription>
                    <span v-if="mode === 'create'">Buat label baru untuk kategorisasi mitra.</span>
                    <span v-else-if="mode === 'edit'">Perbarui informasi label yang sudah ada.</span>
                    <span v-else>Informasi lengkap label.</span>
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Nama Label -->
                <div class="space-y-2">
                    <Label for="nama" class="flex items-center gap-2">
                        <Tag class="h-3 w-3" />
                        Nama Label *
                    </Label>
                    <Input
                        id="nama"
                        v-model="form.nama"
                        :disabled="mode === 'view'"
                        placeholder="Masukkan nama label"
                        :class="{ 'border-destructive': form.errors.nama }"
                    />
                    <p v-if="form.errors.nama" class="text-sm text-destructive">
                        {{ form.errors.nama }}
                    </p>
                </div>

                <!-- Warna Label -->
                <div class="space-y-3">
                    <Label class="flex items-center gap-2">
                        <Palette class="h-3 w-3" />
                        Warna Label *
                    </Label>
                    
                    <!-- Color Preview -->
                    <div class="flex items-center gap-3">
                        <div 
                            class="w-8 h-8 rounded-full border-2 border-gray-300 shadow-sm" 
                            :style="{ backgroundColor: form.warna }"
                        ></div>
                        <Input
                            v-model="form.warna"
                            :disabled="mode === 'view'"
                            type="color"
                            class="w-16 h-8 p-1 cursor-pointer"
                            :class="{ 'border-destructive': form.errors.warna }"
                        />
                        <span class="text-sm text-muted-foreground font-mono">{{ form.warna.toUpperCase() }}</span>
                    </div>

                    <!-- Predefined Colors -->
                    <div v-if="mode !== 'view'" class="space-y-2">
                        <p class="text-sm text-muted-foreground">Atau pilih dari warna yang tersedia:</p>
                        <div class="grid grid-cols-10 gap-2">
                            <button
                                v-for="color in predefinedColors"
                                :key="color"
                                type="button"
                                @click="selectColor(color)"
                                class="w-8 h-8 rounded-full border-2 transition-all hover:scale-110"
                                :class="[
                                    form.warna === color 
                                        ? 'border-gray-900 ring-2 ring-gray-300' 
                                        : 'border-gray-300 hover:border-gray-400'
                                ]"
                                :style="{ backgroundColor: color }"
                                :title="color"
                            ></button>
                        </div>
                    </div>

                    <p v-if="form.errors.warna" class="text-sm text-destructive">
                        {{ form.errors.warna }}
                    </p>
                </div>

                <!-- Preview -->
                <div class="space-y-2">
                    <Label>Preview:</Label>
                    <div class="p-3 border rounded-lg bg-muted/50">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-medium"
                             :style="{ 
                                 backgroundColor: form.warna + '20', 
                                 color: form.warna,
                                 border: `1px solid ${form.warna}40`
                             }">
                            <div 
                                class="w-2 h-2 rounded-full" 
                                :style="{ backgroundColor: form.warna }"
                            ></div>
                            {{ form.nama || 'Nama Label' }}
                        </div>
                    </div>
                </div>
            </form>

            <DialogFooter v-if="mode !== 'view'">
                <Button type="button" variant="outline" @click="$emit('close')">
                    Batal
                </Button>
                <Button 
                    @click="submit" 
                    :disabled="form.processing"
                    class="gap-2"
                >
                    <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                    <span v-if="mode === 'create'">Tambah Label</span>
                    <span v-else>Perbarui Label</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
