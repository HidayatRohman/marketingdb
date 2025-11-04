<script setup lang="ts">
import SolusiDeleteModal from '@/components/SolusiDeleteModal.vue';
import SolusiModal from '@/components/SolusiModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Edit, Plus, Lightbulb, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

interface Solusi {
    id: number;
    nama: string;
    warna: string;
    created_at: string;
    updated_at: string;
}

interface Props {
    solusis: Solusi[];
}

const props = defineProps<Props>();

// Modal states
const solusiModal = ref({
    open: false,
    mode: 'create' as 'create' | 'edit' | 'view',
    solusi: undefined as Solusi | undefined,
});

const deleteModal = ref({
    open: false,
    solusi: undefined as Solusi | undefined,
});

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Solusi', href: '/solusis' },
];

// Modal functions
const openCreateModal = () => {
    solusiModal.value = { open: true, mode: 'create', solusi: undefined };
};

const openEditModal = (solusi: Solusi) => {
    solusiModal.value = { open: true, mode: 'edit', solusi: { ...solusi } };
};

const openViewModal = (solusi: Solusi) => {
    solusiModal.value = { open: true, mode: 'view', solusi: { ...solusi } };
};

const openDeleteModal = (solusi: Solusi) => {
    deleteModal.value = { open: true, solusi: { ...solusi } };
};

const closeSolusiModal = () => {
    solusiModal.value = { open: false, mode: 'create', solusi: undefined };
};

const closeDeleteModal = () => {
    deleteModal.value = { open: false, solusi: undefined };
};

const handleModalSuccess = () => {
    router.reload({ only: ['solusis'] });
};
</script>

<template>
    <Head title="Kelola Solusi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-6 mt-6 space-y-6">
            <!-- Header Section -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-600 via-green-600 to-teal-600 p-4 sm:p-6 text-white my-2 sm:my-3 shadow-sm">
                <div class="relative z-10">
                    <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 class="mb-2 flex items-center gap-3 text-4xl font-bold tracking-tight">
                                <Lightbulb class="h-10 w-10" />
                                Kelola Solusi
                            </h1>
                            <p class="text-xl text-emerald-100">Kelola solusi dengan warna untuk kategorisasi cepat</p>
                        </div>
                        <Button @click="openCreateModal" size="lg" class="gap-2 bg-white font-semibold text-emerald-700 hover:bg-emerald-50 w-full md:w-auto">
                            <Plus class="h-5 w-5" />
                            Tambah Solusi
                        </Button>
                    </div>
                </div>
                <div class="absolute inset-0 opacity-20">
                    <div class="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
                </div>
            </div>

            <!-- Solusi Cards -->
            <Card class="border-0 shadow-sm">
                <CardHeader class="pb-4">
                    <CardTitle class="text-xl font-semibold text-gray-900 dark:text-gray-100"> Daftar Solusi </CardTitle>
                    <p class="text-sm text-muted-foreground">{{ solusis.length }} solusi tersedia untuk kategorisasi produk/CS</p>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <Card
                            v-for="solusi in solusis"
                            :key="solusi.id"
                            class="group cursor-pointer border-2 transition-all duration-200 hover:border-primary/20 hover:shadow-lg"
                            @click="openViewModal(solusi)"
                        >
                            <CardHeader class="pb-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="h-5 w-5 rounded-full border-2 border-white shadow-md ring-1 ring-gray-200 dark:ring-gray-700" :style="{ backgroundColor: solusi.warna }" />
                                        <CardTitle class="text-lg font-medium transition-colors group-hover:text-primary">
                                            {{ solusi.nama }}
                                        </CardTitle>
                                    </div>
                                    <div class="flex gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                        <Button variant="ghost" size="sm" @click.stop="openEditModal(solusi)" class="h-8 w-8 p-0 hover:bg-primary/10">
                                            <Edit class="h-3 w-3" />
                                        </Button>
                                        <Button variant="ghost" size="sm" @click.stop="openDeleteModal(solusi)" class="h-8 w-8 p-0 text-destructive hover:bg-destructive/10 hover:text-destructive">
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
                                            backgroundColor: solusi.warna + '15',
                                            color: solusi.warna,
                                            borderColor: solusi.warna + '30',
                                            border: '1px solid',
                                        }"
                                    >
                                        <Lightbulb class="mr-1 h-3 w-3" />
                                        {{ solusi.warna.toUpperCase() }}
                                    </Badge>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </CardContent>
            </Card>

            <!-- Empty State -->
            <Card v-if="solusis.length === 0" class="border-2 border-dashed border-gray-300 dark:border-gray-600">
                <CardContent class="py-16 text-center">
                    <div class="mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                        <Lightbulb class="h-12 w-12 text-gray-400 dark:text-gray-500" />
                    </div>
                    <h3 class="mb-2 text-xl font-semibold text-gray-900 dark:text-gray-100">Belum ada solusi</h3>
                    <p class="mx-auto mb-6 max-w-sm text-gray-600 dark:text-gray-400">
                        Mulai dengan menambahkan solusi pertama untuk membantu kategorisasi dan analisis.
                    </p>
                    <Button @click="openCreateModal" size="lg" class="gap-2">
                        <Plus class="h-5 w-5" />
                        Tambah Solusi Pertama
                    </Button>
                </CardContent>
            </Card>
        </div>

        <!-- Modals -->
        <SolusiModal :open="solusiModal.open" :mode="solusiModal.mode" :solusi="solusiModal.solusi" @close="closeSolusiModal" @success="handleModalSuccess" />

        <SolusiDeleteModal :open="deleteModal.open" :solusi="deleteModal.solusi" @close="closeDeleteModal" @success="handleModalSuccess" />
    </AppLayout>
</template>