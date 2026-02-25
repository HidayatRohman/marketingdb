<template>
    <div class="chart-container dark:bg-gray-800 dark:text-white rounded-lg">
        <div v-if="error" class="error-message p-4 text-center text-red-500">
            {{ error }}
        </div>
        <canvas v-else ref="chartRef" class="max-h-96"></canvas>
    </div>
</template>

<script setup lang="ts">
import { BarController, BarElement, CategoryScale, Chart, Legend, LinearScale, Title, Tooltip, type ChartConfiguration } from 'chart.js';
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

// Register Chart.js components
Chart.register(CategoryScale, LinearScale, BarElement, BarController, Title, Tooltip, Legend);

interface ProvinceData {
    labels: string[];
    data: number[];
    total: number;
    selected_brand: string;
}

interface Props {
    data: ProvinceData;
}

const props = defineProps<Props>();
const chartRef = ref<HTMLCanvasElement | null>(null);
const error = ref<string | null>(null);
let chartInstance: Chart | null = null;
const isDark = ref(false)
let observer: MutationObserver | null = null

const updateTheme = () => {
    isDark.value = document.documentElement.classList.contains('dark')
}

const createChart = async () => {
    try {
        error.value = null;

        if (!chartRef.value) {
            console.warn('Chart canvas ref not available');
            return;
        }

        if (!props.data || !props.data.labels.length) {
            console.warn('No chart data available');
            return;
        }

        console.log('Creating chart with data:', props.data);

        // Destroy existing chart
        if (chartInstance) {
            chartInstance.destroy();
            chartInstance = null;
        }

        await nextTick();

        const ctx = chartRef.value.getContext('2d');
        if (!ctx) {
            error.value = 'Could not get canvas context';
            return;
        }

        const config: ChartConfiguration = {
            type: 'bar',
            data: {
                labels: props.data.labels,
                datasets: [
                    {
                        label: 'Jumlah Mitra',
                        data: props.data.data,
                        backgroundColor: [
                            'rgba(99, 102, 241, 0.8)', // Indigo
                            'rgba(168, 85, 247, 0.8)', // Purple
                            'rgba(59, 130, 246, 0.8)', // Blue
                            'rgba(16, 185, 129, 0.8)', // Green
                            'rgba(245, 158, 11, 0.8)', // Yellow
                            'rgba(239, 68, 68, 0.8)', // Red
                            'rgba(107, 114, 128, 0.8)', // Gray
                        ],
                        borderColor: [
                            'rgba(99, 102, 241, 1)',
                            'rgba(168, 85, 247, 1)',
                            'rgba(59, 130, 246, 1)',
                            'rgba(16, 185, 129, 1)',
                            'rgba(245, 158, 11, 1)',
                            'rgba(239, 68, 68, 1)',
                            'rgba(107, 114, 128, 1)',
                        ],
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
                        text: `Top 7 Provinsi - ${props.data.selected_brand}`,
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
                        display: false,
                    },
                    tooltip: {
                        backgroundColor: isDark.value ? '#1f2937' : 'rgba(0, 0, 0, 0.8)',
                        titleColor: isDark.value ? '#f3f4f6' : '#fff',
                        bodyColor: isDark.value ? '#f3f4f6' : '#fff',
                        borderColor: isDark.value ? '#374151' : 'rgba(255, 255, 255, 0.1)',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            title: function (context) {
                                return context[0].label;
                            },
                            label: function (context) {
                                const value = context.parsed.y;
                                const total = props.data.total;
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0';
                                return `${value} mitra (${percentage}%)`;
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
        console.log('Chart created successfully');
    } catch (err) {
        console.error('Error creating chart:', err);
        error.value = `Failed to create chart: ${err instanceof Error ? err.message : String(err)}`;
    }
};

watch(isDark, () => {
    createChart()
})

onMounted(() => {
    updateTheme()
    observer = new MutationObserver(updateTheme)
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
    createChart();
});

onUnmounted(() => {
    observer?.disconnect()
    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }
})

watch(
    () => props.data,
    () => {
        createChart();
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
