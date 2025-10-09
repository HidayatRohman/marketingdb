<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { useForm } from '@inertiajs/vue3';
import { AlertTriangle, Trash2, CreditCard } from 'lucide-vue-next';
import { computed } from 'vue';

interface Brand {
    id: number;
    nama: string;
    logo?: string;
    logo_url?: string;
}

interface Mitra {
    id: number;
    nama: string;
    no_telp: string;
}

interface User {
    id: number;
    name: string;
    email: string;
}

interface Transaksi {
    id: number;
    user_id: number;
    tanggal_tf: string;
    tanggal_lead_masuk: string;
    periode_lead: string;
    no_wa: string;
    usia: number;
    paket_brand_id: number;
    lead_awal_brand_id: number;
    sumber: string;
    kabupaten: string;
    provinsi: string;
    status_pembayaran: string;
    nominal_masuk: number;
    harga_paket: number;
    nama_paket: string;
    user: User;
    paket_brand?: Brand;
    lead_awal_brand?: Brand;
    created_at: string;
    updated_at: string;
}

interface Props {
    open: boolean;
    transaksi?: Transaksi;
}

interface Emits {
    close: [];
    success: [];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const form = useForm({});

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

const handleDelete = () => {
    if (!props.transaksi) return;

    form.delete(`/transaksis/${props.transaksi.id}`, {
        onSuccess: () => {
            emit('success');
            emit('close');
        },
        onError: (errors) => {
            console.error('Delete error:', errors);
        },
    });
};

const handleClose = () => {
    emit('close');
};
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-xl text-red-600">
                    <AlertTriangle class="h-5 w-5" />
                    Konfirmasi Hapus
                </DialogTitle>
                <DialogDescription class="text-muted-foreground">
                    Apakah Anda yakin ingin menghapus transaksi ini? Tindakan ini tidak dapat dibatalkan.
                </DialogDescription>
            </DialogHeader>

            <div v-if="transaksi" class="space-y-4">
                <!-- Transaksi Info Card -->
                <div class="rounded-lg border bg-muted/20 p-4">
                    <div class="flex items-start gap-3">
                        <div class="rounded-lg bg-red-100 p-2 dark:bg-red-900/20">
                            <CreditCard class="h-5 w-5 text-red-600 dark:text-red-400" />
                        </div>
                        <div class="flex-1 space-y-2">
                            <div class="flex items-center justify-between">
                                <h4 class="font-semibold text-foreground">Detail Transaksi</h4>
                                <span class="text-sm text-muted-foreground">#{{ transaksi.id }}</span>
                            </div>
                            
                            <div class="grid gap-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Marketing:</span>
                                    <span class="font-medium">{{ transaksi.user.name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Tanggal TF:</span>
                                    <span class="font-medium">{{ formatDate(transaksi.tanggal_tf) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Paket:</span>
                                    <span class="font-medium">{{ transaksi.nama_paket }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Brand:</span>
                                    <span class="font-medium">{{ transaksi.paket_brand?.nama || '-' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Status:</span>
                                    <span class="font-medium">{{ transaksi.status_pembayaran }}</span>
                                </div>
                                <div class="flex justify-between border-t pt-2">
                                    <span class="text-muted-foreground">Nominal:</span>
                                    <span class="font-bold text-green-600 dark:text-green-400">
                                        {{ formatCurrency(transaksi.nominal_masuk) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Warning Message -->
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                    <div class="flex items-start gap-3">
                        <AlertTriangle class="h-5 w-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" />
                        <div class="text-sm">
                            <p class="font-medium text-red-800 dark:text-red-200 mb-1">
                                Peringatan!
                            </p>
                            <p class="text-red-700 dark:text-red-300">
                                Data transaksi yang dihapus tidak dapat dikembalikan. Pastikan Anda telah membackup data yang diperlukan sebelum melanjutkan.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-3 border-t pt-4">
                <Button
                    type="button"
                    variant="outline"
                    @click="handleClose"
                    :disabled="form.processing"
                >
                    Batal
                </Button>
                <Button
                    type="button"
                    variant="destructive"
                    @click="handleDelete"
                    :disabled="form.processing"
                    class="min-w-[100px]"
                >
                    <Trash2 v-if="!form.processing" class="mr-2 h-4 w-4" />
                    <span v-if="form.processing">Menghapus...</span>
                    <span v-else>Hapus</span>
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
/* Custom styles for delete modal */
.space-y-4 > * + * {
    margin-top: 1rem;
}

/* Improve visual hierarchy */
.border-t {
    border-top: 1px solid hsl(var(--border));
}

/* Warning styling */
.border-red-200 {
    border-color: rgb(254 202 202);
}

.bg-red-50 {
    background-color: rgb(254 242 242);
}

.dark .border-red-800 {
    border-color: rgb(153 27 27);
}

.dark .bg-red-900\/20 {
    background-color: rgb(127 29 29 / 0.2);
}
</style>