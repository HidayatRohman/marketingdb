<script setup lang="ts">
import { inject, computed } from 'vue';
import { cn } from '@/lib/utils';

export interface PopoverTriggerProps {
  asChild?: boolean;
  class?: string;
}

const props = withDefaults(defineProps<PopoverTriggerProps>(), {
  asChild: false,
});

const popover = inject<{
  isOpen: any;
  setOpen: (open: boolean) => void;
}>('popover');

const handleClick = () => {
  if (popover) {
    popover.setOpen(!popover.isOpen.value);
  }
};
</script>

<template>
  <div
    v-if="!asChild"
    @click="handleClick"
    :class="cn('cursor-pointer', props.class)"
  >
    <slot />
  </div>
  <slot v-else @click="handleClick" />
</template>
