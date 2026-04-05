<script setup>
import Button from "@/Components/Form/Button.vue";
import Dashboard from "@/Layouts/Dashboard.vue";
import StatCard from "@/Components/Dashboard/StatCard.vue";

import BarChart from "@/Components/Charts/BarChart.vue";
import DoughnutChart from "@/Components/Charts/DoughnutChart.vue";

import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    stats: { type: Object, required: true },
    disposal_stats: { type: Object, required: true },
    activity_data: { type: Object, required: true },
    current_filter: { type: String, default: 'monthly' },
});

const activeTab = ref(props.current_filter);

const changeFilter = (filter) => {
    activeTab.value = filter;
    router.get(route('clerk.dashboard'), { filter }, { preserveState: true });
};

/* BAR CHART DATA */
const performanceData = computed(() => ({
    labels: props.activity_data.labels,
    datasets: [
        {
            label: "Burial Records",
            data: props.activity_data.values,
            backgroundColor: "rgba(34,197,94,0.15)",
            borderColor: "rgba(34,197,94,0.7)",
            borderWidth: 2,
            tension: 0.4,
            fill: true,
        },
    ],
}));

const performanceOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
};

/* DOUGHNUT DATA */
const attendanceData = computed(() => {
    const burial = props.disposal_stats.burial || 0;
    const cremation = props.disposal_stats.cremation || 0;
    const occupied = props.stats.occupied_lots || 0;
    const available = props.stats.available_lots || 0;

    return {
        labels: ["Burial", "Cremation", "Occupied Lots", "Available Lots"],
        datasets: [
            {
                data: [burial, cremation, occupied, available],
                backgroundColor: [
                    "rgba(34,197,94,0.8)",
                    "rgba(234,179,8,0.7)",
                    "rgba(99,102,241,0.7)",
                    "rgba(239,68,68,0.7)",
                ],
                borderWidth: 0,
            },
        ],
    };
});

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
    <div class="p-6 space-y-6">
        <!-- HEADER -->
        <div class="flex items-center justify-between">
            <h1 class="text-3xl font-bold text-green-600 dark:text-green-400">
                Dashboard
            </h1>

            <Button> + Create new data </Button>
        </div>

        <!-- FILTER TABS -->
        <div
            class="flex gap-2 bg-gray-100 dark:bg-neutral-800 p-1 rounded-xl w-fit"
        >
            <button
                v-for="tab in ['today', 'weekly', 'monthly', 'yearly']"
                :key="tab"
                @click="changeFilter(tab)"
                class="px-4 py-2 rounded-lg text-sm font-medium transition"
                :class="
                    activeTab === tab
                        ? 'bg-green-500 text-white'
                        : 'text-gray-600 dark:text-gray-400 hover:bg-green-500/10'
                "
            >
                {{ tab.charAt(0).toUpperCase() + tab.slice(1) }}
            </button>
        </div>

        <!-- STAT CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <StatCard
                title="Total Burial Records"
                :value="stats.total_burial_records.toString()"
            />

            <StatCard
                title="Total Lots"
                :value="stats.total_lots.toString()"
            />

            <StatCard
                title="Occupied Lots"
                :value="stats.occupied_lots.toString()"
            />

            <StatCard
                title="Available Lots"
                :value="stats.available_lots.toString()"
            />
        </div>

        <!-- MAIN DASHBOARD GRID -->
        <div class="grid lg:grid-cols-3 gap-6">
            <!-- LEFT COLUMN -->
            <div class="lg:col-span-2 space-y-6">
                <!-- MONTHLY ACTIVITY -->
                <div class="dashboard-card">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold">
                            {{
                                activeTab === 'today'
                                    ? 'Today Activity'
                                    : activeTab === 'weekly'
                                      ? 'Weekly Activity'
                                      : activeTab === 'yearly'
                                        ? 'Yearly Activity'
                                        : 'Monthly Activity'
                            }}
                        </h3>
                    </div>

                    <div class="h-80">
                        <BarChart
                            :chartData="performanceData"
                            :chartOptions="performanceOptions"
                        />
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="space-y-6">
                <!-- REPORT CHART -->
                <div class="dashboard-card">
                    <h3 class="mb-4 font-semibold">Statistics Overview</h3>

                    <div class="h-64">
                        <DoughnutChart
                            :chartData="attendanceData"
                            :chartOptions="attendanceOptions"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
