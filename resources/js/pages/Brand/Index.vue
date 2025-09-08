<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import BrandModal from '@/components/BrandModal.vue';
import BrandDeleteModal from '@/components/BrandDeleteModal.vue';
import ProvinceChart from '@/components/ProvinceChart.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Search, Plus, Edit, Trash2, Eye, Zap, Filter, BarChart3, MapPin } from 'lucide-vue-next';

interface Brand {
    id: number;
    nama: string;
    logo: string | null;
    logo_url: string | null;
    created_at: string;
    updated_at: string;
}

interface ProvinceAnalytics {
    labels: string[];
    data: number[];
    total: number;
    selected_brand: string;
}

interface Props {
    brands: {
        data: Brand[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    provinceAnalytics: ProvinceAnalytics;
    filters: {
        search?: string;
        selected_brand?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const selectedBrand = ref(props.filters.selected_brand || '');

// Modal states
const brandModal = ref({
    open: false,
    mode: 'create' as 'create' | 'edit' | 'view',
    brand: undefined as Brand | undefined,
});

const deleteModal = ref({
    open: false,
    brand: undefined as Brand | undefined,
});

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Brand', href: '/brands' },
];

let debounceTimer: number;

// Watch for filter changes and update URL
watch([search, selectedBrand], () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get('/brands', {
            search: search.value || undefined,
            selected_brand: selectedBrand.value || undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

// Modal functions
const openCreateModal = () => {
    brandModal.value = {
        open: true,
        mode: 'create',
        brand: undefined,
    };
};

const openEditModal = (brand: Brand) => {
    brandModal.value = {
        open: true,
        mode: 'edit',
        brand: { ...brand },
    };
};

const openViewModal = (brand: Brand) => {
    brandModal.value = {
        open: true,
        mode: 'view',
        brand: { ...brand },
    };
};

const openDeleteModal = (brand: Brand) => {
    deleteModal.value = {
        open: true,
        brand: { ...brand },
    };
};

const closeBrandModal = () => {
    brandModal.value = {
        open: false,
        mode: 'create',
        brand: undefined,
    };
};

const closeDeleteModal = () => {
    deleteModal.value = {
        open: false,
        brand: undefined,
    };
};

const handleModalSuccess = () => {
    // Refresh the page data
    router.reload({ only: ['brands'] });
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>

<template>
    <Head title="Brand" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="layout-main">
            <!-- Header Section -->
            <div class="page-header-gradient">
                <div class="page-header-content">
                    <div class="page-header-layout">
                        <div>
                            <h1 class="page-title">
                                <Zap class="h-10 w-10" />
                                Manajemen Brand
                            </h1>
                            <p class="page-subtitle">
                                Kelola brand dengan mudah dan efisien
                            </p>
                        </div>
                        <Button 
                            @click="openCreateModal"
                            class="btn-primary-white"
                        >
                            <Plus class="mr-2 h-5 w-5" />
                            Tambah Brand
                        </Button>
                    </div>
                </div>
                <div class="page-header-decoration-right"></div>
                <div class="page-header-decoration-left"></div>
            </div>

            <!-- Statistics Bar -->
            <div class="stats-grid-3">
                <Card class="stats-card stats-card-violet">
                    <CardContent class="stats-card-content-small">
                        <div class="stats-card-layout">
                            <div>
                                <p class="stats-card-label stats-label-violet">Total Brand</p>
                                <p class="stats-card-value-small stats-value-violet">{{ brands.total }}</p>
                            </div>
                            <div class="stats-card-icon-small stats-icon-violet">
                                <Zap class="h-5 w-5 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
                
                <Card class="stats-card stats-card-purple">
                    <CardContent class="stats-card-content-small">
                        <div class="stats-card-layout">
                            <div>
                                <p class="stats-card-label stats-label-purple">Halaman Ini</p>
                                <p class="stats-card-value-small stats-value-purple">{{ brands.data.length }}</p>
                            </div>
                            <div class="stats-card-icon-small stats-icon-purple">
                                <Eye class="h-5 w-5 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="stats-card stats-card-indigo">
                    <CardContent class="stats-card-content-small">
                        <div class="stats-card-layout">
                            <div>
                                <p class="stats-card-label stats-label-indigo">Dengan Logo</p>
                                <p class="stats-card-value-small stats-value-indigo">
                                    {{ brands.data.filter(b => b.logo).length }}
                                </p>
                            </div>
                            <div class="stats-card-icon-small stats-icon-indigo">
                                <Plus class="h-5 w-5 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Province Analytics Section -->
            <Card class="analytics-card">
                <CardHeader class="analytics-card-header">
                    <CardTitle class="analytics-card-title">
                        <BarChart3 class="h-6 w-6" />
                        Analisa Provinsi per Brand
                    </CardTitle>
                    <p class="analytics-card-subtitle">
                        Distribusi 7 provinsi teratas berdasarkan jumlah mitra
                    </p>
                </CardHeader>
                <CardContent class="analytics-card-content">
                    <!-- Brand Filter -->
                    <div class="mb-6 flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                        <div class="flex items-center gap-3">
                            <MapPin class="h-5 w-5 text-muted-foreground" />
                            <div class="flex flex-col sm:flex-row gap-2">
                                <label for="brand-filter" class="text-sm font-medium text-muted-foreground">
                                    Filter Brand:
                                </label>
                                <select
                                    id="brand-filter"
                                    v-model="selectedBrand"
                                    class="flex h-9 w-full sm:w-48 rounded-md border border-input bg-background px-3 py-1 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="">Semua Brand</option>
                                    <option 
                                        v-for="brand in brands.data" 
                                        :key="brand.id" 
                                        :value="brand.id"
                                    >
                                        {{ brand.nama }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="text-sm text-muted-foreground">
                            Total: {{ provinceAnalytics.total }} mitra
                        </div>
                    </div>

                    <!-- Chart Container -->
                    <div class="chart-wrapper">
                        <ProvinceChart 
                            v-if="provinceAnalytics.labels.length > 0" 
                            :data="provinceAnalytics" 
                        />
                        <div 
                            v-else 
                            class="flex flex-col items-center justify-center h-96 text-muted-foreground"
                        >
                            <BarChart3 class="h-16 w-16 mb-4 opacity-50" />
                            <p class="text-lg font-medium">Tidak ada data</p>
                            <p class="text-sm">Pilih brand untuk melihat analisa provinsi</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Search Bar -->
            <Card class="search-card">
                <CardContent class="search-card-content">
                    <div class="search-layout">
                        <div class="search-input-wrapper">
                            <Search class="search-icon" />
                            <Input
                                v-model="search"
                                placeholder="Cari berdasarkan nama brand..."
                                class="search-input"
                            />
                        </div>
                        <div class="search-actions">
                            <Button variant="outline" size="icon" class="search-action-btn">
                                <Filter class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Table Card -->
            <Card class="table-card">
                <CardHeader class="table-card-header">
                    <CardTitle class="table-card-title">Daftar Brand</CardTitle>
                </CardHeader>
                <CardContent class="table-card-content">
                    <div class="relative overflow-hidden">
                        <div class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow class="hover:bg-transparent border-b border-border">
                                        <TableHead class="font-semibold text-foreground">Nama Brand</TableHead>
                                        <TableHead class="font-semibold text-foreground">Logo</TableHead>
                                        <TableHead class="font-semibold text-foreground">Tanggal Dibuat</TableHead>
                                        <TableHead class="font-semibold text-foreground text-center w-[120px]">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="brand in brands.data" :key="brand.id" class="hover:bg-muted/30 transition-colors">
                                        <TableCell class="font-medium">
                                            <div class="flex items-center gap-3">
                                                <div class="p-2 bg-gradient-to-br from-violet-100 to-purple-100 dark:from-violet-900/30 dark:to-purple-900/30 rounded-lg">
                                                    <Zap class="h-4 w-4 text-violet-600 dark:text-violet-400" />
                                                </div>
                                                <span>{{ brand.nama }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <div v-if="brand.logo_url" class="w-8 h-8 bg-gray-100 dark:bg-gray-800 rounded flex items-center justify-center">
                                                    <img :src="brand.logo_url" :alt="brand.nama" class="w-6 h-6 object-contain" />
                                                </div>
                                                <div v-else class="w-8 h-8 bg-gray-100 dark:bg-gray-800 rounded flex items-center justify-center">
                                                    <Zap class="h-4 w-4 text-gray-400" />
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <div class="p-1 bg-gray-100 dark:bg-gray-800 rounded">
                                                    <svg class="h-4 w-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                                <span class="text-sm">{{ formatDate(brand.created_at) }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex justify-center gap-2">
                                                <Button 
                                                    variant="ghost" 
                                                    size="sm"
                                                    @click="openViewModal(brand)"
                                                    class="h-9 w-9 p-0 hover:bg-blue-100 hover:text-blue-600 dark:hover:bg-blue-900/30"
                                                >
                                                    <Eye class="h-4 w-4" />
                                                </Button>
                                                <Button 
                                                    variant="ghost" 
                                                    size="sm"
                                                    @click="openEditModal(brand)"
                                                    class="h-9 w-9 p-0 hover:bg-green-100 hover:text-green-600 dark:hover:bg-green-900/30"
                                                >
                                                    <Edit class="h-4 w-4" />
                                                </Button>
                                                <Button 
                                                    variant="ghost" 
                                                    size="sm"
                                                    @click="openDeleteModal(brand)"
                                                    class="h-9 w-9 p-0 hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-900/30"
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
                        <div class="mt-6 flex items-center justify-between p-4 bg-muted/30 rounded-lg">
                            <div class="text-sm text-muted-foreground">
                                Menampilkan <span class="font-medium">{{ brands.data.length }}</span> dari <span class="font-medium">{{ brands.total }}</span> brand
                            </div>
                            <div class="flex items-center gap-2">
                                <Button 
                                    v-if="brands.prev_page_url" 
                                    variant="outline" 
                                    size="sm"
                                    @click="router.get(brands.prev_page_url)"
                                    class="h-9"
                                >
                                    ← Previous
                                </Button>
                                <div class="flex items-center gap-1 mx-2">
                                    <span class="text-sm text-muted-foreground">
                                        Page {{ brands.current_page }} of {{ brands.last_page }}
                                    </span>
                                </div>
                                <Button 
                                    v-if="brands.next_page_url" 
                                    variant="outline" 
                                    size="sm"
                                    @click="router.get(brands.next_page_url)"
                                    class="h-9"
                                >
                                    Next →
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Modals -->
        <BrandModal
            :open="brandModal.open"
            :mode="brandModal.mode"
            :brand="brandModal.brand"
            @close="closeBrandModal"
            @success="handleModalSuccess"
        />

        <BrandDeleteModal
            :open="deleteModal.open"
            :brand="deleteModal.brand"
            @close="closeDeleteModal"
            @success="handleModalSuccess"
        />
    </AppLayout>
</template>

<style scoped>
.analytics-card {
    border: none;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    background: linear-gradient(to bottom right, 
        rgba(255, 255, 255, 1) 0%, 
        rgba(219, 234, 254, 0.3) 50%, 
        rgba(221, 214, 254, 0.3) 100%);
}

.dark .analytics-card {
    background: linear-gradient(to bottom right, 
        rgba(17, 24, 39, 1) 0%, 
        rgba(30, 58, 138, 0.1) 50%, 
        rgba(88, 28, 135, 0.1) 100%);
}

.analytics-card-header {
    padding-bottom: 1rem;
}

.analytics-card-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: rgb(17, 24, 39);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.dark .analytics-card-title {
    color: rgb(243, 244, 246);
}

.analytics-card-subtitle {
    font-size: 0.875rem;
    color: rgb(107, 114, 128);
    margin-top: 0.5rem;
}

.analytics-card-content {
    padding-top: 1rem;
}

.chart-wrapper {
    background-color: white;
    border-radius: 0.5rem;
    padding: 1rem;
    border: 1px solid rgb(229, 231, 235);
}

.dark .chart-wrapper {
    background-color: rgba(31, 41, 55, 0.5);
    border-color: rgb(55, 65, 81);
}
</style>