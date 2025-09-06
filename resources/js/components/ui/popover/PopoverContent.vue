<script setup lang="ts">
import { inject, ref, onMounted, onUnmounted } from 'vue';
import { cn } from '@/lib/utils';

export interface PopoverContentProps {
  class?: string;
  align?: 'start' | 'center' | 'end';
  side?: 'top' | 'right' | 'bottom' | 'left';
  sideOffset?: number;
}

const props = withDefaults(defineProps<PopoverContentProps>(), {
  align: 'center',
  side: 'bottom',
  sideOffset: 4,
});

const popover = inject<{
  isOpen: any;
  setOpen: (open: boolean) => void;
}>('popover');

const contentRef = ref<HTMLElement>();

const handleClickOutside = (event: MouseEvent) => {
  if (contentRef.value && !contentRef.value.contains(event.target as Node)) {
    popover?.setOpen(false);
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
  <Teleport to="body">
    <div
      v-if="popover?.isOpen.value"
      ref="contentRef"
      :class="cn(
        'absolute z-50 w-72 rounded-md border bg-white p-4 text-popover-foreground shadow-md outline-none',
        'data-[state=open]:animate-in data-[state=closed]:animate-out',
        'data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0',
        'data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95',
        'data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2',
        'data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2',
        props.class
      )"
      style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);"
    >
      <slot />
    </div>
  </Teleport>
</template>
