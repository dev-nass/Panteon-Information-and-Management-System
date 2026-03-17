<script setup>
import Button from "@/Components/Form/Button.vue";
import Dashboard from "@/Layouts/Dashboard.vue";
import StatCard from "@/Components/Dashboard/StatCard.vue";
import PerformanceChart from "@/Components/Dashboard/PerformanceChart.vue";
import DonutChart from "@/Components/Dashboard/DonutChart.vue";

defineOptions({
    layout: Dashboard,
});
</script>
<template>
    <div class="p-6 space-y-6">
        <!-- TOP STATS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <StatCard title="Total Records" value="12,600" change="+2%" />
            <StatCard title="New Burials" value="1186" change="+15%" />
            <StatCard title="New Entries" value="22" change="+2%" />
            <StatCard title="Cremation Rate" value="89.9%" change="+5%" />
        </div>

        <!-- MAIN CHARTS -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Performance Chart -->
            <div class="lg:col-span-2">
                <PerformanceChart />
            </div>

            <!-- Donut Chart -->
            <DonutChart />
        </div>
    </div>
</template>
