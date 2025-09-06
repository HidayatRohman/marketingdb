<script setup lang="ts">
import { provide, ref } from 'vue'
import { cn } from '@/lib/utils'

interface Props {
  defaultValue?: string
  class?: string
  orientation?: 'horizontal' | 'vertical'
  dir?: 'ltr' | 'rtl'
  activationMode?: 'automatic' | 'manual'
  modelValue?: string
}

const props = withDefaults(defineProps<Props>(), {
  orientation: 'horizontal',
  dir: 'ltr',
  activationMode: 'automatic',
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const currentValue = ref(props.modelValue || props.defaultValue || '')

const updateValue = (value: string) => {
  currentValue.value = value
  emit('update:modelValue', value)
}

provide('tabsContext', {
  currentValue,
  updateValue,
  orientation: props.orientation,
})
</script>

<template>
  <div
    :class="cn('w-full', props.class)"
    :data-orientation="orientation"
  >
    <slot />
  </div>
</template>
