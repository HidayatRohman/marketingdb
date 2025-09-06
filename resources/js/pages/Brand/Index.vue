<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import BrandModal from '@/components/BrandModal.vue';
import BrandDeleteModal from '@/components/BrandDeleteModal.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Search, Plus, Edit, Trash2, Eye, Zap, Filter } from 'lucide-vue-next';

interface Brand {
    id: number;
    nama: string;
    logo: string | null;
    logo_url: string | null;
    created_at: string;
    updated_at: string;
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
    filters: {
        search?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');

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
watch([search], () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get('/brands', {
            search: search.value || undefined,
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
        <div class="space-y-8">
            <!-- Header Section -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-violet-600 via-purple-600 to-indigo-600 p-8 text-white">
                <div class="relative z-10">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-4xl font-bold tracking-tight mb-2 flex items-center gap-3">
                                <Zap class="h-10 w-10" />
                                Manajemen Brand
                            </h1>
                            <p class="text-xl text-purple-100">
                                Kelola brand dengan mudah dan efisien
                            </p>
                        </div>
                        <Button 
                            @click="openCreateModal"
                            class="bg-white text-purple-600 hover:bg-purple-50 font-semibold shadow-lg"
                        >
                            <Plus class="mr-2 h-5 w-5" />
                            Tambah Brand
                        </Button>
                    </div>
                </div>
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/5 rounded-full -ml-24 -mb-24"></div>
            </div>

            <!-- Statistics Bar -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card class="border-0 shadow-lg bg-gradient-to-br from-violet-50 to-violet-100 dark:from-violet-950 dark:to-violet-900">
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-violet-700 dark:text-violet-300">Total Brand</p>
                                <p class="text-2xl font-bold text-violet-900 dark:text-violet-100">{{ brands.total }}</p>
                            </div>
                            <div class="p-2 bg-violet-500 rounded-lg">
                                <Zap class="h-5 w-5 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
                
                <Card class="border-0 shadow-lg bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950 dark:to-purple-900">
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-purple-700 dark:text-purple-300">Halaman Ini</p>
                                <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ brands.data.length }}</p>
                            </div>
                            <div class="p-2 bg-purple-500 rounded-lg">
                                <Eye class="h-5 w-5 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 shadow-lg bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-950 dark:to-indigo-900">
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-indigo-700 dark:text-indigo-300">Dengan Logo</p>
                                <p class="text-2xl font-bold text-indigo-900 dark:text-indigo-100">
                                    {{ brands.data.filter(b => b.logo).length }}
                                </p>
                            </div>
                            <div class="p-2 bg-indigo-500 rounded-lg">
                                <Plus class="h-5 w-5 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Search Bar -->
            <Card class="border-0 shadow-lg">
                <CardContent class="p-6">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                            <Input
                                v-model="search"
                                placeholder="Cari berdasarkan nama brand..."
                                class="pl-10 h-11"
                            />
                        </div>
                        <div class="flex gap-2">
                            <Button variant="outline" size="icon" class="h-11 w-11">
                                <Filter class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Table Card -->
            <Card class="border-0 shadow-lg">
                <CardHeader class="pb-3">
                    <CardTitle class="text-xl font-semibold">Daftar Brand</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
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
