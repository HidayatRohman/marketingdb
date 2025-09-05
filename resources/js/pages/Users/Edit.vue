<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { onMounted } from 'vue';

// Redirect to users page since we're using modal now
onMounted(() => {
    router.get('/users');
});
</script>

<template>
    <div class="flex items-center justify-center min-h-screen">
        <div class="text-center">
            <div class="w-16 h-16 border-4 border-green-600 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
            <p class="text-muted-foreground">Redirecting to users page...</p>
        </div>
    </div>
</template>
