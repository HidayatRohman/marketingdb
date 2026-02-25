<template>
  <Card class="w-full">
    <CardHeader class="pb-3">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <CardTitle class="text-lg font-semibold text-gray-900 dark:text-white">
            Analisa Berdasarkan Usia
          </CardTitle>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Distribusi transaksi berdasarkan rentang usia
          </p>
        </div>

        <div class="flex items-center gap-2">
          <!-- Refresh Button -->
          <Button
            variant="outline"
            size="sm"
            @click="$emit('refresh')"
            class="h-7 px-2 text-xs border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-800 sm:h-8 sm:px-3"
            title="Refresh Data"
          >
            <RefreshCw class="h-3 w-3" />
            <span class="ml-1 hidden sm:inline">Refresh</span>
          </Button>
        </div>
      </div>
    </CardHeader>

    <CardContent>
      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="flex items-center gap-3 text-gray-500 dark:text-gray-400">
          <div class="animate-spin rounded-full h-6 w-6 border-2 border-indigo-500 border-t-transparent"></div>
          <span class="text-sm">Memuat data analisa...</span>
        </div>
      </div>

      <!-- Chart Container -->
      <div v-else-if="props.data && props.data.length > 0" class="relative">
        <div class="flex items-center justify-center h-64 w-full sm:h-80">
          <div ref="pieRef" class="relative" style="width: 240px; height: 240px;"
               @mouseenter="onPieEnter" @mousemove="onPieMove" @mouseleave="onPieLeave">
            <div class="w-full h-full rounded-full" :style="pieStyle"></div>
            <div class="absolute inset-0 m-auto rounded-full bg-white dark:bg-gray-800" style="width: 120px; height: 120px;"></div>
            <div v-if="tooltip.visible" class="absolute z-10 px-2 py-1 text-xs rounded-md shadow-md bg-gray-900 text-white dark:bg-gray-700"
                 :style="{ left: tooltip.x + 'px', top: tooltip.y + 'px' }">
              <div class="font-medium">{{ tooltip.label }}</div>
              <div>Jumlah: {{ tooltip.count }}</div>
            </div>
          </div>
        </div>

        <!-- Legend & Stats -->
        <div class="mt-4 grid grid-cols-1 gap-3 sm:gap-4 lg:grid-cols-2">
          <!-- Age Legend -->
          <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2 flex items-center gap-2">
              <Palette class="h-4 w-4" />
              Rentang Usia
            </h4>
            <div class="grid grid-cols-1 gap-2">
              <div 
                v-for="(label, index) in ageLabels" 
                :key="index"
                class="flex items-center gap-2 rounded-lg bg-gray-50 dark:bg-gray-800 px-2 py-1"
              >
                <div 
                  class="w-3 h-3 rounded-full flex-shrink-0" 
                  :style="{ backgroundColor: backgroundColors[index] }"
                ></div>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate flex-1">
                  {{ label || 'Unknown' }}
                </span>
                <Badge variant="secondary" class="text-xs flex-shrink-0 dark:bg-gray-700 dark:text-gray-200">
                  {{ countsByLabel[label] || 0 }}
                </Badge>
              </div>
            </div>
          </div>

          <!-- Top Age Buckets -->
          <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2 flex items-center gap-2">
              <Clock class="h-4 w-4" />
              Rentang Usia Terbanyak
            </h4>
            <div class="grid grid-cols-1 gap-2">
              <div
                v-for="(item, idx) in topBuckets"
                :key="idx"
                class="flex items-center justify-between rounded-lg bg-gray-50 dark:bg-gray-800 px-2 py-1"
              >
                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ item.label }}</span>
                <Badge variant="secondary" class="text-xs dark:bg-gray-700 dark:text-gray-200">{{ item.count }}</Badge>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="flex flex-col items-center justify-center py-10">
        <div class="h-10 w-10 text-gray-300 dark:text-gray-600">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <path d="M19 11H5v2h14v-2z" />
          </svg>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 text-center max-w-sm px-4 sm:max-w-md sm:px-0">
          {{ emptyMessage || 'Tidak ada data transaksi untuk periode yang dipilih. Pilih tanggal atau filter yang berbeda.' }}
        </p>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { RefreshCw, Clock, Palette } from 'lucide-vue-next';
