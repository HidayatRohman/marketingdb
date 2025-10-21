<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useForm, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Mail, Search, ShieldCheck, User, UserPlus } from 'lucide-vue-next';
import UserModal from '@/components/UserModal.vue';

interface User {
    id: number;
    name: string;
    email: string;
    role: 'super_admin' | 'admin' | 'marketing' | 'advertiser';
}

interface PageProps {
    users: User[];
    filters: {
        search?: string;
        role?: 'super_admin' | 'admin' | 'marketing' | 'advertiser' | '';
    };
}

const page = usePage<PageProps>();

const search = ref(page.props.filters?.search || '');
const roleFilter = ref<PageProps['filters']['role']>(page.props.filters?.role || '');

const users = computed<User[]>(() => {
  const u = page.props.users as any
  const list = Array.isArray(u) ? u : (u?.data ?? [])
  return list.filter((item): item is User => !!item)
})

const openModal = ref(false);
const modalMode = ref<'create' | 'edit' | 'view'>('create');
const selectedUser = ref<User | undefined>(undefined);

const showCreateModal = () => {
    selectedUser.value = undefined;
    modalMode.value = 'create';
    openModal.value = true;
};

const showEditModal = (user: User) => {
    selectedUser.value = user;
    modalMode.value = 'edit';
    openModal.value = true;
};

const showViewModal = (user: User) => {
    selectedUser.value = user;
    modalMode.value = 'view';
    openModal.value = true;
};

const closeModal = () => {
    openModal.value = false;
};

const onSuccess = () => {
    router.reload({ only: ['users', 'filters', 'permissions'] })
}

// Apply filters by fetching users with query params
const applyFilters = () => {
    router.get('/users', {
        search: search.value || undefined,
        role: roleFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}

const roleLabels = {
    super_admin: 'Super Admin',
    admin: 'Admin',
    marketing: 'Marketing',
    advertiser: 'Advertiser',
};
// Breadcrumbs
const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users', href: '/users' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="layout-content space-y-6">
            <div class="rounded-xl border shadow-sm bg-gradient-to-r from-indigo-50 via-sky-50 to-emerald-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-700 p-4 sm:p-6 my-2 sm:my-3">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold">Manajemen Pengguna</h1>
                        <p class="text-muted-foreground">Kelola akses pengguna sistem Marketing Database</p>
                    </div>
                    <Button @click="showCreateModal" class="gap-2 w-full sm:w-auto">
                        <UserPlus class="h-4 w-4" />
                        Tambah Pengguna
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="col-span-2">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <Input v-model="search" @keyup.enter="applyFilters" placeholder="Cari nama atau email" class="w-full sm:flex-1" />
                        <select v-model="roleFilter" @change="applyFilters" class="w-full sm:w-44 rounded-md border px-3 py-2 text-sm">
                            <option value="">Semua Role</option>
                            <option value="marketing">Marketing</option>
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                            <option value="advertiser">Advertiser</option>
                        </select>
                        <Button variant="outline" class="w-full sm:w-auto gap-2" @click="applyFilters">
                            <Search class="h-4 w-4" />
                            Filter
                        </Button>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead>
                        <tr class="bg-muted">
                            <th class="p-2 sm:p-3 text-left">Nama</th>
                            <th class="p-2 sm:p-3 text-left">Email</th>
                            <th class="p-2 sm:p-3 text-left">Role</th>
                            <th class="p-2 sm:p-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users" :key="user.id" class="border-t">
                            <td class="p-2 sm:p-3">{{ user.name }}</td>
                            <td class="p-2 sm:p-3">{{ user.email }}</td>
                            <td class="p-2 sm:p-3">
                                <Badge
                                    :class="{
                                        'bg-red-100 text-red-800': user.role === 'super_admin',
                                        'bg-blue-100 text-blue-800': user.role === 'admin',
                                        'bg-green-100 text-green-800': user.role === 'marketing',
                                        'bg-orange-100 text-orange-800': user.role === 'advertiser',
                                    }"
                                >
                                    {{ roleLabels[user.role] }}
                                </Badge>
                            </td>
                            <td class="p-2 sm:p-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Button variant="outline" size="sm" @click="showViewModal(user)">
                                        Detail
                                    </Button>
                                    <Button size="sm" @click="showEditModal(user)">Edit</Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Dialog :open="openModal" @update:open="(val) => !val && closeModal()">
                <DialogContent class="sm:max-w-[600px]">
                    <DialogHeader>
                        <DialogTitle>Pengguna</DialogTitle>
                    </DialogHeader>
                    <UserModal :open="openModal" :mode="modalMode" :user="selectedUser" @success="onSuccess" @close="closeModal" />
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
