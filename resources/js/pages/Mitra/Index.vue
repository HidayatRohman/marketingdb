<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import MitraModal from '@/components/MitraModal.vue';
import MitraDeleteModal from '@/components/MitraDeleteModal.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Search, Plus, Edit, Trash2, Eye, Building2, Filter, MoreHorizontal } from 'lucide-vue-next';

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
    brand_id: number;
    brand: Brand;
    chat: 'masuk' | 'followup';
    kota: string;
    provinsi: string;
    transaksi: number | null;
    komentar: string | null;
    created_at: string;
    updated_at: string;
}

interface Props {
    mitras: {
        data: Mitra[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    brands: Brand[];
    filters: {
        search?: string;
        chat?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const chat = ref(props.filters.chat || '');

// Modal states
const mitraModal = ref({
    open: false,
    mode: 'create' as 'create' | 'edit' | 'view',
    mitra: undefined as Mitra | undefined,
});

const deleteModal = ref({
    open: false,
    mitra: undefined as Mitra | undefined,
});

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Mitra', href: '/mitras' },
];

const chatColors = {
    masuk: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 px-3 py-1 rounded-full text-xs font-medium',
    followup: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 px-3 py-1 rounded-full text-xs font-medium',
};

const chatLabels = {
    masuk: 'Masuk',
    followup: 'Follow Up',
};

let debounceTimer: number;

// Watch for filter changes and update URL
watch([search, chat], () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get('/mitras', {
            search: search.value || undefined,
            chat: chat.value || undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

// Modal functions
const openCreateModal = () => {
    mitraModal.value = {
        open: true,
        mode: 'create',
        mitra: undefined,
    };
};

const openEditModal = (mitra: Mitra) => {
    mitraModal.value = {
        open: true,
        mode: 'edit',
        mitra: { ...mitra },
    };
};

const openViewModal = (mitra: Mitra) => {
    mitraModal.value = {
        open: true,
        mode: 'view',
        mitra: { ...mitra },
    };
};

const openDeleteModal = (mitra: Mitra) => {
    deleteModal.value = {
        open: true,
        mitra: { ...mitra },
    };
};

const closeMitraModal = () => {
    mitraModal.value = {
        open: false,
        mode: 'create',
        mitra: undefined,
    };
};

const closeDeleteModal = () => {
    deleteModal.value = {
        open: false,
        mitra: undefined,
    };
};

const handleModalSuccess = () => {
    // Refresh the page data
    router.reload({ only: ['mitras'] });
};

const formatCurrency = (amount: number | null) => {
    if (!amount) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const getChatBadgeVariant = (chat: string) => {
    return chat === 'masuk' ? 'default' : 'secondary';
};
</script>

<template>
    <Head title="Mitra" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-8">
            <!-- Header Section -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 p-8 text-white">
                <div class="relative z-10">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-4xl font-bold tracking-tight mb-2 flex items-center gap-3">
                                <Building2 class="h-10 w-10" />
                                Manajemen Mitra
                            </h1>
                            <p class="text-xl text-teal-100">
                                Kelola mitra bisnis dengan mudah dan efisien
                            </p>
                        </div>
                        <Button 
                            @click="openCreateModal"
                            class="bg-white text-teal-600 hover:bg-teal-50 font-semibold shadow-lg"
                        >
                            <Plus class="mr-2 h-5 w-5" />
                            Tambah Mitra
                        </Button>
                    </div>
                </div>
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/5 rounded-full -ml-24 -mb-24"></div>
            </div>

            <!-- Statistics Bar -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card class="border-0 shadow-lg bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-950 dark:to-emerald-900">
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Total Mitra</p>
                                <p class="text-2xl font-bold text-emerald-900 dark:text-emerald-100">{{ mitras.total }}</p>
                            </div>
                            <div class="p-2 bg-emerald-500 rounded-lg">
                                <Building2 class="h-5 w-5 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
                
                <Card class="border-0 shadow-lg bg-gradient-to-br from-teal-50 to-teal-100 dark:from-teal-950 dark:to-teal-900">
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-teal-700 dark:text-teal-300">Halaman Ini</p>
                                <p class="text-2xl font-bold text-teal-900 dark:text-teal-100">{{ mitras.data.length }}</p>
                            </div>
                            <div class="p-2 bg-teal-500 rounded-lg">
                                <Eye class="h-5 w-5 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 shadow-lg bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950 dark:to-green-900">
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-green-700 dark:text-green-300">Chat Masuk</p>
                                <p class="text-2xl font-bold text-green-900 dark:text-green-100">
                                    {{ mitras.data.filter(m => m.chat === 'masuk').length }}
                                </p>
                            </div>
                            <div class="p-2 bg-green-500 rounded-lg">
                                <Plus class="h-5 w-5 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 shadow-lg bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950 dark:to-blue-900">
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-blue-700 dark:text-blue-300">Follow Up</p>
                                <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                                    {{ mitras.data.filter(m => m.chat === 'followup').length }}
                                </p>
                            </div>
                            <div class="p-2 bg-blue-500 rounded-lg">
                                <Edit class="h-5 w-5 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Search and Filter Bar -->
            <Card class="border-0 shadow-lg">
                <CardContent class="p-6">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                            <Input
                                v-model="search"
                                placeholder="Cari berdasarkan nama, telepon, brand, atau lokasi..."
                                class="pl-10 h-11"
                            />
                        </div>
                        <div class="flex gap-2">
                            <select
                                v-model="chat"
                                class="flex h-11 w-full sm:w-[180px] rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">Semua Chat</option>
                                <option value="masuk">Masuk</option>
                                <option value="followup">Follow Up</option>
                            </select>
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
                    <CardTitle class="text-xl font-semibold">Daftar Mitra</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="relative overflow-hidden">
                        <div class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow class="hover:bg-transparent border-b border-border">
                                        <TableHead class="font-semibold text-foreground">Nama</TableHead>
                                        <TableHead class="font-semibold text-foreground">Kontak</TableHead>
                                        <TableHead class="font-semibold text-foreground">Brand</TableHead>
                                        <TableHead class="font-semibold text-foreground">Chat</TableHead>
                                        <TableHead class="font-semibold text-foreground">Lokasi</TableHead>
                                        <TableHead class="font-semibold text-foreground">Transaksi</TableHead>
                                        <TableHead class="font-semibold text-foreground">Tanggal</TableHead>
                                        <TableHead class="font-semibold text-foreground text-center w-[120px]">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="mitra in mitras.data" :key="mitra.id" class="hover:bg-muted/30 transition-colors">
                                        <TableCell class="font-medium">
                                            <div class="flex items-center gap-3">
                                                <div class="p-2 bg-gradient-to-br from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30 rounded-lg">
                                                    <Building2 class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                                </div>
                                                <span>{{ mitra.nama }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <div class="p-1 bg-gray-100 dark:bg-gray-800 rounded">
                                                    <svg class="h-4 w-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                                                    </svg>
                                                </div>
                                                <span>{{ mitra.no_telp }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>{{ mitra.brand.nama }}</TableCell>
                                        <TableCell>
                                            <Badge :variant="getChatBadgeVariant(mitra.chat)">
                                                {{ chatLabels[mitra.chat] }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{{ mitra.kota }}, {{ mitra.provinsi }}</TableCell>
                                        <TableCell>{{ formatCurrency(mitra.transaksi) }}</TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <div class="p-1 bg-gray-100 dark:bg-gray-800 rounded">
                                                    <svg class="h-4 w-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                                <span class="text-sm">{{ formatDate(mitra.created_at) }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex justify-center gap-2">
                                                <Button 
                                                    variant="ghost" 
                                                    size="sm"
                                                    @click="openViewModal(mitra)"
                                                    class="h-9 w-9 p-0 hover:bg-blue-100 hover:text-blue-600 dark:hover:bg-blue-900/30"
                                                >
                                                    <Eye class="h-4 w-4" />
                                                </Button>
                                                <Button 
                                                    variant="ghost" 
                                                    size="sm"
                                                    @click="openEditModal(mitra)"
                                                    class="h-9 w-9 p-0 hover:bg-green-100 hover:text-green-600 dark:hover:bg-green-900/30"
                                                >
                                                    <Edit class="h-4 w-4" />
                                                </Button>
                                                <Button 
                                                    variant="ghost" 
                                                    size="sm"
                                                    @click="openDeleteModal(mitra)"
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
                                Menampilkan <span class="font-medium">{{ mitras.data.length }}</span> dari <span class="font-medium">{{ mitras.total }}</span> mitra
                            </div>
                            <div class="flex items-center gap-2">
                                <Button 
                                    v-if="mitras.prev_page_url" 
                                    variant="outline" 
                                    size="sm"
                                    @click="router.get(mitras.prev_page_url)"
                                    class="h-9"
                                >
                                    ← Previous
                                </Button>
                                <div class="flex items-center gap-1 mx-2">
                                    <span class="text-sm text-muted-foreground">
                                        Page {{ mitras.current_page }} of {{ mitras.last_page }}
                                    </span>
                                </div>
                                <Button 
                                    v-if="mitras.next_page_url" 
                                    variant="outline" 
                                    size="sm"
                                    @click="router.get(mitras.next_page_url)"
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
        <MitraModal
            :open="mitraModal.open"
            :mode="mitraModal.mode"
            :mitra="mitraModal.mitra"
            :brands="brands"
            @close="closeMitraModal"
            @success="handleModalSuccess"
        />

        <MitraDeleteModal
            :open="deleteModal.open"
            :mitra="deleteModal.mitra"
            @close="closeDeleteModal"
            @success="handleModalSuccess"
        />
    </AppLayout>
</template>
