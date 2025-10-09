<template>
  <div class="relative" ref="containerRef">
    <!-- Date Input Trigger -->
    <div
      @click="toggleCalendar"
      class="group relative flex items-center justify-between cursor-pointer focus:outline-none transition-all duration-300 h-full"
      :class="{ 'ring-2 ring-blue-500/20': isOpen }"
    >
      <div class="flex items-center gap-2">
        <div class="p-1 rounded-md bg-gradient-to-br from-blue-500 to-blue-600 shadow-sm group-hover:shadow-md transition-all duration-300">
          <Calendar class="h-3.5 w-3.5 text-white" />
        </div>
        <span :class="modelValue ? 'text-gray-900 dark:text-white font-medium' : 'text-gray-500 dark:text-gray-400'">
          {{ formattedDate || placeholder }}
        </span>
      </div>
      <div class="p-0.5 rounded-sm bg-gray-100 dark:bg-gray-700 group-hover:bg-blue-50 dark:group-hover:bg-blue-900/30 transition-all duration-300">
        <ChevronDown 
          :class="[
            'h-3.5 w-3.5 text-gray-600 dark:text-gray-300 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-all duration-300',
            isOpen ? 'rotate-180' : ''
          ]" 
        />
      </div>
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
        class="fixed z-[9999] w-80 bg-gradient-to-br from-white via-gray-50 to-white dark:from-gray-800 dark:via-gray-750 dark:to-gray-800 border border-gray-200/50 dark:border-gray-600/50 rounded-2xl shadow-2xl backdrop-blur-sm p-6"
        :style="dropdownStyle"
      >
        <!-- Calendar Header -->
        <div class="flex items-center justify-between mb-6">
          <Button
            type="button"
            variant="ghost"
            size="sm"
            @click="previousMonth"
            class="h-10 w-10 p-0 hover:bg-gradient-to-br hover:from-blue-50 hover:to-blue-100 dark:hover:from-blue-900/30 dark:hover:to-blue-800/30 rounded-xl transition-all duration-300 hover:shadow-md"
          >
            <ChevronLeft class="h-5 w-5 text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-300" />
          </Button>
          
          <div class="flex items-center gap-3">
            <Button
              type="button"
              variant="ghost"
              @click="showMonthPicker = !showMonthPicker"
              class="text-sm font-bold text-gray-900 dark:text-white hover:bg-gradient-to-r hover:from-blue-50 hover:to-blue-100 dark:hover:from-blue-900/30 dark:hover:to-blue-800/30 px-4 py-2 rounded-xl transition-all duration-300 hover:shadow-sm"
            >
              {{ monthNames[currentMonth] }}
            </Button>
            <Button
              type="button"
              variant="ghost"
              @click="showYearPicker = !showYearPicker"
              class="text-sm font-bold text-gray-900 dark:text-white hover:bg-gradient-to-r hover:from-blue-50 hover:to-blue-100 dark:hover:from-blue-900/30 dark:hover:to-blue-800/30 px-4 py-2 rounded-xl transition-all duration-300 hover:shadow-sm"
            >
              {{ currentYear }}
            </Button>
          </div>
          
          <Button
            type="button"
            variant="ghost"
            size="sm"
            @click="nextMonth"
            class="h-10 w-10 p-0 hover:bg-gradient-to-br hover:from-blue-50 hover:to-blue-100 dark:hover:from-blue-900/30 dark:hover:to-blue-800/30 rounded-xl transition-all duration-300 hover:shadow-md"
          >
            <ChevronRight class="h-5 w-5 text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-300" />
          </Button>
        </div>

        <!-- Month Picker -->
        <div v-if="showMonthPicker" class="grid grid-cols-3 gap-3 mb-6">
          <Button
            v-for="(month, index) in monthNames"
            :key="month"
            type="button"
            variant="ghost"
            size="sm"
            @click="selectMonth(index)"
            :class="[
              'text-sm py-3 px-3 rounded-xl font-medium transition-all duration-300',
              index === currentMonth 
                ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-lg hover:shadow-xl transform hover:scale-105' 
                : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-blue-50 hover:to-blue-100 dark:hover:from-blue-900/30 dark:hover:to-blue-800/30 hover:shadow-md'
            ]"
          >
            {{ month.slice(0, 3) }}
          </Button>
        </div>

        <!-- Year Picker -->
        <div v-if="showYearPicker" class="grid grid-cols-4 gap-3 mb-6 max-h-40 overflow-y-auto scrollbar-thin scrollbar-thumb-blue-300 scrollbar-track-gray-100 dark:scrollbar-thumb-blue-600 dark:scrollbar-track-gray-700">
          <Button
            v-for="year in yearRange"
            :key="year"
            type="button"
            variant="ghost"
            size="sm"
            @click="selectYear(year)"
            :class="[
              'text-sm py-3 px-3 rounded-xl font-medium transition-all duration-300',
              year === currentYear 
                ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-lg hover:shadow-xl transform hover:scale-105' 
                : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-blue-50 hover:to-blue-100 dark:hover:from-blue-900/30 dark:hover:to-blue-800/30 hover:shadow-md'
            ]"
          >
            {{ year }}
          </Button>
        </div>

        <!-- Calendar Grid -->
        <div v-if="!showMonthPicker && !showYearPicker">
          <!-- Day Headers -->
          <div class="grid grid-cols-7 mb-4">
            <div
              v-for="day in dayNames"
              :key="day"
              class="text-sm font-bold text-gray-600 dark:text-gray-300 text-center py-3 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 rounded-lg mx-0.5 flex items-center justify-center"
            >
              {{ day }}
            </div>
          </div>

          <!-- Calendar Days -->
          <div class="grid grid-cols-7 gap-2">
            <Button
              v-for="date in calendarDays"
              :key="`${date.year}-${date.month}-${date.day}`"
              type="button"
              variant="ghost"
              size="sm"
              @click="selectDate(date)"
              :disabled="date.disabled"
              :class="[
                'h-10 w-10 p-0 text-sm font-medium rounded-xl transition-all duration-300 text-center flex items-center justify-center',
                {
                  'text-gray-400 dark:text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700/50': !date.isCurrentMonth,
                  'text-gray-900 dark:text-white hover:bg-gradient-to-r hover:from-blue-50 hover:to-blue-100 dark:hover:from-blue-900/30 dark:hover:to-blue-800/30 hover:shadow-md': date.isCurrentMonth && !date.isSelected && !date.isToday,
                  'bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-lg hover:shadow-xl transform hover:scale-110': date.isSelected,
                  'bg-gradient-to-r from-orange-400 to-orange-500 text-white shadow-md ring-2 ring-orange-200 dark:ring-orange-700': date.isToday && !date.isSelected,
                  'cursor-not-allowed opacity-50': date.disabled
                }
              ]"
            >
              {{ date.day }}
            </Button>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="flex items-center justify-between mt-6 pt-4 border-t border-gradient-to-r from-gray-200 via-gray-300 to-gray-200 dark:from-gray-600 dark:via-gray-500 dark:to-gray-600">
          <Button
            type="button"
            variant="ghost"
            size="sm"
            @click="selectToday"
            class="text-sm font-medium bg-gradient-to-r from-blue-500 to-blue-600 text-white hover:from-blue-600 hover:to-blue-700 shadow-md hover:shadow-lg transform hover:scale-105 transition-all duration-300 px-4 py-2 rounded-xl"
          >
            Hari Ini
          </Button>
          <Button
            type="button"
            variant="ghost"
            size="sm"
            @click="clearDate"
            class="text-sm font-medium bg-gradient-to-r from-gray-400 to-gray-500 text-white hover:from-gray-500 hover:to-gray-600 shadow-md hover:shadow-lg transform hover:scale-105 transition-all duration-300 px-4 py-2 rounded-xl"
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
  const viewportHeight = window.innerHeight;
  const viewportWidth = window.innerWidth;
  const dropdownHeight = 400; // Approximate height of calendar dropdown
  const dropdownWidth = 320; // 20rem = 320px
  
  // Center the dropdown in the viewport
  const top = (viewportHeight - dropdownHeight) / 2;
  const left = (viewportWidth - dropdownWidth) / 2;
  
  return {
    top: `${Math.max(16, top)}px`,
    left: `${Math.max(16, left)}px`,
    width: '320px',
    transform: 'none'
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