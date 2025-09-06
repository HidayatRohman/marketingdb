<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Table from '@/components/ui/table/Table.vue';
import TableBody from '@/components/ui/table/TableBody.vue';
import TableCell from '@/components/ui/table/TableCell.vue';
import TableHead from '@/components/ui/table/TableHead.vue';
import TableHeader from '@/components/ui/table/TableHeader.vue';
import TableRow from '@/components/ui/table/TableRow.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import MitraModal from '@/components/MitraModal.vue';
import MitraDeleteModal from '@/components/MitraDeleteModal.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { Search, Plus, Edit, Trash2, Eye, Building2, Filter, MoreHorizontal, Calendar, ChevronDown, ChevronUp, X } from 'lucide-vue-next';

interface Brand {
    id: number;
    nama: string;
    logo?: string;
    logo_url?: string;
}

interface Label {
    id: number;
    nama: string;
    warna: string;
}

interface Mitra {
    id: number;
    nama: string;
    no_telp: string;
    tanggal_lead: string;
    brand_id: number;
    brand: Brand;
    label_id: number | null;
    label: Label | null;
    chat: 'masuk' | 'followup';
    kota: string;
    provinsi: string;
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
    labels: Label[];
    filters: {
        search?: string;
        chat?: string;
        label?: string;
        periode_start?: string;
        periode_end?: string;
        per_page?: number;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const chat = ref(props.filters.chat || '');
const label = ref(props.filters.label || '');
const periodeStart = ref(props.filters.periode_start || '');
const periodeEnd = ref(props.filters.periode_end || new Date().toISOString().split('T')[0]);
const perPage = ref(props.filters.per_page || 30);

// Filter panel state
const showFilters = ref(false);
const hasActiveFilters = computed(() => {
    return search.value || chat.value || label.value || periodeStart.value || (periodeEnd.value && periodeEnd.value !== new Date().toISOString().split('T')[0]);
});

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
watch([search, chat, label, periodeStart, periodeEnd, perPage], () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get('/mitras', {
            search: search.value || undefined,
            chat: chat.value || undefined,
            label: label.value || undefined,
            periode_start: periodeStart.value || undefined,
            periode_end: periodeEnd.value || undefined,
            per_page: perPage.value || 30,
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

const clearFilters = () => {
    search.value = '';
    chat.value = '';
    label.value = '';
    periodeStart.value = '';
    periodeEnd.value = new Date().toISOString().split('T')[0];
    perPage.value = 30;
    showFilters.value = false;
};

const toggleFilters = () => {
    showFilters.value = !showFilters.value;
};

const getFilterParams = () => {
    return {
        search: search.value || undefined,
        chat: chat.value || undefined,
        label: label.value || undefined,
        periode_start: periodeStart.value || undefined,
        periode_end: periodeEnd.value || undefined,
        per_page: perPage.value || 30,
    };
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
                    <!-- Top row with search and filter toggle -->
                    <div class="flex items-center gap-4 mb-4">
                        <!-- Search Input -->
                        <div class="flex-1">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                                <Input
                                    v-model="search"
                                    placeholder="Cari berdasarkan nama, telepon, brand, atau lokasi..."
                                    class="pl-10 h-11"
                                />
                            </div>
                        </div>
                        
                        <!-- Filter Toggle Button -->
                        <div class="flex items-center gap-2">
                            <Button
                                variant="outline"
                                @click="toggleFilters"
                                class="h-11 px-4 relative"
                                :class="{ 'bg-primary text-primary-foreground border-primary': hasActiveFilters }"
                            >
                                <Filter class="h-4 w-4 mr-2" />
                                Filter
                                <ChevronDown v-if="!showFilters" class="h-4 w-4 ml-2" />
                                <ChevronUp v-else class="h-4 w-4 ml-2" />
                                <span v-if="hasActiveFilters" class="absolute -top-1 -right-1 h-3 w-3 bg-red-500 rounded-full"></span>
                            </Button>
                            
                            <Button
                                v-if="hasActiveFilters"
                                variant="ghost"
                                size="sm"
                                @click="clearFilters"
                                class="h-11 text-muted-foreground hover:text-foreground"
                            >
                                <X class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <!-- Expandable Filter Panel -->
                    <div v-if="showFilters" class="border-t pt-4 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Periode Start -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-muted-foreground flex items-center gap-2">
                                    <Calendar class="h-4 w-4" />
                                    Dari Tanggal
                                </label>
                                <Input
                                    type="date"
                                    v-model="periodeStart"
                                    class="h-10"
                                />
                            </div>

                            <!-- Periode End -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-muted-foreground flex items-center gap-2">
                                    <Calendar class="h-4 w-4" />
                                    Sampai Tanggal
                                </label>
                                <Input
                                    type="date"
                                    v-model="periodeEnd"
                                    class="h-10"
                                />
                            </div>

                            <!-- Chat Filter -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-muted-foreground">Status Chat</label>
                                <select
                                    v-model="chat"
                                    class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="">Semua Chat</option>
                                    <option value="masuk">Masuk</option>
                                    <option value="followup">Follow Up</option>
                                </select>
                            </div>

                            <!-- Label Filter -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-muted-foreground">Label</label>
                                <select
                                    v-model="label"
                                    class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="">Semua Label</option>
                                    <option 
                                        v-for="labelOption in labels" 
                                        :key="labelOption.id" 
                                        :value="labelOption.id"
                                    >
                                        {{ labelOption.nama }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Per Page and Active Filters -->
                        <div class="flex items-center justify-between pt-4 border-t">
                            <div class="flex items-center gap-2">
                                <label class="text-sm font-medium text-muted-foreground">Tampilkan:</label>
                                <select
                                    v-model="perPage"
                                    class="flex h-9 w-20 rounded-md border border-input bg-transparent px-2 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                >
                                    <option value="10">10</option>
                                    <option value="20">20</option>
                                    <option value="30">30</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <span class="text-sm text-muted-foreground">per halaman</span>
                            </div>

                            <!-- Active Filters Display -->
                            <div v-if="hasActiveFilters" class="flex items-center gap-2 text-sm text-muted-foreground">
                                <span>Filter aktif:</span>
                                <div class="flex gap-1">
                                    <span v-if="search" class="px-2 py-1 bg-primary/10 text-primary rounded text-xs">Search</span>
                                    <span v-if="chat" class="px-2 py-1 bg-primary/10 text-primary rounded text-xs">{{ chatLabels[chat as keyof typeof chatLabels] }}</span>
                                    <span v-if="label" class="px-2 py-1 bg-primary/10 text-primary rounded text-xs">Label</span>
                                    <span v-if="periodeStart || periodeEnd" class="px-2 py-1 bg-primary/10 text-primary rounded text-xs">Periode</span>
                                </div>
                            </div>
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
                                        <TableHead class="font-semibold text-foreground">Tanggal Lead</TableHead>
                                        <TableHead class="font-semibold text-foreground">Brand</TableHead>
                                        <TableHead class="font-semibold text-foreground">Chat</TableHead>
                                        <TableHead class="font-semibold text-foreground">Lokasi</TableHead>
                                        <TableHead class="font-semibold text-foreground">Label</TableHead>
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
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <div class="p-1 bg-gray-100 dark:bg-gray-800 rounded">
                                                    <svg class="h-4 w-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                                <span class="text-sm">{{ mitra.tanggal_lead ? formatDate(mitra.tanggal_lead) : '-' }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>{{ mitra.brand.nama }}</TableCell>
                                        <TableCell>
                                            <Badge :variant="getChatBadgeVariant(mitra.chat)">
                                                {{ chatLabels[mitra.chat] }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{{ mitra.kota }}, {{ mitra.provinsi }}</TableCell>
                                        <TableCell>
                                            <div v-if="mitra.label" class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium"
                                                 :style="{ 
                                                     backgroundColor: mitra.label.warna + '20', 
                                                     color: mitra.label.warna,
                                                     border: `1px solid ${mitra.label.warna}40`
                                                 }">
                                                <div 
                                                    class="w-2 h-2 rounded-full" 
                                                    :style="{ backgroundColor: mitra.label.warna }"
                                                ></div>
                                                {{ mitra.label.nama }}
                                            </div>
                                            <span v-else class="text-muted-foreground text-sm">-</span>
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
                        <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4 p-4 bg-muted/30 rounded-lg">
                            <div class="text-sm text-muted-foreground">
                                Menampilkan <span class="font-medium">{{ mitras.data.length }}</span> dari <span class="font-medium">{{ mitras.total }}</span> mitra
                                <span v-if="mitras.total > 0">
                                    ({{ ((mitras.current_page - 1) * mitras.per_page) + 1 }} - {{ Math.min(mitras.current_page * mitras.per_page, mitras.total) }})
                                </span>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <!-- First Page -->
                                <Button 
                                    v-if="mitras.current_page > 2"
                                    variant="outline" 
                                    size="sm"
                                    @click="router.get('/mitras', { ...getFilterParams(), page: 1 })"
                                    class="h-9 w-9 p-0"
                                >
                                    1
                                </Button>
                                
                                <!-- Dots if there's a gap -->
                                <span v-if="mitras.current_page > 3" class="text-muted-foreground px-2">...</span>
                                
                                <!-- Previous Page -->
                                <Button 
                                    v-if="mitras.prev_page_url" 
                                    variant="outline" 
                                    size="sm"
                                    @click="router.get(mitras.prev_page_url)"
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
                                    {{ mitras.current_page }}
                                </Button>
                                
                                <!-- Next Page -->
                                <Button 
                                    v-if="mitras.next_page_url" 
                                    variant="outline" 
                                    size="sm"
                                    @click="router.get(mitras.next_page_url)"
                                    class="h-9 px-3"
                                >
                                    Next →
                                </Button>
                                
                                <!-- Dots if there's a gap -->
                                <span v-if="mitras.current_page < mitras.last_page - 2" class="text-muted-foreground px-2">...</span>
                                
                                <!-- Last Page -->
                                <Button 
                                    v-if="mitras.current_page < mitras.last_page - 1"
                                    variant="outline" 
                                    size="sm"
                                    @click="router.get('/mitras', { ...getFilterParams(), page: mitras.last_page })"
                                    class="h-9 w-9 p-0"
                                >
                                    {{ mitras.last_page }}
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
            :labels="labels"
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
