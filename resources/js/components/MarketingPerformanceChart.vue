<template>
    <div class="chart-container dark:bg-gray-800 dark:text-white rounded-lg">
        <div v-if="error" class="error-message p-4 text-center text-red-500">
            {{ error }}
        </div>
        <canvas v-else :key="canvasKey" ref="chartRef" :id="canvasId" class="max-h-96"></canvas>
    </div>
</template>

<script setup lang="ts">
import { BarController, BarElement, CategoryScale, Chart, Legend, LinearScale, Title, Tooltip, type ChartConfiguration } from 'chart.js';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

// Register Chart.js components
Chart.register(CategoryScale, LinearScale, BarElement, BarController, Title, Tooltip, Legend);

interface MarketingData {
    id: number;
    name: string;
    email: string;
    total_leads: number;
    closed_leads: number;
    closing_rate: number;
}

interface Props {
    data: MarketingData[];
    title?: string;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Performa Marketing',
});

const chartRef = ref<HTMLCanvasElement | null>(null);
const error = ref<string | null>(null);
let chartInstance: Chart | null = null;
const isDark = ref(false)
let observer: MutationObserver | null = null

// Canvas management to avoid reuse issues
const canvasKey = ref(0);
const canvasId = computed(() => `marketing-performance-chart-${canvasKey.value}`);
const bumpCanvas = async () => {
    canvasKey.value++;
    await nextTick();
};

const updateTheme = () => {
    isDark.value = document.documentElement.classList.contains('dark')
}

const isCreating = ref(false);

const destroyChart = () => {
    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }

    if (chartRef.value) {
        const existing = Chart.getChart(chartRef.value);
        if (existing) existing.destroy();
    }

    const existingById = Chart.getChart(canvasId.value);
    if (existingById) existingById.destroy();
};

const createChart = async () => {
    if (isCreating.value) return;

    try {
        error.value = null;

        await nextTick();

        if (!chartRef.value) {
            console.warn('Chart canvas ref not available');
            return;
        }

        if (!props.data || !props.data.length) {
            console.warn('No marketing data available');
            return;
        }

        isCreating.value = true;
        console.log('Creating marketing chart with data:', props.data);

        // Destroy existing chart
        destroyChart();

        const ctx = chartRef.value.getContext('2d');
        if (!ctx) {
            error.value = 'Could not get canvas context';
            isCreating.value = false;
            return;
        }

        const labels = props.data.map((item) => item.name);
        const totalLeadsData = props.data.map((item) => item.total_leads);
        const closedLeadsData = props.data.map((item) => item.closed_leads);

        const config: ChartConfiguration = {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Total Leads',
                        data: totalLeadsData,
                        backgroundColor: 'rgba(99, 102, 241, 0.8)',
                        borderColor: 'rgba(99, 102, 241, 1)',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    },
                    {
                        label: 'Leads Closed',
                        data: closedLeadsData,
                        backgroundColor: 'rgba(16, 185, 129, 0.8)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: props.title,
                        font: {
                            size: 16,
                            weight: 'bold',
                        },
                        color: isDark.value ? '#d1d5db' : 'rgb(55, 65, 81)', // gray-700
                        padding: {
                            top: 10,
                            bottom: 30,
                        },
                    },
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: isDark.value ? '#d1d5db' : '#374151'
                        }
                    },
                    tooltip: {
                        backgroundColor: isDark.value ? '#1f2937' : 'rgba(0, 0, 0, 0.8)',
                        titleColor: isDark.value ? '#f3f4f6' : '#fff',
                        bodyColor: isDark.value ? '#f3f4f6' : '#fff',
                        borderColor: isDark.value ? '#374151' : 'rgba(255, 255, 255, 0.1)',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: true,
                        callbacks: {
                            afterLabel: function (context) {
                                const dataIndex = context.dataIndex;
                                const marketing = props.data[dataIndex];
                                return `Closing Rate: ${marketing.closing_rate}%`;
                            },
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: isDark.value ? '#9ca3af' : 'rgb(107, 114, 128)', // gray-500
                            font: {
                                size: 12,
                            },
                        },
                        grid: {
                            color: isDark.value ? '#374151' : 'rgba(107, 114, 128, 0.1)',
                        },
                    },
                    x: {
                        ticks: {
                            color: isDark.value ? '#9ca3af' : 'rgb(107, 114, 128)', // gray-500
                            font: {
                                size: 12,
                            },
                            maxRotation: 45,
                            minRotation: 0,
                        },
                        grid: {
                            display: false,
                        },
                    },
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart',
                },
            },
        };

        chartInstance = new Chart(ctx, config);
        console.log('Marketing chart created successfully');
    } catch (err) {
        console.error('Error creating marketing chart:', err);
        error.value = `Failed to create chart: ${err instanceof Error ? err.message : String(err)}`;
    } finally {
        isCreating.value = false;
    }
};

watch(isDark, async () => {
    destroyChart();
    await bumpCanvas();
    await createChart();
})

onMounted(async () => {
    updateTheme()
    observer = new MutationObserver(updateTheme)
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
    await createChart();
});

onUnmounted(() => {
    observer?.disconnect()
    destroyChart();
})

watch(
    () => props.data,
    async () => {
        destroyChart();
        await bumpCanvas();
        await createChart();
    },
    { deep: true },
);
</script>

<style scoped>
.chart-container {
    position: relative;
    height: 400px;
    width: 100%;
}
</style>
