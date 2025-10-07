<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import DatePicker from '@/components/ui/datepicker/DatePicker.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, watch, onMounted } from 'vue';
import { Calendar, User, CreditCard, MapPin, Phone, DollarSign } from 'lucide-vue-next';

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
    role: string;
}

interface Transaksi {
    id: number;
    user_id: number;
    mitra_id: number;
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
    mitra: Mitra;
    paketBrand: Brand;
    leadAwalBrand: Brand;
    created_at: string;
    updated_at: string;
}

interface Props {
    open: boolean;
    mode: 'create' | 'edit' | 'view';
    transaksi?: Transaksi;
    mitras: Mitra[];
    brands: Brand[];
    currentUser: User;
}

interface Emits {
    close: [];
    success: [];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Form data
const form = useForm({
    user_id: props.currentUser.id,
    mitra_id: null as number | null,
    tanggal_tf: new Date().toISOString().split('T')[0],
    tanggal_lead_masuk: '',
    periode_lead: '',
    no_wa: '',
    usia: null as number | null,
    paket_brand_id: null as number | null,
    lead_awal_brand_id: null as number | null,
    sumber: '',
    kabupaten: '',
    provinsi: '',
    status_pembayaran: '',
    nominal_masuk: null as number | null,
    harga_paket: null as number | null,
    nama_paket: '',
});

// Options
const bulanOptions = [
    { value: 'Januari', label: 'Januari' },
    { value: 'Februari', label: 'Februari' },
    { value: 'Maret', label: 'Maret' },
    { value: 'April', label: 'April' },
    { value: 'Mei', label: 'Mei' },
    { value: 'Juni', label: 'Juni' },
    { value: 'Juli', label: 'Juli' },
    { value: 'Agustus', label: 'Agustus' },
    { value: 'September', label: 'September' },
    { value: 'Oktober', label: 'Oktober' },
    { value: 'November', label: 'November' },
    { value: 'Desember', label: 'Desember' },
];

const usiaOptions = Array.from({ length: 69 }, (_, i) => ({
    value: i + 17,
    label: (i + 17).toString(),
}));

const sumberOptions = [
    { value: 'Unknown', label: 'Unknown' },
    { value: 'IG', label: 'Instagram' },
    { value: 'FB', label: 'Facebook' },
    { value: 'WA', label: 'WhatsApp' },
    { value: 'Tiktok', label: 'TikTok' },
    { value: 'Web', label: 'Website' },
    { value: 'Google', label: 'Google' },
    { value: 'Organik', label: 'Organik' },
    { value: 'Teman', label: 'Teman' },
];

const statusPembayaranOptions = [
    { value: 'Dp / TJ', label: 'Dp / TJ' },
    { value: 'Tambahan Dp', label: 'Tambahan Dp' },
    { value: 'Pelunasan', label: 'Pelunasan' },
];

const provinsiOptions = [
    'Aceh', 'Sumatera Utara', 'Sumatera Barat', 'Riau', 'Kepulauan Riau', 'Jambi',
    'Sumatera Selatan', 'Bangka Belitung', 'Bengkulu', 'Lampung', 'DKI Jakarta',
    'Jawa Barat', 'Jawa Tengah', 'DI Yogyakarta', 'Jawa Timur', 'Banten',
    'Bali', 'Nusa Tenggara Barat', 'Nusa Tenggara Timur', 'Kalimantan Barat',
    'Kalimantan Tengah', 'Kalimantan Selatan', 'Kalimantan Timur', 'Kalimantan Utara',
    'Sulawesi Utara', 'Sulawesi Tengah', 'Sulawesi Selatan', 'Sulawesi Tenggara',
    'Gorontalo', 'Sulawesi Barat', 'Maluku', 'Maluku Utara', 'Papua', 'Papua Barat',
    'Papua Selatan', 'Papua Tengah', 'Papua Pegunungan', 'Papua Barat Daya'
].map(provinsi => ({ value: provinsi, label: provinsi }));

// Computed properties
const isViewMode = computed(() => props.mode === 'view');
const isEditMode = computed(() => props.mode === 'edit');
const isCreateMode = computed(() => props.mode === 'create');

const modalTitle = computed(() => {
    switch (props.mode) {
        case 'create':
            return 'Tambah Transaksi Baru';
        case 'edit':
            return 'Edit Transaksi';
        case 'view':
            return 'Detail Transaksi';
        default:
            return 'Transaksi';
    }
});

const selectedMitra = computed(() => {
    if (!form.mitra_id) return null;
    return props.mitras.find(mitra => mitra.id === form.mitra_id);
});

// Watch for mitra selection to auto-fill no_wa
watch(() => form.mitra_id, (newMitraId) => {
    if (newMitraId && selectedMitra.value) {
        form.no_wa = selectedMitra.value.no_telp;
    }
});

// Watch for props changes
watch(() => props.open, (isOpen) => {
    if (isOpen) {
        resetForm();
        if (props.transaksi && (props.mode === 'edit' || props.mode === 'view')) {
            populateForm(props.transaksi);
        }
    }
});

// Ensure data is available on mount
onMounted(() => {
    // Component mounted successfully
});

const resetForm = () => {
    form.reset();
    form.user_id = props.currentUser.id;
    form.tanggal_tf = new Date().toISOString().split('T')[0];
    form.clearErrors();
};

const populateForm = (transaksi: Transaksi) => {
    form.user_id = transaksi.user_id;
    form.mitra_id = transaksi.mitra_id;
    form.tanggal_tf = transaksi.tanggal_tf;
    form.tanggal_lead_masuk = transaksi.tanggal_lead_masuk;
    form.periode_lead = transaksi.periode_lead;
    form.no_wa = transaksi.no_wa;
    form.usia = transaksi.usia;
    form.paket_brand_id = transaksi.paket_brand_id;
    form.lead_awal_brand_id = transaksi.lead_awal_brand_id;
    form.sumber = transaksi.sumber;
    form.kabupaten = transaksi.kabupaten;
    form.provinsi = transaksi.provinsi;
    form.status_pembayaran = transaksi.status_pembayaran;
    form.nominal_masuk = transaksi.nominal_masuk;
    form.harga_paket = transaksi.harga_paket;
    form.nama_paket = transaksi.nama_paket;
};

const handleSubmit = () => {
    if (isViewMode.value) return;

    const url = isEditMode.value ? `/transaksis/${props.transaksi?.id}` : '/transaksis';
    const method = isEditMode.value ? 'put' : 'post';

    form[method](url, {
        onSuccess: () => {
            emit('success');
            emit('close');
        },
        onError: (errors) => {
            console.error('Form errors:', errors);
        },
    });
};

const handleClose = () => {
    emit('close');
};

const formatCurrency = (value: string) => {
    // Remove non-numeric characters except dots and commas
    const numericValue = value.replace(/[^0-9]/g, '');
    
    // Format with thousand separators
    return numericValue.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
};

const handleCurrencyInput = (field: 'nominal_masuk' | 'harga_paket', event: Event) => {
    const target = event.target as HTMLInputElement;
    const formatted = formatCurrency(target.value);
    target.value = formatted;
    
    // Convert back to number for form data
    const numericValue = parseInt(formatted.replace(/\./g, '')) || 0;
    form[field] = numericValue;
};
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-xl">
                    <CreditCard class="h-5 w-5" />
                    {{ modalTitle }}
                </DialogTitle>
                <DialogDescription>
                    {{ isCreateMode ? 'Tambah data transaksi baru' : isEditMode ? 'Edit data transaksi' : 'Lihat detail transaksi' }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Marketing Info -->
                <div class="rounded-lg border bg-muted/20 p-4">
                    <h3 class="mb-3 flex items-center gap-2 font-semibold">
                        <User class="h-4 w-4" />
                        Informasi Marketing
                    </h3>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="marketing">Nama Marketing</Label>
                            <Input
                                id="marketing"
                                :value="currentUser.name"
                                disabled
                                class="bg-muted"
                            />
                        </div>
                    </div>
                </div>

                <!-- Mitra & Contact Info -->
                <div class="rounded-lg border bg-muted/20 p-4">
                    <h3 class="mb-3 flex items-center gap-2 font-semibold">
                        <Phone class="h-4 w-4" />
                        Informasi Mitra & Kontak
                    </h3>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="mitra_id">Nama Mitra *</Label>
                            <Select :value="form.mitra_id?.toString()" @update:model-value="(value) => form.mitra_id = value ? parseInt(value) : null" :disabled="isViewMode">
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih mitra" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="mitra in mitras" :key="mitra.id" :value="mitra.id.toString()">
                                        {{ mitra.nama }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <div v-if="form.errors.mitra_id" class="text-sm text-red-600">
                                {{ form.errors.mitra_id }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="no_wa">No WhatsApp</Label>
                            <Input
                                id="no_wa"
                                v-model="form.no_wa"
                                :disabled="isViewMode"
                                placeholder="Nomor WhatsApp"
                            />
                            <div v-if="form.errors.no_wa" class="text-sm text-red-600">
                                {{ form.errors.no_wa }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Date & Period Info -->
                <div class="rounded-lg border bg-muted/20 p-4">
                    <h3 class="mb-3 flex items-center gap-2 font-semibold">
                        <Calendar class="h-4 w-4" />
                        Informasi Tanggal & Periode
                    </h3>
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="tanggal_tf">Tanggal TF *</Label>
                            <DatePicker
                                v-model="form.tanggal_tf"
                                :disabled="isViewMode"
                                placeholder="Pilih tanggal TF"
                            />
                            <div v-if="form.errors.tanggal_tf" class="text-sm text-red-600">
                                {{ form.errors.tanggal_tf }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="tanggal_lead_masuk">Tanggal Lead Masuk *</Label>
                            <DatePicker
                                v-model="form.tanggal_lead_masuk"
                                :disabled="isViewMode"
                                placeholder="Pilih tanggal lead masuk"
                            />
                            <div v-if="form.errors.tanggal_lead_masuk" class="text-sm text-red-600">
                                {{ form.errors.tanggal_lead_masuk }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="periode_lead">Periode Lead *</Label>
                            <Select :value="form.periode_lead" @update:model-value="(value) => form.periode_lead = value" :disabled="isViewMode">
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih bulan" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="bulan in bulanOptions" :key="bulan.value" :value="bulan.value">
                                        {{ bulan.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <div v-if="form.errors.periode_lead" class="text-sm text-red-600">
                                {{ form.errors.periode_lead }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Info -->
                <div class="rounded-lg border bg-muted/20 p-4">
                    <h3 class="mb-3 flex items-center gap-2 font-semibold">
                        <User class="h-4 w-4" />
                        Informasi Customer
                    </h3>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="usia">Usia *</Label>
                            <Select :value="form.usia?.toString()" @update:model-value="(value) => form.usia = value ? parseInt(value) : null" :disabled="isViewMode">
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih usia" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="usia in usiaOptions" :key="usia.value" :value="usia.value.toString()">
                                        {{ usia.label }} tahun
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <div v-if="form.errors.usia" class="text-sm text-red-600">
                                {{ form.errors.usia }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="sumber">Sumber *</Label>
                            <Select :value="form.sumber" @update:model-value="(value) => form.sumber = value" :disabled="isViewMode">
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih sumber" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="sumber in sumberOptions" :key="sumber.value" :value="sumber.value">
                                        {{ sumber.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <div v-if="form.errors.sumber" class="text-sm text-red-600">
                                {{ form.errors.sumber }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Location Info -->
                <div class="rounded-lg border bg-muted/20 p-4">
                    <h3 class="mb-3 flex items-center gap-2 font-semibold">
                        <MapPin class="h-4 w-4" />
                        Informasi Lokasi
                    </h3>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="kabupaten">Kabupaten *</Label>
                            <Input
                                id="kabupaten"
                                v-model="form.kabupaten"
                                :disabled="isViewMode"
                                placeholder="Masukkan kabupaten"
                            />
                            <div v-if="form.errors.kabupaten" class="text-sm text-red-600">
                                {{ form.errors.kabupaten }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="provinsi">Provinsi *</Label>
                            <Select :value="form.provinsi" @update:model-value="(value) => form.provinsi = value" :disabled="isViewMode">
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih provinsi" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="provinsi in provinsiOptions" :key="provinsi.value" :value="provinsi.value">
                                        {{ provinsi.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <div v-if="form.errors.provinsi" class="text-sm text-red-600">
                                {{ form.errors.provinsi }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Package & Brand Info -->
                <div class="rounded-lg border bg-muted/20 p-4">
                    <h3 class="mb-3 flex items-center gap-2 font-semibold">
                        <CreditCard class="h-4 w-4" />
                        Informasi Paket & Brand
                    </h3>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="paket_brand_id">Paket Brand *</Label>
                            <Select :value="form.paket_brand_id?.toString()" @update:model-value="(value) => form.paket_brand_id = value ? parseInt(value) : null" :disabled="isViewMode">
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih paket brand" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="brand in brands" :key="brand.id" :value="brand.id.toString()">
                                        {{ brand.nama }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <div v-if="form.errors.paket_brand_id" class="text-sm text-red-600">
                                {{ form.errors.paket_brand_id }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="lead_awal_brand_id">Lead Awal Brand *</Label>
                            <Select :value="form.lead_awal_brand_id?.toString()" @update:model-value="(value) => form.lead_awal_brand_id = value ? parseInt(value) : null" :disabled="isViewMode">
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih lead awal brand" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="brand in brands" :key="brand.id" :value="brand.id.toString()">
                                        {{ brand.nama }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <div v-if="form.errors.lead_awal_brand_id" class="text-sm text-red-600">
                                {{ form.errors.lead_awal_brand_id }}
                            </div>
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <Label for="nama_paket">Nama Paket *</Label>
                            <Input
                                id="nama_paket"
                                v-model="form.nama_paket"
                                :disabled="isViewMode"
                                placeholder="Masukkan nama paket"
                            />
                            <div v-if="form.errors.nama_paket" class="text-sm text-red-600">
                                {{ form.errors.nama_paket }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Info -->
                <div class="rounded-lg border bg-muted/20 p-4">
                    <h3 class="mb-3 flex items-center gap-2 font-semibold">
                        <DollarSign class="h-4 w-4" />
                        Informasi Pembayaran
                    </h3>
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="status_pembayaran">Status Pembayaran *</Label>
                            <Select :value="form.status_pembayaran" @update:model-value="(value) => form.status_pembayaran = value" :disabled="isViewMode">
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="status in statusPembayaranOptions" :key="status.value" :value="status.value">
                                        {{ status.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <div v-if="form.errors.status_pembayaran" class="text-sm text-red-600">
                                {{ form.errors.status_pembayaran }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="nominal_masuk">Nominal Masuk *</Label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">Rp</span>
                                <Input
                                    id="nominal_masuk"
                                    :value="form.nominal_masuk ? formatCurrency(form.nominal_masuk.toString()) : ''"
                                    @input="handleCurrencyInput('nominal_masuk', $event)"
                                    :disabled="isViewMode"
                                    placeholder="0"
                                    class="pl-10"
                                />
                            </div>
                            <div v-if="form.errors.nominal_masuk" class="text-sm text-red-600">
                                {{ form.errors.nominal_masuk }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="harga_paket">Harga Paket *</Label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">Rp</span>
                                <Input
                                    id="harga_paket"
                                    :value="form.harga_paket ? formatCurrency(form.harga_paket.toString()) : ''"
                                    @input="handleCurrencyInput('harga_paket', $event)"
                                    :disabled="isViewMode"
                                    placeholder="0"
                                    class="pl-10"
                                />
                            </div>
                            <div v-if="form.errors.harga_paket" class="text-sm text-red-600">
                                {{ form.errors.harga_paket }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-3 border-t pt-4">
                    <Button type="button" variant="outline" @click="handleClose">
                        {{ isViewMode ? 'Tutup' : 'Batal' }}
                    </Button>
                    <Button
                        v-if="!isViewMode"
                        type="submit"
                        :disabled="form.processing"
                        class="min-w-[100px]"
                    >
                        <span v-if="form.processing">Menyimpan...</span>
                        <span v-else>{{ isEditMode ? 'Update' : 'Simpan' }}</span>
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
/* Custom styles for form sections */
.space-y-6 > * + * {
    margin-top: 1.5rem;
}

/* Improve form readability */
.rounded-lg.border {
    transition: border-color 0.2s ease;
}

.rounded-lg.border:hover {
    border-color: hsl(var(--border));
}

/* Currency input styling */
.relative input[type="text"] {
    padding-left: 2.5rem;
}
</style>