<template>
  <Card class="w-full">
    <CardHeader class="pb-3">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <CardTitle class="text-lg font-semibold text-gray-900 dark:text-white">
            Analisa Berdasarkan Lead Awal Iklan
          </CardTitle>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            {{ subtitleText }}
          </p>
        </div>

        <div class="flex items-center gap-2">
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

      <!-- Content -->
      <div v-else-if="processed.length > 0" class="space-y-4">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
          <!-- Grafik Lead Awal (Progress Bars) -->
          <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 bg-gradient-to-br from-indigo-50 to-pink-50 dark:from-indigo-900/20 dark:to-pink-900/20">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3 flex items-center gap-2">
              <TrendingUp class="h-4 w-4" />
              Grafik Lead Awal
            </h4>
            <div class="space-y-3">
              <div
                v-for="(item, idx) in processed"
                :key="item.lead_awal + '-' + idx"
                class="space-y-1"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="inline-flex h-2.5 w-2.5 items-center justify-center rounded-full ring-2 ring-white dark:ring-gray-900"
                      :style="{ backgroundColor: colorFor(item.lead_awal, idx) }"
                    ></span>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      {{ item.lead_awal || 'Unknown' }}
                    </span>
                  </div>
                  <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ item.count }} ({{ item.percentage.toFixed(1) }}%)
                  </span>
                </div>
                <Progress
                  :value="item.percentage"
                  class="h-2 rounded-lg transition-all duration-200 hover:h-3"
                  :trackClass="'bg-gray-100 dark:bg-gray-800'"
                  :barStyle="{ background: gradientFor(item.lead_awal, idx) }"
                />
              </div>
            </div>
          </div>

          <!-- Detail Lead Awal -->
          <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3 flex items-center gap-2">
              <Palette class="h-4 w-4" />
              Detail Lead Awal
            </h4>
            <div class="space-y-2 max-h-64 overflow-auto pr-1">
              <div
                v-for="(item, idx) in processed"
                :key="'detail-' + item.lead_awal + '-' + idx"
                class="flex items-center justify-between rounded-lg bg-gray-50 dark:bg-gray-800 px-2 py-1"
              >
                <div class="flex items-center gap-2">
                  <div
                    class="w-3 h-3 rounded-full"
                    :style="{ backgroundColor: colorFor(item.lead_awal, idx) }"
                  ></div>
                  <span class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate">
                    {{ item.lead_awal || 'Unknown' }}
                  </span>
                </div>
                <div class="flex items-center gap-2">
                  <Badge variant="secondary" class="text-xs">{{ item.count }}</Badge>
                  <span class="text-xs text-gray-500">{{ item.percentage.toFixed(1) }}%</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Bottom Stats -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
          <div class="rounded-xl bg-blue-50 px-4 py-3 text-blue-900 dark:bg-blue-900/30 dark:text-blue-200">
            <div class="text-sm font-medium">Total Transaksi</div>
            <div class="text-2xl font-bold">{{ total }}</div>
          </div>
          <div class="rounded-xl bg-emerald-50 px-4 py-3 text-emerald-900 dark:bg-emerald-900/30 dark:text-emerald-200">
            <div class="text-sm font-medium">Lead Awal Terbanyak</div>
            <div class="text-base font-semibold truncate">
              {{ topItem?.lead_awal || '-' }}
            </div>
            <div class="text-sm">
              {{ topItem?.count || 0 }} transaksi ({{ topItem ? topItem.percentage.toFixed(1) : 0 }}%)
            </div>
          </div>
          <div class="rounded-xl bg-purple-50 px-4 py-3 text-purple-900 dark:bg-purple-900/30 dark:text-purple-200">
            <div class="text-sm font-medium">Jumlah Lead Awal</div>
            <div class="text-2xl font-bold">{{ processed.length }}</div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="flex flex-col items-center justify-center py-8 sm:py-12">
        <div class="rounded-full bg-gray-100 dark:bg-gray-800 p-3 mb-3 sm:p-4 sm:mb-4">
          <TrendingUp class="h-6 w-6 text-gray-400 sm:h-8 sm:w-8" />
        </div>
        <h3 class="text-base font-medium text-gray-900 dark:text-white mb-2 sm:text-lg">
          Belum Ada Data Transaksi
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 text-center max-w-sm px-4 sm:max-w-md sm:px-0">
          Tidak ada data untuk periode yang dipilih. Pilih tanggal atau filter yang berbeda.
        </p>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { TrendingUp, RefreshCw, Palette } from 'lucide-vue-next';
