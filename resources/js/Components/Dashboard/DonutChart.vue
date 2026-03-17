<script setup>
import { onMounted, ref } from "vue";

const chartRef = ref(null);

onMounted(() => {
    new Chart(chartRef.value, {
        type: "doughnut",
        data: {
            labels: ["Buried", "Cremated", "Reserved", "Available"],
            datasets: [
                {
                    data: [12562, 10, 25, 4],
                    backgroundColor: [
                        "#22c55e",
                        "#4ade80",
                        "#16a34a",
                        "#86efac",
                    ],
                },
            ],
        },
        options: {
            plugins: {
                legend: { position: "right" },
            },
        },
    });
});
</script>

<template>
    <div
        class="p-6 rounded-xl bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 shadow-sm"
    >
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">
            Burial Distribution
        </h3>

        <canvas ref="chartRef"></canvas>
    </div>
</template>
