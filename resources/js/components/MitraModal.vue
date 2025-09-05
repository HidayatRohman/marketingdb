<script setup lang="ts">
import { ref, watch } from 'vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/vue3';
import { Loader2, Building2, Phone, Package, MessageSquare, MapPin, DollarSign, FileText } from 'lucide-vue-next';

interface Brand {
    id: number;
    nama: string;
    logo?: string;
    logo_url?: string;
}

interface Mitra {
    id?: number;
    nama: string;
    no_telp: string;
    brand_id: number;
    brand?: Brand;
    chat: 'masuk' | 'followup';
    kota: string;
    provinsi: string;
    transaksi: number | null;
    komentar: string | null;
}

interface Props {
    open: boolean;
    mode: 'create' | 'edit' | 'view';
    mitra?: Mitra;
    brands: Brand[];
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
    success: [];
}>();

const form = useForm({
    nama: '',
    no_telp: '',
    brand_id: null as number | null,
    chat: 'masuk' as 'masuk' | 'followup',
    kota: '',
    provinsi: '',
    transaksi: null as number | null,
    komentar: '',
});

// Watch for mitra prop changes
watch(() => props.mitra, (newMitra) => {
    if (newMitra) {
        form.nama = newMitra.nama || '';
        form.no_telp = newMitra.no_telp || '';
        form.brand_id = newMitra.brand_id || null;
        form.chat = newMitra.chat || 'masuk';
        form.kota = newMitra.kota || '';
        form.provinsi = newMitra.provinsi || '';
        form.transaksi = newMitra.transaksi;
        form.komentar = newMitra.komentar || '';
    } else {
        form.reset();
    }
}, { immediate: true });

// Reset form when modal closes
watch(() => props.open, (isOpen) => {
    if (!isOpen) {
        form.reset();
        form.clearErrors();
    }
});

const submit = () => {
    if (props.mode === 'create') {
        form.post('/mitras', {
            onSuccess: () => {
                emit('success');
                emit('close');
            }
        });
    } else if (props.mode === 'edit' && props.mitra?.id) {
        form.put(`/mitras/${props.mitra.id}`, {
            onSuccess: () => {
                emit('success');
                emit('close');
            }
        });
    }
};

const chatLabels = {
    masuk: 'Masuk',
    followup: 'Follow Up',
};

const formatCurrency = (value: string) => {
    // Remove non-numeric characters except decimal point
    const numericValue = value.replace(/[^\d]/g, '');
    if (numericValue) {
        form.transaksi = parseInt(numericValue);
    } else {
        form.transaksi = null;
    }
};

const displayCurrency = (value: number | null) => {
    if (!value) return '';
    return new Intl.NumberFormat('id-ID').format(value);
};
</script>

