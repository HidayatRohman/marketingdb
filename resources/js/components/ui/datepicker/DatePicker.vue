<template>
  <div class="relative" ref="containerRef">
    <!-- Date Input Trigger -->
    <div
      @click="toggleCalendar"
      class="flex items-center justify-between w-full px-3 py-2.5 text-sm bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 dark:bg-gray-800 dark:border-gray-600 dark:hover:border-gray-500"
      :class="{ 'border-indigo-500 ring-2 ring-indigo-500': isOpen }"
    >
      <div class="flex items-center gap-2">
        <Calendar class="h-4 w-4 text-gray-500 dark:text-gray-400" />
        <span :class="modelValue ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400'">
          {{ formattedDate || placeholder }}
        </span>
      </div>
      <ChevronDown 
        :class="[
          'h-4 w-4 text-gray-500 dark:text-gray-400 transition-transform duration-200',
          isOpen ? 'rotate-180' : ''
        ]" 
      />
    </div>

    <!-- Calendar Dropdown -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 scale-95 translate-y-1"
      enter-to-class="opacity-100 scale-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 scale-100 translate-y-0"
      leave-to-class="opacity-0 scale-95 translate-y-1"
    >
      <div
        v-if="isOpen"
        class="fixed z-[9999] w-80 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl p-4"
        :style="dropdownStyle"
      >
        <!-- Calendar Header -->
        <div class="flex items-center justify-between mb-4">
          <Button
            type="button"
            variant="ghost"
            size="sm"
            @click="previousMonth"
            class="h-8 w-8 p-0 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md"
          >
            <ChevronLeft class="h-4 w-4 text-gray-600 dark:text-gray-300" />
          </Button>
          
          <div class="flex items-center gap-2">
            <Button
              type="button"
              variant="ghost"
              @click="showMonthPicker = !showMonthPicker"
              class="text-sm font-semibold text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 px-3 py-1 rounded-md"
            >
              {{ monthNames[currentMonth] }}
            </Button>
            <Button
              type="button"
              variant="ghost"
              @click="showYearPicker = !showYearPicker"
              class="text-sm font-semibold text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 px-3 py-1 rounded-md"
            >
              {{ currentYear }}
            </Button>
          </div>
          
          <Button
            type="button"
            variant="ghost"
            size="sm"
            @click="nextMonth"
            class="h-8 w-8 p-0 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md"
          >
            <ChevronRight class="h-4 w-4 text-gray-600 dark:text-gray-300" />
          </Button>
        </div>

        <!-- Month Picker -->
        <div v-if="showMonthPicker" class="grid grid-cols-3 gap-2 mb-4">
          <Button
            v-for="(month, index) in monthNames"
            :key="month"
            type="button"
            variant="ghost"
            size="sm"
            @click="selectMonth(index)"
            :class="[
              'text-xs py-2 px-2',
              index === currentMonth ? 'bg-indigo-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
            ]"
          >
            {{ month.slice(0, 3) }}
          </Button>
        </div>

        <!-- Year Picker -->
        <div v-if="showYearPicker" class="grid grid-cols-4 gap-2 mb-4 max-h-32 overflow-y-auto">
          <Button
            v-for="year in yearRange"
            :key="year"
            type="button"
            variant="ghost"
            size="sm"
            @click="selectYear(year)"
            :class="[
              'text-xs py-2 px-2',
              year === currentYear ? 'bg-indigo-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
            ]"
          >
            {{ year }}
          </Button>
        </div>

        <!-- Calendar Grid -->
        <div v-if="!showMonthPicker && !showYearPicker">
          <!-- Day Headers -->
          <div class="grid grid-cols-7 mb-2">
            <div
              v-for="day in dayNames"
              :key="day"
              class="text-xs font-medium text-gray-500 dark:text-gray-400 text-center py-2"
            >
              {{ day }}
            </div>
          </div>

          <!-- Calendar Days -->
          <div class="grid grid-cols-7 gap-1">
            <Button
              v-for="date in calendarDays"
              :key="`${date.year}-${date.month}-${date.day}`"
              type="button"
              variant="ghost"
              size="sm"
              @click="selectDate(date)"
              :disabled="date.disabled"
              :class="[
                'h-8 w-8 p-0 text-xs font-normal rounded-md',
                {
                  'text-gray-400 dark:text-gray-500': !date.isCurrentMonth,
                  'text-gray-900 dark:text-white': date.isCurrentMonth && !date.isSelected && !date.isToday,
                  'bg-indigo-500 text-white hover:bg-indigo-600': date.isSelected,
                  'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white': date.isToday && !date.isSelected,
                  'hover:bg-gray-100 dark:hover:bg-gray-700': !date.isSelected && date.isCurrentMonth,
                  'cursor-not-allowed opacity-50': date.disabled
                }
              ]"
            >
              {{ date.day }}
            </Button>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
          <Button
            type="button"
            variant="ghost"
            size="sm"
            @click="selectToday"
            class="text-xs text-indigo-600 dark:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-700 px-3 py-2 rounded-md"
          >
            Hari Ini
          </Button>
          <Button
            type="button"
            variant="ghost"
            size="sm"
            @click="clearDate"
            class="text-xs text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 px-3 py-2 rounded-md"
          >
            Clear
          </Button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Calendar, ChevronDown, ChevronLeft, ChevronRight } from 'lucide-vue-next';

