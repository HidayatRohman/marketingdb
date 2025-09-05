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
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Kelola Label</h1>
                    <p class="text-muted-foreground">
                        Kelola label untuk kategorisasi mitra bisnis
                    </p>
                </div>
                <Button @click="openCreateModal" class="gap-2">
                    <Plus class="h-4 w-4" />
                    Tambah Label
                </Button>
            </div>

            <!-- Label Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <Card 
                    v-for="label in labels" 
                    :key="label.id" 
                    class="hover:shadow-md transition-shadow cursor-pointer"
                    @click="openViewModal(label)"
                >
                    <CardHeader class="pb-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div 
                                    class="w-4 h-4 rounded-full border-2 border-white shadow-sm" 
                                    :style="{ backgroundColor: label.warna }"
                                ></div>
                                <CardTitle class="text-lg">{{ label.nama }}</CardTitle>
                            </div>
                            <div class="flex gap-1">
                                <Button 
                                    variant="ghost" 
                                    size="sm"
                                    @click.stop="openEditModal(label)"
                                    class="h-8 w-8 p-0"
                                >
                                    <Edit class="h-3 w-3" />
                                </Button>
                                <Button 
                                    variant="ghost" 
                                    size="sm"
                                    @click.stop="openDeleteModal(label)"
                                    class="h-8 w-8 p-0 text-destructive hover:text-destructive"
                                >
                                    <Trash2 class="h-3 w-3" />
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-2">
                            <Badge 
                                variant="secondary" 
                                class="text-xs"
                                :style="{ 
                                    backgroundColor: label.warna + '20', 
                                    color: label.warna,
                                    borderColor: label.warna 
                                }"
                            >
                                <Tag class="h-3 w-3 mr-1" />
                                {{ label.warna.toUpperCase() }}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <div v-if="labels.length === 0" class="text-center py-12">
                <Tag class="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                <h3 class="text-lg font-semibold text-muted-foreground mb-2">Belum ada label</h3>
                <p class="text-muted-foreground mb-4">Mulai dengan menambahkan label pertama Anda.</p>
                <Button @click="openCreateModal" class="gap-2">
                    <Plus class="h-4 w-4" />
                    Tambah Label Pertama
                </Button>
            </div>
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
