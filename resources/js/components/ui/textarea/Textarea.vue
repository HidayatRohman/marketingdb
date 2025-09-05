<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { Primitive, type PrimitiveProps } from 'reka-ui'

interface Props extends PrimitiveProps {
  class?: HTMLAttributes['class']
  placeholder?: string
  rows?: number
  disabled?: boolean
  modelValue?: string
}

const props = withDefaults(defineProps<Props>(), {
  as: 'textarea',
  rows: 3,
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<template>
  <Primitive
    :value="modelValue"
    @input="emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
    :as="as"
    :as-child="asChild"
    :placeholder="placeholder"
    :rows="rows"
    :disabled="disabled"
    :class="cn(
      'flex min-h-[60px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
      props.class
    )"
  >
    <slot />
  </Primitive>
</template>
