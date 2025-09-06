<script setup lang="ts">
import { computed, provide, ref } from 'vue';

export interface SelectProps {
  modelValue?: any;
  defaultValue?: any;
  open?: boolean;
  defaultOpen?: boolean;
  disabled?: boolean;
  required?: boolean;
}

const props = withDefaults(defineProps<SelectProps>(), {
  defaultOpen: false,
  disabled: false,
  required: false,
});

const emit = defineEmits<{
  'update:modelValue': [value: any];
  'update:open': [open: boolean];
}>();

const isControlled = computed(() => props.modelValue !== undefined);
const internalValue = ref(props.defaultValue);
const internalOpen = ref(props.defaultOpen);

const value = computed({
  get: () => isControlled.value ? props.modelValue : internalValue.value,
  set: (val) => {
    if (isControlled.value) {
      emit('update:modelValue', val);
    } else {
      internalValue.value = val;
    }
  }
});

const open = computed({
  get: () => props.open !== undefined ? props.open : internalOpen.value,
  set: (val) => {
    if (props.open !== undefined) {
      emit('update:open', val);
    } else {
      internalOpen.value = val;
    }
  }
});

const onValueChange = (val: any) => {
  value.value = val;
  open.value = false;
};

provide('select', {
  value,
  open,
  onValueChange,
  disabled: computed(() => props.disabled),
});
</script>

<template>
  <div class="relative">
    <slot />
  </div>
</template>
