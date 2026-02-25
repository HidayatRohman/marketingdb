<template>
    <div class="chart-container">
        <div v-if="error" class="error-message p-4 text-center text-red-500">
            {{ error }}
        </div>
        <canvas v-else ref="chartRef" class="max-h-96"></canvas>
    </div>
</template>

<script setup lang="ts">
import { BarController, BarElement, CategoryScale, Chart, Legend, LinearScale, Title, Tooltip, type ChartConfiguration, type ChartOptions } from 'chart.js';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

// Register Chart.js components
Chart.register(CategoryScale, LinearScale, BarElement, BarController, Title, Tooltip, Legend);

interface BrandData {
    id: number;
    nama: string;
    logo_url: string | null;
    total_leads: number;
    closed_leads: number;
    closing_rate: number;
}

interface Props {
    data: BrandData[];
    title?: string;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Performa Brand',
});

const chartRef = ref<HTMLCanvasElement | null>(null);
const error = ref<string | null>(null);
let chartInstance: Chart | null = null;

// Dark mode detection
const isDark = ref(document.documentElement.classList.contains('dark'));
let observer: MutationObserver | null = null;

const updateTheme = () => {
    isDark.value = document.documentElement.classList.contains('dark');
};

const chartOptions = computed<ChartOptions<'bar'>>(() => ({
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
            color: isDark.value ? '#f3f4f6' : 'rgb(55, 65, 81)', // gray-100 : gray-700
            padding: {
                top: 10,
                bottom: 30,
            },
        },
        legend: {
            display: true,
            position: 'top',
            labels: {
                color: isDark.value ? '#d1d5db' : '#374151', // gray-300 : gray-700
            },
        },
        tooltip: {
            backgroundColor: isDark.value ? '#1f2937' : 'rgba(0, 0, 0, 0.8)',
            titleColor: '#fff',
            bodyColor: '#fff',
            borderColor: isDark.value ? '#374151' : 'rgba(255, 255, 255, 0.1)',
            borderWidth: 1,
            cornerRadius: 8,
            displayColors: true,
            callbacks: {
                afterLabel: function (context) {
                    const dataIndex = context.dataIndex;
                    const brand = props.data[dataIndex];
                    return `Closing Rate: ${brand.closing_rate}%`;
                },
            },
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                stepSize: 1,
                color: isDark.value ? '#9ca3af' : 'rgb(107, 114, 128)', // gray-400 : gray-500
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
                color: isDark.value ? '#9ca3af' : 'rgb(107, 114, 128)', // gray-400 : gray-500
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
}));

const destroyChart = () => {
    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }
};

const createChart = async () => {
    try {
        error.value = null;

        if (!chartRef.value) {
            console.warn('Chart canvas ref not available');
            return;
        }

        if (!props.data || !props.data.length) {
            console.warn('No brand data available');
            return;
        }

        // Destroy existing chart
        destroyChart();

        await nextTick();

        const ctx = chartRef.value.getContext('2d');
        if (!ctx) {
            error.value = 'Could not get canvas context';
            return;
        }

        const labels = props.data.map((item) => item.nama);
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
                        backgroundColor: 'rgba(168, 85, 247, 0.8)',
                        borderColor: 'rgba(168, 85, 247, 1)',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    },
                    {
                        label: 'Leads Closed',
                        data: closedLeadsData,
                        backgroundColor: 'rgba(245, 158, 11, 0.8)',
                        borderColor: 'rgba(245, 158, 11, 1)',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    },
                ],
            },
            options: chartOptions.value,
        };

        chartInstance = new Chart(ctx, config);
    } catch (err) {
        console.error('Error creating brand chart:', err);
        error.value = `Failed to create chart: ${err instanceof Error ? err.message : String(err)}`;
    }
};

onMounted(() => {
    observer = new MutationObserver(updateTheme);
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
    
    createChart();
});

onUnmounted(() => {
    if (observer) {
        observer.disconnect();
    }
    destroyChart();
});

watch(isDark, () => {
    createChart();
});

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
