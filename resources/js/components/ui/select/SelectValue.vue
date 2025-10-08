<script setup lang="ts">
import { inject, computed } from 'vue';

export interface SelectValueProps {
  placeholder?: string;
}

const props = withDefaults(defineProps<SelectValueProps>(), {
  placeholder: 'Select an option'
});

const select = inject<{
  value: any;
}>('select');

const displayValue = computed(() => {
  const currentValue = select?.value?.value;
  if (currentValue === null || currentValue === undefined || currentValue === '') {
    return props.placeholder;
  }
  return currentValue;
});

const hasValue = computed(() => {
  const currentValue = select?.value?.value;
  return currentValue !== null && currentValue !== undefined && currentValue !== '';
});
</script>

<template>
  <span :class="{ 'text-muted-foreground': !hasValue }">{{ displayValue }}</span>
</template>
