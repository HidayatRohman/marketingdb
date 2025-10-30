<script setup lang="ts">
import MitraDeleteModal from '@/components/MitraDeleteModal.vue';
import MitraImportExportActions from '@/components/MitraImportExportActions.vue';
import MitraModal from '@/components/MitraModal.vue';
import HourlyLeadsChart from '@/components/HourlyLeadsChart.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DatePicker from '@/components/ui/datepicker/DatePicker.vue';
import { Input } from '@/components/ui/input';
import Table from '@/components/ui/table/Table.vue';
import TableBody from '@/components/ui/table/TableBody.vue';
import TableCell from '@/components/ui/table/TableCell.vue';
import TableHead from '@/components/ui/table/TableHead.vue';
import TableHeader from '@/components/ui/table/TableHeader.vue';
import TableRow from '@/components/ui/table/TableRow.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Building2, Calendar, ChevronDown, ChevronUp, Clock, Edit, Eye, Filter, Plus, Search, Trash2, User, X } from 'lucide-vue-next';
import { ref, computed, watch, onMounted, nextTick } from 'vue';

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
    chat: 'masuk' | 'followup' | 'followup_2' | 'followup_3';
    kota: string;
    provinsi: string;
    komentar: string | null;
    webinar: 'Tidak' | 'Ikut';
    created_at: string;
    updated_at: string;
}

