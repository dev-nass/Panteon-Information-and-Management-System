<script setup>
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
} from "chart.js";

import { Bar } from "vue-chartjs";

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
);

defineProps({
    chartData: Object,
    chartOptions: Object,
    height: { type: String, default: "400px" },
});
</script>

<template>
    <div
        class="p-5 rounded-xl bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 shadow-sm hover:shadow-md transition"
        :style="{ height }"
    >
        <Bar :data="chartData" :options="chartOptions" />
    </div>
</template>
