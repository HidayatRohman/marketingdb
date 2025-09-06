<script setup lang="ts">
import { inject, computed } from 'vue';
import { cn } from '@/lib/utils';
import { ChevronDown } from 'lucide-vue-next';

export interface SelectTriggerProps {
  class?: string;
  disabled?: boolean;
}

const props = withDefaults(defineProps<SelectTriggerProps>(), {});

const select = inject<{
  value: any;
  open: any;
  disabled: any;
}>('select');

const isDisabled = computed(() => props.disabled || select?.disabled?.value);

const toggleOpen = () => {
  if (!isDisabled.value && select) {
    select.open.value = !select.open.value;
  }
};
</script>

<template>
  <button
    type="button"
    :disabled="isDisabled"
    @click="toggleOpen"
    :class="cn(
      'flex h-9 w-full items-center justify-between whitespace-nowrap rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
      props.class
    )"
  >
    <slot />
    <ChevronDown class="h-4 w-4 opacity-50" />
  </button>
</template>
