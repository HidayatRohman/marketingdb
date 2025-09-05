<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import LabelModal from '@/components/LabelModal.vue';
import LabelDeleteModal from '@/components/LabelDeleteModal.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Search, Plus, Edit, Trash2, Tag } from 'lucide-vue-next';

interface Label {
    id: number;
    nama: string;
    warna: string;
    created_at: string;
    updated_at: string;
}

interface Props {
    labels: Label[];
}

const props = defineProps<Props>();

// Modal states
const labelModal = ref({
    open: false,
    mode: 'create' as 'create' | 'edit' | 'view',
    label: undefined as Label | undefined,
});

const deleteModal = ref({
    open: false,
    label: undefined as Label | undefined,
});

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Label', href: '/labels' },
];

// Modal functions
const openCreateModal = () => {
    labelModal.value = {
        open: true,
        mode: 'create',
        label: undefined,
    };
};

const openEditModal = (label: Label) => {
    labelModal.value = {
        open: true,
        mode: 'edit',
        label: { ...label },
    };
};

const openViewModal = (label: Label) => {
    labelModal.value = {
        open: true,
        mode: 'view',
        label: { ...label },
    };
};

const openDeleteModal = (label: Label) => {
    deleteModal.value = {
        open: true,
        label: { ...label },
    };
};

const closeLabelModal = () => {
    labelModal.value = {
        open: false,
        mode: 'create',
        label: undefined,
    };
};

const closeDeleteModal = () => {
    deleteModal.value = {
        open: false,
        label: undefined,
    };
};

const handleModalSuccess = () => {
    // Refresh the page data
    router.reload({ only: ['labels'] });
};
</script>

<template>
    <Head title="Kelola Label" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-8">
            <!-- Header Section -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 p-8 text-white">
                <div class="relative z-10">
                    <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 class="text-4xl font-bold tracking-tight mb-2 flex items-center gap-3">
                                <Tag class="h-10 w-10" />
                                Kelola Label
                            </h1>
                            <p class="text-xl text-blue-100">
                                Kelola label untuk kategorisasi mitra bisnis dengan mudah
                            </p>
                        </div>
                        <Button 
                            @click="openCreateModal" 
                            size="lg"
                            class="bg-white text-blue-600 hover:bg-blue-50 font-semibold gap-2"
                        >
                            <Plus class="h-5 w-5" />
                            Tambah Label
                        </Button>
                    </div>
                </div>
                <!-- Background decoration -->
                <div class="absolute inset-0 opacity-20">
                    <div class="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
                </div>
            </div>

            <!-- Label Cards -->
            <Card class="border-0 shadow-sm">
                <CardHeader class="pb-4">
                    <CardTitle class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                        Daftar Label
                    </CardTitle>
                    <p class="text-sm text-muted-foreground">
                        {{ labels.length }} label tersedia untuk kategorisasi mitra
                    </p>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <Card 
                            v-for="label in labels" 
                            :key="label.id" 
                            class="group hover:shadow-lg transition-all duration-200 cursor-pointer border-2 hover:border-primary/20"
                            @click="openViewModal(label)"
                        >
                            <CardHeader class="pb-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div 
                                            class="w-5 h-5 rounded-full border-2 border-white shadow-md ring-1 ring-gray-200 dark:ring-gray-700" 
                                            :style="{ backgroundColor: label.warna }"
                                        ></div>
                                        <CardTitle class="text-lg font-medium group-hover:text-primary transition-colors">
                                            {{ label.nama }}
                                        </CardTitle>
                                    </div>
                                    <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <Button 
                                            variant="ghost" 
                                            size="sm"
                                            @click.stop="openEditModal(label)"
                                            class="h-8 w-8 p-0 hover:bg-primary/10"
                                        >
                                            <Edit class="h-3 w-3" />
                                        </Button>
                                        <Button 
                                            variant="ghost" 
                                            size="sm"
                                            @click.stop="openDeleteModal(label)"
                                            class="h-8 w-8 p-0 text-destructive hover:text-destructive hover:bg-destructive/10"
                                        >
                                            <Trash2 class="h-3 w-3" />
                                        </Button>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent class="pt-0">
                                <div class="flex items-center gap-2">
                                    <Badge 
                                        variant="secondary" 
                                        class="text-xs font-medium px-2 py-1"
                                        :style="{ 
                                            backgroundColor: label.warna + '15', 
                                            color: label.warna,
                                            borderColor: label.warna + '30',
                                            border: '1px solid'
                                        }"
                                    >
                                        <Tag class="h-3 w-3 mr-1" />
                                        {{ label.warna.toUpperCase() }}
                                    </Badge>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </CardContent>
            </Card>

            <!-- Empty State -->
            <Card v-if="labels.length === 0" class="border-dashed border-2 border-gray-300 dark:border-gray-600">
                <CardContent class="text-center py-16">
                    <div class="mx-auto w-24 h-24 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-6">
                        <Tag class="h-12 w-12 text-gray-400 dark:text-gray-500" />
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">
                        Belum ada label
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-sm mx-auto">
                        Mulai dengan menambahkan label pertama untuk mengkategorikan mitra bisnis Anda.
                    </p>
                    <Button 
                        @click="openCreateModal" 
                        size="lg"
                        class="gap-2"
                    >
                        <Plus class="h-5 w-5" />
                        Tambah Label Pertama
                    </Button>
                </CardContent>
            </Card>
        </div>

        <!-- Modals -->
        <LabelModal
            :open="labelModal.open"
            :mode="labelModal.mode"
            :label="labelModal.label"
            @close="closeLabelModal"
            @success="handleModalSuccess"
        />

        <LabelDeleteModal
            :open="deleteModal.open"
            :label="deleteModal.label"
            @close="closeDeleteModal"
            @success="handleModalSuccess"
        />
    </AppLayout>
</template>