import { computed, ref } from 'vue';

/* HTML-based pie chart; no Chart.js registration */

interface AgeAnalyticsData {
  usia_bucket: string;
  count: number;
  total_nominal: number;
}

interface Props {
  data?: AgeAnalyticsData[];
  loading?: boolean;
  emptyMessage?: string;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  emptyMessage: '',
});

const emit = defineEmits<{
  refresh: [];
}>();

const pieRef = ref<HTMLDivElement>();
const tooltip = ref({ visible: false, x: 0, y: 0, label: '', count: 0 });

// Colors
const palette = [
  '#6366f1', // Indigo
  '#10b981', // Emerald
  '#f59e0b', // Amber
  '#ef4444', // Red
  '#3b82f6', // Blue
  '#14b8a6', // Teal
  '#a78bfa', // Violet
  '#f97316', // Orange
];

const ageLabels = computed(() => (props.data || []).map(item => item.usia_bucket || 'Unknown'));
const counts = computed(() => ageLabels.value.map(label => countsByLabel.value[label] || 0));
const total = computed(() => counts.value.reduce((a, b) => a + b, 0));
const backgroundColors = computed(() => ageLabels.value.map((_, idx) => palette[idx % palette.length]));

// Aggregations
const countsByLabel = computed<Record<string, number>>(() => {
  const result: Record<string, number> = {};
  if (!props.data) return result;
  for (const item of props.data) {
    result[item.usia_bucket || 'Unknown'] = item.count || 0;
  }
  return result;
});

const topBuckets = computed(() => {
  if (!props.data) return [];
  return props.data
    .map(item => ({ label: item.usia_bucket || 'Unknown', count: item.count || 0 }))
    .sort((a, b) => b.count - a.count)
    .slice(0, 3);
});

const pieStyle = computed(() => {
  const colors = backgroundColors.value;
  const c = counts.value;
  const t = total.value;
  if (t <= 0) return { background: 'conic-gradient(#e5e7eb 0deg 360deg)', borderRadius: '50%' } as any;
  let current = 0;
  const parts: string[] = [];
  for (let i = 0; i < c.length; i++) {
    const angle = (c[i] / t) * 360;
    const start = current;
    const end = current + angle;
    parts.push(`${colors[i]} ${start}deg ${end}deg`);
    current = end;
  }
  return { background: `conic-gradient(${parts.join(', ')})`, borderRadius: '50%' } as any;
});

/* no chart options for HTML pie */

/* no Chart.js lifecycle needed */
const segmentRanges = computed(() => {
  const labels = ageLabels.value;
  const c = counts.value;
  const colors = backgroundColors.value;
  const t = total.value;
  let current = 0;
  const ranges: { start: number; end: number; label: string; count: number; color: string }[] = [];
  for (let i = 0; i < c.length; i++) {
    const angle = t > 0 ? (c[i] / t) * 360 : 0;
    const start = current;
    const end = current + angle;
    ranges.push({ start, end, label: labels[i] || 'Unknown', count: c[i] || 0, color: colors[i] });
    current = end;
  }
  return ranges;
});

const onPieEnter = () => {
  tooltip.value.visible = true;
};

const onPieLeave = () => {
  tooltip.value.visible = false;
};

const onPieMove = (e: MouseEvent) => {
  const el = pieRef.value;
  if (!el) return;
  const rect = el.getBoundingClientRect();
  const cx = rect.left + rect.width / 2;
  const cy = rect.top + rect.height / 2;
  const dx = e.clientX - cx;
  const dy = e.clientY - cy;
  let angle = Math.atan2(dy, dx) * (180 / Math.PI);
  if (angle < 0) angle += 360;
  const seg = segmentRanges.value.find(s => angle >= s.start && angle < s.end) || segmentRanges.value[segmentRanges.value.length - 1];
  if (!seg) return;
  tooltip.value.label = seg.label;
  tooltip.value.count = seg.count;
  tooltip.value.x = e.clientX - rect.left + 8;
  tooltip.value.y = e.clientY - rect.top + 8;
};
</script>