<script setup>
import { onMounted, ref } from "vue";

const chartRef = ref(null);

onMounted(() => {
    new Chart(chartRef.value, {
        type: "bar",
        data: {
            labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
            datasets: [
                {
                    label: "Records",
                    data: [65, 45, 55, 75, 40, 70, 60],
                    backgroundColor: "#22c55e",
                },
            ],
        },
        options: {
            plugins: {
                legend: { display: false },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: "#9ca3af" },
                },
                y: {
                    grid: { color: "#1f2937" },
                    ticks: { color: "#9ca3af" },
                },
            },
        },
    });
});
</script>

<template>
    <div
        class="p-6 rounded-xl bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 shadow-sm"
    >
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                Records Performance
            </h3>

            <button
                class="px-3 py-1 text-sm rounded-md bg-gray-100 dark:bg-neutral-700 text-gray-600 dark:text-gray-300"
            >
                Weekly
            </button>
        </div>

        <canvas ref="chartRef"></canvas>
    </div>
</template>
