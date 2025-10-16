<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'

interface Props {
  value?: number
  max?: number
  class?: string
  trackClass?: string
  trackStyle?: Record<string, string | number>
  barClass?: string
  barStyle?: Record<string, string | number>
}

const props = withDefaults(defineProps<Props>(), {
  value: 0,
  max: 100,
  trackClass: '',
  trackStyle: undefined,
  barClass: '',
  barStyle: undefined,
})

const percentage = computed(() => {
  return Math.min(Math.max((props.value / props.max) * 100, 0), 100)
})
</script>

<template>
  <div
    :class="cn('relative h-2 w-full overflow-hidden rounded-full bg-secondary', props.class, props.trackClass)"
    :style="props.trackStyle"
  >
    <div
      :class="cn('h-full w-full flex-1 transition-all duration-300 ease-in-out', props.barClass || 'bg-primary')"
      :style="Object.assign({ transform: `translateX(-${100 - percentage}%)` }, props.barStyle || {})"
    />
  </div>
</template>