interface CalendarDate {
  day: number;
  month: number;
  year: number;
  isCurrentMonth: boolean;
  isToday: boolean;
  isSelected: boolean;
  disabled: boolean;
}

interface Props {
  modelValue?: string | null;
  placeholder?: string;
  disabled?: boolean;
  minDate?: string;
  maxDate?: string;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Pilih tanggal',
  disabled: false,
});

const emit = defineEmits<{
  'update:modelValue': [value: string | null];
}>();

// Refs
const containerRef = ref<HTMLElement>();
const isOpen = ref(false);
const showMonthPicker = ref(false);
const showYearPicker = ref(false);
const currentMonth = ref(new Date().getMonth());
const currentYear = ref(new Date().getFullYear());

// Constants
const monthNames = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

// Computed
const formattedDate = computed(() => {
  if (!props.modelValue) return '';
  const date = new Date(props.modelValue);
  return date.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
});

const dropdownStyle = computed(() => {
  if (!containerRef.value) return { top: '100%', left: '0px', right: '0px' };
  
  const rect = containerRef.value.getBoundingClientRect();
  const viewportHeight = window.innerHeight;
  const viewportWidth = window.innerWidth;
  const dropdownHeight = 400; // Approximate height of calendar dropdown
  const dropdownWidth = 320; // 20rem = 320px
  const spaceBelow = viewportHeight - rect.bottom;
  const spaceAbove = rect.top;
  
  let top: number;
  let left: number;
  
  // Determine vertical position
  if (spaceBelow < dropdownHeight && spaceAbove > dropdownHeight) {
    // Show above
    top = rect.top - dropdownHeight - 8;
  } else {
    // Show below
    top = rect.bottom + 8;
  }
  
  // Determine horizontal position
  left = rect.left;
  
  // Ensure dropdown doesn't go off-screen horizontally
  if (left + dropdownWidth > viewportWidth) {
    left = viewportWidth - dropdownWidth - 16;
  }
  if (left < 16) {
    left = 16;
  }
  
  return {
    top: `${Math.max(16, top)}px`,
    left: `${left}px`,
    width: '320px'
  };
});

const yearRange = computed(() => {
  const start = currentYear.value - 10;
  const end = currentYear.value + 10;
  return Array.from({ length: end - start + 1 }, (_, i) => start + i);
});

const calendarDays = computed(() => {
  const days: CalendarDate[] = [];
  const firstDay = new Date(currentYear.value, currentMonth.value, 1);
  const lastDay = new Date(currentYear.value, currentMonth.value + 1, 0);
  const today = new Date();
  const selectedDate = props.modelValue ? new Date(props.modelValue) : null;
  
  // Get first day of week (0 = Sunday, 1 = Monday, etc.)
  const startOfWeek = firstDay.getDay();
  
  // Previous month days
  const prevMonth = new Date(currentYear.value, currentMonth.value - 1, 0);
  for (let i = startOfWeek - 1; i >= 0; i--) {
    const day = prevMonth.getDate() - i;
    const date = new Date(currentYear.value, currentMonth.value - 1, day);
    days.push({
      day,
      month: currentMonth.value - 1,
      year: currentYear.value,
      isCurrentMonth: false,
      isToday: isSameDay(date, today),
      isSelected: selectedDate ? isSameDay(date, selectedDate) : false,
      disabled: isDateDisabled(date)
    });
  }
  
  // Current month days
  for (let day = 1; day <= lastDay.getDate(); day++) {
    const date = new Date(currentYear.value, currentMonth.value, day);
    days.push({
      day,
      month: currentMonth.value,
      year: currentYear.value,
      isCurrentMonth: true,
      isToday: isSameDay(date, today),
      isSelected: selectedDate ? isSameDay(date, selectedDate) : false,
      disabled: isDateDisabled(date)
    });
  }
  
  // Next month days to fill the grid
  const remainingDays = 42 - days.length; // 6 rows × 7 days = 42
  for (let day = 1; day <= remainingDays; day++) {
    const date = new Date(currentYear.value, currentMonth.value + 1, day);
    days.push({
      day,
      month: currentMonth.value + 1,
      year: currentYear.value,
      isCurrentMonth: false,
      isToday: isSameDay(date, today),
      isSelected: selectedDate ? isSameDay(date, selectedDate) : false,
      disabled: isDateDisabled(date)
    });
  }
  
  return days;
});

