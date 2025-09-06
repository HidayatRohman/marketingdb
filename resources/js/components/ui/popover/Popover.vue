<script setup lang="ts">
import { ref, provide, computed } from 'vue';

export interface PopoverProps {
  open?: boolean;
  defaultOpen?: boolean;
  modal?: boolean;
}

const props = withDefaults(defineProps<PopoverProps>(), {
  open: undefined,
  defaultOpen: false,
  modal: false,
});

const emit = defineEmits<{
  'update:open': [open: boolean];
}>();

const isControlled = computed(() => props.open !== undefined);
const internalOpen = ref(props.defaultOpen);

const isOpen = computed({
  get: () => isControlled.value ? props.open! : internalOpen.value,
  set: (value) => {
    if (isControlled.value) {
      emit('update:open', value);
    } else {
      internalOpen.value = value;
    }
  }
});

const setOpen = (open: boolean) => {
  isOpen.value = open;
};

provide('popover', {
  isOpen,
  setOpen,
});
</script>

<template>
  <slot />
</template>
