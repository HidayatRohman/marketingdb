<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import DatePicker from '@/components/ui/datepicker/DatePicker.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, watch, onMounted, nextTick } from 'vue';
import { Calendar, User, CreditCard, MapPin, Phone, DollarSign } from 'lucide-vue-next';

interface Brand {
    id: number;
    nama: string;
    logo?: string;
    logo_url?: string;
}

interface Mitra {
    id: number;
    nama_mitra: string;
    no_telp: string;
}

interface Sumber {
    id: number;
    nama: string;
    warna: string;
}

interface User {
    id: number;
    name: string;
    role: string;
}

interface Transaksi {
    id: number;
    user_id: number;
    nama_marketing: string;
    tanggal_tf: string;
    tanggal_lead_masuk: string;
    periode_lead: string;
    no_wa: string;
    usia: number;
    nama_mitra?: string;
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
    paketBrand?: Brand;
    leadAwalBrand?: Brand;
    created_at: string;
    updated_at: string;
}

interface Props {
    open: boolean;
    mode: 'create' | 'edit' | 'view';
    transaksi?: Transaksi;
    brands: Brand[];
    currentUser: User;
}

interface Emits {
    close: [];
    success: [];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
const page = usePage();

// Form data
const form = useForm({
    user_id: props.currentUser.id,
    nama_marketing: props.currentUser.name,
    tanggal_tf: new Date().toISOString().split('T')[0],
    tanggal_lead_masuk: new Date().toISOString().split('T')[0],
    periode_lead: '',
    no_wa: '',
    usia: null,
    nama_mitra: '',
    paket_brand_id: null,
    lead_awal_brand_id: null,
    sumber: '',
    kabupaten: '',
    provinsi: '',
    status_pembayaran: '',
    nominal_masuk: null,
    harga_paket: null,
    nama_paket: '',
});

// Options
const usiaOptions = Array.from({ length: 63 }, (_, i) => ({
    value: i + 18,
    label: (i + 18).toString(),
}));

const sumberOptions = [
    { value: 'Unknown', label: 'Tidak Tahu' },
    { value: 'Organik', label: 'Organik' },
    { value: 'Web', label: 'Web' },
    { value: 'IG', label: 'Instagram' },
    { value: 'FB', label: 'Facebook' },
    { value: 'WA', label: 'Whatsapp' },
    { value: 'Google', label: 'Google' },
    { value: 'Tiktok', label: 'Tiktok' },
    { value: 'Teman', label: 'Teman' },
];

const periodeLeadOptions = [
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

const statusPembayaranOptions = [
    { value: 'Dp / TJ', label: 'DP / Tanda Jadi' },
    { value: 'Tambahan Dp', label: 'Tambahan DP' },
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
    form.nama_marketing = props.currentUser.name;
    form.tanggal_tf = new Date().toISOString().split('T')[0];
    form.tanggal_lead_masuk = new Date().toISOString().split('T')[0];
    form.periode_lead = '';
    form.no_wa = '';
    form.usia = null;
    form.nama_mitra = '';
    form.paket_brand_id = null;
    form.lead_awal_brand_id = null;
    form.sumber = '';
    form.kabupaten = '';
    form.provinsi = '';
    form.status_pembayaran = '';
    form.nominal_masuk = null;
    form.harga_paket = null;
    form.nama_paket = '';
    form.clearErrors();
};

const populateForm = (transaksi: Transaksi) => {
    form.user_id = transaksi.user_id;
    form.nama_marketing = transaksi.nama_marketing || props.currentUser.name;
    form.tanggal_tf = transaksi.tanggal_tf;
    form.tanggal_lead_masuk = transaksi.tanggal_lead_masuk;
    form.periode_lead = transaksi.periode_lead;
    form.no_wa = transaksi.no_wa;
    form.usia = transaksi.usia;
    form.nama_mitra = transaksi.nama_mitra || '';
    form.paket_brand_id = transaksi.paket_brand_id;
    form.lead_awal_brand_id = transaksi.lead_awal_brand_id;
    form.sumber = transaksi.sumber;
    form.kabupaten = transaksi.kabupaten;
    form.provinsi = transaksi.provinsi;
    form.status_pembayaran = transaksi.status_pembayaran;
    form.nominal_masuk = transaksi.nominal_masuk || null;
    form.harga_paket = transaksi.harga_paket || null;
    form.nama_paket = transaksi.nama_paket;
};

const handleSubmit = () => {
    if (isViewMode.value) return;

    const url = isEditMode.value ? `/transaksis/${props.transaksi?.id}` : '/transaksis';
    const method = isEditMode.value ? 'put' : 'post';

    console.log('Submitting form data:', form.data());
    
    form[method](url, {
        onSuccess: (response) => {
            console.log('Form submitted successfully:', response);
            
            // Tampilkan notifikasi sukses eksplisit
            nextTick(() => {
                // Set flash message secara manual jika backend tidak mengirim
                if (!page.props.flash?.success) {
                    page.props.flash = page.props.flash || {};
                    page.props.flash.success = isEditMode.value 
                        ? 'Transaksi berhasil diperbarui!' 
                        : 'Transaksi berhasil ditambahkan!';
                }
            });
            
            emit('success');
            emit('close');
        },
        onError: (errors) => {
            console.error('Form submission failed with errors:', errors);
            console.error('Form processing state:', form.processing);
            console.error('Form hasErrors:', form.hasErrors);
            
            // Tampilkan notifikasi error eksplisit
            nextTick(() => {
                if (!page.props.flash?.error) {
                    page.props.flash = page.props.flash || {};
                    
                    // Buat pesan error yang informatif
                    const errorMessages = Object.values(errors).flat();
                    if (errorMessages.length > 0) {
                        page.props.flash.error = `Gagal menyimpan transaksi: ${errorMessages[0]}`;
                    } else {
                        page.props.flash.error = 'Terjadi kesalahan saat menyimpan transaksi. Silakan coba lagi.';
                    }
                }
            });
            
            console.error('Validation errors:', Object.values(errors).flat());
        },
        onFinish: () => {
            console.log('Form submission finished');
        }
    });
};

const handleClose = () => {
    emit('close');
};

const formatCurrency = (value: string | number) => {
    // Convert to string and remove non-numeric characters
    const stringValue = value.toString();
    const numericValue = stringValue.replace(/[^0-9]/g, '');
    
    // Return empty string if no numeric value
    if (!numericValue) return '';
    
    // Format with thousand separators using Indonesian locale
    const number = parseInt(numericValue);
    return new Intl.NumberFormat('id-ID').format(number);
};

const handleCurrencyInput = (field: 'nominal_masuk' | 'harga_paket', event: Event) => {
    const target = event.target as HTMLInputElement;
    const inputValue = target.value;
    
    // Remove non-numeric characters
    const numericOnly = inputValue.replace(/[^0-9]/g, '');
    
    // Format the display value
    const formatted = formatCurrency(numericOnly);
    target.value = formatted;
    
    // Convert back to number for form data
    const numericValue = numericOnly ? parseInt(numericOnly) : null;
    form[field] = numericValue;
};
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="w-[900px] max-w-[95vw] max-h-[95vh] overflow-y-auto bg-white dark:bg-gray-900 border-2 border-gray-200 dark:border-gray-700 shadow-2xl">
            <DialogHeader class="border-b border-gray-200 dark:border-gray-700 pb-6 mb-6">
                <DialogTitle class="flex items-center gap-3 text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                    <div class="p-2 rounded-lg bg-gradient-to-r from-blue-500 to-indigo-500 text-white shadow-lg">
                        <CreditCard class="h-6 w-6" />
                    </div>
                    {{ modalTitle }}
                </DialogTitle>
                <DialogDescription class="text-base text-gray-700 dark:text-gray-300 mt-2">
                    {{ isCreateMode ? 'Tambah data transaksi baru dengan mudah dan cepat' : isEditMode ? 'Edit dan perbarui data transaksi' : 'Lihat detail lengkap transaksi' }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleSubmit" class="space-y-8">
                <!-- Marketing Info -->
                <div class="group rounded-xl border-2 border-blue-200 bg-blue-50 p-6 shadow-sm transition-all duration-300 hover:border-blue-300 hover:shadow-md dark:border-gray-600 dark:bg-gray-800">
                    <h3 class="mb-4 flex items-center gap-3 text-lg font-bold text-gray-800 dark:text-gray-200">
                        <div class="p-2 rounded-lg bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-md">
                            <User class="h-5 w-5" />
                        </div>
                        Informasi Marketing
                    </h3>
                    <div class="grid gap-6 grid-cols-1">
                        <div class="space-y-3">
                            <Label for="marketing" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Nama Marketing</Label>
                            <Input
                                id="marketing"
                                v-model="form.nama_marketing"
                                :disabled="isViewMode"
                                placeholder="Masukkan nama marketing"
                                class="h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-base transition-all duration-200 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            />
                        </div>
                    </div>
                </div>

                <!-- Customer Info -->
                <div class="group rounded-xl border-2 border-orange-200 bg-orange-50 p-6 shadow-sm transition-all duration-300 hover:border-orange-300 hover:shadow-md dark:border-gray-600 dark:bg-gray-800">
                    <h3 class="mb-4 flex items-center gap-3 text-lg font-bold text-gray-800 dark:text-gray-200">
                        <div class="p-2 rounded-lg bg-gradient-to-r from-orange-500 to-amber-600 text-white shadow-md">
                            <User class="h-5 w-5" />
                        </div>
                        Informasi Customer
                    </h3>
                    <div class="grid gap-6 grid-cols-1">
                        <div class="space-y-3">
                            <Label for="nama_mitra" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Nama Mitra</Label>
                            <Input
                                id="nama_mitra"
                                v-model="form.nama_mitra"
                                :disabled="isViewMode"
                                placeholder="Masukkan nama mitra"
                                class="h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-base transition-all duration-200 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            />
                            <div v-if="form.errors.nama_mitra" class="text-sm font-medium text-red-600 bg-red-50 px-3 py-2 rounded-lg border border-red-200">
                                {{ form.errors.nama_mitra }}
                            </div>
                        </div>

                        <div class="space-y-3">
                            <Label for="no_wa" class="text-sm font-semibold text-gray-700 dark:text-gray-300">No WhatsApp</Label>
                            <Input
                                id="no_wa"
                                v-model="form.no_wa"
                                :disabled="isViewMode"
                                placeholder="Masukkan nomor WhatsApp"
                                class="h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-base transition-all duration-200 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            />
                            <div v-if="form.errors.no_wa" class="text-sm font-medium text-red-600 bg-red-50 px-3 py-2 rounded-lg border border-red-200">
                                {{ form.errors.no_wa }}
                            </div>
                        </div>

                        <div class="space-y-3">
                            <Label for="usia" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Usia *</Label>
                            <select 
                                id="usia"
                                v-model="form.usia" 
                                :disabled="isViewMode"
                                class="h-12 w-full rounded-lg border border-gray-300 bg-gray-50 px-4 text-base transition-all duration-200 hover:border-emerald-300 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                                <option v-for="usia in usiaOptions" :key="usia.value" :value="usia.value">
                                    {{ usia.label }} tahun
                                </option>
                            </select>
                            <div v-if="form.errors.usia" class="text-sm font-medium text-red-600 bg-red-50 px-3 py-2 rounded-lg border border-red-200">
                                {{ form.errors.usia }}
                            </div>
                        </div>

                        <div class="space-y-3">
                            <Label for="sumber" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Sumber *</Label>
                            <select 
                                id="sumber"
                                v-model="form.sumber" 
                                :disabled="isViewMode"
                                class="h-12 w-full rounded-lg border border-gray-300 bg-gray-50 px-4 text-base transition-all duration-200 hover:border-emerald-300 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                                <option v-for="sumber in sumberOptions" :key="sumber.value" :value="sumber.value">
                                    {{ sumber.label }}
                                </option>
                            </select>
                            <div v-if="form.errors.sumber" class="text-sm font-medium text-red-600 bg-red-50 px-3 py-2 rounded-lg border border-red-200">
                                {{ form.errors.sumber }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Date & Period Info -->
                <div class="group rounded-xl border-2 border-purple-200 bg-purple-50 p-6 shadow-sm transition-all duration-300 hover:border-purple-300 hover:shadow-md dark:border-gray-600 dark:bg-gray-800">
                    <h3 class="mb-4 flex items-center gap-3 text-lg font-bold text-gray-800 dark:text-gray-200">
                        <div class="p-2 rounded-lg bg-gradient-to-r from-purple-500 to-violet-600 text-white shadow-md">
                            <Calendar class="h-5 w-5" />
                        </div>
                        Informasi Tanggal & Periode
                    </h3>
                    <div class="grid gap-6 grid-cols-1">
                        <div class="space-y-3">
                            <Label for="tanggal_tf" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Tanggal TF *</Label>
                            <div class="w-full">
                                <DatePicker
                                    v-model="form.tanggal_tf"
                                    :disabled="isViewMode"
                                    placeholder="Pilih tanggal TF"
                                    class="!w-full !h-12 !rounded-lg !border !border-gray-300 !bg-gray-50 !px-4 !text-base !transition-all !duration-200 !focus:ring-2 !focus:ring-emerald-100 !focus:border-emerald-400 dark:!bg-gray-700 dark:!border-gray-600 dark:!text-white"
                                />
                            </div>
                            <div v-if="form.errors.tanggal_tf" class="text-sm font-medium text-red-600 bg-red-50 px-3 py-2 rounded-lg border border-red-200">
                                {{ form.errors.tanggal_tf }}
                            </div>
                        </div>

                        <div class="space-y-3">
                            <Label for="tanggal_lead_masuk" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Tanggal Lead Masuk *</Label>
                            <div class="w-full">
                                <DatePicker
                                    v-model="form.tanggal_lead_masuk"
                                    :disabled="isViewMode"
                                    placeholder="Pilih tanggal lead masuk"
                                    class="!w-full !h-12 !rounded-lg !border !border-gray-300 !bg-gray-50 !px-4 !text-base !transition-all !duration-200 !focus:ring-2 !focus:ring-emerald-100 !focus:border-emerald-400 dark:!bg-gray-700 dark:!border-gray-600 dark:!text-white"
                                />
                            </div>
                            <div v-if="form.errors.tanggal_lead_masuk" class="text-sm font-medium text-red-600 bg-red-50 px-3 py-2 rounded-lg border border-red-200">
                                {{ form.errors.tanggal_lead_masuk }}
                            </div>
                        </div>

                        <div class="space-y-3">
                            <Label for="periode_lead" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Periode Lead *</Label>
                            <select 
                                id="periode_lead"
                                v-model="form.periode_lead" 
                                :disabled="isViewMode"
                                class="h-12 w-full rounded-lg border border-gray-300 bg-gray-50 px-4 text-base transition-all duration-200 hover:border-emerald-300 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                                <option v-for="periode in periodeLeadOptions" :key="periode.value" :value="periode.value">
                                    {{ periode.label }}
                                </option>
                            </select>
                            <div v-if="form.errors.periode_lead" class="text-sm font-medium text-red-600 bg-red-50 px-3 py-2 rounded-lg border border-red-200">
                                {{ form.errors.periode_lead }}
                            </div>
                        </div>
                    </div>
                </div>



                <!-- Location Info -->
                <div class="group rounded-xl border-2 border-teal-200 bg-teal-50 p-6 shadow-sm transition-all duration-300 hover:border-teal-300 hover:shadow-md dark:border-gray-600 dark:bg-gray-800">
                    <h3 class="mb-4 flex items-center gap-3 text-lg font-bold text-gray-800 dark:text-gray-200">
                        <div class="p-2 rounded-lg bg-gradient-to-r from-teal-500 to-cyan-600 text-white shadow-md">
                            <MapPin class="h-5 w-5" />
                        </div>
                        Informasi Lokasi
                    </h3>
                    <div class="grid gap-6 grid-cols-1">
                        <div class="space-y-3">
                            <Label for="kabupaten" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Kabupaten *</Label>
                            <Input
                                id="kabupaten"
                                v-model="form.kabupaten"
                                :disabled="isViewMode"
                                placeholder="Masukkan kabupaten"
                                class="h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-base transition-all duration-200 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            />
                            <div v-if="form.errors.kabupaten" class="text-sm font-medium text-red-600 bg-red-50 px-3 py-2 rounded-lg border border-red-200">
                                {{ form.errors.kabupaten }}
                            </div>
                        </div>

                        <div class="space-y-3">
                            <Label for="provinsi" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Provinsi *</Label>
                            <select 
                                id="provinsi"
                                v-model="form.provinsi" 
                                :disabled="isViewMode"
                                class="h-12 w-full rounded-lg border border-gray-300 bg-gray-50 px-4 text-base transition-all duration-200 hover:border-emerald-300 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                                <option v-for="provinsi in provinsiOptions" :key="provinsi.value" :value="provinsi.value">
                                    {{ provinsi.label }}
                                </option>
                            </select>
                            <div v-if="form.errors.provinsi" class="text-sm font-medium text-red-600 bg-red-50 px-3 py-2 rounded-lg border border-red-200">
                                {{ form.errors.provinsi }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Package & Brand Info -->
                <div class="group rounded-xl border-2 border-indigo-200 bg-indigo-50 p-6 shadow-sm transition-all duration-300 hover:border-indigo-300 hover:shadow-md dark:border-gray-600 dark:bg-gray-800">
                    <h3 class="mb-4 flex items-center gap-3 text-lg font-bold text-gray-800 dark:text-gray-200">
                        <div class="p-2 rounded-lg bg-gradient-to-r from-indigo-500 to-blue-600 text-white shadow-md">
                            <CreditCard class="h-5 w-5" />
                        </div>
                        Informasi Paket & Brand
                    </h3>
                    <div class="grid gap-6 grid-cols-1">
                        <div class="space-y-3">
                            <Label for="paket_brand_id" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Paket Brand *</Label>
                            <select 
                                id="paket_brand_id"
                                v-model="form.paket_brand_id" 
                                :disabled="isViewMode"
                                class="h-12 w-full rounded-lg border border-gray-300 bg-gray-50 px-4 text-base transition-all duration-200 hover:border-emerald-300 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                                <option value="" disabled>Pilih Paket Brand</option>
                                <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                                    {{ brand.nama }}
                                </option>
                            </select>
                            <div v-if="form.errors.paket_brand_id" class="text-sm font-medium text-red-600 bg-red-50 px-3 py-2 rounded-lg border border-red-200">
                                {{ form.errors.paket_brand_id }}
                            </div>
                        </div>

                        <div class="space-y-3">
                            <Label for="lead_awal_brand_id" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Lead Awal Brand *</Label>
                            <select 
                                id="lead_awal_brand_id"
                                v-model="form.lead_awal_brand_id" 
                                :disabled="isViewMode"
                                class="h-12 w-full rounded-lg border border-gray-300 bg-gray-50 px-4 text-base transition-all duration-200 hover:border-emerald-300 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                                <option value="" disabled>Pilih Lead Awal Brand</option>
                                <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                                    {{ brand.nama }}
                                </option>
                            </select>
                            <div v-if="form.errors.lead_awal_brand_id" class="text-sm font-medium text-red-600 bg-red-50 px-3 py-2 rounded-lg border border-red-200">
                                {{ form.errors.lead_awal_brand_id }}
                            </div>
                        </div>

                        <div class="space-y-3">
                            <Label for="nama_paket" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Nama Paket *</Label>
                            <Input
                                id="nama_paket"
                                v-model="form.nama_paket"
                                :disabled="isViewMode"
                                placeholder="Masukkan nama paket"
                                class="h-12 rounded-lg border border-gray-300 bg-gray-50 px-4 text-base transition-all duration-200 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            />
                            <div v-if="form.errors.nama_paket" class="text-sm font-medium text-red-600 bg-red-50 px-3 py-2 rounded-lg border border-red-200">
                                {{ form.errors.nama_paket }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Info -->
                <div class="group rounded-xl border-2 border-emerald-200 bg-emerald-50 p-6 shadow-sm transition-all duration-300 hover:border-emerald-300 hover:shadow-md dark:border-gray-600 dark:bg-gray-800">
                    <h3 class="mb-4 flex items-center gap-3 text-lg font-bold text-gray-800 dark:text-gray-200">
                        <div class="p-2 rounded-lg bg-gradient-to-r from-emerald-500 to-green-600 text-white shadow-md">
                            <DollarSign class="h-5 w-5" />
                        </div>
                        Informasi Pembayaran
                    </h3>
                    <div class="grid gap-6 grid-cols-1">
                        <div class="space-y-3">
                            <Label for="status_pembayaran" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Status Pembayaran *</Label>
                            <select 
                                id="status_pembayaran"
                                v-model="form.status_pembayaran" 
                                :disabled="isViewMode"
                                class="h-12 w-full rounded-lg border border-gray-300 bg-gray-50 px-4 text-base transition-all duration-200 hover:border-emerald-300 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                                <option v-for="status in statusPembayaranOptions" :key="status.value" :value="status.value">
                                    {{ status.label }}
                                </option>
                            </select>
                            <div v-if="form.errors.status_pembayaran" class="text-sm font-medium text-red-600 bg-red-50 px-3 py-2 rounded-lg border border-red-200">
                                {{ form.errors.status_pembayaran }}
                            </div>
                        </div>

                        <div class="space-y-3">
                            <Label for="nominal_masuk" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Nominal Masuk *</Label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500 dark:text-gray-400 font-medium">Rp</span>
                                <input
                                    id="nominal_masuk"
                                    type="text"
                                    :value="form.nominal_masuk ? formatCurrency(form.nominal_masuk.toString()) : ''"
                                    @input="handleCurrencyInput('nominal_masuk', $event)"
                                    :disabled="isViewMode"
                                    placeholder="0"
                                    class="h-12 w-full rounded-lg border border-gray-300 bg-gray-50 pl-10 pr-4 text-base transition-all duration-200 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none"
                                />
                            </div>
                            <div v-if="form.errors.nominal_masuk" class="text-sm font-medium text-red-600 bg-red-50 px-3 py-2 rounded-lg border border-red-200">
                                {{ form.errors.nominal_masuk }}
                            </div>
                        </div>

                        <div class="space-y-3">
                            <Label for="harga_paket" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Harga Paket *</Label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500 dark:text-gray-400 font-medium">Rp</span>
                                <input
                                    id="harga_paket"
                                    type="text"
                                    :value="form.harga_paket ? formatCurrency(form.harga_paket.toString()) : ''"
                                    @input="handleCurrencyInput('harga_paket', $event)"
                                    :disabled="isViewMode"
                                    placeholder="0"
                                    class="h-12 w-full rounded-lg border border-gray-300 bg-gray-50 pl-10 pr-4 text-base transition-all duration-200 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none"
                                />
                            </div>
                            <div v-if="form.errors.harga_paket" class="text-sm font-medium text-red-600 bg-red-50 px-3 py-2 rounded-lg border border-red-200">
                                {{ form.errors.harga_paket }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-4 border-t-2 border-gray-200 pt-6 mt-8 dark:border-gray-700">
                    <Button 
                        type="button" 
                        variant="outline" 
                        @click="handleClose"
                        class="h-12 px-8 text-base font-semibold border-2 border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:border-gray-400 transition-all duration-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        {{ isViewMode ? 'Tutup' : 'Batal' }}
                    </Button>
                    <Button
                        v-if="!isViewMode"
                        type="submit"
                        :disabled="form.processing"
                        class="h-12 px-8 text-base font-semibold bg-gradient-to-r from-blue-600 to-indigo-600 text-white hover:from-blue-700 hover:to-indigo-700 shadow-lg hover:shadow-xl transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed min-w-[120px]"
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