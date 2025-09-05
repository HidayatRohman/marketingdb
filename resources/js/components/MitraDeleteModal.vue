<script setup lang="ts">
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/vue3';
import { AlertTriangle, Loader2 } from 'lucide-vue-next';

interface Brand {
    id: number;
    nama: string;
    logo?: string;
    logo_url?: string;
}

interface Label {
    id: number;
    nama: string;
    warna: string;
}

interface Mitra {
    id: number;
    nama: string;
    no_telp: string;
    brand_id: number;
    brand?: Brand;
    label_id: number | null;
    label?: Label | null;
    chat: 'masuk' | 'followup';
    kota: string;
    provinsi: string;
    komentar: string | null;
}

interface Props {
    open: boolean;
    mitra?: Mitra;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
    success: [];
}>();

const form = useForm({});

const deleteMitra = () => {
    if (!props.mitra?.id) return;
    
    form.delete(`/mitras/${props.mitra.id}`, {
        onSuccess: () => {
            emit('success');
            emit('close');
        }
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="(value) => !value && $emit('close')">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-destructive">
                    <AlertTriangle class="h-5 w-5" />
                    Hapus Mitra
                </DialogTitle>
                <DialogDescription class="text-left">
                    Apakah Anda yakin ingin menghapus mitra <strong>{{ mitra?.nama }}</strong>?
                    <br><br>
                    <span class="text-destructive font-medium">
                        Tindakan ini tidak dapat dibatalkan.
                    </span>
                </DialogDescription>
            </DialogHeader>

            <div v-if="mitra" class="space-y-3 py-4 px-4 bg-muted/30 rounded-lg">
                <div class="space-y-1">
                    <p class="text-sm font-medium">Detail Mitra:</p>
                    <div class="text-sm text-muted-foreground space-y-1">
                        <p><span class="font-medium">Nama:</span> {{ mitra.nama }}</p>
                        <p><span class="font-medium">Telepon:</span> {{ mitra.no_telp }}</p>
                        <p><span class="font-medium">Brand:</span> {{ mitra.brand?.nama || 'N/A' }}</p>
                        <p><span class="font-medium">Lokasi:</span> {{ mitra.kota }}, {{ mitra.provinsi }}</p>
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button
                    type="button"
                    variant="outline"
                    @click="$emit('close')"
                    :disabled="form.processing"
                >
                    Batal
                </Button>
                <Button
                    variant="destructive"
                    @click="deleteMitra"
                    :disabled="form.processing"
                    class="min-w-[80px]"
                >
                    <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                    Hapus
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
