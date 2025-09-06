<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

export interface CalendarProps {
  modelValue?: Date;
  placeholder?: Date;
  mode?: 'single' | 'multiple' | 'range';
  disabled?: (date: Date) => boolean;
  class?: string;
}

const props = withDefaults(defineProps<CalendarProps>(), {
  mode: 'single',
});

const emit = defineEmits<{
  'update:modelValue': [value: Date | undefined];
}>();

const currentMonth = computed(() => {
  const date = props.modelValue || props.placeholder || new Date();
  return new Date(date.getFullYear(), date.getMonth(), 1);
});

const currentYear = computed(() => currentMonth.value.getFullYear());
const currentMonthIndex = computed(() => currentMonth.value.getMonth());

const daysInMonth = computed(() => {
  return new Date(currentYear.value, currentMonthIndex.value + 1, 0).getDate();
});

const firstDayOfMonth = computed(() => {
  return new Date(currentYear.value, currentMonthIndex.value, 1).getDay();
});

const days = computed(() => {
  const daysArray = [];
  
  // Add empty cells for days before the first day of the month
  for (let i = 0; i < firstDayOfMonth.value; i++) {
    daysArray.push(null);
  }
  
  // Add days of the month
  for (let day = 1; day <= daysInMonth.value; day++) {
    daysArray.push(day);
  }
  
  return daysArray;
});

const monthNames = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const dayNames = ['Ming', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

const isSelected = (day: number) => {
  if (!props.modelValue || !day) return false;
  return props.modelValue.getDate() === day &&
         props.modelValue.getMonth() === currentMonthIndex.value &&
         props.modelValue.getFullYear() === currentYear.value;
};

const isToday = (day: number) => {
  if (!day) return false;
  const today = new Date();
  return today.getDate() === day &&
         today.getMonth() === currentMonthIndex.value &&
         today.getFullYear() === currentYear.value;
};

const selectDate = (day: number) => {
  if (!day) return;
  
  const date = new Date(currentYear.value, currentMonthIndex.value, day);
  if (props.disabled?.(date)) return;
  
  emit('update:modelValue', date);
};

const previousMonth = () => {
  const prevDate = new Date(currentYear.value, currentMonthIndex.value - 1, 1);
  emit('update:modelValue', prevDate);
};

const nextMonth = () => {
  const nextDate = new Date(currentYear.value, currentMonthIndex.value + 1, 1);
  emit('update:modelValue', nextDate);
};
</script>

<template>
  <div :class="cn('p-3', props.class)">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <button
        @click="previousMonth"
        class="p-1 hover:bg-gray-100 rounded"
        type="button"
      >
        ‹
      </button>
      
      <div class="font-semibold">
        {{ monthNames[currentMonthIndex] }} {{ currentYear }}
      </div>
      
      <button
        @click="nextMonth"
        class="p-1 hover:bg-gray-100 rounded"
        type="button"
      >
        ›
      </button>
    </div>

    <!-- Day names -->
    <div class="grid grid-cols-7 gap-1 mb-2">
      <div
        v-for="dayName in dayNames"
        :key="dayName"
        class="text-center text-sm font-medium text-gray-500 p-2"
      >
        {{ dayName }}
      </div>
    </div>

    <!-- Days -->
    <div class="grid grid-cols-7 gap-1">
      <button
        v-for="(day, index) in days"
        :key="index"
        type="button"
        @click="day ? selectDate(day) : undefined"
        :disabled="!day || (disabled && disabled(new Date(currentYear, currentMonthIndex, day)))"
        :class="cn(
          'p-2 text-sm rounded hover:bg-gray-100 transition-colors',
          {
            'text-gray-400': !day,
            'bg-blue-500 text-white hover:bg-blue-600': day && isSelected(day),
            'bg-gray-100': day && isToday(day) && !isSelected(day),
            'cursor-not-allowed opacity-50': day && disabled && disabled(new Date(currentYear, currentMonthIndex, day))
          }
        )"
      >
        {{ day }}
      </button>
    </div>
  </div>
</template>
