<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Link } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import { useSiteSettings } from '@/composables/useSiteSettings';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { siteLogo, siteTitle } = useSiteSettings();
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <!-- Mobile logo on the right -->
        <div class="ml-auto lg:hidden">
            <Link :href="dashboard()" class="flex items-center gap-x-2">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg border border-sidebar-border/60 bg-muted/40 p-1">
                    <img v-if="siteLogo" :src="siteLogo" :alt="siteTitle" class="size-7 rounded object-contain" />
                    <AppLogoIcon v-else class="size-7 fill-current text-black dark:text-white" />
                </div>
            </Link>
        </div>
    </header>
</template>
