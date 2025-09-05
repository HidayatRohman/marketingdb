<script setup lang="ts">
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/vue3';
import { Loader2, AlertTriangle, Tag } from 'lucide-vue-next';

interface Label {
    id: number;
    nama: string;
    warna: string;
}

interface Props {
    open: boolean;
    label?: Label;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
    success: [];
}>();

const form = useForm({});

const deleteLabel = () => {
    if (!props.label) return;
    
    form.delete(`/labels/${props.label.id}`, {
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
                <DialogTitle class="flex items-center gap-2 text-xl text-destructive">
                    <AlertTriangle class="h-5 w-5" />
                    Hapus Label
                </DialogTitle>
                <DialogDescription>
                    Tindakan ini tidak dapat dibatalkan. Label akan dihapus secara permanen.
                </DialogDescription>
            </DialogHeader>

            <div v-if="label" class="space-y-4">
                <div class="bg-muted p-4 rounded-lg">
                    <p class="text-sm text-muted-foreground mb-2">Label yang akan dihapus:</p>
                    <div class="flex items-center gap-2">
                        <div 
                            class="w-4 h-4 rounded-full border-2 border-white shadow-sm" 
                            :style="{ backgroundColor: label.warna }"
                        ></div>
                        <span class="font-medium">{{ label.nama }}</span>
                        <span class="text-xs text-muted-foreground font-mono">{{ label.warna.toUpperCase() }}</span>
                    </div>
                </div>

                <div class="bg-destructive/10 border border-destructive/20 p-4 rounded-lg">
                    <div class="flex gap-3">
                        <AlertTriangle class="h-5 w-5 text-destructive mt-0.5 flex-shrink-0" />
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-destructive">Peringatan</p>
                            <p class="text-sm text-muted-foreground">
                                Jika label ini sedang digunakan oleh mitra, maka field label pada mitra tersebut akan dikosongkan.
                            </p>
                        </div>
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
                    @click="deleteLabel"
                    :disabled="form.processing"
                    class="gap-2"
                >
                    <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Hapus Label
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
