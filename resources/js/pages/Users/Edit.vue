<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { onMounted } from 'vue';

// Redirect to users page since we're using modal now
onMounted(() => {
    router.get('/users');
});
</script>

<template>
    <div class="flex min-h-screen items-center justify-center">
        <div class="text-center">
            <div class="mx-auto mb-4 h-16 w-16 animate-spin rounded-full border-4 border-green-600 border-t-transparent"></div>
            <p class="text-muted-foreground">Redirecting to users page...</p>
        </div>
    </div>
</template>
