<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { router } from '@inertiajs/vue3';
import { AlertTriangle, Trash2, Zap } from 'lucide-vue-next';
import { ref } from 'vue';

interface Brand {
    id: number;
    nama: string;
    logo: string | null;
    logo_url: string | null;
    created_at: string;
    updated_at: string;
}

interface Props {
    open: boolean;
    brand?: Brand;
}

interface Emits {
    (e: 'close'): void;
    (e: 'success'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const isDeleting = ref(false);

const handleDelete = async () => {
    if (!props.brand) return;

    isDeleting.value = true;

    router.delete(`/brands/${props.brand.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('success');
            emit('close');
        },
        onError: () => {
            isDeleting.value = false;
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};

const handleClose = () => {
    if (!isDeleting.value) {
        emit('close');
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-red-600">
                    <div class="rounded-lg bg-red-100 p-2 dark:bg-red-900/30">
                        <AlertTriangle class="h-5 w-5 text-red-600 dark:text-red-400" />
                    </div>
                    Hapus Brand
                </DialogTitle>
                <DialogDescription> Tindakan ini tidak dapat dibatalkan. Brand akan dihapus secara permanen dari sistem. </DialogDescription>
            </DialogHeader>

            <div v-if="brand" class="py-4">
                <div class="flex items-center gap-4 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                    <div class="flex-shrink-0">
                        <div
                            v-if="brand.logo_url"
                            class="flex h-12 w-12 items-center justify-center rounded-lg border bg-white shadow-sm dark:bg-gray-800"
                        >
                            <img
                                :src="brand.logo_url"
                                :alt="brand.nama"
                                class="h-10 w-10 rounded object-contain"
                                @error="(e) => ((e.target as HTMLImageElement).style.display = 'none')"
                            />
                        </div>
                        <div
                            v-else
                            class="flex h-12 w-12 items-center justify-center rounded-lg bg-gradient-to-br from-violet-100 to-purple-100 dark:from-violet-900/30 dark:to-purple-900/30"
                        >
                            <Zap class="h-6 w-6 text-violet-600 dark:text-violet-400" />
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-semibold text-red-900 dark:text-red-100">{{ brand.nama }}</h3>
                        <p class="text-sm text-red-700 dark:text-red-300">
                            {{ brand.logo_url ? 'Dengan logo' : 'Tanpa logo' }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 text-sm text-muted-foreground">
                    <p>Brand yang akan dihapus:</p>
                    <ul class="mt-2 ml-4 list-disc space-y-1">
                        <li>
                            Nama: <strong>{{ brand.nama }}</strong>
                        </li>
                        <li>
                            Status logo: <strong>{{ brand.logo_url ? 'Ada' : 'Tidak ada' }}</strong>
                        </li>
                    </ul>
                </div>
            </div>

            <DialogFooter>
                <Button type="button" variant="outline" @click="handleClose" :disabled="isDeleting"> Batal </Button>
                <Button type="button" variant="destructive" @click="handleDelete" :disabled="isDeleting" class="bg-red-600 hover:bg-red-700">
                    <span v-if="isDeleting" class="flex items-center gap-2">
                        <div class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                        Menghapus...
                    </span>
                    <span v-else class="flex items-center gap-2">
                        <Trash2 class="h-4 w-4" />
                        Hapus Brand
                    </span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
