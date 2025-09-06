<script setup lang="ts">
import { inject, ref, onMounted, onUnmounted } from 'vue';
import { cn } from '@/lib/utils';

export interface SelectContentProps {
  class?: string;
}

const props = withDefaults(defineProps<SelectContentProps>(), {});

const select = inject<{
  open: any;
}>('select');

const contentRef = ref<HTMLElement>();

const handleClickOutside = (event: MouseEvent) => {
  if (contentRef.value && !contentRef.value.contains(event.target as Node)) {
    if (select) {
      select.open.value = false;
    }
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
      v-if="select?.open?.value"
      ref="contentRef"
      :class="cn(
        'absolute z-50 min-w-[8rem] overflow-hidden rounded-md border bg-background text-popover-foreground shadow-md',
        'data-[state=open]:animate-in data-[state=closed]:animate-out',
        'data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0',
        'data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95',
        props.class
      )"
      style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);"
    >
      <div class="p-1">
        <slot />
      </div>
    </div>
  </Teleport>
</template>
