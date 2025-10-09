<script setup lang="ts">
import TransaksiDeleteModal from '@/components/TransaksiDeleteModal.vue';
import TransaksiModal from '@/components/TransaksiModal.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DatePicker from '@/components/ui/datepicker/DatePicker.vue';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import Table from '@/components/ui/table/Table.vue';
import TableBody from '@/components/ui/table/TableBody.vue';
import TableCell from '@/components/ui/table/TableCell.vue';
import TableHead from '@/components/ui/table/TableHead.vue';
import TableHeader from '@/components/ui/table/TableHeader.vue';
import TableRow from '@/components/ui/table/TableRow.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { CreditCard, Calendar, ChevronDown, ChevronUp, Edit, Eye, Filter, Plus, Search, Trash2, User, X, DollarSign, Phone } from 'lucide-vue-next';
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { debounce } from 'lodash';

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
    transaksis: {
        data: Transaksi[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    brands: Brand[];
    currentUser: {
        id: number;
        name: string;
        role: string;
    };
    filters: {
        search?: string;
        status_pembayaran?: string;
        periode_start?: string;
        periode_end?: string;
        per_page?: number;
    };
    permissions: {
        canCrud: boolean;
        canOnlyView: boolean;
        canOnlyViewOwn: boolean;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const statusPembayaran = ref(props.filters.status_pembayaran || '');
const periodeStart = ref(props.filters.periode_start || '');
const periodeEnd = ref(props.filters.periode_end || '');
const perPage = ref(props.filters.per_page || 10);
const showFilters = ref(false);

// Modal states
const transaksiModal = ref({
    open: false,
    mode: 'create' as 'create' | 'edit' | 'view',
    transaksi: undefined as Transaksi | undefined,
});

const deleteModal = ref({
    open: false,
    transaksi: undefined as Transaksi | undefined,
});

// Breadcrumbs
const breadcrumbs = [
    { label: 'Dashboard', href: '/dashboard' },
    { label: 'Transaksi', href: '/transaksis' },
];

// Computed property untuk memastikan data transaksis reactive
const transaksiData = computed(() => props.transaksis);

// Debounced search function
const debouncedSearch = debounce(() => {
    router.get(
        '/transaksis',
        {
            search: search.value || undefined,
            status_pembayaran: statusPembayaran.value || undefined,
            periode_start: periodeStart.value || undefined,
            periode_end: periodeEnd.value || undefined,
            per_page: perPage.value || 10,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}, 300);

// Watch for filter changes
watch([search, statusPembayaran, periodeStart, periodeEnd, perPage], () => {
    debouncedSearch();
});

// Modal functions
const openCreateModal = () => {
    transaksiModal.value = {
        open: true,
        mode: 'create',
        transaksi: undefined,
    };
};

const openEditModal = (transaksi: Transaksi) => {
    transaksiModal.value = {
        open: true,
        mode: 'edit',
        transaksi: { ...transaksi },
    };
};

const openViewModal = (transaksi: Transaksi) => {
    transaksiModal.value = {
        open: true,
        mode: 'view',
        transaksi: { ...transaksi },
    };
};

const openDeleteModal = (transaksi: Transaksi) => {
    deleteModal.value = {
        open: true,
        transaksi: { ...transaksi },
    };
};

const closeTransaksiModal = () => {
    transaksiModal.value = {
        open: false,
        mode: 'create',
        transaksi: undefined,
    };
};

const closeDeleteModal = () => {
    deleteModal.value = {
        open: false,
        transaksi: undefined,
    };
};

const handleModalSuccess = () => {
    // Refresh the page data
    router.reload({ only: ['transaksis'] });
};

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

const getStatusBadgeVariant = (status: string) => {
    switch (status) {
        case 'Dp / TJ':
            return 'default';
        case 'Tambahan Dp':
            return 'secondary';
        case 'Pelunasan':
            return 'destructive';
        default:
            return 'outline';
    }
};

const clearFilters = () => {
    search.value = '';
    statusPembayaran.value = '';
    periodeStart.value = '';
    periodeEnd.value = '';
    perPage.value = 10;
    showFilters.value = false;
};

const toggleFilters = () => {
    showFilters.value = !showFilters.value;
};

const getFilterParams = () => {
    return {
        search: search.value || undefined,
        status_pembayaran: statusPembayaran.value || undefined,
        periode_start: periodeStart.value || undefined,
        periode_end: periodeEnd.value || undefined,
        per_page: perPage.value || 10,
    };
};

// Status pembayaran options
const statusOptions = [
    { value: 'Dp / TJ', label: 'Dp / TJ' },
    { value: 'Tambahan Dp', label: 'Tambahan Dp' },
    { value: 'Pelunasan', label: 'Pelunasan' },
];

// Per page options
const perPageOptions = [
    { value: 10, label: '10' },
    { value: 25, label: '25' },
    { value: 50, label: '50' },
    { value: 100, label: '100' },
];
</script>

<template>
    <Head title="Transaksi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-6 mt-6 space-y-6">
            <!-- Header Section -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-700 p-6 text-white shadow-2xl sm:p-8">
                <div class="relative z-10">
                    <!-- Header Content - Responsive Layout -->
                    <div class="flex flex-col space-y-6 lg:flex-row lg:items-center lg:justify-between lg:space-y-0">
                        <!-- Title Section -->
                        <div class="flex-1">
                            <div class="mb-4 inline-flex items-center rounded-full bg-white/20 px-4 py-2 backdrop-blur-sm">
                                <CreditCard class="mr-2 h-5 w-5" />
                                <span class="text-sm font-medium">Dashboard Transaksi</span>
                            </div>
                            <h1 class="mb-3 text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                                Manajemen Transaksi
                            </h1>
                            <p class="text-lg text-blue-100 opacity-90 sm:text-xl">Kelola transaksi dan pembayaran dengan mudah dan efisien</p>
                        </div>
                        
                        <!-- Action Buttons - Responsive -->
                        <div class="flex flex-col space-y-3 sm:flex-row sm:space-x-4 sm:space-y-0 lg:flex-shrink-0">
                            <!-- Add Transaksi Button -->
                            <div v-if="permissions.canCrud">
                                <Button
                                    @click="openCreateModal"
                                    class="group relative w-full overflow-hidden rounded-xl border-2 border-white/30 bg-white/10 px-6 py-3 text-base font-semibold text-white backdrop-blur-sm transition-all duration-300 hover:bg-white/20 hover:scale-105 hover:shadow-xl dark:border-white/20 dark:bg-white/5 dark:hover:bg-white/10"
                                >
                                    <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/20 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                                    <Plus class="mr-2 h-5 w-5" />
                                    <span class="relative z-10">Tambah Transaksi Baru</span>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Enhanced Background Elements -->
                <div class="absolute top-0 right-0 -mt-32 -mr-32 h-64 w-64 rounded-full bg-gradient-to-br from-white/20 to-white/5 blur-3xl"></div>
                <div class="absolute bottom-0 left-0 -mb-24 -ml-24 h-48 w-48 rounded-full bg-gradient-to-tr from-purple-400/20 to-blue-400/20 blur-2xl"></div>
                <div class="absolute top-1/2 right-1/4 h-32 w-32 rounded-full bg-gradient-to-br from-indigo-400/10 to-purple-400/10 blur-xl"></div>
            </div>

            <!-- Statistics Bar -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <Card class="group relative overflow-hidden border-0 bg-gradient-to-br from-blue-50 to-blue-100 shadow-lg transition-all duration-300 hover:shadow-xl hover:scale-105 dark:from-blue-900/20 dark:to-blue-800/20 dark:border dark:border-blue-700/30">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-blue-700 dark:text-blue-300 mb-2">Total Transaksi</p>
                                <p class="text-3xl font-bold text-blue-900 dark:text-blue-100">{{ transaksiData.total }}</p>
                                <div class="mt-2 h-1 w-full bg-blue-200 dark:bg-blue-800 rounded-full">
                                    <div class="h-full w-3/4 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full transition-all duration-1000"></div>
                                </div>
                            </div>
                            <div class="ml-4 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 p-4 shadow-lg group-hover:scale-110 transition-transform duration-300">
                                <CreditCard class="h-7 w-7 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="group relative overflow-hidden border-0 bg-gradient-to-br from-emerald-50 to-emerald-100 shadow-lg transition-all duration-300 hover:shadow-xl hover:scale-105 dark:from-emerald-900/20 dark:to-emerald-800/20 dark:border dark:border-emerald-700/30">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-300 mb-2">Total Nominal</p>
                                <p class="text-2xl font-bold text-emerald-900 dark:text-emerald-100">
                                    {{ formatCurrency(transaksiData.data.reduce((sum, t) => sum + t.nominal_masuk, 0)) }}
                                </p>
                                <div class="mt-2 h-1 w-full bg-emerald-200 dark:bg-emerald-800 rounded-full">
                                    <div class="h-full w-4/5 bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-full transition-all duration-1000"></div>
                                </div>
                            </div>
                            <div class="ml-4 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 p-4 shadow-lg group-hover:scale-110 transition-transform duration-300">
                                <DollarSign class="h-7 w-7 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="group relative overflow-hidden border-0 bg-gradient-to-br from-purple-50 to-purple-100 shadow-lg transition-all duration-300 hover:shadow-xl hover:scale-105 dark:from-purple-900/20 dark:to-purple-800/20 dark:border dark:border-purple-700/30">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-purple-700 dark:text-purple-300 mb-2">Bulan Ini</p>
                                <p class="text-3xl font-bold text-purple-900 dark:text-purple-100">
                                    {{ transaksiData.data.filter(t => new Date(t.tanggal_tf).getMonth() === new Date().getMonth()).length }}
                                </p>
                                <div class="mt-2 h-1 w-full bg-purple-200 dark:bg-purple-800 rounded-full">
                                    <div class="h-full w-2/3 bg-gradient-to-r from-purple-500 to-purple-600 rounded-full transition-all duration-1000"></div>
                                </div>
                            </div>
                            <div class="ml-4 rounded-2xl bg-gradient-to-br from-purple-500 to-purple-600 p-4 shadow-lg group-hover:scale-110 transition-transform duration-300">
                                <Calendar class="h-7 w-7 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="group relative overflow-hidden border-0 bg-gradient-to-br from-amber-50 to-amber-100 shadow-lg transition-all duration-300 hover:shadow-xl hover:scale-105 dark:from-amber-900/20 dark:to-amber-800/20 dark:border dark:border-amber-700/30">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-amber-700 dark:text-amber-300 mb-2">Rata-rata</p>
                                <p class="text-2xl font-bold text-amber-900 dark:text-amber-100">
                                    {{ transaksiData.total > 0 ? formatCurrency(transaksiData.data.reduce((sum, t) => sum + t.nominal_masuk, 0) / transaksiData.total) : formatCurrency(0) }}
                                </p>
                                <div class="mt-2 h-1 w-full bg-amber-200 dark:bg-amber-800 rounded-full">
                                    <div class="h-full w-5/6 bg-gradient-to-r from-amber-500 to-amber-600 rounded-full transition-all duration-1000"></div>
                                </div>
                            </div>
                            <div class="ml-4 rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 p-4 shadow-lg group-hover:scale-110 transition-transform duration-300">
                                <User class="h-7 w-7 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Search and Filters -->
            <Card class="group border-0 bg-white/80 backdrop-blur-sm shadow-xl transition-all duration-300 hover:shadow-2xl dark:bg-gray-900/80 dark:border dark:border-gray-700/50">
                <CardContent class="p-6">
                    <div class="space-y-6">
                        <!-- Search Bar -->
                        <div class="flex flex-col space-y-4 sm:flex-row sm:space-x-6 sm:space-y-0">
                            <div class="flex-1 relative group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-all duration-200 group-focus-within:text-blue-500">
                                    <Search class="h-5 w-5 text-gray-400 group-focus-within:text-blue-500 dark:group-focus-within:text-blue-400 transition-colors duration-200" />
                                </div>
                                <Input
                                    v-model="search"
                                    placeholder="Cari berdasarkan nama mitra, marketing, atau nomor WhatsApp..."
                                    class="w-full pl-12 pr-4 py-3 text-base border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 hover:shadow-lg focus:shadow-xl transform focus:scale-[1.02] dark:border-gray-600 dark:focus:border-blue-400 dark:bg-gray-800/50"
                                />
                                <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-blue-500/10 to-purple-500/10 opacity-0 transition-opacity duration-300 group-focus-within:opacity-100 pointer-events-none"></div>
                            </div>
                            <div class="flex space-x-3">
                                <Button
                                    @click="toggleFilters"
                                    :class="[
                                        'group relative overflow-hidden flex items-center space-x-2 px-6 py-3 rounded-xl font-semibold transition-all duration-300 border-2 transform hover:scale-105',
                                        showFilters 
                                            ? 'bg-blue-500 text-white border-blue-500 shadow-lg hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700' 
                                            : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50 hover:border-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700'
                                    ]"
                                >
                                    <div class="absolute inset-0 bg-gradient-to-r from-white/20 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                                    <Filter class="h-5 w-5 transition-transform duration-200 group-hover:rotate-12" />
                                    <span>{{ showFilters ? 'Sembunyikan Filter' : 'Tampilkan Filter' }}</span>
                                    <ChevronDown v-if="!showFilters" class="h-4 w-4 transition-transform duration-200 group-hover:scale-110" />
                                    <ChevronUp v-else class="h-4 w-4 transition-transform duration-200 group-hover:scale-110" />
                                </Button>
                                <Button
                                    v-if="search || statusPembayaran || periodeStart || periodeEnd"
                                    @click="clearFilters"
                                    class="group relative overflow-hidden flex items-center space-x-2 px-6 py-3 rounded-xl font-semibold bg-white text-red-600 border-2 border-red-200 hover:bg-red-50 hover:border-red-300 transition-all duration-300 transform hover:scale-105 dark:bg-gray-800 dark:text-red-400 dark:border-red-700/50 dark:hover:bg-red-900/20"
                                >
                                    <div class="absolute inset-0 bg-gradient-to-r from-red-500/10 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                                    <X class="h-5 w-5 transition-transform duration-200 group-hover:rotate-90" />
                                    <span>Bersihkan</span>
                                </Button>
                            </div>
                        </div>

                        <!-- Advanced Filters -->
                        <Transition
                            enter-active-class="transition-all duration-500 ease-out"
                            enter-from-class="opacity-0 transform -translate-y-4 scale-95"
                            enter-to-class="opacity-100 transform translate-y-0 scale-100"
                            leave-active-class="transition-all duration-300 ease-in"
                            leave-from-class="opacity-100 transform translate-y-0 scale-100"
                            leave-to-class="opacity-0 transform -translate-y-4 scale-95"
                        >
                            <div v-if="showFilters" class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 p-6 shadow-lg border-t-2 border-blue-200/50 mt-6 dark:from-blue-900/20 dark:via-purple-900/20 dark:to-pink-900/20 dark:border-blue-700/50">
                                <div class="absolute inset-0 bg-gradient-to-r from-blue-500/5 via-purple-500/5 to-pink-500/5 animate-pulse"></div>
                                <div class="relative grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                                    <!-- Status Pembayaran Filter -->
                                    <div class="group space-y-3">
                                        <label class="flex items-center space-x-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                                            <span class="text-lg">💳</span>
                                            <span>Status Pembayaran</span>
                                        </label>
                                        <Select v-model="statusPembayaran">
                                            <SelectTrigger class="w-full p-3 border-2 border-blue-200 rounded-xl bg-white/80 backdrop-blur-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-200 transition-all duration-300 hover:shadow-lg dark:border-blue-600 dark:focus:border-blue-400 dark:bg-gray-800/80">
                                                <SelectValue placeholder="🔍 Pilih status" />
                                            </SelectTrigger>
                                            <SelectContent class="rounded-xl border-2 border-blue-200 shadow-xl">
                                                <SelectItem value="">🔍 Semua Status</SelectItem>
                                                <SelectItem v-for="status in statusOptions" :key="status.value" :value="status.value">
                                                    {{ status.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <!-- Tanggal Mulai Filter -->
                                    <div class="group space-y-3">
                                        <label class="flex items-center space-x-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                                            <span class="text-lg">📅</span>
                                            <span>Tanggal Mulai</span>
                                        </label>
                                        <DatePicker 
                                            v-model="periodeStart" 
                                            placeholder="Pilih tanggal mulai"
                                            class="w-full p-3 border-2 border-purple-200 rounded-xl bg-white/80 backdrop-blur-sm focus:border-purple-500 focus:ring-4 focus:ring-purple-200 transition-all duration-300 hover:shadow-lg dark:border-purple-600 dark:focus:border-purple-400 dark:bg-gray-800/80"
                                        />
                                    </div>

                                    <!-- Tanggal Akhir Filter -->
                                    <div class="group space-y-3">
                                        <label class="flex items-center space-x-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                                            <span class="text-lg">📅</span>
                                            <span>Tanggal Akhir</span>
                                        </label>
                                        <DatePicker 
                                            v-model="periodeEnd" 
                                            placeholder="Pilih tanggal akhir"
                                            class="w-full p-3 border-2 border-purple-200 rounded-xl bg-white/80 backdrop-blur-sm focus:border-purple-500 focus:ring-4 focus:ring-purple-200 transition-all duration-300 hover:shadow-lg dark:border-purple-600 dark:focus:border-purple-400 dark:bg-gray-800/80"
                                        />
                                    </div>

                                    <!-- Per Page Filter -->
                                    <div class="group space-y-3">
                                        <label class="flex items-center space-x-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                                            <span class="text-lg">📄</span>
                                            <span>Per Halaman</span>
                                        </label>
                                        <Select v-model="perPage">
                                            <SelectTrigger class="w-full p-3 border-2 border-pink-200 rounded-xl bg-white/80 backdrop-blur-sm focus:border-pink-500 focus:ring-4 focus:ring-pink-200 transition-all duration-300 hover:shadow-lg dark:border-pink-600 dark:focus:border-pink-400 dark:bg-gray-800/80">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent class="rounded-xl border-2 border-pink-200 shadow-xl">
                                                <SelectItem v-for="option in perPageOptions" :key="option.value" :value="option.value">
                                                    {{ option.label }} item
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </CardContent>
            </Card>

            <!-- Transaksi Table -->
            <Card class="border-0 bg-white/90 backdrop-blur-sm shadow-2xl dark:bg-gray-900/90 dark:border dark:border-gray-700/50">
                <CardHeader class="border-b-2 border-gradient-to-r from-blue-500 to-purple-500 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 p-6">
                    <div class="flex items-center justify-between">
                        <CardTitle class="flex items-center space-x-3 text-2xl font-bold text-gray-900 dark:text-white">
                            <div class="rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 p-3">
                                <CreditCard class="h-6 w-6 text-white" />
                            </div>
                            <span>Daftar Transaksi</span>
                        </CardTitle>
                        <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                            <span class="rounded-full bg-blue-100 px-3 py-1 font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                {{ transaksiData.total }} Total
                            </span>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow class="border-b border-gray-200 dark:border-gray-700">
                                    <TableHead class="w-16 text-center font-bold text-gray-700 dark:text-gray-300 py-4 px-3">No</TableHead>
                                    <TableHead class="font-bold text-gray-700 dark:text-gray-300 py-4 px-3">Marketing</TableHead>
                                    <TableHead class="font-bold text-gray-700 dark:text-gray-300 py-4 px-3">Nama Mitra</TableHead>
                                    <TableHead class="font-bold text-gray-700 dark:text-gray-300 py-4 px-3">No Whatsapp</TableHead>
                                    <TableHead class="font-bold text-gray-700 dark:text-gray-300 py-4 px-3">Tgl Transfer</TableHead>
                                    <TableHead class="font-bold text-gray-700 dark:text-gray-300 py-4 px-3">Tanggal Masuk Lead</TableHead>
                                    <TableHead class="font-bold text-gray-700 dark:text-gray-300 py-4 px-3">Periode Lead</TableHead>
                                    <TableHead class="font-bold text-gray-700 dark:text-gray-300 py-4 px-3">Usia</TableHead>
                                    <TableHead class="font-bold text-gray-700 dark:text-gray-300 py-4 px-3">Sumber</TableHead>
                                    <TableHead class="font-bold text-gray-700 dark:text-gray-300 py-4 px-3">Kabupaten</TableHead>
                                    <TableHead class="font-bold text-gray-700 dark:text-gray-300 py-4 px-3">Provinsi</TableHead>
                                    <TableHead class="font-bold text-gray-700 dark:text-gray-300 py-4 px-3">Paket Brand</TableHead>
                                    <TableHead class="font-bold text-gray-700 dark:text-gray-300 py-4 px-3">Lead Awal</TableHead>
                                    <TableHead class="font-bold text-gray-700 dark:text-gray-300 py-4 px-3">Nama Paket</TableHead>
                                    <TableHead class="font-bold text-gray-700 dark:text-gray-300 py-4 px-3">Status Pembayaran</TableHead>
                                    <TableHead class="font-bold text-gray-700 dark:text-gray-300 py-4 px-3">Nominal Masuk</TableHead>
                                    <TableHead class="font-bold text-gray-700 dark:text-gray-300 py-4 px-3">Harga Paket</TableHead>
                                    <TableHead class="w-32 text-center font-bold text-gray-700 dark:text-gray-300 py-4 px-3">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="transaksiData.data.length === 0" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <TableCell colspan="18" class="py-16 text-center">
                                        <div class="flex flex-col items-center space-y-4">
                                            <div class="rounded-full bg-gray-100 p-6 dark:bg-gray-800">
                                                <Search class="h-12 w-12 text-gray-400" />
                                            </div>
                                            <div class="space-y-2">
                                                <p class="text-lg font-semibold text-gray-600 dark:text-gray-400">Tidak ada transaksi ditemukan</p>
                                                <p class="text-sm text-gray-500 dark:text-gray-500">Coba ubah filter pencarian atau tambah transaksi baru</p>
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-for="(transaksi, index) in transaksiData.data" :key="transaksi.id" class="border-b border-gray-200 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800/50">
                                    
                                    <!-- No -->
                                     <TableCell class="w-16 text-center font-medium text-gray-700 dark:text-gray-300 py-4 px-3">
                                         {{ (transaksiData.current_page - 1) * transaksiData.per_page + index + 1 }}
                                     </TableCell>
                                    <!-- Marketing -->
                                     <TableCell class="relative font-semibold text-gray-800 dark:text-gray-200 py-4 px-3">
                                         <div class="flex items-center space-x-3">
                                             <div class="relative h-10 w-10 rounded-full bg-gradient-to-br from-emerald-400 via-blue-500 to-purple-600 flex items-center justify-center text-white text-sm font-bold shadow-lg transition-all duration-200 group-hover:scale-110 group-hover:shadow-xl group-hover:rotate-12">
                                                 <span class="relative z-10">{{ (transaksi.user?.name || 'U').charAt(0).toUpperCase() }}</span>
                                                 <div class="absolute inset-0 rounded-full bg-gradient-to-br from-white/20 to-transparent opacity-0 transition-opacity duration-200 group-hover:opacity-100"></div>
                                             </div>
                                             <div class="flex flex-col">
                                                 <span class="font-bold text-gray-800 dark:text-gray-200 transition-colors duration-200 group-hover:text-blue-600 dark:group-hover:text-blue-400">{{ transaksi.user.name }}</span>
                                                 <span class="text-xs text-gray-500 dark:text-gray-400">Marketing</span>
                                             </div>
                                         </div>
                                     </TableCell>
                                    <!-- Nama Mitra -->
                                    <TableCell class="font-medium text-gray-700 dark:text-gray-300 py-4 px-3">
                                        <span class="text-sm">{{ transaksi.nama_mitra || '-' }}</span>
                                    </TableCell>
                                    <!-- No Whatsapp -->
                                    <TableCell class="font-medium text-gray-700 dark:text-gray-300 py-4 px-3">
                                        <div class="flex items-center gap-2">
                                            <Phone class="h-4 w-4 text-muted-foreground" />
                                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                                {{ transaksi.no_wa || '-' }}
                                            </span>
                                        </div>
                                    </TableCell>
                                    <!-- Tgl Transfer -->
                                    <TableCell class="font-medium text-gray-700 dark:text-gray-300 py-4 px-3">
                                        <span class="text-sm">{{ formatDate(transaksi.tanggal_tf) }}</span>
                                    </TableCell>
                                    <!-- Tanggal Masuk Lead -->
                                    <TableCell class="font-medium text-gray-700 dark:text-gray-300 py-4 px-3">
                                        <span class="text-sm">{{ formatDate(transaksi.tanggal_lead_masuk) || '-' }}</span>
                                    </TableCell>
                                    <!-- Periode Lead -->
                                    <TableCell class="font-medium text-gray-700 dark:text-gray-300 py-4 px-3">
                                        <span class="text-sm">{{ transaksi.periode_lead || '-' }}</span>
                                    </TableCell>
                                    <!-- Usia -->
                                    <TableCell class="font-medium text-gray-700 dark:text-gray-300 py-4 px-3">
                                        <span class="text-sm">{{ transaksi.usia || '-' }}</span>
                                    </TableCell>
                                    <!-- Sumber -->
                                    <TableCell class="font-medium text-gray-700 dark:text-gray-300 py-4 px-3">
                                        <span class="text-sm">{{ transaksi.sumber || '-' }}</span>
                                    </TableCell>
                                    <!-- Kabupaten -->
                                    <TableCell class="font-medium text-gray-700 dark:text-gray-300 py-4 px-3">
                                        <span class="text-sm">{{ transaksi.kabupaten || '-' }}</span>
                                    </TableCell>
                                    <!-- Provinsi -->
                                    <TableCell class="font-medium text-gray-700 dark:text-gray-300 py-4 px-3">
                                        <span class="text-sm">{{ transaksi.provinsi || '-' }}</span>
                                    </TableCell>
                                    <!-- Paket Brand -->
                                    <TableCell class="font-medium text-gray-700 dark:text-gray-300 py-4 px-3">
                                        <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                            {{ transaksi.paket_brand?.nama || '-' }}
                                        </span>
                                    </TableCell>
                                    <!-- Lead Awal -->
                                    <TableCell class="font-medium text-gray-700 dark:text-gray-300 py-4 px-3">
                                        <span class="text-sm">{{ transaksi.lead_awal_brand?.nama || '-' }}</span>
                                    </TableCell>
                                    <!-- Nama Paket -->
                                    <TableCell class="font-medium text-gray-700 dark:text-gray-300 py-4 px-3">
                                        <span class="text-sm font-medium">{{ transaksi.nama_paket || '-' }}</span>
                                    </TableCell>
                                    <!-- Status Pembayaran -->
                                    <TableCell class="py-4 px-3">
                                        <Badge
                                            :class="[
                                                'px-4 py-2 text-xs font-bold rounded-xl shadow-lg transition-all duration-200 hover:scale-105',
                                                transaksi.status_pembayaran === 'Pelunasan'
                                                    ? 'bg-gradient-to-r from-emerald-500 to-green-600 text-white shadow-emerald-200 dark:shadow-emerald-900/50'
                                                    : transaksi.status_pembayaran === 'Dp / TJ'
                                                    ? 'bg-gradient-to-r from-blue-500 to-indigo-600 text-white shadow-blue-200 dark:shadow-blue-900/50'
                                                    : transaksi.status_pembayaran === 'Tambahan Dp'
                                                    ? 'bg-gradient-to-r from-amber-500 to-orange-600 text-white shadow-amber-200 dark:shadow-amber-900/50'
                                                    : 'bg-gradient-to-r from-gray-500 to-slate-600 text-white shadow-gray-200 dark:shadow-gray-900/50'
                                            ]"
                                        >
                                            <span class="flex items-center space-x-1">
                                                <span v-if="transaksi.status_pembayaran === 'Pelunasan'">✅</span>
                                                <span v-else-if="transaksi.status_pembayaran === 'Dp / TJ'">⏳</span>
                                                <span v-else-if="transaksi.status_pembayaran === 'Tambahan Dp'">💰</span>
                                                <span v-else>❓</span>
                                                <span>{{ transaksi.status_pembayaran }}</span>
                                            </span>
                                        </Badge>
                                    </TableCell>
                                    <!-- Nominal Masuk -->
                                    <TableCell class="py-4 px-3">
                                        <div class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-emerald-100 via-green-50 to-emerald-100 px-4 py-3 text-right shadow-lg transition-all duration-300 hover:shadow-xl dark:from-emerald-900/30 dark:via-green-900/20 dark:to-emerald-900/30">
                                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                                            <span class="relative text-sm font-bold text-emerald-700 dark:text-emerald-300">
                                                {{ formatCurrency(transaksi.nominal_masuk) }}
                                            </span>
                                        </div>
                                    </TableCell>
                                    <!-- Harga Paket -->
                                    <TableCell class="py-4 px-3">
                                        <div class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-blue-100 via-indigo-50 to-blue-100 px-4 py-3 text-right shadow-lg transition-all duration-300 hover:shadow-xl dark:from-blue-900/30 dark:via-indigo-900/20 dark:to-blue-900/30">
                                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                                            <span class="relative text-sm font-bold text-blue-700 dark:text-blue-300">
                                                {{ formatCurrency(transaksi.harga_paket) || '-' }}
                                            </span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="w-32 text-center py-4 px-3">
                                        <div class="flex justify-center space-x-2">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click="openViewModal(transaksi)"
                                                class="h-10 w-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 p-0 text-white shadow-lg transition-all duration-200 hover:scale-110 hover:shadow-xl dark:from-blue-600 dark:to-blue-700"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                v-if="permissions.canCrud || (permissions.canOnlyViewOwn && transaksi.user_id === currentUser.id)"
                                                variant="ghost"
                                                size="sm"
                                                @click="openEditModal(transaksi)"
                                                class="h-10 w-10 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 p-0 text-white shadow-lg transition-all duration-200 hover:scale-110 hover:shadow-xl dark:from-emerald-600 dark:to-emerald-700"
                                            >
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                v-if="permissions.canCrud || (permissions.canOnlyViewOwn && transaksi.user_id === currentUser.id)"
                                                variant="ghost"
                                                size="sm"
                                                @click="openDeleteModal(transaksi)"
                                                class="h-10 w-10 rounded-xl bg-gradient-to-br from-red-500 to-red-600 p-0 text-white shadow-lg transition-all duration-200 hover:scale-110 hover:shadow-xl dark:from-red-600 dark:to-red-700"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Enhanced Pagination -->
                    <div class="mt-6 flex flex-col items-center justify-between gap-6 rounded-2xl bg-gradient-to-r from-blue-50 via-purple-50 to-pink-50 p-6 shadow-lg dark:from-blue-900/20 dark:via-purple-900/20 dark:to-pink-900/20 sm:flex-row">
                        <div class="flex items-center space-x-4">
                            <div class="rounded-xl bg-gradient-to-r from-blue-100 to-purple-100 px-4 py-2 dark:from-blue-900/30 dark:to-purple-900/30">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    📊 Menampilkan <span class="font-bold text-blue-600 dark:text-blue-400">{{ transaksiData.data.length }}</span> dari <span class="font-bold text-purple-600 dark:text-purple-400">{{ transaksiData.total }}</span> transaksi
                                    <span v-if="transaksiData.total > 0" class="text-gray-600 dark:text-gray-400">
                                        ({{ (transaksiData.current_page - 1) * transaksiData.per_page + 1 }} - {{ Math.min(transaksiData.current_page * transaksiData.per_page, transaksiData.total) }})
                                    </span>
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <!-- First Page -->
                            <Button
                                v-if="transaksiData.current_page > 2"
                                @click="router.get('/transaksis', { ...getFilterParams(), page: 1 })"
                                class="h-10 w-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 p-0 text-white shadow-lg transition-all duration-200 hover:scale-110 hover:shadow-xl dark:from-blue-600 dark:to-blue-700"
                            >
                                1
                            </Button>

                            <!-- Dots if there's a gap -->
                            <span v-if="transaksiData.current_page > 3" class="px-2 text-gray-500 dark:text-gray-400">...</span>

                            <!-- Previous Page -->
                            <Button
                                v-if="transaksiData.prev_page_url"
                                @click="router.get(transaksiData.prev_page_url)"
                                class="h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 px-4 text-white shadow-lg transition-all duration-200 hover:scale-105 hover:shadow-xl dark:from-emerald-600 dark:to-emerald-700"
                            >
                                ← Prev
                            </Button>

                            <!-- Current Page -->
                            <div class="rounded-xl bg-gradient-to-r from-purple-500 to-pink-600 px-6 py-2 shadow-lg">
                                <span class="text-sm font-bold text-white">
                                    {{ transaksiData.current_page }} / {{ transaksiData.last_page }}
                                </span>
                            </div>

                            <!-- Next Page -->
                            <Button
                                v-if="transaksiData.next_page_url"
                                @click="router.get(transaksiData.next_page_url)"
                                class="h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 px-4 text-white shadow-lg transition-all duration-200 hover:scale-105 hover:shadow-xl dark:from-emerald-600 dark:to-emerald-700"
                            >
                                Next →
                            </Button>

                            <!-- Dots if there's a gap -->
                            <span v-if="transaksiData.current_page < transaksiData.last_page - 2" class="px-2 text-gray-500 dark:text-gray-400">...</span>

                            <!-- Last Page -->
                            <Button
                                v-if="transaksiData.current_page < transaksiData.last_page - 1"
                                @click="router.get('/transaksis', { ...getFilterParams(), page: transaksiData.last_page })"
                                class="h-10 w-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 p-0 text-white shadow-lg transition-all duration-200 hover:scale-110 hover:shadow-xl dark:from-blue-600 dark:to-blue-700"
                            >
                                {{ transaksiData.last_page }}
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Modals -->
        <TransaksiModal
            :open="transaksiModal.open"
            :mode="transaksiModal.mode"
            :transaksi="transaksiModal.transaksi"
            :brands="brands"
            :current-user="currentUser"
            @close="closeTransaksiModal"
            @success="handleModalSuccess"
        />

        <TransaksiDeleteModal
            :open="deleteModal.open"
            :transaksi="deleteModal.transaksi"
            @close="closeDeleteModal"
            @success="handleModalSuccess"
        />
    </AppLayout>
</template>

<style scoped>
/* Custom styles for responsive design */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Ensure consistent card spacing */
.space-y-6 > * + * {
    margin-top: 1.5rem;
}

/* Improve button touch targets on mobile */
@media (max-width: 640px) {
    button {
        min-height: 44px;
        min-width: 44px;
    }
}
</style>