<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Search, Plus, Edit, Trash2, Eye } from 'lucide-vue-next';

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

const search = ref(props.filters.search || '');
const role = ref(props.filters.role || '');

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users', href: '/users' },
];

const roleColors = {
    super_admin: 'bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-medium',
    admin: 'bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium',
    marketing: 'bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium',
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
        router.get('/users', {
            search: search.value || undefined,
            role: role.value || undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

const deleteUser = (user: User) => {
    if (window.confirm(`Apakah Anda yakin ingin menghapus user ${user.name}?`)) {
        router.delete(`/users/${user.id}`);
    }
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
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Users</h1>
                    <p class="text-muted-foreground">
                        Kelola pengguna sistem
                    </p>
                </div>
                <Link href="/users/create">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Tambah User
                    </Button>
                </Link>
            </div>

            <!-- Filters -->
            <Card>
                <CardContent class="pt-6">
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <div class="relative">
                                <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                <Input
                                    v-model="search"
                                    placeholder="Cari nama atau email..."
                                    class="pl-10"
                                />
                            </div>
                        </div>
                        <div class="w-48">
                            <select 
                                v-model="role" 
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
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
            <Card>
                <CardHeader>
                    <CardTitle>Daftar Users</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full caption-bottom text-sm">
                            <thead class="border-b">
                                <tr class="border-b transition-colors hover:bg-muted/50">
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Nama</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Email</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Role</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Tanggal Dibuat</th>
                                    <th class="h-12 px-4 text-right align-middle font-medium text-muted-foreground">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="border-0">
                                <tr v-for="user in users.data" :key="user.id" class="border-b transition-colors hover:bg-muted/50">
                                    <td class="p-4 align-middle font-medium">{{ user.name }}</td>
                                    <td class="p-4 align-middle">{{ user.email }}</td>
                                    <td class="p-4 align-middle">
                                        <span :class="roleColors[user.role]">
                                            {{ roleLabels[user.role] }}
                                        </span>
                                    </td>
                                    <td class="p-4 align-middle">{{ formatDate(user.created_at) }}</td>
                                    <td class="p-4 align-middle text-right">
                                        <div class="flex justify-end gap-2">
                                            <Link :href="`/users/${user.id}`">
                                                <Button variant="ghost" size="sm">
                                                    <Eye class="h-4 w-4" />
                                                </Button>
                                            </Link>
                                            <Link :href="`/users/${user.id}/edit`">
                                                <Button variant="ghost" size="sm">
                                                    <Edit class="h-4 w-4" />
                                                </Button>
                                            </Link>
                                            <Button 
                                                variant="ghost" 
                                                size="sm"
                                                @click="deleteUser(user)"
                                                class="text-red-600 hover:text-red-700"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4 flex items-center justify-between">
                        <div class="text-sm text-muted-foreground">
                            Menampilkan {{ users.data.length }} dari {{ users.total }} users
                        </div>
                        <div class="flex gap-2">
                            <Link v-if="users.prev_page_url" :href="users.prev_page_url">
                                <Button variant="outline" size="sm">Previous</Button>
                            </Link>
                            <Link v-if="users.next_page_url" :href="users.next_page_url">
                                <Button variant="outline" size="sm">Next</Button>
                            </Link>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
