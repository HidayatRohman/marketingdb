<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useSiteSettings } from '@/composables/useSiteSettings';

const { siteTitle, siteLogo } = useSiteSettings();
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
        <img v-if="siteLogo" :src="siteLogo" :alt="siteTitle" class="size-7 rounded object-contain" />
        <AppLogoIcon v-else class="size-5 fill-current text-white dark:text-black" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">{{ siteTitle }}</span>
    </div>
</template>
