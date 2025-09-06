<script setup lang="ts">
import { inject } from 'vue'
import { cn } from '@/lib/utils'

interface Props {
  class?: string
}

const props = defineProps<Props>()

const tabsContext = inject('tabsContext') as any
</script>

<template>
  <div
    :class="cn(
      'inline-flex h-10 items-center justify-center rounded-md bg-muted p-1 text-muted-foreground',
      props.class
    )"
    role="tablist"
    :data-orientation="tabsContext?.orientation"
  >
    <slot />
  </div>
</template>
