<script setup lang="ts">
import { inject } from 'vue';
import { cn } from '@/lib/utils';

export interface SelectItemProps {
  value: any;
  class?: string;
  disabled?: boolean;
}

const props = withDefaults(defineProps<SelectItemProps>(), {
  disabled: false,
});

const select = inject<{
  onValueChange: (value: any) => void;
}>('select');

const handleClick = () => {
  if (!props.disabled && select) {
    select.onValueChange(props.value);
  }
};
</script>

<template>
  <div
    :class="cn(
      'relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-2 pr-8 text-sm outline-none focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
      'hover:bg-accent hover:text-accent-foreground',
      props.class
    )"
    @click="handleClick"
  >
    <slot />
  </div>
</template>
