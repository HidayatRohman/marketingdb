<script setup lang="ts">
// import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem, SidebarGroup, SidebarMenuSub, SidebarMenuSubItem, SidebarMenuSubButton } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Calendar, CreditCard, Handshake, Kanban, LayoutGrid, Tag, TrendingUp, Users, Zap, Globe, Briefcase, Settings, BarChart3, MessageSquare, History, Wrench, Package, AlertTriangle, Lightbulb, ChevronDown } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage();

// Get user permissions from auth data
const auth = computed(() => page.props.auth as any);
const permissions = computed(() => auth.value?.permissions || {});

// Define main navigation items with role-based visibility
const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];

    // Only non-CS roles see Dashboard in main navigation
    if (permissions.value.role !== 'cs') {
        items.push({
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
        });
    }


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

    // Task Management - tampilkan untuk semua role termasuk CS
    items.push({
        title: 'Task Management',
        href: '/task-management',
        icon: Kanban,
    });

    // To Do List - tampilkan untuk semua role termasuk CS
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

    // Brands/Labels/Sumber/Pekerjaan dipindahkan ke grup Pengaturan (lihat di bawah)

    // Analisa Bisnis - accessible by all roles but with different permissions
    if (permissions.value.hasFullAccess || permissions.value.hasReadOnlyAccess || permissions.value.hasLimitedAccess) {
        items.push({
            title: 'Analisa Bisnis',
            href: '/analisa-bisnis',
            icon: BarChart3,
        });
    }

    // Pengaturan - ditampilkan sebagai grup terpisah di bawah
    
    return items;
});

// Settings group expand control: closed by default, opens on click or when active route
const settingsOpen = ref(false);
const isSettingsOpen = computed(() =>
    settingsOpen.value ||
    page.url.startsWith('/settings') ||
    page.url.startsWith('/brands') ||
    page.url.startsWith('/labels') ||
    page.url.startsWith('/sumbers') ||
    page.url.startsWith('/pekerjaans')
);

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
                        <Link href="/dashboard">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <!-- Settings Group: menampung Brand, Label, Sumber, Pekerjaan, dan link Pengaturan -->
            <SidebarGroup v-if="permissions.hasFullAccess || permissions.hasReadOnlyAccess" class="px-2 py-0 mt-2">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child tooltip="Pengaturan">
                            <div class="flex items-center gap-2 w-full" @click="settingsOpen = !settingsOpen">
                                <Settings />
                                <span>Pengaturan</span>
                                <ChevronDown class="h-4 w-4 ml-auto transition-transform" :class="isSettingsOpen ? 'rotate-180' : 'rotate-0'" />
                            </div>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <SidebarMenuSub v-if="isSettingsOpen">
                    <SidebarMenuSubItem v-if="permissions.hasFullAccess">
                        <SidebarMenuSubButton as-child :is-active="page.url.startsWith('/settings')">
                            <Link href="/settings/site">
                                <Settings />
                                <span>Pengaturan Situs</span>
                            </Link>
                        </SidebarMenuSubButton>
                    </SidebarMenuSubItem>
                    <SidebarMenuSubItem>
                        <SidebarMenuSubButton as-child :is-active="page.url.startsWith('/brands')">
                            <Link href="/brands">
                                <Zap />
                                <span>Brand</span>
                            </Link>
                        </SidebarMenuSubButton>
                    </SidebarMenuSubItem>
                    <SidebarMenuSubItem>
                        <SidebarMenuSubButton as-child :is-active="page.url.startsWith('/labels')">
                            <Link href="/labels">
                                <Tag />
                                <span>Label</span>
                            </Link>
                        </SidebarMenuSubButton>
                    </SidebarMenuSubItem>
                    <SidebarMenuSubItem>
                        <SidebarMenuSubButton as-child :is-active="page.url.startsWith('/sumbers')">
                            <Link href="/sumbers">
                                <Globe />
                                <span>Sumber</span>
                            </Link>
                        </SidebarMenuSubButton>
                    </SidebarMenuSubItem>
                    <SidebarMenuSubItem>
                        <SidebarMenuSubButton as-child :is-active="page.url.startsWith('/pekerjaans')">
                            <Link href="/pekerjaans">
                                <Briefcase />
                                <span>Pekerjaan</span>
                            </Link>
                        </SidebarMenuSubButton>
                    </SidebarMenuSubItem>
                </SidebarMenuSub>
            </SidebarGroup>
            <!-- CS Menu Group: tampil untuk CS, Super Admin, Admin, dan Advertiser -->
            <SidebarGroup v-if="permissions.hasFullAccess || permissions.hasReadOnlyAccess || permissions.role === 'cs'" class="px-2 py-0 mt-2">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child tooltip="CS">
                            <div class="flex items-center gap-2">
                                <MessageSquare />
                                <span>CS</span>
                            </div>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <SidebarMenuSub>
                    <SidebarMenuSubItem>
                        <SidebarMenuSubButton as-child :is-active="page.url.startsWith('/cs/repeats')">
                            <Link href="/cs/repeats">
                                <History />
                                <span>CS Repeat</span>
                            </Link>
                        </SidebarMenuSubButton>
                    </SidebarMenuSubItem>
                    <SidebarMenuSubItem>
                        <SidebarMenuSubButton as-child :is-active="page.url.startsWith('/cs/maintenances')">
                            <Link href="/cs/maintenances">
                                <Wrench />
                                <span>CS Maintenance</span>
                            </Link>
                        </SidebarMenuSubButton>
                    </SidebarMenuSubItem>
                    <SidebarMenuSubItem>
                        <SidebarMenuSubButton as-child :is-active="page.url.startsWith('/products')">
                            <Link href="/products">
                                <Package />
                                <span>Produk</span>
                            </Link>
                        </SidebarMenuSubButton>
                    </SidebarMenuSubItem>
                    <SidebarMenuSubItem>
                        <SidebarMenuSubButton as-child :is-active="page.url.startsWith('/kendalas')">
                            <Link href="/kendalas">
                                <AlertTriangle />
                                <span>Kendala</span>
                            </Link>
                        </SidebarMenuSubButton>
                    </SidebarMenuSubItem>
                    <SidebarMenuSubItem>
                        <SidebarMenuSubButton as-child :is-active="page.url.startsWith('/solusis')">
                            <Link href="/solusis">
                                <Lightbulb />
                                <span>Solusi</span>
                            </Link>
                        </SidebarMenuSubButton>
                    </SidebarMenuSubItem>
                </SidebarMenuSub>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <!-- <NavFooter :items="footerNavItems" /> -->
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
