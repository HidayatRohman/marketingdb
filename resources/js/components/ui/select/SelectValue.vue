<script setup lang="ts">
import { inject, computed } from 'vue';

const select = inject<{
  value: any;
}>('select');

const displayValue = computed(() => {
  return select?.value?.value || '';
});
</script>

<template>
  <span>{{ displayValue }}</span>
</template>
