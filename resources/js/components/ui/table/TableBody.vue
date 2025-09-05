<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { Primitive, type PrimitiveProps } from 'reka-ui'

interface Props extends PrimitiveProps {
  class?: HTMLAttributes['class']
}

const props = withDefaults(defineProps<Props>(), {
  as: 'tbody',
})
</script>

<template>
  <Primitive
    :as="as"
    :as-child="asChild"
    :class="cn('[&_tr:last-child]:border-0', props.class)"
  >
    <slot />
  </Primitive>
</template>
