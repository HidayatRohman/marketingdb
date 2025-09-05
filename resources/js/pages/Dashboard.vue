<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Users, UserCheck, Shield, Briefcase } from 'lucide-vue-next';

interface UserStats {
    total: number;
    super_admin: number;
    admin: number;
    marketing: number;
}

interface Props {
    userStats?: UserStats;
}

const props = withDefaults(defineProps<Props>(), {
    userStats: () => ({ total: 0, super_admin: 0, admin: 0, marketing: 0 }),
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Dashboard</h1>
                <p class="text-muted-foreground">
                    Selamat datang di Marketing Database
                </p>
            </div>

            <!-- Statistics Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Users</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ userStats.total }}</div>
                        <p class="text-xs text-muted-foreground">
                            Total pengguna sistem
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Super Admin</CardTitle>
                        <Shield class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ userStats.super_admin }}</div>
                        <p class="text-xs text-muted-foreground">
                            Super Administrator
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Admin</CardTitle>
                        <UserCheck class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ userStats.admin }}</div>
                        <p class="text-xs text-muted-foreground">
                            Administrator
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Marketing</CardTitle>
                        <Briefcase class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ userStats.marketing }}</div>
                        <p class="text-xs text-muted-foreground">
                            Marketing Staff
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Quick Actions -->
            <div class="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Quick Actions</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <Link href="/users" class="block">
                            <div class="flex items-center p-3 bg-muted rounded-lg hover:bg-muted/80 transition-colors">
                                <Users class="h-5 w-5 mr-3" />
                                <div>
                                    <p class="font-medium">Manage Users</p>
                                    <p class="text-sm text-muted-foreground">Kelola pengguna sistem</p>
                                </div>
                            </div>
                        </Link>
                        <Link href="/users/create" class="block">
                            <div class="flex items-center p-3 bg-muted rounded-lg hover:bg-muted/80 transition-colors">
                                <UserCheck class="h-5 w-5 mr-3" />
                                <div>
                                    <p class="font-medium">Add New User</p>
                                    <p class="text-sm text-muted-foreground">Tambah pengguna baru</p>
                                </div>
                            </div>
                        </Link>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Recent Activity</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <div class="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium">System ready</p>
                                    <p class="text-xs text-muted-foreground">All systems operational</p>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div class="w-2 h-2 bg-blue-500 rounded-full mr-3"></div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium">User management active</p>
                                    <p class="text-xs text-muted-foreground">CRUD operations available</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