<template>
    <Dialog :open="open" @update:open="(value) => !value && $emit('close')">
        <DialogContent class="sm:max-w-[600px] max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-xl">
                    <Building2 class="h-5 w-5" />
                    <span v-if="mode === 'create'">Tambah Mitra Baru</span>
                    <span v-else-if="mode === 'edit'">Edit Mitra</span>
                    <span v-else>Detail Mitra</span>
                </DialogTitle>
                <DialogDescription>
                    <span v-if="mode === 'create'">Tambahkan mitra bisnis baru ke dalam sistem.</span>
                    <span v-else-if="mode === 'edit'">Perbarui informasi mitra yang sudah ada.</span>
                    <span v-else>Informasi lengkap mitra bisnis.</span>
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Informasi Dasar -->
                <div class="space-y-4">
                    <h3 class="text-sm font-medium text-muted-foreground flex items-center gap-2">
                        <Building2 class="h-4 w-4" />
                        Informasi Dasar
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="nama" class="flex items-center gap-2">
                                <Building2 class="h-3 w-3" />
                                Nama Mitra *
                            </Label>
                            <Input
                                id="nama"
                                v-model="form.nama"
                                :disabled="mode === 'view'"
                                placeholder="Masukkan nama mitra"
                                :class="{ 'border-destructive': form.errors.nama }"
                            />
                            <p v-if="form.errors.nama" class="text-sm text-destructive">
                                {{ form.errors.nama }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="no_telp" class="flex items-center gap-2">
                                <Phone class="h-3 w-3" />
                                No. Telepon *
                            </Label>
                            <Input
                                id="no_telp"
                                v-model="form.no_telp"
                                :disabled="mode === 'view'"
                                placeholder="Contoh: 08123456789"
                                :class="{ 'border-destructive': form.errors.no_telp }"
                            />
                            <p v-if="form.errors.no_telp" class="text-sm text-destructive">
                                {{ form.errors.no_telp }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="brand_id" class="flex items-center gap-2">
                            <Package class="h-3 w-3" />
                            Brand *
                        </Label>
                        <select
                            id="brand_id"
                            v-model="form.brand_id"
                            :disabled="mode === 'view'"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            :class="{ 'border-destructive': form.errors.brand_id }"
                        >
                            <option value="">Pilih brand</option>
                            <option 
                                v-for="brand in brands" 
                                :key="brand.id" 
                                :value="brand.id"
                            >
                                {{ brand.nama }}
                            </option>
                        </select>
                        <p v-if="form.errors.brand_id" class="text-sm text-destructive">
                            {{ form.errors.brand_id }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="chat" class="flex items-center gap-2">
                            <MessageSquare class="h-3 w-3" />
                            Status Chat *
                        </Label>
                        <select
                            id="chat"
                            v-model="form.chat"
                            :disabled="mode === 'view'"
                            :class="[
                                'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
                                { 'border-destructive': form.errors.chat }
                            ]"
                        >
                            <option value="">Pilih status chat</option>
                            <option value="masuk">Masuk</option>
                            <option value="followup">Follow Up</option>
                        </select>
                        <p v-if="form.errors.chat" class="text-sm text-destructive">
                            {{ form.errors.chat }}
                        </p>
                    </div>
                </div>

                <!-- Lokasi -->
                <div class="space-y-4">
                    <h3 class="text-sm font-medium text-muted-foreground flex items-center gap-2">
                        <MapPin class="h-4 w-4" />
                        Lokasi
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="kota" class="flex items-center gap-2">
                                <MapPin class="h-3 w-3" />
                                Kota *
                            </Label>
                            <Input
                                id="kota"
                                v-model="form.kota"
                                :disabled="mode === 'view'"
                                placeholder="Masukkan nama kota"
                                :class="{ 'border-destructive': form.errors.kota }"
                            />
                            <p v-if="form.errors.kota" class="text-sm text-destructive">
                                {{ form.errors.kota }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="provinsi" class="flex items-center gap-2">
                                <MapPin class="h-3 w-3" />
                                Provinsi *
                            </Label>
                            <Input
                                id="provinsi"
                                v-model="form.provinsi"
                                :disabled="mode === 'view'"
                                placeholder="Masukkan nama provinsi"
                                :class="{ 'border-destructive': form.errors.provinsi }"
                            />
                            <p v-if="form.errors.provinsi" class="text-sm text-destructive">
                                {{ form.errors.provinsi }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Transaksi & Komentar -->
                <div class="space-y-4">
                    <h3 class="text-sm font-medium text-muted-foreground flex items-center gap-2">
                        <DollarSign class="h-4 w-4" />
                        Informasi Tambahan
                    </h3>

                    <div class="space-y-2">
                        <Label for="transaksi" class="flex items-center gap-2">
                            <DollarSign class="h-3 w-3" />
                            Nilai Transaksi (Opsional)
                        </Label>
                        <Input
                            id="transaksi"
                            :value="displayCurrency(form.transaksi)"
                            @input="formatCurrency($event.target.value)"
                            :disabled="mode === 'view'"
                            placeholder="Contoh: 1000000"
                            :class="{ 'border-destructive': form.errors.transaksi }"
                        />
                        <p class="text-xs text-muted-foreground">Masukkan angka tanpa titik atau koma</p>
                        <p v-if="form.errors.transaksi" class="text-sm text-destructive">
                            {{ form.errors.transaksi }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="komentar" class="flex items-center gap-2">
                            <FileText class="h-3 w-3" />
                            Komentar (Opsional)
                        </Label>
                        <Textarea
                            id="komentar"
                            v-model="form.komentar"
                            :disabled="mode === 'view'"
                            placeholder="Tambahkan catatan atau komentar..."
                            rows="3"
                            :class="{ 'border-destructive': form.errors.komentar }"
                        />
                        <p v-if="form.errors.komentar" class="text-sm text-destructive">
                            {{ form.errors.komentar }}
                        </p>
                    </div>
                </div>
            </form>

            <DialogFooter v-if="mode !== 'view'" class="mt-6">
                <Button
                    type="button"
                    variant="outline"
                    @click="$emit('close')"
                    :disabled="form.processing"
                >
                    Batal
                </Button>
                <Button
                    @click="submit"
                    :disabled="form.processing"
                    class="min-w-[100px]"
                >
                    <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                    {{ mode === 'create' ? 'Simpan' : 'Perbarui' }}
                </Button>
            </DialogFooter>
            
            <DialogFooter v-else class="mt-6">
                <Button
                    type="button"
                    @click="$emit('close')"
                >
                    Tutup
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
