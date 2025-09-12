<template>
  <div class="relative" ref="containerRef">
    <!-- Time Input Trigger -->
    <div
      @click="toggleTimePicker"
      class="flex items-center justify-between w-full px-3 py-2.5 text-sm bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 dark:bg-gray-800 dark:border-gray-600 dark:hover:border-gray-500"
      :class="{ 'border-indigo-500 ring-2 ring-indigo-500': isOpen }"
    >
      <div class="flex items-center gap-2">
        <Clock class="h-4 w-4 text-gray-500 dark:text-gray-400" />
        <span :class="modelValue ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400'">
          {{ formattedTime || placeholder }}
        </span>
      </div>
      <ChevronDown 
        :class="[
          'h-4 w-4 text-gray-500 dark:text-gray-400 transition-transform duration-200',
          isOpen ? 'rotate-180' : ''
        ]" 
      />
    </div>

    <!-- Time Picker Dropdown -->
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
        class="absolute z-50 mt-2 w-72 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl p-4"
        :class="dropdownPosition"
      >
        <!-- Time Display -->
        <div class="text-center mb-4">
          <div class="text-2xl font-bold text-gray-900 dark:text-white font-mono">
            {{ String(selectedHour).padStart(2, '0') }}:{{ String(selectedMinute).padStart(2, '0') }}
          </div>
          <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            {{ formatTime12Hour(selectedHour, selectedMinute) }}
          </div>
        </div>

        <!-- Time Controls -->
        <div class="grid grid-cols-2 gap-4 mb-4">
          <!-- Hour Control -->
          <div class="text-center">
            <Label class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2 block">Jam</Label>
            <div class="flex flex-col items-center space-y-2">
              <Button
                variant="ghost"
                size="sm"
                @click="adjustHour(1)"
                class="h-8 w-8 p-0 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md"
              >
                <ChevronUp class="h-4 w-4 text-gray-600 dark:text-gray-300" />
              </Button>
              
              <div class="h-24 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-md w-16">
                <Button
                  v-for="hour in hours"
                  :key="hour"
                  variant="ghost"
                  size="sm"
                  @click="selectHour(hour)"
                  :class="[
                    'w-full h-8 text-xs font-mono justify-center',
                    hour === selectedHour 
                      ? 'bg-indigo-500 text-white hover:bg-indigo-600' 
                      : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                  ]"
                >
                  {{ String(hour).padStart(2, '0') }}
                </Button>
              </div>
              
              <Button
                variant="ghost"
                size="sm"
                @click="adjustHour(-1)"
                class="h-8 w-8 p-0 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md"
              >
                <ChevronDown class="h-4 w-4 text-gray-600 dark:text-gray-300" />
              </Button>
            </div>
          </div>

          <!-- Minute Control -->
          <div class="text-center">
            <Label class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2 block">Menit</Label>
            <div class="flex flex-col items-center space-y-2">
              <Button
                variant="ghost"
                size="sm"
                @click="adjustMinute(minuteStep)"
                class="h-8 w-8 p-0 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md"
              >
                <ChevronUp class="h-4 w-4 text-gray-600 dark:text-gray-300" />
              </Button>
              
              <div class="h-24 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-md w-16">
                <Button
                  v-for="minute in minutes"
                  :key="minute"
                  variant="ghost"
                  size="sm"
                  @click="selectMinute(minute)"
                  :class="[
                    'w-full h-8 text-xs font-mono justify-center',
                    minute === selectedMinute 
                      ? 'bg-indigo-500 text-white hover:bg-indigo-600' 
                      : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                  ]"
                >
                  {{ String(minute).padStart(2, '0') }}
                </Button>
              </div>
              
              <Button
                variant="ghost"
                size="sm"
                @click="adjustMinute(-minuteStep)"
                class="h-8 w-8 p-0 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md"
              >
                <ChevronDown class="h-4 w-4 text-gray-600 dark:text-gray-300" />
              </Button>
            </div>
          </div>
        </div>

        <!-- Quick Time Presets -->
        <div class="grid grid-cols-3 gap-2 mb-4">
          <Button
            v-for="preset in timePresets"
            :key="preset.label"
            variant="ghost"
            size="sm"
            @click="selectTimePreset(preset.hour, preset.minute)"
            class="text-xs py-2 px-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md"
          >
            {{ preset.label }}
          </Button>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-600">
          <Button
            variant="ghost"
            size="sm"
            @click="selectCurrentTime"
            class="text-xs text-indigo-600 dark:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-700 px-3 py-2 rounded-md"
          >
            Sekarang
          </Button>
          <div class="flex items-center gap-2">
            <Button
              variant="ghost"
              size="sm"
              @click="clearTime"
              class="text-xs text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 px-3 py-2 rounded-md"
            >
              Clear
            </Button>
            <Button
              size="sm"
              @click="confirmTime"
              class="text-xs bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-md"
            >
              OK
            </Button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Clock, ChevronDown, ChevronUp, ChevronDown as ChevronDownIcon } from 'lucide-vue-next';

