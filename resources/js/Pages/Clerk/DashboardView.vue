<script setup>
import Button from "@/Components/Form/Button.vue";
import Dashboard from "@/Layouts/Dashboard.vue";
import StatCard from "@/Components/Dashboard/StatCard.vue";

import BarChart from "@/Components/Charts/BarChart.vue";
import DoughnutChart from "@/Components/Charts/DoughnutChart.vue";
/* BAR CHART DATA */

const performanceData = {
    labels: ["1 June", "2 June", "3 June", "4 June", "5 June", "6 June"],
    datasets: [
        {
            label: "Performance",
            backgroundColor: "#22c55e",
            borderRadius: 8,
            data: [60, 45, 50, 70, 40, 65],
        },
    ],
};

const performanceOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
    },
};

/* DOUGHNUT DATA */

const attendanceData = {
    labels: ["Present", "Leave", "Holiday", "Absent"],
    datasets: [
        {
            backgroundColor: ["#22c55e", "#eab308", "#6366f1", "#ef4444"],
            data: [12562, 10, 25, 4],
        },
    ],
};

const attendanceOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: "70%",
};

defineOptions({
    layout: Dashboard,
});
</script>
<template>
    <div class="p-6 space-y-8">
        <h1 class="text-4xl font-bold text-green-600 dark:text-green-400">
            Dashboard
        </h1>
        <!-- STAT CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <StatCard title="Total Records" value="12,600" change="+2%" />
            <StatCard title="New Burials" value="1186" change="+15%" />
            <StatCard title="New Entries" value="22" change="+2%" />
            <StatCard title="Cremation Rate" value="89.9%" change="+5%" />
        </div>

        <!-- CHARTS -->
        <div class="grid lg:grid-cols-2 gap-6">
            <!-- PERFORMANCE CHART -->
            <div class="dashboard-card">
                <h3 class="mb-4 font-semibold">Records Performance</h3>
                <BarChart
                    :chartData="performanceData"
                    :chartOptions="performanceOptions"
                />
            </div>

            <!-- ATTENDANCE -->
            <div class="dashboard-card">
                <h3 class="mb-4 font-semibold">Burial Distribution</h3>
                <DoughnutChart
                    :chartData="attendanceData"
                    :chartOptions="attendanceOptions"
                />
            </div>
        </div>
    </div>
</template>
