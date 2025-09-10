<script setup lang="ts">
import DeleteConfirmModal from '@/components/DeleteConfirmModal.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import UserModal from '@/components/UserModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Edit, Eye, Filter, MoreHorizontal, Plus, Search, Trash2, Users } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
    role: 'super_admin' | 'admin' | 'marketing';
    created_at: string;
}

interface Props {
    users: {
        data: User[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filters: {
        search?: string;
        role?: string;
    };
}

const props = defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as any);

// Check if current user is admin (should hide actions)
const isAdmin = computed(() => {
    return auth.value?.user?.role === 'admin';
});

const search = ref(props.filters.search || '');
const role = ref(props.filters.role || '');

// Modal states
const userModal = ref({
    open: false,
    mode: 'create' as 'create' | 'edit' | 'view',
    user: undefined as User | undefined,
});

const deleteModal = ref({
    open: false,
    user: undefined as User | undefined,
});

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users', href: '/users' },
];

const roleColors = {
    super_admin: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 px-3 py-1 rounded-full text-xs font-medium',
    admin: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 px-3 py-1 rounded-full text-xs font-medium',
    marketing: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 px-3 py-1 rounded-full text-xs font-medium',
};

const roleLabels = {
    super_admin: 'Super Admin',
    admin: 'Admin',
    marketing: 'Marketing',
};

let debounceTimer: number;

// Watch for filter changes and update URL
watch([search, role], () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(
            '/users',
            {
                search: search.value || undefined,
                role: role.value || undefined,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    }, 300);
});

// Modal functions
const openCreateModal = () => {
    userModal.value = {
        open: true,
        mode: 'create',
        user: undefined,
    };
};

const openEditModal = (user: User) => {
    userModal.value = {
        open: true,
        mode: 'edit',
        user: { ...user },
    };
};

const openViewModal = (user: User) => {
    userModal.value = {
        open: true,
        mode: 'view',
        user: { ...user },
    };
};

const openDeleteModal = (user: User) => {
    deleteModal.value = {
        open: true,
        user: { ...user },
    };
};

const closeUserModal = () => {
    userModal.value = {
        open: false,
        mode: 'create',
        user: undefined,
    };
};

const closeDeleteModal = () => {
    deleteModal.value = {
        open: false,
        user: undefined,
    };
};

