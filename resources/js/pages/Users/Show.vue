<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Edit, Trash2 } from 'lucide-vue-next';

interface User {
    id: number;
    name: string;
    email: string;
    role: 'super_admin' | 'admin' | 'marketing';
    created_at: string;
    updated_at: string;
}

interface Props {
    user: User;
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users', href: '/users' },
    { title: props.user.name, href: `/users/${props.user.id}` },
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

const deleteUser = () => {
    if (window.confirm(`Apakah Anda yakin ingin menghapus user ${props.user.name}?`)) {
        router.delete(`/users/${props.user.id}`);
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head :title="user.name" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link href="/users">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft class="h-4 w-4" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">{{ user.name }}</h1>
                        <p class="text-muted-foreground">
                            Detail informasi user
                        </p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Link :href="`/users/${user.id}/edit`">
                        <Button>
                            <Edit class="mr-2 h-4 w-4" />
                            Edit User
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- User Information -->
            <div class="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Informasi Dasar</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Nama</label>
                            <p class="text-base font-medium">{{ user.name }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Email</label>
                            <p class="text-base">{{ user.email }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Role</label>
                            <div class="mt-1">
                                <span :class="roleColors[user.role]">
                                    {{ roleLabels[user.role] }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Informasi Sistem</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">ID User</label>
                            <p class="text-base font-mono">{{ user.id }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Tanggal Dibuat</label>
                            <p class="text-base">{{ formatDate(user.created_at) }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Terakhir Diupdate</label>
                            <p class="text-base">{{ formatDate(user.updated_at) }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Actions -->
            <Card>
                <CardHeader>
                    <CardTitle>Aksi</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex gap-4">
                        <Link :href="`/users/${user.id}/edit`">
                            <Button>
                                <Edit class="mr-2 h-4 w-4" />
                                Edit User
                            </Button>
                        </Link>
                        <Button 
                            variant="destructive"
                            @click="deleteUser"
                        >
                            <Trash2 class="mr-2 h-4 w-4" />
                            Hapus User
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
