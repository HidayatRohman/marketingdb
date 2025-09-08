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
import { Search, Plus, Edit, Trash2, Eye, Building2, Filter, MoreHorizontal, Calendar, ChevronDown, ChevronUp, X, User, Clock, Download, Upload, FileSpreadsheet } from 'lucide-vue-next';

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

interface User {
    id: number;
    name: string;
    email: string;
}

interface Mitra {
    id: number;
    nama: string;
    no_telp: string;
    tanggal_lead: string;
    user_id: number | null;
    brand_id: number;
    brand: Brand;
    user: User | null;
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
    users: User[];
    currentUser: {
        id: number;
        name: string;
        role: string;
    };
    filters: {
        search?: string;
        chat?: string;
        label?: string;
        user?: string;
        periode_start?: string;
        periode_end?: string;
        per_page?: number;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const chat = ref(props.filters.chat || '');
const label = ref(props.filters.label || '');
const user = ref(props.filters.user || '');
const periodeStart = ref(props.filters.periode_start || new Date().toISOString().split('T')[0]);
const periodeEnd = ref(props.filters.periode_end || new Date().toISOString().split('T')[0]);
const perPage = ref(props.filters.per_page || 30);

// Date filter presets
const datePresets = [
    { key: 'today', label: 'Hari Ini', value: 'today', days: 0 },
    { key: '3days', label: '3 Hari', value: '3days', days: 3 },
    { key: '7days', label: '7 Hari', value: '7days', days: 7 },
    { key: '2weeks', label: '2 Minggu', value: '2weeks', days: 14 },
    { key: '1month', label: '1 Bulan', value: '1month', days: 30 },
    { key: 'custom', label: 'Custom', value: 'custom', days: null },
];

const selectedPreset = ref('today');

// Set date range based on preset
const setDatePreset = (preset: string) => {
    selectedPreset.value = preset;
    const today = new Date();
    const endDate = new Date().toISOString().split('T')[0];
    
    if (preset === 'today') {
        periodeStart.value = endDate;
        periodeEnd.value = endDate;
    } else if (preset === 'custom') {
        // Don't change dates, let user set manually
        return;
    } else {
        const presetData = datePresets.find(p => p.key === preset);
        if (presetData && presetData.days !== null) {
            const startDate = new Date();
            startDate.setDate(today.getDate() - presetData.days);
            periodeStart.value = startDate.toISOString().split('T')[0];
            periodeEnd.value = endDate;
        }
    }
};

// Filter panel state
const showFilters = ref(false);
const hasActiveFilters = computed(() => {
    return search.value || chat.value || label.value || user.value || 
           periodeStart.value || periodeEnd.value || 
           selectedPreset.value !== 'today';
});

// Watch for manual date changes to update preset to custom
watch([periodeStart, periodeEnd], () => {
    const today = new Date().toISOString().split('T')[0];
    
    // Check if current dates match any preset
    let matchingPreset = 'custom';
    
    if (periodeStart.value === today && periodeEnd.value === today) {
        matchingPreset = 'today';
    } else {
        for (const preset of datePresets) {
            if (preset.days !== null && preset.key !== 'today') {
                const startDate = new Date();
                startDate.setDate(startDate.getDate() - preset.days);
                const expectedStart = startDate.toISOString().split('T')[0];
                
                if (periodeStart.value === expectedStart && periodeEnd.value === today) {
                    matchingPreset = preset.key;
                    break;
                }
            }
        }
    }
    
    selectedPreset.value = matchingPreset;
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
watch([search, chat, label, user, periodeStart, periodeEnd, perPage], () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get('/mitras', {
            search: search.value || undefined,
            chat: chat.value || undefined,
            label: label.value || undefined,
            user: user.value || undefined,
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

// Function to format phone number for WhatsApp
const formatWhatsAppNumber = (phoneNumber: string) => {
    // Remove all non-numeric characters
    let cleaned = phoneNumber.replace(/\D/g, '');
    
    // If starts with '0', replace with '62' (Indonesia country code)
    if (cleaned.startsWith('0')) {
        cleaned = '62' + cleaned.substring(1);
    }
    
    // If doesn't start with '62', add it
    if (!cleaned.startsWith('62')) {
        cleaned = '62' + cleaned;
    }
    
    return cleaned;
};

// Function to create WhatsApp URL
const createWhatsAppUrl = (phoneNumber: string, message: string = '') => {
    const formattedNumber = formatWhatsAppNumber(phoneNumber);
    const encodedMessage = encodeURIComponent(message);
    return `https://wa.me/${formattedNumber}${message ? `?text=${encodedMessage}` : ''}`;
};

// Function to open WhatsApp
const openWhatsApp = (phoneNumber: string, mitraName: string) => {
    const message = `Halo ${mitraName}, saya ingin menindaklanjuti mengenai inquiry Anda.`;
    const url = createWhatsAppUrl(phoneNumber, message);
    window.open(url, '_blank');
};

const getChatBadgeVariant = (chat: string) => {
    return chat === 'masuk' ? 'default' : 'secondary';
};

const clearFilters = () => {
    search.value = '';
    chat.value = '';
    label.value = '';
    user.value = '';
    periodeStart.value = new Date().toISOString().split('T')[0];
    periodeEnd.value = new Date().toISOString().split('T')[0];
    selectedPreset.value = 'today';
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
        user: user.value || undefined,
        periode_start: periodeStart.value || undefined,
        periode_end: periodeEnd.value || undefined,
        per_page: perPage.value || 30,
    };
};

// Export/Import functions
const isExporting = ref(false);
const isImporting = ref(false);
const importFile = ref<File | null>(null);

const exportData = async (format: 'csv' | 'xlsx') => {
    try {
        isExporting.value = true;
        
        // Get current filter parameters
        const filters = getFilterParams();
        
        // Create export URL with filters
        const params = new URLSearchParams();
        Object.entries(filters).forEach(([key, value]) => {
            if (value !== undefined) {
                params.append(key, String(value));
            }
        });
        params.append('export', format);
        
        // Create download link
        const url = `/mitras/export?${params.toString()}`;
        
        // Create temporary link and trigger download
        const link = document.createElement('a');
        link.href = url;
        link.download = `mitra-data-${new Date().toISOString().split('T')[0]}.${format}`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
    } catch (error) {
        console.error('Export failed:', error);
        alert('Export gagal. Silakan coba lagi.');
    } finally {
        isExporting.value = false;
    }
};

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    
    if (file) {
        // Validate file type
        const allowedTypes = [
            'text/csv',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        
        if (!allowedTypes.includes(file.type)) {
            alert('Format file tidak didukung. Silakan pilih file CSV atau XLSX.');
            return;
        }
        
        importFile.value = file;
        importData();
    }
};

const importData = async () => {
    if (!importFile.value) return;
    
    try {
        isImporting.value = true;
        
        const formData = new FormData();
        formData.append('file', importFile.value);
        
        const response = await fetch('/mitras/import', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });
        
        if (response.ok) {
            const result = await response.json();
            alert(`Import berhasil! ${result.imported || 0} data berhasil diimport.`);
            handleModalSuccess(); // Refresh data
        } else {
            const error = await response.json();
            alert(`Import gagal: ${error.message || 'Terjadi kesalahan'}`);
        }
        
    } catch (error) {
        console.error('Import failed:', error);
        alert('Import gagal. Silakan coba lagi.');
    } finally {
        isImporting.value = false;
        importFile.value = null;
        
        // Reset file input
        const fileInput = document.getElementById('import-file') as HTMLInputElement;
        if (fileInput) fileInput.value = '';
    }
};

const triggerFileInput = () => {
    const fileInput = document.getElementById('import-file') as HTMLInputElement;
    fileInput?.click();
};

const downloadTemplate = async (format: 'csv' | 'xlsx') => {
    try {
        // Create download link for template
        const url = `/mitras/template?format=${format}`;
        
        // Create temporary link and trigger download
        const link = document.createElement('a');
        link.href = url;
        link.download = `mitra-template.${format}`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
    } catch (error) {
        console.error('Template download failed:', error);
        alert('Download template gagal. Silakan coba lagi.');
    }
};

const downloadGuide = async () => {
    try {
        // Create download link for guide
        const url = `/mitras/guide`;
        
        // Create temporary link and trigger download
        const link = document.createElement('a');
        link.href = url;
        link.download = 'panduan-import-mitra.md';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
    } catch (error) {
        console.error('Guide download failed:', error);
        alert('Download panduan gagal. Silakan coba lagi.');
    }
};
</script>

<template>
    <Head title="Mitra" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 mt-6 mx-6">
            <!-- Header Section -->
            <div class="relative overflow-hidden rounded-xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 p-6 text-white">
                <div class="relative z-10">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold tracking-tight mb-2 flex items-center gap-3">
                                <Building2 class="h-8 w-8" />
                                Manajemen Mitra
                            </h1>
                            <p class="text-lg text-teal-100">
                                Kelola mitra bisnis dengan mudah dan efisien
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <!-- Export Dropdown -->
                            <div class="relative group">
                                <Button 
                                    :disabled="isExporting"
                                    class="bg-gradient-to-r from-blue-500 to-blue-600 text-white border border-blue-600 hover:from-blue-600 hover:to-blue-700 font-semibold shadow-lg px-4 py-2 transition-all duration-200"
                                >
                                    <Download class="mr-2 h-4 w-4" />
                                    {{ isExporting ? 'Exporting...' : 'Export' }}
                                    <ChevronDown class="ml-2 h-4 w-4" />
                                </Button>
                                
                                <!-- Dropdown Menu -->
                                <div class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                                    <div class="p-1">
                                        <button
                                            @click="exportData('csv')"
                                            :disabled="isExporting"
                                            class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md flex items-center gap-2 disabled:opacity-50"
                                        >
                                            <FileSpreadsheet class="h-4 w-4" />
                                            Export sebagai CSV
                                        </button>
                                        <button
                                            @click="exportData('xlsx')"
                                            :disabled="isExporting"
                                            class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md flex items-center gap-2 disabled:opacity-50"
                                        >
                                            <FileSpreadsheet class="h-4 w-4" />
                                            Export sebagai XLSX
                                        </button>
                                        <hr class="my-1 border-gray-200 dark:border-gray-600" />
                                        <button
                                            @click="downloadTemplate('xlsx')"
                                            class="w-full text-left px-3 py-2 text-sm text-blue-700 dark:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/50 rounded-md flex items-center gap-2"
                                        >
                                            <Download class="h-4 w-4" />
                                            Download Template XLSX
                                        </button>
                                        <button
                                            @click="downloadTemplate('csv')"
                                            class="w-full text-left px-3 py-2 text-sm text-blue-700 dark:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/50 rounded-md flex items-center gap-2"
                                        >
                                            <Download class="h-4 w-4" />
                                            Download Template CSV
                                        </button>
                                        <hr class="my-1 border-gray-200 dark:border-gray-600" />
                                        <button
                                            @click="downloadGuide"
                                            class="w-full text-left px-3 py-2 text-sm text-green-700 dark:text-green-300 hover:bg-green-50 dark:hover:bg-green-900/50 rounded-md flex items-center gap-2"
                                        >
                                            <FileSpreadsheet class="h-4 w-4" />
                                            Panduan Import Lengkap
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Import Button with Tooltip -->
                            <div class="relative group">
                                <Button 
                                    @click="triggerFileInput"
                                    :disabled="isImporting"
                                    class="bg-gradient-to-r from-orange-500 to-orange-600 text-white border border-orange-600 hover:from-orange-600 hover:to-orange-700 font-semibold shadow-lg px-4 py-2 transition-all duration-200"
                                >
                                    <Upload class="mr-2 h-4 w-4" />
                                    {{ isImporting ? 'Importing...' : 'Import' }}
                                </Button>

                                <!-- Tooltip -->
                                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 w-64 bg-gray-900 text-white text-xs rounded-lg py-2 px-3 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                                    <div class="text-center">
                                        <div class="font-semibold mb-1">Import Data Mitra</div>
                                        <div class="text-gray-300">
                                            Format: CSV, XLSX<br/>
                                            Download template terlebih dahulu
                                        </div>
                                    </div>
                                    <!-- Arrow -->
                                    <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
                                </div>
                            </div>

                            <!-- Hidden File Input -->
                            <input
                                id="import-file"
                                type="file"
                                accept=".csv,.xlsx,.xls"
                                @change="handleFileSelect"
                                class="hidden"
                            />

                            <!-- Add Mitra Button -->
                            <Button 
                                @click="openCreateModal"
                                class="bg-gradient-to-r from-white to-gray-100 dark:from-gray-800 dark:to-gray-900 text-teal-600 dark:text-teal-400 border border-white/50 dark:border-gray-700 hover:from-teal-50 hover:to-white dark:hover:from-gray-700 dark:hover:to-gray-800 font-semibold shadow-lg px-4 py-2 transition-all duration-200"
                            >
                                <Plus class="mr-2 h-4 w-4" />
                                Tambah Mitra
                            </Button>
                        </div>
                    </div>
                </div>
                <div class="absolute top-0 right-0 w-48 h-48 bg-white/10 rounded-full -mr-24 -mt-24"></div>
                <div class="absolute bottom-0 left-0 w-32 h-32 bg-white/5 rounded-full -ml-16 -mb-16"></div>
            </div>

            <!-- Statistics Bar -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card class="border-0 shadow-md bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-950 dark:to-emerald-900">
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300 mb-1">Total Mitra</p>
                                <p class="text-2xl font-bold text-emerald-900 dark:text-emerald-100">{{ mitras.total }}</p>
                            </div>
                            <div class="p-2 bg-emerald-500 rounded-lg">
                                <Building2 class="h-5 w-5 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
                
                <Card class="border-0 shadow-md bg-gradient-to-br from-teal-50 to-teal-100 dark:from-teal-950 dark:to-teal-900">
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-teal-700 dark:text-teal-300 mb-1">Halaman Ini</p>
                                <p class="text-2xl font-bold text-teal-900 dark:text-teal-100">{{ mitras.data.length }}</p>
                            </div>
                            <div class="p-2 bg-teal-500 rounded-lg">
                                <Eye class="h-5 w-5 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 shadow-md bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950 dark:to-green-900">
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-green-700 dark:text-green-300 mb-1">Chat Masuk</p>
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

                <Card class="border-0 shadow-md bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950 dark:to-blue-900">
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-blue-700 dark:text-blue-300 mb-1">Follow Up</p>
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
            <Card class="border-0 shadow-md">
                <CardContent class="p-4">
                    <!-- Top row with search and filter toggle -->
                    <div class="flex items-center gap-4 mb-3">
                        <!-- Search Input -->
                        <div class="flex-1">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                                <Input
                                    v-model="search"
                                    placeholder="Cari berdasarkan nama, telepon, brand, atau lokasi..."
                                    class="pl-10 h-10"
                                />
                            </div>
                        </div>
                        
                        <!-- Filter Toggle Button -->
                        <div class="flex items-center gap-2">
                            <Button
                                variant="outline"
                                @click="toggleFilters"
                                class="h-10 px-4 relative font-medium transition-all duration-200"
                                :class="{ 
                                    'bg-gradient-to-r from-emerald-500 to-teal-600 text-white border-emerald-500 hover:from-emerald-600 hover:to-teal-700 shadow-md': hasActiveFilters,
                                    'bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 text-gray-800 dark:text-gray-200 border-gray-300 dark:border-gray-600 hover:from-gray-200 hover:to-gray-300 dark:hover:from-gray-600 dark:hover:to-gray-700': !hasActiveFilters
                                }"
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
                                class="h-10 bg-gradient-to-r from-red-100 to-red-200 dark:from-red-900/50 dark:to-red-800/50 text-red-700 dark:text-red-300 border border-red-300 dark:border-red-700 hover:from-red-200 hover:to-red-300 dark:hover:from-red-800/60 dark:hover:to-red-700/60 transition-all duration-200"
                            >
                                <X class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <!-- Expandable Filter Panel -->
                    <div v-if="showFilters" class="border-t pt-3 space-y-3">
                        <!-- Date Preset Quick Filters -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-foreground/90 dark:text-foreground flex items-center gap-2">
                                <Clock class="h-4 w-4" />
                                Filter Periode Cepat
                            </label>
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    v-for="preset in datePresets"
                                    :key="preset.key"
                                    variant="outline"
                                    size="sm"
                                    @click="setDatePreset(preset.key)"
                                    :class="{
                                        'bg-gradient-to-r from-blue-500 to-blue-600 text-white border-blue-500 hover:from-blue-600 hover:to-blue-700 shadow-md': selectedPreset === preset.key,
                                        'bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 text-gray-800 dark:text-gray-200 border-gray-300 dark:border-gray-600 hover:from-gray-200 hover:to-gray-300 dark:hover:from-gray-600 dark:hover:to-gray-700': selectedPreset !== preset.key
                                    }"
                                    class="px-3 py-1 h-8 text-xs font-medium transition-all duration-200"
                                >
                                    {{ preset.label }}
                                </Button>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
                            <!-- Periode Start -->
                            <div class="space-y-1">
                                <label class="text-sm font-medium text-foreground/90 dark:text-foreground flex items-center gap-2">
                                    <Calendar class="h-4 w-4" />
                                    Dari Tanggal
                                </label>
                                <Input
                                    type="date"
                                    v-model="periodeStart"
                                    class="h-9"
                                />
                            </div>

                            <!-- Periode End -->
                            <div class="space-y-1">
                                <label class="text-sm font-medium text-foreground/90 dark:text-foreground flex items-center gap-2">
                                    <Calendar class="h-4 w-4" />
                                    Sampai Tanggal
                                </label>
                                <Input
                                    type="date"
                                    v-model="periodeEnd"
                                    class="h-9"
                                />
                            </div>

                            <!-- Marketing Filter -->
                            <div class="space-y-1">
                                <label class="text-sm font-medium text-foreground/90 dark:text-foreground flex items-center gap-2">
                                    <User class="h-4 w-4" />
                                    Marketing
                                </label>
                                <select
                                    v-model="user"
                                    class="flex h-9 w-full rounded-md border border-input bg-background text-foreground px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 [&>option]:bg-background [&>option]:text-foreground"
                                >
                                    <option value="" class="bg-background text-foreground">Semua Marketing</option>
                                    <option 
                                        v-for="userOption in users" 
                                        :key="userOption.id" 
                                        :value="userOption.id"
                                        class="bg-background text-foreground"
                                    >
                                        {{ userOption.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Chat Filter -->
                            <div class="space-y-1">
                                <label class="text-sm font-medium text-foreground/90 dark:text-foreground">Status Chat</label>
                                <select
                                    v-model="chat"
                                    class="flex h-9 w-full rounded-md border border-input bg-background text-foreground px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 [&>option]:bg-background [&>option]:text-foreground"
                                >
                                    <option value="" class="bg-background text-foreground">Semua Chat</option>
                                    <option value="masuk" class="bg-background text-foreground">Masuk</option>
                                    <option value="followup" class="bg-background text-foreground">Follow Up</option>
                                </select>
                            </div>

                            <!-- Label Filter -->
                            <div class="space-y-1">
                                <label class="text-sm font-medium text-foreground/90 dark:text-foreground">Label</label>
                                <select
                                    v-model="label"
                                    class="flex h-9 w-full rounded-md border border-input bg-background text-foreground px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 [&>option]:bg-background [&>option]:text-foreground"
                                >
                                    <option value="" class="bg-background text-foreground">Semua Label</option>
                                    <option 
                                        v-for="labelOption in labels" 
                                        :key="labelOption.id" 
                                        :value="labelOption.id"
                                        class="bg-background text-foreground"
                                    >
                                        {{ labelOption.nama }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Per Page and Active Filters -->
                        <div class="flex items-center justify-between pt-3 border-t">
                            <div class="flex items-center gap-2">
                                <label class="text-sm font-medium text-foreground/80 dark:text-foreground/90">Tampilkan:</label>
                                <select
                                    v-model="perPage"
                                    class="flex h-8 w-20 rounded-md border border-input bg-background text-foreground px-2 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring [&>option]:bg-background [&>option]:text-foreground"
                                >
                                    <option value="10" class="bg-background text-foreground">10</option>
                                    <option value="20" class="bg-background text-foreground">20</option>
                                    <option value="30" class="bg-background text-foreground">30</option>
                                    <option value="50" class="bg-background text-foreground">50</option>
                                    <option value="100" class="bg-background text-foreground">100</option>
                                </select>
                                <span class="text-sm text-foreground/80 dark:text-foreground/90">per halaman</span>
                            </div>

                            <!-- Active Filters Display -->
                            <div v-if="hasActiveFilters" class="flex items-center gap-2 text-sm text-foreground/80 dark:text-foreground/90">
                                <span>Filter aktif:</span>
                                <div class="flex gap-1">
                                    <span v-if="search" class="px-2 py-1 bg-primary/10 text-primary rounded text-xs">Search</span>
                                    <span v-if="chat" class="px-2 py-1 bg-primary/10 text-primary rounded text-xs">{{ chatLabels[chat as keyof typeof chatLabels] }}</span>
                                    <span v-if="label" class="px-2 py-1 bg-primary/10 text-primary rounded text-xs">Label</span>
                                    <span v-if="user" class="px-2 py-1 bg-primary/10 text-primary rounded text-xs">Marketing</span>
                                    <span v-if="periodeStart || periodeEnd" class="px-2 py-1 bg-primary/10 text-primary rounded text-xs">Periode</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Table Card -->
            <Card class="border-0 shadow-md">
                <CardHeader class="pb-2">
                    <CardTitle class="text-lg font-semibold">Daftar Mitra</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="relative overflow-hidden">
                        <div class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow class="hover:bg-transparent border-b border-border">
                                        <TableHead class="font-semibold text-foreground py-3">Nama</TableHead>
                                        <TableHead class="font-semibold text-foreground py-3">Kontak</TableHead>
                                        <TableHead class="font-semibold text-foreground py-3">Tanggal Lead</TableHead>
                                        <TableHead class="font-semibold text-foreground py-3">Marketing</TableHead>
                                        <TableHead class="font-semibold text-foreground py-3">Brand</TableHead>
                                        <TableHead class="font-semibold text-foreground py-3">Chat</TableHead>
                                        <TableHead class="font-semibold text-foreground py-3">Lokasi</TableHead>
                                        <TableHead class="font-semibold text-foreground py-3">Label</TableHead>
                                        <TableHead class="font-semibold text-foreground text-center w-[120px] py-3">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <!-- Empty State -->
                                    <TableRow v-if="mitras.data.length === 0" class="hover:bg-transparent">
                                        <TableCell colspan="9" class="text-center py-8">
                                            <div class="flex justify-center">
                                                <div class="max-w-md mx-auto">
                                                    <Card class="border-2 border-dashed border-orange-300 dark:border-orange-600 bg-gradient-to-br from-orange-50 to-amber-50 dark:from-gray-800 dark:to-gray-900 shadow-lg">
                                                        <CardContent class="p-8">
                                                            <div class="flex flex-col items-center justify-center space-y-4">
                                                                <div class="p-4 bg-orange-100 dark:bg-orange-500/20 rounded-full ring-4 ring-orange-200 dark:ring-orange-400/30">
                                                                    <Building2 class="h-12 w-12 text-orange-600 dark:text-orange-300" />
                                                                </div>
                                                                <div class="space-y-3 text-center">
                                                                    <h3 class="text-xl font-bold text-orange-900 dark:text-orange-200">
                                                                        Tidak Ada Data Mitra
                                                                    </h3>
                                                                    <div class="p-3 bg-orange-100 dark:bg-orange-500/10 rounded-lg border border-orange-200 dark:border-orange-400/30">
                                                                        <p class="text-sm font-medium text-orange-800 dark:text-orange-100">
                                                                            <span v-if="hasActiveFilters">
                                                                                ⚠️ Tidak ditemukan mitra yang sesuai dengan filter yang dipilih.<br>
                                                                                Coba ubah kriteria pencarian atau hapus filter.
                                                                            </span>
                                                                            <span v-else>
                                                                                📋 Belum ada data mitra yang tersedia di sistem.
                                                                            </span>
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </CardContent>
                                                    </Card>
                                                </div>
                                            </div>
                                        </TableCell>
                                    </TableRow>

                                    <!-- Data Rows -->
                                    <TableRow v-for="mitra in mitras.data" :key="mitra.id" class="hover:bg-muted/30 transition-colors">
                                        <TableCell class="font-medium py-3">
                                            <div class="flex items-center gap-3">
                                                <div class="p-2 bg-gradient-to-br from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30 rounded-lg">
                                                    <Building2 class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                                </div>
                                                <span class="text-gray-900 dark:text-gray-100">{{ mitra.nama }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <div class="p-1 bg-green-100 dark:bg-green-800 rounded">
                                                    <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
                                                    </svg>
                                                </div>
                                                <button 
                                                    @click="openWhatsApp(mitra.no_telp, mitra.nama)"
                                                    class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 hover:underline transition-colors duration-200 font-medium"
                                                    :title="`Hubungi ${mitra.nama} via WhatsApp`"
                                                >
                                                    {{ mitra.no_telp }}
                                                </button>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <div class="p-1 bg-gray-100 dark:bg-gray-800 rounded">
                                                    <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ mitra.tanggal_lead ? formatDate(mitra.tanggal_lead) : '-' }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <div class="p-1 bg-blue-100 dark:bg-blue-800 rounded">
                                                    <User class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                                </div>
                                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ mitra.user?.name || '-' }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <span class="text-sm text-gray-900 dark:text-gray-100 font-medium">{{ mitra.brand.nama }}</span>
                                        </TableCell>
                                        <TableCell>
                                            <Badge :variant="getChatBadgeVariant(mitra.chat)">
                                                {{ chatLabels[mitra.chat] }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <span class="text-sm text-gray-900 dark:text-gray-100">{{ mitra.kota }}, {{ mitra.provinsi }}</span>
                                        </TableCell>
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
                                                    class="h-9 w-9 p-0 bg-gradient-to-r from-blue-100 to-blue-200 dark:from-blue-900/50 dark:to-blue-800/50 text-blue-700 dark:text-blue-300 border border-blue-300 dark:border-blue-700 hover:from-blue-200 hover:to-blue-300 dark:hover:from-blue-800/60 dark:hover:to-blue-700/60 transition-all duration-200"
                                                >
                                                    <Eye class="h-4 w-4" />
                                                </Button>
                                                <Button 
                                                    variant="ghost" 
                                                    size="sm"
                                                    @click="openEditModal(mitra)"
                                                    class="h-9 w-9 p-0 bg-gradient-to-r from-green-100 to-green-200 dark:from-green-900/50 dark:to-green-800/50 text-green-700 dark:text-green-300 border border-green-300 dark:border-green-700 hover:from-green-200 hover:to-green-300 dark:hover:from-green-800/60 dark:hover:to-green-700/60 transition-all duration-200"
                                                >
                                                    <Edit class="h-4 w-4" />
                                                </Button>
                                                <Button 
                                                    variant="ghost" 
                                                    size="sm"
                                                    @click="openDeleteModal(mitra)"
                                                    class="h-9 w-9 p-0 bg-gradient-to-r from-red-100 to-red-200 dark:from-red-900/50 dark:to-red-800/50 text-red-700 dark:text-red-300 border border-red-300 dark:border-red-700 hover:from-red-200 hover:to-red-300 dark:hover:from-red-800/60 dark:hover:to-red-700/60 transition-all duration-200"
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
                        <div class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-3 p-3 bg-muted/20 rounded-lg">
                            <div class="text-sm text-foreground/80 dark:text-foreground/90">
                                Menampilkan <span class="font-medium text-foreground">{{ mitras.data.length }}</span> dari <span class="font-medium text-foreground">{{ mitras.total }}</span> mitra
                                <span v-if="mitras.total > 0" class="text-foreground/70 dark:text-foreground/80">
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
                                    class="h-9 w-9 p-0 bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 text-gray-800 dark:text-gray-200 border-gray-300 dark:border-gray-600 hover:from-gray-200 hover:to-gray-300 dark:hover:from-gray-600 dark:hover:to-gray-700 transition-all duration-200"
                                >
                                    1
                                </Button>
                                
                                <!-- Dots if there's a gap -->
                                <span v-if="mitras.current_page > 3" class="text-foreground/60 px-2">...</span>
                                
                                <!-- Previous Page -->
                                <Button 
                                    v-if="mitras.prev_page_url" 
                                    variant="outline" 
                                    size="sm"
                                    @click="router.get(mitras.prev_page_url)"
                                    class="h-9 px-3 bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 text-gray-800 dark:text-gray-200 border-gray-300 dark:border-gray-600 hover:from-gray-200 hover:to-gray-300 dark:hover:from-gray-600 dark:hover:to-gray-700 transition-all duration-200"
                                >
                                    ← Prev
                                </Button>
                                
                                <!-- Current Page -->
                                <Button 
                                    variant="default" 
                                    size="sm"
                                    class="h-9 w-9 p-0 bg-gradient-to-r from-blue-500 to-blue-600 text-white border-blue-500 shadow-md"
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
                                    class="h-9 px-3 bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 text-gray-800 dark:text-gray-200 border-gray-300 dark:border-gray-600 hover:from-gray-200 hover:to-gray-300 dark:hover:from-gray-600 dark:hover:to-gray-700 transition-all duration-200"
                                >
                                    Next →
                                </Button>
                                
                                <!-- Dots if there's a gap -->
                                <span v-if="mitras.current_page < mitras.last_page - 2" class="text-foreground/60 px-2">...</span>
                                
                                <!-- Last Page -->
                                <Button 
                                    v-if="mitras.current_page < mitras.last_page - 1"
                                    variant="outline" 
                                    size="sm"
                                    @click="router.get('/mitras', { ...getFilterParams(), page: mitras.last_page })"
                                    class="h-9 w-9 p-0 bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 text-gray-800 dark:text-gray-200 border-gray-300 dark:border-gray-600 hover:from-gray-200 hover:to-gray-300 dark:hover:from-gray-600 dark:hover:to-gray-700 transition-all duration-200"
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
            :marketing-users="users"
            :current-user="currentUser"
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