interface Props {
  modelValue?: string | null;
  placeholder?: string;
  disabled?: boolean;
  minuteStep?: number;
  format12Hour?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Pilih waktu',
  disabled: false,
  minuteStep: 15,
  format12Hour: false,
});

const emit = defineEmits<{
  'update:modelValue': [value: string | null];
}>();

// Refs
const containerRef = ref<HTMLElement>();
const isOpen = ref(false);
const selectedHour = ref(new Date().getHours());
const selectedMinute = ref(Math.floor(new Date().getMinutes() / props.minuteStep) * props.minuteStep);

// Initialize time from modelValue
if (props.modelValue) {
  const [hour, minute] = props.modelValue.split(':').map(Number);
  selectedHour.value = hour;
  selectedMinute.value = minute;
}

// Computed
const formattedTime = computed(() => {
  if (!props.modelValue) return '';
  const [hour, minute] = props.modelValue.split(':').map(Number);
  return `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;
});

const dropdownPosition = computed(() => {
  return 'left-0 right-0';
});

const hours = computed(() => {
  return Array.from({ length: 24 }, (_, i) => i);
});

const minutes = computed(() => {
  const mins = [];
  for (let i = 0; i < 60; i += props.minuteStep) {
    mins.push(i);
  }
  return mins;
});

const timePresets = computed(() => {
  return [
    { label: '09:00', hour: 9, minute: 0 },
    { label: '12:00', hour: 12, minute: 0 },
    { label: '14:00', hour: 14, minute: 0 },
    { label: '16:00', hour: 16, minute: 0 },
    { label: '18:00', hour: 18, minute: 0 },
    { label: '20:00', hour: 20, minute: 0 },
  ];
});

// Methods
const formatTime12Hour = (hour: number, minute: number): string => {
  if (!props.format12Hour) return '';
  const period = hour >= 12 ? 'PM' : 'AM';
  const displayHour = hour % 12 || 12;
  return `${displayHour}:${String(minute).padStart(2, '0')} ${period}`;
};

const toggleTimePicker = () => {
  if (props.disabled) return;
  isOpen.value = !isOpen.value;
};

const selectHour = (hour: number) => {
  selectedHour.value = hour;
};

const selectMinute = (minute: number) => {
  selectedMinute.value = minute;
};

const adjustHour = (delta: number) => {
  let newHour = selectedHour.value + delta;
  if (newHour < 0) newHour = 23;
  if (newHour > 23) newHour = 0;
  selectedHour.value = newHour;
};

const adjustMinute = (delta: number) => {
  let newMinute = selectedMinute.value + delta;
  if (newMinute < 0) newMinute = 60 - props.minuteStep;
  if (newMinute >= 60) newMinute = 0;
  selectedMinute.value = newMinute;
};

const selectTimePreset = (hour: number, minute: number) => {
  selectedHour.value = hour;
  selectedMinute.value = minute;
};

const selectCurrentTime = () => {
  const now = new Date();
  selectedHour.value = now.getHours();
  selectedMinute.value = Math.floor(now.getMinutes() / props.minuteStep) * props.minuteStep;
};

const clearTime = () => {
  emit('update:modelValue', null);
  isOpen.value = false;
};

const confirmTime = () => {
  const timeString = `${String(selectedHour.value).padStart(2, '0')}:${String(selectedMinute.value).padStart(2, '0')}`;
  emit('update:modelValue', timeString);
  isOpen.value = false;
};

// Handle click outside
const handleClickOutside = (event: Event) => {
  if (containerRef.value && !containerRef.value.contains(event.target as Node)) {
    isOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>