const handleModalSuccess = () => {
    // Refresh the page data
    router.reload({ only: ['users'] });
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
    <Head title="Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-6 mt-6 space-y-6">
            <!-- Header Section -->
            <div class="relative overflow-hidden rounded-xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 p-4 text-white sm:p-6">
                <div class="relative z-10">
                    <!-- Header Content - Responsive Layout -->
                    <div class="flex flex-col space-y-4 lg:flex-row lg:items-center lg:justify-between lg:space-y-0">
                        <!-- Title Section -->
                        <div class="flex-1">
                            <h1 class="mb-2 flex items-center gap-2 text-xl font-bold tracking-tight sm:gap-3 sm:text-2xl lg:text-3xl">
                                <Users class="h-5 w-5 sm:h-6 sm:w-6 lg:h-8 lg:w-8" />
                                Manajemen Users
                            </h1>
                            <p class="text-sm text-purple-100 sm:text-base lg:text-lg">Kelola pengguna sistem dengan mudah dan efisien</p>
                        </div>
                        
                        <!-- Action Button - Responsive -->
                        <div v-if="!isAdmin" class="lg:flex-shrink-0">
                            <Button 
                                @click="openCreateModal" 
                                class="w-full border border-white/50 bg-gradient-to-r from-white to-gray-100 px-4 py-2 text-sm font-semibold text-purple-600 shadow-lg transition-all duration-200 hover:from-purple-50 hover:to-white dark:border-gray-700 dark:from-gray-800 dark:to-gray-900 dark:text-purple-400 dark:hover:from-gray-700 dark:hover:to-gray-800 sm:w-auto sm:text-base lg:text-base"
                            >
                                <Plus class="mr-1 h-4 w-4 sm:mr-2" />
                                <span class="sm:hidden">Tambah</span>
                                <span class="hidden sm:inline">Tambah User</span>
                            </Button>
                        </div>
                    </div>
                </div>
                <div class="absolute top-0 right-0 -mt-24 -mr-24 h-48 w-48 rounded-full bg-white/10"></div>
                <div class="absolute bottom-0 left-0 -mb-16 -ml-16 h-32 w-32 rounded-full bg-white/5"></div>
            </div>

            <!-- Statistics Bar -->
            <div class="stats-grid grid gap-4 md:grid-cols-4">
                <Card class="border-0 bg-gradient-to-br from-blue-50 to-blue-100 shadow-lg dark:from-blue-950 dark:to-blue-900">
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-blue-700 dark:text-blue-300">Total Users</p>
                                <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ users.total }}</p>
                            </div>
                            <div class="rounded-lg bg-blue-500 p-2">
                                <Users class="h-5 w-5 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-green-50 to-green-100 shadow-lg dark:from-green-950 dark:to-green-900">
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-green-700 dark:text-green-300">Halaman Ini</p>
                                <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ users.data.length }}</p>
                            </div>
                            <div class="rounded-lg bg-green-500 p-2">
                                <Eye class="h-5 w-5 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-purple-50 to-purple-100 shadow-lg dark:from-purple-950 dark:to-purple-900">
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-purple-700 dark:text-purple-300">Per Halaman</p>
                                <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ users.per_page }}</p>
                            </div>
                            <div class="rounded-lg bg-purple-500 p-2">
                                <Filter class="h-5 w-5 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-orange-50 to-orange-100 shadow-lg dark:from-orange-950 dark:to-orange-900">
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-orange-700 dark:text-orange-300">Halaman</p>
                                <p class="text-2xl font-bold text-orange-900 dark:text-orange-100">
                                    {{ users.current_page }} / {{ users.last_page }}
                                </p>
                            </div>
                            <div class="rounded-lg bg-orange-500 p-2">
                                <MoreHorizontal class="h-5 w-5 text-white" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card class="border-0 shadow-md">
                <CardContent class="pt-6">
                    <div class="flex flex-col gap-4 sm:flex-row">
                        <div class="flex-1">
                            <div class="relative">
                                <Search class="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                <Input v-model="search" placeholder="Cari nama atau email..." class="h-12 pl-10 text-base" />
                            </div>
                        </div>
                        <div class="w-full sm:w-48">
                            <select
                                v-model="role"
                                class="flex h-12 w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">Semua Role</option>
                                <option value="super_admin">Super Admin</option>
                                <option value="admin">Admin</option>
                                <option value="marketing">Marketing</option>
                            </select>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Users Table -->
            <Card class="border-0 shadow-md">
                <CardHeader class="pb-2">
                    <CardTitle class="text-lg font-semibold">Daftar Users</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="relative overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full caption-bottom text-sm">
                            <thead class="border-b-2">
                                <tr class="border-b transition-colors">
                                    <th class="h-14 px-4 text-left align-middle font-semibold text-muted-foreground">Nama</th>
                                    <th class="h-14 px-4 text-left align-middle font-semibold text-muted-foreground">Email</th>
                                    <th class="h-14 px-4 text-left align-middle font-semibold text-muted-foreground">Role</th>
                                    <th class="h-14 px-4 text-left align-middle font-semibold text-muted-foreground">Tanggal Dibuat</th>
                                    <th class="h-14 px-4 text-center align-middle font-semibold text-muted-foreground">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="border-0">
                                <tr v-for="user in users.data" :key="user.id" class="border-b transition-colors hover:bg-muted/50">
                                    <td class="p-4 align-middle">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-purple-600 text-sm font-semibold text-white"
                                            >
                                                {{ user.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <div>
                                                <p class="font-medium">{{ user.name }}</p>
                                                <p class="text-sm text-muted-foreground">ID: {{ user.id }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div class="flex items-center gap-2">
                                            <div class="rounded bg-gray-100 p-1 dark:bg-gray-800">
                                                <svg class="h-4 w-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                                                </svg>
                                            </div>
                                            <span>{{ user.email }}</span>
                                        </div>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <span :class="roleColors[user.role]">
                                            {{ roleLabels[user.role] }}
                                        </span>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div class="flex items-center gap-2">
                                            <div class="rounded bg-gray-100 p-1 dark:bg-gray-800">
                                                <svg class="h-4 w-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                                        clip-rule="evenodd"
                                                    ></path>
                                                </svg>
                                            </div>
                                            <span class="text-sm">{{ formatDate(user.created_at) }}</span>
                                        </div>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div class="flex justify-center gap-2">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click="openViewModal(user)"
                                                class="h-9 w-9 p-0 hover:bg-blue-100 hover:text-blue-600 dark:hover:bg-blue-900/30"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                            <!-- Hide Edit and Delete buttons for admin users -->
                                            <Button
                                                v-if="!isAdmin"
                                                variant="ghost"
                                                size="sm"
                                                @click="openEditModal(user)"
                                                class="h-9 w-9 p-0 hover:bg-green-100 hover:text-green-600 dark:hover:bg-green-900/30"
                                            >
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                v-if="!isAdmin"
                                                variant="ghost"
                                                size="sm"
                                                @click="openDeleteModal(user)"
                                                class="h-9 w-9 p-0 hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-900/30"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                    </div>

                    <!-- Enhanced Pagination -->
                    <div class="pagination-wrapper mt-6 flex flex-col gap-4 rounded-lg bg-muted/30 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-sm text-muted-foreground">
                            Menampilkan <span class="font-medium">{{ users.data.length }}</span> dari
                            <span class="font-medium">{{ users.total }}</span> users
                        </div>
                        <div class="flex items-center gap-2">
                            <Button v-if="users.prev_page_url" variant="outline" size="sm" @click="router.get(users.prev_page_url)" class="h-9">
                                ← Previous
                            </Button>
                            <div class="mx-2 flex items-center gap-1">
                                <span class="text-sm text-muted-foreground"> Page {{ users.current_page }} of {{ users.last_page }} </span>
                            </div>
                            <Button v-if="users.next_page_url" variant="outline" size="sm" @click="router.get(users.next_page_url)" class="h-9">
                                Next →
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Modals -->
        <UserModal :open="userModal.open" :mode="userModal.mode" :user="userModal.user" @close="closeUserModal" @success="handleModalSuccess" />

        <DeleteConfirmModal :open="deleteModal.open" :user="deleteModal.user" @close="closeDeleteModal" @success="handleModalSuccess" />
    </AppLayout>
</template>

<style scoped>
/* Responsive table improvements */
@media (max-width: 768px) {
    /* Stack pagination on mobile */
    .pagination-wrapper {
        flex-direction: column;
        gap: 1rem;
    }
    
    /* Improve table readability on mobile */
    table {
        font-size: 0.875rem;
    }
    
    th, td {
        padding: 0.5rem !important;
    }
    
    /* Hide less important columns on very small screens */
    .hide-on-mobile {
        display: none;
    }
}

@media (max-width: 640px) {
    /* Further optimize for very small screens */
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Ensure consistent card spacing */
.space-y-6 > * + * {
    margin-top: 1.5rem;
}

/* Improve button touch targets on mobile */
@media (max-width: 640px) {
    button {
        min-height: 44px;
        min-width: 44px;
    }
}
</style>
