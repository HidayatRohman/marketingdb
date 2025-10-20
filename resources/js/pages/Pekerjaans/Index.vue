<script setup lang="ts">
import PekerjaanDeleteModal from '@/components/PekerjaanDeleteModal.vue';
import PekerjaanModal from '@/components/PekerjaanModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Edit, Plus, Briefcase, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

interface Pekerjaan {
    id: number;
    nama: string;
    warna: string;
    created_at: string;
    updated_at: string;
}

interface Props {
    pekerjaans: Pekerjaan[];
    permissions: {
        canCrud: boolean;
        canOnlyView: boolean;
        canOnlyViewOwn: boolean;
    };
}

const props = defineProps<Props>();

// Modal states
const pekerjaanModal = ref({
    open: false,
    mode: 'create' as 'create' | 'edit' | 'view',
    pekerjaan: undefined as Pekerjaan | undefined,
});

const deleteModal = ref({
    open: false,
    pekerjaan: undefined as Pekerjaan | undefined,
});

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pekerjaan', href: '/pekerjaans' },
];

// Modal functions
const openCreateModal = () => {
    pekerjaanModal.value = {
        open: true,
        mode: 'create',
        pekerjaan: undefined,
    };
};

const openEditModal = (pekerjaan: Pekerjaan) => {
    pekerjaanModal.value = {
        open: true,
        mode: 'edit',
        pekerjaan: { ...pekerjaan },
    };
};

const openViewModal = (pekerjaan: Pekerjaan) => {
    pekerjaanModal.value = {
        open: true,
        mode: 'view',
        pekerjaan: { ...pekerjaan },
    };
};

const openDeleteModal = (pekerjaan: Pekerjaan) => {
    deleteModal.value = {
        open: true,
        pekerjaan: { ...pekerjaan },
    };
};

const closePekerjaanModal = () => {
    pekerjaanModal.value = {
        open: false,
        mode: 'create',
        pekerjaan: undefined,
    };
};

const closeDeleteModal = () => {
    deleteModal.value = {
        open: false,
        pekerjaan: undefined,
    };
};

const handleModalSuccess = () => {
    // Refresh the page data
    router.reload({ only: ['pekerjaans'] });
};
</script>

<template>
    <Head title="Kelola Pekerjaan" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-6 mt-6 space-y-6">
            <!-- Header Section -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-green-600 via-teal-600 to-cyan-600 p-4 sm:p-6 text-white my-2 sm:my-3 shadow-sm">
                <div class="relative z-10">
                    <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 class="mb-2 flex items-center gap-3 text-4xl font-bold tracking-tight">
                                <Briefcase class="h-10 w-10" />
                                Kelola Pekerjaan
                            </h1>
                            <p class="text-xl text-green-100">Kelola daftar pekerjaan untuk pengelompokan data</p>
                        </div>
                        <Button 
                            v-if="props.permissions.canCrud"
                            @click="openCreateModal" 
                            size="lg" 
                            class="gap-2 bg-white font-semibold text-green-600 hover:bg-green-50 w-full md:w-auto"
                        >
                            <Plus class="h-5 w-5" />
                            Tambah Pekerjaan
                        </Button>
                    </div>
                </div>
                <!-- Background decoration -->
                <div class="absolute inset-0 opacity-20">
                    <div class="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
                </div>
            </div>

            <!-- Pekerjaan Cards -->
            <Card class="border-0 shadow-sm">
                <CardHeader class="pb-4">
                    <CardTitle class="text-xl font-semibold text-gray-900 dark:text-gray-100"> Daftar Pekerjaan </CardTitle>
                    <p class="text-sm text-muted-foreground">{{ pekerjaans.length }} pekerjaan tersedia</p>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <Card
                            v-for="pekerjaan in pekerjaans"
                            :key="pekerjaan.id"
                            class="group cursor-pointer border-2 transition-all duration-200 hover:border-primary/20 hover:shadow-lg"
                            @click="openViewModal(pekerjaan)"
                        >
                            <CardHeader class="pb-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-5 w-5 rounded-full border-2 border-white shadow-md ring-1 ring-gray-200 dark:ring-gray-700"
                                            :style="{ backgroundColor: pekerjaan.warna }"
                                        ></div>
                                        <CardTitle class="text-lg font-medium transition-colors group-hover:text-primary">
                                            {{ pekerjaan.nama }}
                                        </CardTitle>
                                    </div>
                                    <div v-if="props.permissions.canCrud" class="flex gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                        <Button variant="ghost" size="sm" @click.stop="openEditModal(pekerjaan)" class="h-8 w-8 p-0 hover:bg-primary/10">
                                            <Edit class="h-3 w-3" />
                                        </Button>
                                        <Button variant="ghost" size="sm" @click.stop="openDeleteModal(pekerjaan)" class="h-8 w-8 p-0 hover:bg-destructive/10">
                                            <Trash2 class="h-3 w-3 text-destructive" />
                                        </Button>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent class="pt-0">
                                <div class="flex items-center justify-between text-sm text-muted-foreground">
                                    <Badge variant="outline" class="px-2 py-1">
                                        {{ pekerjaan.warna.toUpperCase() }}
                                    </Badge>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </CardContent>
            </Card>

            <!-- Empty State -->
            <Card v-if="pekerjaans.length === 0" class="border-2 border-dashed border-gray-300 dark:border-gray-600">
                <CardContent class="py-16 text-center">
                    <div class="mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                        <Briefcase class="h-12 w-12 text-gray-400 dark:text-gray-500" />
                    </div>
                    <h3 class="mb-2 text-xl font-semibold text-gray-900 dark:text-gray-100">Belum ada pekerjaan</h3>
                    <p class="mx-auto mb-6 max-w-sm text-gray-600 dark:text-gray-400">
                        Mulai dengan menambahkan pekerjaan pertama untuk pengelompokan data Anda.
                    </p>
                    <Button 
                        v-if="props.permissions.canCrud"
                        @click="openCreateModal" 
                        size="lg" 
                        class="gap-2"
                    >
                        <Plus class="h-5 w-5" />
                        Tambah Pekerjaan Pertama
                    </Button>
                </CardContent>
            </Card>

            <!-- Modals -->
            <PekerjaanModal
                :open="pekerjaanModal.open"
                :mode="pekerjaanModal.mode"
                :pekerjaan="pekerjaanModal.pekerjaan"
                @close="closePekerjaanModal"
                @success="handleModalSuccess"
            />
            <PekerjaanDeleteModal
                :open="deleteModal.open"
                :pekerjaan="deleteModal.pekerjaan"
                @close="closeDeleteModal"
                @success="handleModalSuccess"
            />
        </div>
    </AppLayout>
</template>