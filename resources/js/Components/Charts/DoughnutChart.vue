<script setup>
import { Chart as ChartJS, Title, Tooltip, Legend, ArcElement } from "chart.js";

import { Doughnut } from "vue-chartjs";

ChartJS.register(Title, Tooltip, Legend, ArcElement);

defineProps({
    chartData: Object,
    chartOptions: Object,
});
</script>

<template>
    <div
        class="h-100 p-5 rounded-xl bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 shadow-sm hover:shadow-md transition"
    >
        <Doughnut :data="chartData" :options="chartOptions" />
    </div>
</template>
