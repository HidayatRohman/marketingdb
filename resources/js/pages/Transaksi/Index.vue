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
import { CreditCard, Calendar, ChevronDown, ChevronUp, Edit, Eye, Filter, Plus, Search, Trash2, User, X, DollarSign } from 'lucide-vue-next';
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
    transaksis: {
        data: Transaksi[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    mitras: Mitra[];
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
            <div class="relative overflow-hidden rounded-xl bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 p-4 text-white sm:p-6">
                <div class="relative z-10">
                    <!-- Header Content - Responsive Layout -->
                    <div class="flex flex-col space-y-4 lg:flex-row lg:items-center lg:justify-between lg:space-y-0">
                        <!-- Title Section -->
                        <div class="flex-1">
                            <h1 class="mb-2 flex items-center gap-2 text-xl font-bold tracking-tight sm:gap-3 sm:text-2xl lg:text-3xl">
                                <CreditCard class="h-5 w-5 sm:h-6 sm:w-6 lg:h-8 lg:w-8" />
                                Manajemen Transaksi
                            </h1>
                            <p class="text-sm text-blue-100 sm:text-base lg:text-lg">Kelola transaksi dan pembayaran dengan mudah</p>
                        </div>
                        
                        <!-- Action Buttons - Responsive -->
                        <div class="flex flex-col space-y-3 sm:flex-row sm:space-x-3 sm:space-y-0 lg:flex-shrink-0">
                            <!-- Add Transaksi Button -->
                            <div v-if="permissions.canCrud">
                                <Button
                                    @click="openCreateModal"
                                    class="w-full border border-white/50 bg-gradient-to-r from-white to-gray-100 px-4 py-2 text-sm font-semibold text-blue-600 shadow-lg transition-all duration-200 hover:from-blue-50 hover:to-white dark:border-gray-700 dark:from-gray-800 dark:to-gray-900 dark:text-blue-400"
                                >
                                    <Plus class="mr-1 h-4 w-4 sm:mr-2" />
                                    <span class="sm:hidden">Tambah</span>
                                    <span class="hidden sm:inline">Tambah Transaksi</span>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="absolute top-0 right-0 -mt-24 -mr-24 h-48 w-48 rounded-full bg-white/10"></div>
                <div class="absolute bottom-0 left-0 -mb-16 -ml-16 h-32 w-32 rounded-full bg-white/5"></div>
            </div>

            <!-- Statistics Bar -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card class="border-0 bg-gradient-to-br from-blue-50 to-blue-100 shadow-md dark:from-blue-950 dark:to-blue-900">
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="mb-1 text-sm font-medium text-blue-700 dark:text-blue-300">Total Transaksi</p>
                                <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ transaksiData.total }}</p>
                            </div>
                            <div class="rounded-lg bg-blue-500 p-2">
                                <CreditCard class="h-5 w-5 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-green-50 to-green-100 shadow-md dark:from-green-950 dark:to-green-900">
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="mb-1 text-sm font-medium text-green-700 dark:text-green-300">Total Nominal</p>
                                <p class="text-lg font-bold text-green-900 dark:text-green-100">
                                    {{ formatCurrency(transaksiData.data.reduce((sum, t) => sum + t.nominal_masuk, 0)) }}
                                </p>
                            </div>
                            <div class="rounded-lg bg-green-500 p-2">
                                <DollarSign class="h-5 w-5 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-purple-50 to-purple-100 shadow-md dark:from-purple-950 dark:to-purple-900">
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="mb-1 text-sm font-medium text-purple-700 dark:text-purple-300">Bulan Ini</p>
                                <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">
                                    {{ transaksiData.data.filter(t => new Date(t.tanggal_tf).getMonth() === new Date().getMonth()).length }}
                                </p>
                            </div>
                            <div class="rounded-lg bg-purple-500 p-2">
                                <Calendar class="h-5 w-5 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-orange-50 to-orange-100 shadow-md dark:from-orange-950 dark:to-orange-900">
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="mb-1 text-sm font-medium text-orange-700 dark:text-orange-300">Rata-rata</p>
                                <p class="text-lg font-bold text-orange-900 dark:text-orange-100">
                                    {{ transaksiData.total > 0 ? formatCurrency(transaksiData.data.reduce((sum, t) => sum + t.nominal_masuk, 0) / transaksiData.total) : formatCurrency(0) }}
                                </p>
                            </div>
                            <div class="rounded-lg bg-orange-500 p-2">
                                <User class="h-5 w-5 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Search and Filters -->
            <Card class="border-0 shadow-md">
                <CardContent class="p-4">
                    <div class="space-y-4">
                        <!-- Search Bar -->
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="relative flex-1">
                                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    v-model="search"
                                    placeholder="Cari transaksi, mitra, atau marketing..."
                                    class="pl-10 pr-4"
                                />
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    variant="outline"
                                    @click="toggleFilters"
                                    class="flex items-center gap-2"
                                >
                                    <Filter class="h-4 w-4" />
                                    Filter
                                    <ChevronDown v-if="!showFilters" class="h-4 w-4" />
                                    <ChevronUp v-else class="h-4 w-4" />
                                </Button>
                                <Button
                                    v-if="search || statusPembayaran || periodeStart || periodeEnd"
                                    variant="ghost"
                                    @click="clearFilters"
                                    class="flex items-center gap-2"
                                >
                                    <X class="h-4 w-4" />
                                    Clear
                                </Button>
                            </div>
                        </div>

                        <!-- Advanced Filters -->
                        <div v-if="showFilters" class="grid gap-4 border-t pt-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <label class="mb-2 block text-sm font-medium">Status Pembayaran</label>
                                <Select v-model="statusPembayaran">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">Semua Status</SelectItem>
                                        <SelectItem v-for="status in statusOptions" :key="status.value" :value="status.value">
                                            {{ status.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium">Tanggal Mulai</label>
                                <DatePicker v-model="periodeStart" placeholder="Pilih tanggal mulai" />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium">Tanggal Akhir</label>
                                <DatePicker v-model="periodeEnd" placeholder="Pilih tanggal akhir" />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium">Per Halaman</label>
                                <Select v-model="perPage">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in perPageOptions" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Transaksi Table -->
            <Card class="border-0 shadow-md">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <CreditCard class="h-5 w-5" />
                        Daftar Transaksi
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/50">
                                    <TableHead class="w-[100px]">No</TableHead>
                                    <TableHead>Marketing</TableHead>
                                    <TableHead>Mitra</TableHead>
                                    <TableHead>Tanggal TF</TableHead>
                                    <TableHead>Paket</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Nominal</TableHead>
                                    <TableHead>Provinsi</TableHead>
                                    <TableHead class="text-center">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="transaksiData.data.length === 0">
                                    <TableCell colspan="9" class="text-center py-8">
                                        <div class="flex flex-col items-center gap-2">
                                            <CreditCard class="h-12 w-12 text-muted-foreground" />
                                            <p class="text-muted-foreground">Tidak ada transaksi ditemukan</p>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-for="(transaksi, index) in transaksiData.data" :key="transaksi.id" class="hover:bg-muted/50">
                                    <TableCell class="font-medium">
                                        {{ (transaksiData.current_page - 1) * transaksiData.per_page + index + 1 }}
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <User class="h-4 w-4 text-muted-foreground" />
                                            <span class="font-medium">{{ transaksi.user.name }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div>
                                            <p class="font-medium">{{ transaksi.mitra.nama }}</p>
                                            <p class="text-sm text-muted-foreground">{{ transaksi.no_wa }}</p>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <span class="text-sm">{{ formatDate(transaksi.tanggal_tf) }}</span>
                                    </TableCell>
                                    <TableCell>
                                        <div>
                                            <p class="font-medium">{{ transaksi.nama_paket }}</p>
                                            <p class="text-sm text-muted-foreground">{{ transaksi.paketBrand.nama }}</p>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="getStatusBadgeVariant(transaksi.status_pembayaran)">
                                            {{ transaksi.status_pembayaran }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <span class="font-medium">{{ formatCurrency(transaksi.nominal_masuk) }}</span>
                                    </TableCell>
                                    <TableCell>
                                        <span class="text-sm">{{ transaksi.provinsi }}</span>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex justify-center gap-2">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click="openViewModal(transaksi)"
                                                class="h-9 w-9 border border-blue-300 bg-gradient-to-r from-blue-100 to-blue-200 p-0 text-blue-700 transition-all duration-200 hover:from-blue-200 hover:to-blue-300"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                v-if="permissions.canCrud || (permissions.canOnlyViewOwn && transaksi.user_id === currentUser.id)"
                                                variant="ghost"
                                                size="sm"
                                                @click="openEditModal(transaksi)"
                                                class="h-9 w-9 border border-green-300 bg-gradient-to-r from-green-100 to-green-200 p-0 text-green-700 transition-all duration-200 hover:from-green-200 hover:to-green-300"
                                            >
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                v-if="permissions.canCrud || (permissions.canOnlyViewOwn && transaksi.user_id === currentUser.id)"
                                                variant="ghost"
                                                size="sm"
                                                @click="openDeleteModal(transaksi)"
                                                class="h-9 w-9 border border-red-300 bg-gradient-to-r from-red-100 to-red-200 p-0 text-red-700 transition-all duration-200 hover:from-red-200 hover:to-red-300"
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
                    <div class="mt-4 flex flex-col items-center justify-between gap-3 rounded-lg bg-muted/20 p-3 sm:flex-row">
                        <div class="text-sm text-foreground/80 dark:text-foreground/90">
                            Menampilkan <span class="font-medium text-foreground">{{ transaksiData.data.length }}</span> dari
                            <span class="font-medium text-foreground">{{ transaksiData.total }}</span> transaksi
                            <span v-if="transaksiData.total > 0" class="text-foreground/70 dark:text-foreground/80">
                                ({{ (transaksiData.current_page - 1) * transaksiData.per_page + 1 }} -
                                {{ Math.min(transaksiData.current_page * transaksiData.per_page, transaksiData.total) }})
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <!-- First Page -->
                            <Button
                                v-if="transaksiData.current_page > 2"
                                variant="outline"
                                size="sm"
                                @click="router.get('/transaksis', { ...getFilterParams(), page: 1 })"
                                class="h-9 w-9 p-0"
                            >
                                1
                            </Button>

                            <!-- Dots if there's a gap -->
                            <span v-if="transaksiData.current_page > 3" class="px-2 text-foreground/60">...</span>

                            <!-- Previous Page -->
                            <Button
                                v-if="transaksiData.prev_page_url"
                                variant="outline"
                                size="sm"
                                @click="router.get(transaksiData.prev_page_url)"
                                class="h-9 px-3"
                            >
                                ← Prev
                            </Button>

                            <!-- Current Page -->
                            <Button
                                variant="default"
                                size="sm"
                                class="h-9 w-9 p-0"
                                disabled
                            >
                                {{ transaksiData.current_page }}
                            </Button>

                            <!-- Next Page -->
                            <Button
                                v-if="transaksiData.next_page_url"
                                variant="outline"
                                size="sm"
                                @click="router.get(transaksiData.next_page_url)"
                                class="h-9 px-3"
                            >
                                Next →
                            </Button>

                            <!-- Dots if there's a gap -->
                            <span v-if="transaksiData.current_page < transaksiData.last_page - 2" class="px-2 text-foreground/60">...</span>

                            <!-- Last Page -->
                            <Button
                                v-if="transaksiData.current_page < transaksiData.last_page - 1"
                                variant="outline"
                                size="sm"
                                @click="router.get('/transaksis', { ...getFilterParams(), page: transaksiData.last_page })"
                                class="h-9 w-9 p-0"
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
            :mitras="mitras"
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