// Methods
const isSameDay = (date1: Date, date2: Date): boolean => {
  return date1.getDate() === date2.getDate() &&
         date1.getMonth() === date2.getMonth() &&
         date1.getFullYear() === date2.getFullYear();
};

const isDateDisabled = (date: Date): boolean => {
  if (props.minDate && date < new Date(props.minDate)) return true;
  if (props.maxDate && date > new Date(props.maxDate)) return true;
  return false;
};

const toggleCalendar = () => {
  if (props.disabled) return;
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    showMonthPicker.value = false;
    showYearPicker.value = false;
    // Set current month/year to selected date if exists
    if (props.modelValue) {
      const date = new Date(props.modelValue);
      currentMonth.value = date.getMonth();
      currentYear.value = date.getFullYear();
    }
    // Force recalculation of dropdown position
    setTimeout(() => {
      if (containerRef.value) {
        containerRef.value.getBoundingClientRect();
      }
    }, 0);
  }
};

const selectDate = (date: CalendarDate) => {
  if (date.disabled) return;
  const selectedDate = new Date(date.year, date.month, date.day);
  
  // Use timezone-safe date formatting instead of toISOString()
  const year = selectedDate.getFullYear();
  const month = String(selectedDate.getMonth() + 1).padStart(2, '0');
  const day = String(selectedDate.getDate()).padStart(2, '0');
  const dateString = `${year}-${month}-${day}`;
  
  emit('update:modelValue', dateString);
  isOpen.value = false;
};

const selectMonth = (month: number) => {
  currentMonth.value = month;
  showMonthPicker.value = false;
};

const selectYear = (year: number) => {
  currentYear.value = year;
  showYearPicker.value = false;
};

const previousMonth = () => {
  if (currentMonth.value === 0) {
    currentMonth.value = 11;
    currentYear.value--;
  } else {
    currentMonth.value--;
  }
};

const nextMonth = () => {
  if (currentMonth.value === 11) {
    currentMonth.value = 0;
    currentYear.value++;
  } else {
    currentMonth.value++;
  }
};

const selectToday = () => {
  const today = new Date();
  
  // Use timezone-safe date formatting instead of toISOString()
  const year = today.getFullYear();
  const month = String(today.getMonth() + 1).padStart(2, '0');
  const day = String(today.getDate()).padStart(2, '0');
  const dateString = `${year}-${month}-${day}`;
  
  emit('update:modelValue', dateString);
  isOpen.value = false;
};

const clearDate = () => {
  emit('update:modelValue', null);
  isOpen.value = false;
};

// Handle click outside
const handleClickOutside = (event: Event) => {
  if (containerRef.value && !containerRef.value.contains(event.target as Node)) {
    isOpen.value = false;
    showMonthPicker.value = false;
    showYearPicker.value = false;
  }
};

// Handle window resize
const handleResize = () => {
  if (isOpen.value && containerRef.value) {
    // Force recalculation of dropdown position on resize
    containerRef.value.getBoundingClientRect();
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  window.addEventListener('resize', handleResize);
  window.addEventListener('scroll', handleResize);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
  window.removeEventListener('resize', handleResize);
  window.removeEventListener('scroll', handleResize);
});

// Watch for model value changes
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    const date = new Date(newValue);
    currentMonth.value = date.getMonth();
    currentYear.value = date.getFullYear();
  }
});
</script>