interface HourlyAnalysisData {
    hour: number;
    brands: Record<string, number>;
    total: number;
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
    hourlyAnalysis: HourlyAnalysisData[];
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
const periodeStart = ref(props.filters.periode_start || '');
const periodeEnd = ref(props.filters.periode_end || '');
const perPage = ref(props.filters.per_page || 30);

// Computed property untuk memastikan data mitras reactive
const mitrasData = computed(() => props.mitras);

// Debug: Log props changes
watch(() => props.mitras, (newMitras) => {
    console.log('Props mitras updated:', {
        total: newMitras.total,
        count: newMitras.data.length,
        first_item: newMitras.data[0],
        filters: props.filters
    });
}, { deep: true });

// Force reactivity on mount
onMounted(() => {
    console.log('Component mounted with data:', {
        total: props.mitras.total,
        count: props.mitras.data.length,
        filters: props.filters
    });
    
    // Force reactivity update
    nextTick(() => {
        console.log('NextTick - ensuring reactivity');
    });
});

// Date filter presets
const datePresets = [
    { key: 'today', label: 'Hari Ini', value: 'today', days: 0 },
    { key: 'yesterday', label: 'Kemarin', value: 'yesterday', days: -1 },
    { key: '3days', label: '3 Hari', value: '3days', days: 3 },
    { key: '7days', label: '7 Hari', value: '7days', days: 7 },
    { key: '2weeks', label: '2 Minggu', value: '2weeks', days: 14 },
    { key: '1month', label: '1 Bulan', value: '1month', days: 30 },
    { key: 'custom', label: 'Custom', value: 'custom', days: null },
];

const selectedPreset = ref('');

// Chart data management
const chartData = ref<HourlyAnalysisData[]>(props.hourlyAnalysis || []);

// Set date range based on preset
const setDatePreset = (preset: string) => {
    selectedPreset.value = preset;
    const today = new Date();
    const endDate = new Date().toISOString().split('T')[0];

    if (preset === 'today') {
        periodeStart.value = endDate;
        periodeEnd.value = endDate;
    } else if (preset === 'yesterday') {
        const yesterday = new Date();
        yesterday.setDate(today.getDate() - 1);
        const yesterdayDate = yesterday.toISOString().split('T')[0];
        periodeStart.value = yesterdayDate;
        periodeEnd.value = yesterdayDate;
    } else if (preset === 'custom') {
        // Don't change dates, let user set manually
        return;
    } else {
        const presetData = datePresets.find((p) => p.key === preset);
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
    return search.value || chat.value || label.value || user.value || periodeStart.value || periodeEnd.value;
});

// Watch for manual date changes to update preset to custom
watch([periodeStart, periodeEnd], () => {
    // Only update preset if both dates are set
    if (!periodeStart.value && !periodeEnd.value) {
        selectedPreset.value = '';
        return;
    }

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
    followup_2: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300 px-3 py-1 rounded-full text-xs font-medium',
    followup_3: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300 px-3 py-1 rounded-full text-xs font-medium',
};

const chatLabels = {
    masuk: 'Baru',
    followup: 'Follow Up',
    followup_2: 'Follow Up 2',
    followup_3: 'Follow Up 3',
};

let debounceTimer: number;

// Watch for filter changes and update URL
watch([search, chat, label, user, periodeStart, periodeEnd, perPage], () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        console.log('Frontend Filter Debug:', {
            search: search.value,
            chat: chat.value,
            label: label.value,
            user: user.value,
            periode_start: periodeStart.value,
            periode_end: periodeEnd.value,
            per_page: perPage.value || 30,
        });
        
        router.get(
            '/mitras',
            {
                search: search.value || undefined,
                chat: chat.value || undefined,
                label: label.value || undefined,
                user: user.value || undefined,
                periode_start: periodeStart.value || undefined,
                periode_end: periodeEnd.value || undefined,
                per_page: perPage.value || 30,
            },
            {
                preserveState: true,
                replace: true,
                onStart: () => {
                    console.log('Request started with filters:', {
                        periode_start: periodeStart.value,
                        periode_end: periodeEnd.value
                    });
                },
                onSuccess: (page) => {
                    console.log('Frontend Response Debug:', {
                        mitras_total: page.props.mitras?.total,
                        mitras_count: page.props.mitras?.data?.length,
                        filters_received: page.props.filters,
                        first_mitra: page.props.mitras?.data?.[0],
                        all_props: page.props
                    });
                },
                onError: (errors) => {
                    console.error('Request failed:', errors);
                }
            },
        );
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

const handleImportSuccess = () => {
    handleModalSuccess(); // Refresh data
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
    periodeStart.value = '';
    periodeEnd.value = '';
    selectedPreset.value = '';
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

// Update chart data when props change
watch(() => props.hourlyAnalysis, (newHourlyAnalysis) => {
    chartData.value = newHourlyAnalysis || [];
}, { deep: true });

// Debug initial data on component mount
onMounted(() => {
    console.log('Component Mounted - Initial Data Debug:', {
        mitras_total: props.mitras.total,
        mitras_count: props.mitras.data?.length,
        mitras_data: props.mitras.data,
        current_filters: {
            search: search.value,
            chat: chat.value,
            label: label.value,
            user: user.value,
            periode_start: periodeStart.value,
            periode_end: periodeEnd.value,
            per_page: perPage.value
        }
    });
});
</script>

<template>
    <Head title="Mitra" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-6 mt-6 space-y-6">
            <!-- Header Section -->
            <div class="relative overflow-hidden rounded-xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 p-4 text-white sm:p-6">
                <div class="relative z-10">
                    <!-- Header Content - Responsive Layout -->
                    <div class="flex flex-col space-y-4 lg:flex-row lg:items-center lg:justify-between lg:space-y-0">
                        <!-- Title Section -->
                        <div class="flex-1">
                            <h1 class="mb-2 flex items-center gap-2 text-xl font-bold tracking-tight sm:gap-3 sm:text-2xl lg:text-3xl">
                                <Building2 class="h-5 w-5 sm:h-6 sm:w-6 lg:h-8 lg:w-8" />
                                Manajemen Mitra
                            </h1>
                            <p class="text-sm text-teal-100 sm:text-base lg:text-lg">Kelola mitra bisnis dengan mudah dan efisien</p>
                        </div>
                        
                        <!-- Action Buttons - Responsive -->
                        <div class="flex flex-col space-y-3 sm:flex-row sm:space-x-3 sm:space-y-0 lg:flex-shrink-0">
                            <!-- Import/Export Actions -->
                            <div class="order-1 sm:order-2">
                                <MitraImportExportActions :filters="getFilterParams()" @import-success="handleImportSuccess" />
                            </div>

                            <!-- Add Mitra Button -->
                            <div class="order-2 sm:order-1">
                                <Button
                                    @click="openCreateModal"
                                    class="w-full border border-white/50 bg-gradient-to-r from-white to-gray-100 px-4 py-2 text-sm font-semibold text-teal-600 shadow-lg transition-all duration-200 hover:from-teal-50 hover:to-white dark:border-gray-700 dark:from-gray-800 dark:to-gray-900 dark:text-teal-400 dark:hover:from-gray-700 dark:hover:to-gray-800 sm:w-auto sm:text-base lg:text-base"
                                >
                                    <Plus class="mr-1 h-4 w-4 sm:mr-2" />
                                    <span class="sm:hidden">Tambah</span>
                                    <span class="hidden sm:inline">Tambah Mitra</span>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="absolute top-0 right-0 -mt-24 -mr-24 h-48 w-48 rounded-full bg-white/10"></div>
                <div class="absolute bottom-0 left-0 -mb-16 -ml-16 h-32 w-32 rounded-full bg-white/5"></div>
            </div>

            <!-- Statistics Bar -->
            <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-6">
                <Card class="border-0 bg-gradient-to-br from-emerald-50 to-emerald-100 shadow-md dark:from-emerald-950 dark:to-emerald-900">
                    <CardContent class="p-4">
                        <div class="relative">
                            <div class="absolute top-2 right-2 rounded-md bg-emerald-500 p-1.5">
                                <Building2 class="h-4 w-4 text-white" />
                            </div>
                            <div>
                                <p class="mb-1 text-sm font-medium text-emerald-700 dark:text-emerald-300">Total Mitra</p>
                                <p class="text-2xl font-bold text-emerald-900 dark:text-emerald-100">{{ mitrasData.total }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-teal-50 to-teal-100 shadow-md dark:from-teal-950 dark:to-teal-900">
                    <CardContent class="p-4">
                        <div class="relative">
                            <div class="absolute top-2 right-2 rounded-md bg-teal-500 p-1.5">
                                <Eye class="h-4 w-4 text-white" />
                            </div>
                            <div>
                                <p class="mb-1 text-sm font-medium text-teal-700 dark:text-teal-300">Halaman Ini</p>
                                <p class="text-2xl font-bold text-teal-900 dark:text-teal-100">{{ props.mitras.data.length }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-green-50 to-green-100 shadow-md dark:from-green-950 dark:to-green-900">
                    <CardContent class="p-4">
                        <div class="relative">
                            <div class="absolute top-2 right-2 rounded-md bg-green-500 p-1.5">
                                <Plus class="h-4 w-4 text-white" />
                            </div>
                            <div>
                                <p class="mb-1 text-sm font-medium text-green-700 dark:text-green-300">Chat Baru</p>
                                <p class="text-2xl font-bold text-green-900 dark:text-green-100">
                                    {{ props.mitras.data.filter((m) => m.chat === 'masuk').length }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-blue-50 to-blue-100 shadow-md dark:from-blue-950 dark:to-blue-900">
                    <CardContent class="p-4">
                        <div class="relative">
                            <div class="absolute top-2 right-2 rounded-md bg-blue-500 p-1.5">
                                <Edit class="h-4 w-4 text-white" />
                            </div>
                            <div>
                                <p class="mb-1 text-sm font-medium text-blue-700 dark:text-blue-300">Follow Up</p>
                                <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                                    {{ props.mitras.data.filter((m) => m.chat === 'followup').length }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-yellow-50 to-yellow-100 shadow-md dark:from-yellow-950 dark:to-yellow-900">
                    <CardContent class="p-4">
                        <div class="relative">
                            <div class="absolute top-2 right-2 rounded-md bg-yellow-500 p-1.5">
                                <Clock class="h-4 w-4 text-white" />
                            </div>
                            <div>
                                <p class="mb-1 text-sm font-medium text-yellow-700 dark:text-yellow-300">Follow Up 2</p>
                                <p class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">
                                    {{ props.mitras.data.filter((m) => m.chat === 'followup_2').length }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-purple-50 to-purple-100 shadow-md dark:from-purple-950 dark:to-purple-900">
                    <CardContent class="p-4">
                        <div class="relative">
                            <div class="absolute top-2 right-2 rounded-md bg-purple-500 p-1.5">
                                <Calendar class="h-4 w-4 text-white" />
                            </div>
                            <div>
                                <p class="mb-1 text-sm font-medium text-purple-700 dark:text-purple-300">Follow Up 3</p>
                                <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">
                                    {{ props.mitras.data.filter((m) => m.chat === 'followup_3').length }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Hourly Leads Analysis Chart -->
            

            <!-- Search and Filter Bar -->
            <Card class="border-0 shadow-md">
                <CardContent class="p-3 sm:p-4">
                    <!-- Top row with search and filter toggle -->
                    <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                        <!-- Search Input -->
                        <div class="flex-1">
                            <div class="relative">
                                <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-muted-foreground" />
                                <Input 
                                    v-model="search" 
                                    placeholder="Cari nama, telepon, brand, lokasi..." 
                                    class="h-9 pl-10 text-sm sm:h-10" 
                                />
                            </div>
                        </div>

                        <!-- Filter Toggle Button -->
                        <div class="flex items-center gap-2">
                            <Button
                                variant="outline"
                                @click="toggleFilters"
                                class="relative h-9 flex-1 px-3 font-medium transition-all duration-200 sm:h-10 sm:flex-none sm:px-4"
                                :class="{
                                    'border-emerald-500 bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-md hover:from-emerald-600 hover:to-teal-700':
                                        hasActiveFilters,
                                    'border-gray-300 bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 hover:from-gray-200 hover:to-gray-300 dark:border-gray-600 dark:from-gray-700 dark:to-gray-800 dark:text-gray-200 dark:hover:from-gray-600 dark:hover:to-gray-700':
                                        !hasActiveFilters,
                                }"
                            >
                                <Filter class="mr-2 h-4 w-4" />
                                <span class="hidden sm:inline">Filter</span>
                                <span class="sm:hidden">Filter Data</span>
                                <ChevronDown v-if="!showFilters" class="ml-2 h-4 w-4" />
                                <ChevronUp v-else class="ml-2 h-4 w-4" />
                                <span v-if="hasActiveFilters" class="absolute -top-1 -right-1 h-3 w-3 rounded-full bg-red-500"></span>
                            </Button>

                            <Button
                                v-if="hasActiveFilters"
                                variant="ghost"
                                size="sm"
                                @click="clearFilters"
                                class="h-9 w-9 border border-red-300 bg-gradient-to-r from-red-100 to-red-200 p-0 text-red-700 transition-all duration-200 hover:from-red-200 hover:to-red-300 dark:border-red-700 dark:from-red-900/50 dark:to-red-800/50 dark:text-red-300 dark:hover:from-red-800/60 dark:hover:to-red-700/60 sm:h-10 sm:w-auto sm:px-3"
                                title="Hapus Semua Filter"
                            >
                                <X class="h-4 w-4" />
                                <span class="ml-1 hidden sm:inline">Clear</span>
                            </Button>
                        </div>
                    </div>

                    <!-- Expandable Filter Panel -->
                    <div v-if="showFilters" class="space-y-3 border-t pt-3">
                        <!-- Date Preset Quick Filters -->
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm font-medium text-foreground/90 dark:text-foreground">
                                <Clock class="h-4 w-4" />
                                Filter Periode Cepat
                            </label>
                            <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
                                <Button
                                    v-for="preset in datePresets"
                                    :key="preset.key"
                                    variant="outline"
                                    size="sm"
                                    @click="setDatePreset(preset.key)"
                                    :class="{
                                        'border-blue-500 bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-md hover:from-blue-600 hover:to-blue-700':
                                            selectedPreset === preset.key,
                                        'border-gray-300 bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 hover:from-gray-200 hover:to-gray-300 dark:border-gray-600 dark:from-gray-700 dark:to-gray-800 dark:text-gray-200 dark:hover:from-gray-600 dark:hover:to-gray-700':
                                            selectedPreset !== preset.key,
                                    }"
                                    class="h-8 px-2 py-1 text-xs font-medium transition-all duration-200 sm:px-3"
                                >
                                    <span class="truncate">{{ preset.label }}</span>
                                </Button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                            <!-- Periode Start -->
                            <div class="space-y-1">
                                <label class="flex items-center gap-2 text-sm font-medium text-foreground/90 dark:text-foreground">
                                    <Calendar class="h-4 w-4" />
                                    Dari Tanggal
                                </label>
                                <DatePicker
                                    v-model="periodeStart"
                                    placeholder="Pilih tanggal mulai"
                                    :max-date="periodeEnd || undefined"
                                />
                            </div>

                            <!-- Periode End -->
                            <div class="space-y-1">
                                <label class="flex items-center gap-2 text-sm font-medium text-foreground/90 dark:text-foreground">
                                    <Calendar class="h-4 w-4" />
                                    Sampai Tanggal
                                </label>
                                <DatePicker
                                    v-model="periodeEnd"
                                    placeholder="Pilih tanggal akhir"
                                />
                            </div>

                            <!-- Marketing Filter -->
                            <div class="space-y-1">
                                <label class="flex items-center gap-2 text-sm font-medium text-foreground/90 dark:text-foreground">
                                    <User class="h-4 w-4" />
                                    Marketing
                                </label>
                                <select
                                    v-model="user"
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 [&>option]:bg-background [&>option]:text-foreground"
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
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 [&>option]:bg-background [&>option]:text-foreground"
                                >
                                    <option value="" class="bg-background text-foreground">Semua Chat</option>
                                    <option value="masuk" class="bg-background text-foreground">Baru</option>
                                    <option value="followup" class="bg-background text-foreground">Follow Up</option>
                                    <option value="followup_2" class="bg-background text-foreground">Follow Up 2</option>
                                    <option value="followup_3" class="bg-background text-foreground">Follow Up 3</option>
                                </select>
                            </div>

                            <!-- Label Filter -->
                            <div class="space-y-1">
                                <label class="text-sm font-medium text-foreground/90 dark:text-foreground">Label</label>
                                <select
                                    v-model="label"
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 [&>option]:bg-background [&>option]:text-foreground"
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
                        <div class="flex flex-col items-start gap-3 border-t pt-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-2">
                                <label class="text-sm font-medium text-foreground/80 dark:text-foreground/90">Tampilkan:</label>
                                <select
                                    v-model="perPage"
                                    class="flex h-8 w-20 rounded-md border border-input bg-background px-2 py-1 text-sm text-foreground shadow-sm transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none [&>option]:bg-background [&>option]:text-foreground"
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
                            <div v-if="hasActiveFilters" class="flex flex-col items-start gap-2 text-sm text-foreground/80 dark:text-foreground/90 sm:flex-row sm:items-center">
                                <span class="font-medium">Filter aktif:</span>
                                <div class="flex flex-wrap gap-1">
                                    <span v-if="search" class="rounded bg-primary/10 px-2 py-1 text-xs text-primary">Search</span>
                                    <span v-if="chat" class="rounded bg-primary/10 px-2 py-1 text-xs text-primary">{{
                                        chatLabels[chat as keyof typeof chatLabels]
                                    }}</span>
                                    <span v-if="label" class="rounded bg-primary/10 px-2 py-1 text-xs text-primary">Label</span>
                                    <span v-if="user" class="rounded bg-primary/10 px-2 py-1 text-xs text-primary">Marketing</span>
                                    <span v-if="periodeStart || periodeEnd" class="rounded bg-primary/10 px-2 py-1 text-xs text-primary"
                                        >Periode</span
                                    >
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
                        <div class="overflow-x-auto table-compact-mobile">
                            <Table>
                                <TableHeader>
                                    <TableRow class="border-b border-border hover:bg-transparent">
                                        <TableHead class="sticky left-0 z-30 bg-background p-2 sm:p-3 font-semibold text-foreground text-sm sm:text-base min-w-[120px] sm:min-w-[180px] border-r border-border">Nama</TableHead>
                                        <TableHead class="py-3 font-semibold text-foreground">Kontak</TableHead>
                                        <TableHead class="py-3 font-semibold text-foreground">Tanggal Lead</TableHead>
                                        <TableHead class="py-3 font-semibold text-foreground">Marketing</TableHead>
                                        <TableHead class="py-3 font-semibold text-foreground">Brand</TableHead>
                                        <TableHead class="py-3 font-semibold text-foreground">Chat</TableHead>
                                        <TableHead class="py-3 font-semibold text-foreground">Lokasi</TableHead>
                                        <TableHead class="py-3 font-semibold text-foreground">Label</TableHead>
                                        <TableHead class="py-3 font-semibold text-foreground">Webinar</TableHead>
                                        <TableHead class="w-[120px] py-3 text-center font-semibold text-foreground">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <!-- Empty State -->
                                    <TableRow v-if="mitrasData.data.length === 0" class="hover:bg-transparent">
                                        <TableCell colspan="10" class="py-8 text-center">
                                            <div class="flex justify-center">
                                                <div class="mx-auto max-w-md">
                                                    <Card
                                                        class="border-2 border-dashed border-orange-300 bg-gradient-to-br from-orange-50 to-amber-50 shadow-lg dark:border-orange-600 dark:from-gray-800 dark:to-gray-900"
                                                    >
                                                        <CardContent class="p-8">
                                                            <div class="flex flex-col items-center justify-center space-y-4">
                                                                <div
                                                                    class="rounded-full bg-orange-100 p-4 ring-4 ring-orange-200 dark:bg-orange-500/20 dark:ring-orange-400/30"
                                                                >
                                                                    <Building2 class="h-12 w-12 text-orange-600 dark:text-orange-300" />
                                                                </div>
                                                                <div class="space-y-3 text-center">
                                                                    <h3 class="text-xl font-bold text-orange-900 dark:text-orange-200">
                                                                        Tidak Ada Data Mitra
                                                                    </h3>
                                                                    <div
                                                                        class="rounded-lg border border-orange-200 bg-orange-100 p-3 dark:border-orange-400/30 dark:bg-orange-500/10"
                                                                    >
                                                                        <p class="text-sm font-medium text-orange-800 dark:text-orange-100">
                                                                            <span v-if="hasActiveFilters">
                                                                                ⚠️ Tidak ditemukan mitra yang sesuai dengan filter yang dipilih.<br />
                                                                                Coba ubah kriteria pencarian atau hapus filter.
                                                                            </span>
                                                                            <span v-else> 📋 Belum ada data mitra yang tersedia di sistem. </span>
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
                                    <TableRow v-for="mitra in mitrasData.data" :key="mitra.id" class="transition-colors hover:bg-muted/30">
                                        <TableCell class="sticky left-0 z-20 bg-background p-2 sm:p-3 font-medium text-sm sm:text-base min-w-[120px] sm:min-w-[180px] border-r border-border">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="rounded-lg bg-gradient-to-br from-emerald-100 to-teal-100 p-2 dark:from-emerald-900/30 dark:to-teal-900/30"
                                                >
                                                    <Building2 class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                                </div>
                                                <span class="text-gray-900 dark:text-gray-100">{{ mitra.nama }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <div class="rounded bg-green-100 p-1 dark:bg-green-800">
                                                    <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"
                                                        />
                                                    </svg>
                                                </div>
                                                <button
                                                    @click="openWhatsApp(mitra.no_telp, mitra.nama)"
                                                    class="font-medium text-green-600 transition-colors duration-200 hover:text-green-800 hover:underline dark:text-green-400 dark:hover:text-green-300"
                                                    :title="`Hubungi ${mitra.nama} via WhatsApp`"
                                                >
                                                    {{ mitra.no_telp }}
                                                </button>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <div class="rounded bg-gray-100 p-1 dark:bg-gray-800">
                                                    <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            fill-rule="evenodd"
                                                            d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                                            clip-rule="evenodd"
                                                        ></path>
                                                    </svg>
                                                </div>
                                                <span class="text-sm text-gray-900 dark:text-gray-100">{{
                                                    mitra.tanggal_lead ? formatDate(mitra.tanggal_lead) : '-'
                                                }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <div class="rounded bg-blue-100 p-1 dark:bg-blue-800">
                                                    <User class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                                </div>
                                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ mitra.user?.name || '-' }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ mitra.brand.nama }}</span>
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
                                            <div
                                                v-if="mitra.label"
                                                class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium"
                                                :style="{
                                                    backgroundColor: mitra.label.warna + '20',
                                                    color: mitra.label.warna,
                                                    border: `1px solid ${mitra.label.warna}40`,
                                                }"
                                            >
                                                <div class="h-2 w-2 rounded-full" :style="{ backgroundColor: mitra.label.warna }"></div>
                                                {{ mitra.label.nama }}
                                            </div>
                                            <span v-else class="text-sm text-muted-foreground">-</span>
                                        </TableCell>
                                        <TableCell>
                                            <Badge 
                                                :class="mitra.webinar === 'Ikut' 
                                                    ? 'bg-green-100 text-green-700 border-green-200 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-700' 
                                                    : 'bg-red-100 text-red-700 border-red-200 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-700'"
                                                variant="outline"
                                            >
                                                {{ mitra.webinar }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex justify-center gap-2">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    @click="openViewModal(mitra)"
                                                    class="h-9 w-9 border border-blue-300 bg-gradient-to-r from-blue-100 to-blue-200 p-0 text-blue-700 transition-all duration-200 hover:from-blue-200 hover:to-blue-300 dark:border-blue-700 dark:from-blue-900/50 dark:to-blue-800/50 dark:text-blue-300 dark:hover:from-blue-800/60 dark:hover:to-blue-700/60"
                                                >
                                                    <Eye class="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    @click="openEditModal(mitra)"
                                                    class="h-9 w-9 border border-green-300 bg-gradient-to-r from-green-100 to-green-200 p-0 text-green-700 transition-all duration-200 hover:from-green-200 hover:to-green-300 dark:border-green-700 dark:from-green-900/50 dark:to-green-800/50 dark:text-green-300 dark:hover:from-green-800/60 dark:hover:to-green-700/60"
                                                >
                                                    <Edit class="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    @click="openDeleteModal(mitra)"
                                                    class="h-9 w-9 border border-red-300 bg-gradient-to-r from-red-100 to-red-200 p-0 text-red-700 transition-all duration-200 hover:from-red-200 hover:to-red-300 dark:border-red-700 dark:from-red-900/50 dark:to-red-800/50 dark:text-red-300 dark:hover:from-red-800/60 dark:hover:to-red-700/60"
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
                                Menampilkan <span class="font-medium text-foreground">{{ mitrasData.data.length }}</span> dari
                <span class="font-medium text-foreground">{{ mitrasData.total }}</span> mitra
                        <span v-if="mitrasData.total > 0" class="text-foreground/70 dark:text-foreground/80">
                            ({{ (mitrasData.current_page - 1) * mitrasData.per_page + 1 }} -
                            {{ Math.min(mitrasData.current_page * mitrasData.per_page, mitrasData.total) }})
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <!-- First Page -->
                                <Button
                                    v-if="mitrasData.current_page > 2"
                                    variant="outline"
                                    size="sm"
                                    @click="router.get('/mitras', { ...getFilterParams(), page: 1 })"
                                    class="h-9 w-9 border-gray-300 bg-gradient-to-r from-gray-100 to-gray-200 p-0 text-gray-800 transition-all duration-200 hover:from-gray-200 hover:to-gray-300 dark:border-gray-600 dark:from-gray-700 dark:to-gray-800 dark:text-gray-200 dark:hover:from-gray-600 dark:hover:to-gray-700"
                                >
                                    1
                                </Button>

                                <!-- Dots if there's a gap -->
                                <span v-if="mitrasData.current_page > 3" class="px-2 text-foreground/60">...</span>

                                <!-- Previous Page -->
                                <Button
                                    v-if="mitrasData.prev_page_url"
                                    variant="outline"
                                    size="sm"
                                    @click="router.get(mitrasData.prev_page_url)"
                                    class="h-9 border-gray-300 bg-gradient-to-r from-gray-100 to-gray-200 px-3 text-gray-800 transition-all duration-200 hover:from-gray-200 hover:to-gray-300 dark:border-gray-600 dark:from-gray-700 dark:to-gray-800 dark:text-gray-200 dark:hover:from-gray-600 dark:hover:to-gray-700"
                                >
                                    ← Prev
                                </Button>

                                <!-- Current Page -->
                                <Button
                                    variant="default"
                                    size="sm"
                                    class="h-9 w-9 border-blue-500 bg-gradient-to-r from-blue-500 to-blue-600 p-0 text-white shadow-md"
                                    disabled
                                >
                                    {{ mitrasData.current_page }}
                                </Button>

                                <!-- Next Page -->
                                <Button
                                    v-if="mitrasData.next_page_url"
                                    variant="outline"
                                    size="sm"
                                    @click="router.get(mitrasData.next_page_url)"
                                    class="h-9 border-gray-300 bg-gradient-to-r from-gray-100 to-gray-200 px-3 text-gray-800 transition-all duration-200 hover:from-gray-200 hover:to-gray-300 dark:border-gray-600 dark:from-gray-700 dark:to-gray-800 dark:text-gray-200 dark:hover:from-gray-600 dark:hover:to-gray-700"
                                >
                                    Next →
                                </Button>

                                <!-- Dots if there's a gap -->
                                <span v-if="mitrasData.current_page < mitrasData.last_page - 2" class="px-2 text-foreground/60">...</span>

                                <!-- Last Page -->
                                <Button
                                    v-if="mitrasData.current_page < mitrasData.last_page - 1"
                                    variant="outline"
                                    size="sm"
                                    @click="router.get('/mitras', { ...getFilterParams(), page: mitrasData.last_page })"
                                    class="h-9 w-9 border-gray-300 bg-gradient-to-r from-gray-100 to-gray-200 p-0 text-gray-800 transition-all duration-200 hover:from-gray-200 hover:to-gray-300 dark:border-gray-600 dark:from-gray-700 dark:to-gray-800 dark:text-gray-200 dark:hover:from-gray-600 dark:hover:to-gray-700"
                                >
                                    {{ mitrasData.last_page }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Hourly Leads Analysis Chart (dipindah ke bawah Daftar Mitra) -->
            <HourlyLeadsChart
                :data="chartData"
                :loading="false"
                :selected-date="periodeStart === periodeEnd ? periodeStart : undefined"
                :empty-message="hasActiveFilters ? 'Tidak ada data lead untuk filter yang dipilih.' : 'Belum ada data lead yang tersedia.'"
            />
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

        <MitraDeleteModal :open="deleteModal.open" :mitra="deleteModal.mitra" @close="closeDeleteModal" @success="handleModalSuccess" />
    </AppLayout>
</template>
