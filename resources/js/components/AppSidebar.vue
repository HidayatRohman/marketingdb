<script setup lang="ts">
// import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Calendar, CreditCard, Handshake, Kanban, LayoutGrid, Tag, TrendingUp, Users, Zap, Globe, Briefcase, Settings, BarChart3 } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage();

// Get user permissions from auth data
const auth = computed(() => page.props.auth as any);
const permissions = computed(() => auth.value?.permissions || {});

// Define main navigation items with role-based visibility
const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];


    // Mitra - accessible by all roles but with different permissions
    if (permissions.value.hasFullAccess || permissions.value.hasReadOnlyAccess || permissions.value.hasLimitedAccess) {
        items.push({
            title: 'Mitra',
            href: '/mitras',
            icon: Handshake,
        });
        // Seminar - placed right below Mitra
        items.push({
            title: 'Seminar',
            href: '/seminars',
            icon: Calendar,
        });
    }

    // Transaksi - accessible by all roles but with different permissions
    if (permissions.value.hasFullAccess || permissions.value.hasReadOnlyAccess || permissions.value.hasLimitedAccess) {
        items.push({
            title: 'Transaksi',
            href: '/transaksis',
            icon: CreditCard,
        });
    }

    // IKLAN - hanya untuk Admin & Super Admin (disembunyikan untuk Marketing)
    if (permissions.value.hasFullAccess || permissions.value.hasReadOnlyAccess) {
        items.push({
            title: 'Iklan',
            href: '/iklan-budgets',
            icon: TrendingUp,
        });
    }

    // Task Management - accessible by all authenticated users
    items.push({
        title: 'Task Management',
        href: '/task-management',
        icon: Kanban,
    });

    // To Do List - accessible by all authenticated users
    items.push({
        title: 'To Do List',
        href: '/todos',
        icon: Calendar,
    });

    // Users - only Super Admin and Admin can access
    if (permissions.value.hasFullAccess || permissions.value.hasReadOnlyAccess) {
        items.push({
            title: 'Users',
            href: '/users',
            icon: Users,
        });
    }

    // Brands - only Super Admin and Admin can access
    if (permissions.value.hasFullAccess || permissions.value.hasReadOnlyAccess) {
        items.push({
            title: 'Brand',
            href: '/brands',
            icon: Zap,
        });
    }

    // Labels - only Super Admin and Admin can access
    if (permissions.value.hasFullAccess || permissions.value.hasReadOnlyAccess) {
        items.push({
            title: 'Label',
            href: '/labels',
            icon: Tag,
        });
    }

    // Sumber - only Super Admin and Admin can access
    if (permissions.value.hasFullAccess || permissions.value.hasReadOnlyAccess) {
        items.push({
            title: 'Sumber',
            href: '/sumbers',
            icon: Globe,
        });
    }

    // Pekerjaan - only Super Admin and Admin can access
    if (permissions.value.hasFullAccess || permissions.value.hasReadOnlyAccess) {
        items.push({
            title: 'Pekerjaan',
            href: '/pekerjaans',
            icon: Briefcase,
        });
    }

    // Analisa Bisnis - accessible by all roles but with different permissions
    if (permissions.value.hasFullAccess || permissions.value.hasReadOnlyAccess || permissions.value.hasLimitedAccess) {
        items.push({
            title: 'Analisa Bisnis',
            href: '/analisa-bisnis',
            icon: BarChart3,
        });
    }

    // Pengaturan - hanya untuk Super Admin (akses penuh)
    if (permissions.value.hasFullAccess) {
        items.push({
            title: 'Pengaturan',
            href: '/settings/site',
            icon: Settings,
        });
    }

    return items;
});

// Footer navigation items removed - Github Repo and Documentation links hidden for all users
// const footerNavItems: NavItem[] = [
//     {
//         title: 'Github Repo',
//         href: 'https://github.com/laravel/vue-starter-kit',
//         icon: Folder,
//     },
//     {
//         title: 'Documentation',
//         href: 'https://laravel.com/docs/starter-kits#vue',
//         icon: BookOpen,
//     },
// ];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <!-- <NavFooter :items="footerNavItems" /> -->
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
