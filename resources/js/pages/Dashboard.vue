<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Users, UserCheck, Shield, Briefcase, Plus, BarChart3, TrendingUp, Activity, Clock, Calendar } from 'lucide-vue-next';

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
        <div class="space-y-8">
            <!-- Welcome Section -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 p-8 text-white">
                <div class="relative z-10">
                    <h1 class="text-4xl font-bold tracking-tight mb-2">Selamat Datang! 👋</h1>
                    <p class="text-xl text-blue-100 mb-6">
                        Mari kelola sistem Marketing Database dengan mudah dan efisien
                    </p>
                    <div class="flex gap-4">
                        <Link href="/users/create">
                            <Button class="bg-white text-blue-600 hover:bg-blue-50 font-semibold">
                                <Plus class="mr-2 h-5 w-5" />
                                Tambah User Baru
                            </Button>
                        </Link>
                        <Link href="/users">
                            <Button variant="outline" class="border-white text-white hover:bg-white/10">
                                <Users class="mr-2 h-5 w-5" />
                                Kelola Users
                            </Button>
                        </Link>
                    </div>
                </div>
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/5 rounded-full -ml-24 -mb-24"></div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <Card class="relative overflow-hidden border-0 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950 dark:to-blue-900">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-blue-700 dark:text-blue-300">Total Users</CardTitle>
                        <div class="p-2 bg-blue-500 rounded-lg">
                            <Users class="h-5 w-5 text-white" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-blue-900 dark:text-blue-100">{{ userStats.total }}</div>
                        <p class="text-xs text-blue-600 dark:text-blue-400 flex items-center mt-1">
                            <TrendingUp class="h-3 w-3 mr-1" />
                            Total pengguna aktif
                        </p>
                    </CardContent>
                    <div class="absolute bottom-0 right-0 w-16 h-16 bg-blue-200/30 rounded-full -mr-8 -mb-8"></div>
                </Card>

                <Card class="relative overflow-hidden border-0 bg-gradient-to-br from-red-50 to-red-100 dark:from-red-950 dark:to-red-900">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-red-700 dark:text-red-300">Super Admin</CardTitle>
                        <div class="p-2 bg-red-500 rounded-lg">
                            <Shield class="h-5 w-5 text-white" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-red-900 dark:text-red-100">{{ userStats.super_admin }}</div>
                        <p class="text-xs text-red-600 dark:text-red-400 flex items-center mt-1">
                            <Activity class="h-3 w-3 mr-1" />
                            Akses penuh sistem
                        </p>
                    </CardContent>
                    <div class="absolute bottom-0 right-0 w-16 h-16 bg-red-200/30 rounded-full -mr-8 -mb-8"></div>
                </Card>

                <Card class="relative overflow-hidden border-0 bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-950 dark:to-amber-900">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-amber-700 dark:text-amber-300">Admin</CardTitle>
                        <div class="p-2 bg-amber-500 rounded-lg">
                            <UserCheck class="h-5 w-5 text-white" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-amber-900 dark:text-amber-100">{{ userStats.admin }}</div>
                        <p class="text-xs text-amber-600 dark:text-amber-400 flex items-center mt-1">
                            <BarChart3 class="h-3 w-3 mr-1" />
                            Kelola operasional
                        </p>
                    </CardContent>
                    <div class="absolute bottom-0 right-0 w-16 h-16 bg-amber-200/30 rounded-full -mr-8 -mb-8"></div>
                </Card>

                <Card class="relative overflow-hidden border-0 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950 dark:to-green-900">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-green-700 dark:text-green-300">Marketing</CardTitle>
                        <div class="p-2 bg-green-500 rounded-lg">
                            <Briefcase class="h-5 w-5 text-white" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-green-900 dark:text-green-100">{{ userStats.marketing }}</div>
                        <p class="text-xs text-green-600 dark:text-green-400 flex items-center mt-1">
                            <Calendar class="h-3 w-3 mr-1" />
                            Tim pemasaran
                        </p>
                    </CardContent>
                    <div class="absolute bottom-0 right-0 w-16 h-16 bg-green-200/30 rounded-full -mr-8 -mb-8"></div>
                </Card>
            </div>

            <!-- Main Content Grid -->
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Quick Actions -->
                <div class="lg:col-span-2">
                    <Card class="border-0 shadow-lg">
                        <CardHeader>
                            <CardTitle class="flex items-center text-xl">
                                <Activity class="mr-3 h-6 w-6 text-blue-500" />
                                Aksi Cepat
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <Link href="/users" class="group">
                                    <div class="relative overflow-hidden rounded-xl bg-gradient-to-r from-blue-500 to-blue-600 p-6 text-white transition-all duration-300 hover:from-blue-600 hover:to-blue-700 hover:scale-105 hover:shadow-xl">
                                        <div class="flex items-center">
                                            <div class="p-2 bg-white/20 rounded-lg mr-4">
                                                <Users class="h-6 w-6" />
                                            </div>
                                            <div>
                                                <h3 class="font-semibold text-lg">Kelola Users</h3>
                                                <p class="text-blue-100 text-sm">Lihat dan edit semua pengguna</p>
                                            </div>
                                        </div>
                                        <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -mr-10 -mt-10"></div>
                                    </div>
                                </Link>
                                
                                <Link href="/users/create" class="group">
                                    <div class="relative overflow-hidden rounded-xl bg-gradient-to-r from-green-500 to-green-600 p-6 text-white transition-all duration-300 hover:from-green-600 hover:to-green-700 hover:scale-105 hover:shadow-xl">
                                        <div class="flex items-center">
                                            <div class="p-2 bg-white/20 rounded-lg mr-4">
                                                <Plus class="h-6 w-6" />
                                            </div>
                                            <div>
                                                <h3 class="font-semibold text-lg">Tambah User</h3>
                                                <p class="text-green-100 text-sm">Buat pengguna baru</p>
                                            </div>
                                        </div>
                                        <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -mr-10 -mt-10"></div>
                                    </div>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- System Status -->
                <Card class="border-0 shadow-lg">
                    <CardHeader>
                        <CardTitle class="flex items-center text-xl">
                            <Clock class="mr-3 h-6 w-6 text-green-500" />
                            Status Sistem
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div class="flex items-center p-4 bg-green-50 dark:bg-green-950/20 rounded-lg">
                                <div class="w-3 h-3 bg-green-500 rounded-full mr-3 animate-pulse"></div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-green-800 dark:text-green-200">Server Online</p>
                                    <p class="text-xs text-green-600 dark:text-green-400">Semua sistem berjalan normal</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center p-4 bg-blue-50 dark:bg-blue-950/20 rounded-lg">
                                <div class="w-3 h-3 bg-blue-500 rounded-full mr-3 animate-pulse"></div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Database Aktif</p>
                                    <p class="text-xs text-blue-600 dark:text-blue-400">Koneksi database stabil</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center p-4 bg-purple-50 dark:bg-purple-950/20 rounded-lg">
                                <div class="w-3 h-3 bg-purple-500 rounded-full mr-3 animate-pulse"></div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-purple-800 dark:text-purple-200">CRUD Tersedia</p>
                                    <p class="text-xs text-purple-600 dark:text-purple-400">Operasi user management aktif</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
