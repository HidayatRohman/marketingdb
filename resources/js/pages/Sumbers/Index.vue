<script setup lang="ts">
import SumberDeleteModal from '@/components/SumberDeleteModal.vue';
import SumberModal from '@/components/SumberModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Edit, Plus, Globe, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

interface Sumber {
    id: number;
    nama: string;
    warna: string;
    created_at: string;
    updated_at: string;
}

interface Props {
    sumbers: Sumber[];
    permissions: {
        canCrud: boolean;
        canOnlyView: boolean;
        canOnlyViewOwn: boolean;
    };
}

const props = defineProps<Props>();

// Modal states
const sumberModal = ref({
    open: false,
    mode: 'create' as 'create' | 'edit' | 'view',
    sumber: undefined as Sumber | undefined,
});

const deleteModal = ref({
    open: false,
    sumber: undefined as Sumber | undefined,
});

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Sumber', href: '/sumbers' },
];

// Modal functions
const openCreateModal = () => {
    sumberModal.value = {
        open: true,
        mode: 'create',
        sumber: undefined,
    };
};

const openEditModal = (sumber: Sumber) => {
    sumberModal.value = {
        open: true,
        mode: 'edit',
        sumber: { ...sumber },
    };
};

const openViewModal = (sumber: Sumber) => {
    sumberModal.value = {
        open: true,
        mode: 'view',
        sumber: { ...sumber },
    };
};

const openDeleteModal = (sumber: Sumber) => {
    deleteModal.value = {
        open: true,
        sumber: { ...sumber },
    };
};

const closeSumberModal = () => {
    sumberModal.value = {
        open: false,
        mode: 'create',
        sumber: undefined,
    };
};

const closeDeleteModal = () => {
    deleteModal.value = {
        open: false,
        sumber: undefined,
    };
};

const handleModalSuccess = () => {
    // Refresh the page data
    router.reload({ only: ['sumbers'] });
};
</script>

<template>
    <Head title="Kelola Sumber" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-8">
            <!-- Header Section -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-green-600 via-teal-600 to-cyan-600 p-8 text-white">
                <div class="relative z-10">
                    <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 class="mb-2 flex items-center gap-3 text-4xl font-bold tracking-tight">
                                <Globe class="h-10 w-10" />
                                Kelola Sumber
                            </h1>
                            <p class="text-xl text-green-100">Kelola sumber lead untuk kategorisasi transaksi dengan mudah</p>
                        </div>
                        <Button 
                            v-if="props.permissions.canCrud"
                            @click="openCreateModal" 
                            size="lg" 
                            class="gap-2 bg-white font-semibold text-green-600 hover:bg-green-50"
                        >
                            <Plus class="h-5 w-5" />
                            Tambah Sumber
                        </Button>
                    </div>
                </div>
                <!-- Background decoration -->
                <div class="absolute inset-0 opacity-20">
                    <div class="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
                </div>
            </div>

            <!-- Sumber Cards -->
            <Card class="border-0 shadow-sm">
                <CardHeader class="pb-4">
                    <CardTitle class="text-xl font-semibold text-gray-900 dark:text-gray-100"> Daftar Sumber </CardTitle>
                    <p class="text-sm text-muted-foreground">{{ sumbers.length }} sumber tersedia untuk kategorisasi transaksi</p>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <Card
                            v-for="sumber in sumbers"
                            :key="sumber.id"
                            class="group cursor-pointer border-2 transition-all duration-200 hover:border-primary/20 hover:shadow-lg"
                            @click="openViewModal(sumber)"
                        >
                            <CardHeader class="pb-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-5 w-5 rounded-full border-2 border-white shadow-md ring-1 ring-gray-200 dark:ring-gray-700"
                                            :style="{ backgroundColor: sumber.warna }"
                                        ></div>
                                        <CardTitle class="text-lg font-medium transition-colors group-hover:text-primary">
                                            {{ sumber.nama }}
                                        </CardTitle>
                                    </div>
                                    <div v-if="props.permissions.canCrud" class="flex gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                        <Button variant="ghost" size="sm" @click.stop="openEditModal(sumber)" class="h-8 w-8 p-0 hover:bg-primary/10">
                                            <Edit class="h-3 w-3" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click.stop="openDeleteModal(sumber)"
                                            class="h-8 w-8 p-0 text-destructive hover:bg-destructive/10 hover:text-destructive"
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
                                        class="px-2 py-1 text-xs font-medium"
                                        :style="{
                                            backgroundColor: sumber.warna + '15',
                                            color: sumber.warna,
                                            borderColor: sumber.warna + '30',
                                            border: '1px solid',
                                        }"
                                    >
                                        <Globe class="mr-1 h-3 w-3" />
                                        {{ sumber.warna.toUpperCase() }}
                                    </Badge>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </CardContent>
            </Card>

            <!-- Empty State -->
            <Card v-if="sumbers.length === 0" class="border-2 border-dashed border-gray-300 dark:border-gray-600">
                <CardContent class="py-16 text-center">
                    <div class="mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                        <Globe class="h-12 w-12 text-gray-400 dark:text-gray-500" />
                    </div>
                    <h3 class="mb-2 text-xl font-semibold text-gray-900 dark:text-gray-100">Belum ada sumber</h3>
                    <p class="mx-auto mb-6 max-w-sm text-gray-600 dark:text-gray-400">
                        Mulai dengan menambahkan sumber pertama untuk mengkategorikan transaksi Anda.
                    </p>
                    <Button 
                        v-if="props.permissions.canCrud"
                        @click="openCreateModal" 
                        size="lg" 
                        class="gap-2"
                    >
                        <Plus class="h-5 w-5" />
                        Tambah Sumber Pertama
                    </Button>
                </CardContent>
            </Card>
        </div>

        <!-- Modals -->
        <SumberModal
            :open="sumberModal.open"
            :mode="sumberModal.mode"
            :sumber="sumberModal.sumber"
            @close="closeSumberModal"
            @success="handleModalSuccess"
        />

        <SumberDeleteModal :open="deleteModal.open" :sumber="deleteModal.sumber" @close="closeDeleteModal" @success="handleModalSuccess" />
    </AppLayout>
</template>