import { computed } from 'vue';

interface LeadAwalAnalyticsData {
  lead_awal: string;
  count: number;
  total_nominal: number;
}

interface Props {
  data?: LeadAwalAnalyticsData[];
  loading?: boolean;
  startDate?: string;
  endDate?: string;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  startDate: undefined,
  endDate: undefined,
});

defineEmits<{ refresh: [] }>();

const total = computed(() => (props.data || []).reduce((sum, d) => sum + (d.count || 0), 0));

const processed = computed(() => {
  const t = total.value;
  const arr = (props.data || []).map((d) => ({
    lead_awal: d.lead_awal || 'Unknown',
    count: d.count || 0,
    percentage: t > 0 ? (d.count / t) * 100 : 0,
  }));
  return arr.sort((a, b) => b.count - a.count);
});

const topItem = computed(() => processed.value[0]);

const paletteMap: Record<string, string> = {
  'Facebook': '#3b82f6',
  'YouTube': '#ef4444',
  'IG': '#6366f1',
  'Instagram': '#6366f1',
  'FB': '#3b82f6',
  'Google': '#f59e0b',
  'Web': '#14b8a6',
  'Unknown': '#9ca3af',
};

const colorFor = (label: string | undefined, index: number) => {
  const base = label ? paletteMap[label] : paletteMap['Unknown'];
  if (base) return base;
  const hues = [210, 260, 300, 180, 20, 45, 90, 120, 150, 200];
  const hue = hues[index % hues.length];
  return `hsl(${hue}, 70%, 55%)`;
};

// Utilities to create pleasing gradients based on a base color
const hexToRgb = (hex: string) => {
  const normalized = hex.replace('#', '');
  const bigint = parseInt(normalized.length === 3 ? normalized.split('').map((c) => c + c).join('') : normalized, 16);
  const r = (bigint >> 16) & 255;
  const g = (bigint >> 8) & 255;
  const b = bigint & 255;
  return { r, g, b };
};

const clamp = (n: number) => Math.max(0, Math.min(255, Math.round(n)));

const lightenHex = (hex: string, amount: number) => {
  // amount: 0..1, blend with white
  const { r, g, b } = hexToRgb(hex);
  const nr = clamp(r + (255 - r) * amount);
  const ng = clamp(g + (255 - g) * amount);
  const nb = clamp(b + (255 - b) * amount);
  return `rgb(${nr}, ${ng}, ${nb})`;
};

const gradientFor = (label: string | undefined, index: number) => {
  const base = colorFor(label, index);
  // If base is hsl(...), CSS can use it directly; for hex, create a lightened start
  const start = base.startsWith('#') ? lightenHex(base, 0.25) : base;
  const end = base;
  return `linear-gradient(90deg, ${start}, ${end})`;
};

// Format and compute dynamic subtitle based on selected period
const formatDate = (value?: string) => {
  if (!value) return undefined;
  const d = new Date(value);
  if (isNaN(d.getTime())) return value;
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const subtitleText = computed(() => {
  const start = formatDate(props.startDate);
  const end = formatDate(props.endDate);

  let periodLabel = 'periode terpilih';
  if (start && end) {
    periodLabel = start === end ? `tanggal ${start}` : `periode ${start} – ${end}`;
  } else if (start && !end) {
    periodLabel = `periode mulai ${start}`;
  } else if (!start && end) {
    periodLabel = `periode hingga ${end}`;
  }

  return `Distribusi jumlah transaksi per lead awal iklan pada ${periodLabel}`;
});
</script>