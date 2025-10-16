<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useForm } from '@inertiajs/vue3';
import { AlertTriangle, Loader2 } from 'lucide-vue-next';

interface Pekerjaan {
    id: number;
    nama: string;
    warna: string;
}

interface Props {
    open: boolean;
    pekerjaan?: Pekerjaan;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
    success: [];
}>();

const form = useForm({});

const deletePekerjaan = () => {
    if (!props.pekerjaan) return;

    form.delete(`/pekerjaans/${props.pekerjaan.id}`, {
        onSuccess: () => {
            emit('success');
            emit('close');
        },
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="(value) => !value && $emit('close')">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-xl text-destructive">
                    <AlertTriangle class="h-5 w-5" />
                    Hapus Pekerjaan
                </DialogTitle>
                <DialogDescription> Tindakan ini tidak dapat dibatalkan. Pekerjaan akan dihapus secara permanen. </DialogDescription>
            </DialogHeader>

            <div v-if="pekerjaan" class="space-y-4">
                <div class="rounded-lg bg-muted p-4">
                    <p class="text-sm">Anda akan menghapus pekerjaan:</p>
                    <p class="text-lg font-semibold">{{ pekerjaan.nama }}</p>
                </div>
            </div>

            <DialogFooter class="gap-2">
                <Button type="button" variant="outline" @click="$emit('close')">Batal</Button>
                <Button variant="destructive" :disabled="form.processing" @click="deletePekerjaan" class="gap-2">
                    <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Hapus
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>