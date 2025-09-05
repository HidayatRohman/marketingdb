<script setup lang="ts">
import { ref } from 'vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/vue3';
import { Loader2, AlertTriangle, Trash2 } from 'lucide-vue-next';

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
}

interface Props {
    open: boolean;
    user?: User;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
    success: [];
}>();

const isDeleting = ref(false);

const deleteUser = async () => {
    if (!props.user) return;
    
    isDeleting.value = true;
    
    router.delete(`/users/${props.user.id}`, {
        onSuccess: () => {
            emit('success');
            emit('close');
        },
        onFinish: () => {
            isDeleting.value = false;
        }
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="(value) => !value && $emit('close')">
        <DialogContent class="sm:max-w-[400px]">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-xl text-red-600">
                    <AlertTriangle class="h-5 w-5" />
                    Konfirmasi Hapus
                </DialogTitle>
                <DialogDescription>
                    Aksi ini tidak dapat dibatalkan. User akan dihapus secara permanen.
                </DialogDescription>
            </DialogHeader>

            <div v-if="user" class="py-4">
                <div class="bg-red-50 dark:bg-red-950/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
                    <div class="flex items-start gap-3">
                        <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                            <Trash2 class="h-5 w-5 text-red-600" />
                        </div>
                        <div class="flex-1">
                            <h4 class="font-medium text-red-900 dark:text-red-100">
                                Yakin ingin menghapus user ini?
                            </h4>
                            <div class="mt-2 space-y-1">
                                <p class="text-sm text-red-700 dark:text-red-300">
                                    <span class="font-medium">Nama:</span> {{ user.name }}
                                </p>
                                <p class="text-sm text-red-700 dark:text-red-300">
                                    <span class="font-medium">Email:</span> {{ user.email }}
                                </p>
                                <p class="text-sm text-red-700 dark:text-red-300">
                                    <span class="font-medium">Role:</span> {{ user.role }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button 
                    type="button" 
                    variant="outline" 
                    @click="$emit('close')"
                    :disabled="isDeleting"
                >
                    Batal
                </Button>
                <Button 
                    type="button" 
                    variant="destructive"
                    @click="deleteUser"
                    :disabled="isDeleting"
                    class="min-w-[100px]"
                >
                    <Loader2 v-if="isDeleting" class="h-4 w-4 animate-spin mr-2" />
                    <Trash2 v-else class="h-4 w-4 mr-2" />
                    Hapus
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
