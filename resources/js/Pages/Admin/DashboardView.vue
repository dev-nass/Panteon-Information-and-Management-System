<script setup>
import Button from "@/Components/Form/Button.vue";
import Dashboard from "@/Layouts/Dashboard.vue";
import StatCard from "@/Components/Dashboard/StatCard.vue";

import BarChart from "@/Components/Charts/BarChart.vue";
import DoughnutChart from "@/Components/Charts/DoughnutChart.vue";
import HorizontalBarChart from "@/Components/Charts/HorizontalBarChart.vue";

import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    stats: { type: Object, required: true },
    disposal_stats: { type: Object, required: true },
    activity_data: { type: Object, default: null },
    phase_data: { type: Object, default: null },
    cluster_data: { type: Array, default: null },
    phases: { type: Array, default: () => [] },
    selected_phase_id: { type: Number, default: null },
    current_tab: { type: String, default: "summary" },
    current_filter: { type: String, default: "monthly" },
    selected_year: { type: Number, default: new Date().getFullYear() },
});

const activeTab = ref(props.current_tab);
const activeFilter = ref(props.current_filter);
const selectedYear = ref(props.selected_year);
const selectedPhaseId = ref(props.selected_phase_id);

const currentYear = new Date().getFullYear();
const yearOptions = Array.from(
    { length: currentYear - 2013 + 1 },
    (_, i) => 2013 + i,
).reverse();

const changeTab = (tab) => {
    activeTab.value = tab;
    router.get(
        route("admin.dashboard"),
        { tab },
        { preserveState: true },
    );
};

const changeFilter = (filter) => {
    activeFilter.value = filter;
    router.get(
        route("admin.dashboard"),
        { tab: activeTab.value, filter, year: selectedYear.value },
        { preserveState: true },
    );
};

const changeYear = () => {
    router.get(
        route("admin.dashboard"),
        { tab: activeTab.value, filter: activeFilter.value, year: selectedYear.value },
        { preserveState: true },
    );
};

const changePhase = () => {
    router.get(
        route("admin.dashboard"),
        { tab: activeTab.value, phase_id: selectedPhaseId.value },
        { preserveState: true },
    );
};

/* BAR CHART DATA */
const performanceData = computed(() => {
    if (!props.activity_data) return null;
    return {
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
    };
});

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

/* PHASE OCCUPANCY DATA */
const phaseOccupancyData = computed(() => {
    if (!props.phase_data) return null;
    return {
        labels: props.phase_data.labels,
        datasets: [
            {
                label: "Occupied",
                data: props.phase_data.occupied,
                backgroundColor: "rgba(34,197,94,0.7)",
                borderColor: "rgba(34,197,94,1)",
                borderWidth: 1,
            },
            {
                label: "Available",
                data: props.phase_data.available,
                backgroundColor: "rgba(156,163,175,0.5)",
                borderColor: "rgba(156,163,175,1)",
                borderWidth: 1,
            },
        ],
    };
});

const phaseOccupancyOptions = {
    indexAxis: "y",
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: true, position: "top" },
    },
    scales: {
        x: { stacked: true },
        y: { stacked: true },
    },
};

const clusterDonutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: "70%",
    plugins: {
        legend: { display: true, position: "bottom" },
    },
};

defineOptions({
    layout: Dashboard,
});
</script>

<template>
    <div class="p-6 space-y-6">
        <!-- HEADER WITH TABS -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-green-600 dark:text-green-400">
                    Dashboard
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Overview of burial records and lot occupancy
                </p>
            </div>
            <div
                class="flex gap-2 bg-gray-100 dark:bg-neutral-800 p-1 rounded-xl"
            >
                <button
                    v-for="tab in ['summary', 'phases', 'clusters']"
                    :key="tab"
                    @click="changeTab(tab)"
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
        </div>

        <!-- FILTER TABS AND YEAR SELECTOR (Only for Summary) -->
        <div
            v-if="activeTab === 'summary'"
            class="flex items-center justify-between gap-4"
        >
            <div
                class="flex gap-2 bg-gray-100 dark:bg-neutral-800 p-1 rounded-xl w-fit"
            >
                <button
                    v-for="filter in ['today', 'weekly', 'monthly', 'yearly']"
                    :key="filter"
                    @click="changeFilter(filter)"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition"
                    :class="
                        activeFilter === filter
                            ? 'bg-green-500 text-white'
                            : 'text-gray-600 dark:text-gray-400 hover:bg-green-500/10'
                    "
                >
                    {{ filter.charAt(0).toUpperCase() + filter.slice(1) }}
                </button>
            </div>

            <div class="flex items-center gap-2">
                <label
                    class="text-sm font-medium text-gray-600 dark:text-gray-300"
                >
                    Year:
                </label>
                <select
                    v-model="selectedYear"
                    @change="changeYear"
                    class="px-3 py-2 border bg-white dark:bg-neutral-800 border-gray-200 dark:border-neutral-700 rounded-lg text-sm text-gray-800 dark:text-neutral-200 focus:border-green-500 focus:ring-2 focus:ring-green-500"
                >
                    <option
                        v-for="year in yearOptions"
                        :key="year"
                        :value="year"
                    >
                        {{ year }}
                    </option>
                </select>
            </div>
        </div>

        <!-- STAT CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <StatCard
                title="Total Burial Records"
                :value="stats.total_burial_records.toString()"
            />

            <StatCard title="Total Lots" :value="stats.total_lots.toString()" />

            <StatCard
                title="Occupied Lots"
                :value="stats.occupied_lots.toString()"
            />

            <StatCard
                title="Available Lots"
                :value="stats.available_lots.toString()"
            />
        </div>

        <!-- SUMMARY TAB CONTENT -->
        <div v-if="activeTab === 'summary'" class="grid lg:grid-cols-3 gap-6">
            <!-- LEFT COLUMN -->
            <div class="lg:col-span-2 space-y-6">
                <!-- ACTIVITY CHART -->
                <div class="dashboard-card">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold">
                            {{
                                activeFilter === "today"
                                    ? "Today Activity"
                                    : activeFilter === "weekly"
                                      ? "Weekly Activity"
                                      : activeFilter === "yearly"
                                        ? "Yearly Activity"
                                        : "Monthly Activity"
                            }}
                        </h3>
                    </div>

                    <div class="h-80">
                        <BarChart
                            v-if="performanceData"
                            :chartData="performanceData"
                            :chartOptions="performanceOptions"
                        />
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="space-y-6">
                <!-- STATISTICS OVERVIEW -->
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

        <!-- PHASES TAB CONTENT -->
        <div v-if="activeTab === 'phases'" class="space-y-6">
            <div class="dashboard-card">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold">Phase Occupancy</h3>
                </div>

                <div class="h-96">
                    <HorizontalBarChart
                        v-if="phaseOccupancyData"
                        :chartData="phaseOccupancyData"
                        :chartOptions="phaseOccupancyOptions"
                    />
                </div>
            </div>
        </div>

        <!-- CLUSTERS TAB CONTENT -->
        <div v-if="activeTab === 'clusters'" class="space-y-6">
            <div class="flex items-center gap-2">
                <label
                    class="text-sm font-medium text-gray-600 dark:text-gray-300"
                >
                    Phase:
                </label>
                <select
                    v-model="selectedPhaseId"
                    @change="changePhase"
                    class="px-3 py-2 border bg-white dark:bg-neutral-800 border-gray-200 dark:border-neutral-700 rounded-lg text-sm text-gray-800 dark:text-neutral-200 focus:border-green-500 focus:ring-2 focus:ring-green-500"
                >
                    <option :value="null">All Phases</option>
                    <option
                        v-for="phase in phases"
                        :key="phase.id"
                        :value="phase.id"
                    >
                        {{ phase.phase_name }}
                    </option>
                </select>
            </div>

            <div
                v-if="cluster_data && cluster_data.length > 0"
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"
            >
                <div
                    v-for="cluster in cluster_data"
                    :key="cluster.id"
                    class="dashboard-card"
                >
                    <h3 class="font-semibold text-center mb-2">
                        {{ cluster.name }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 text-center mb-4">
                        {{ cluster.phase_name }}
                    </p>
                    <div class="h-48">
                        <DoughnutChart
                            :chartData="{
                                labels: ['Occupied', 'Available'],
                                datasets: [
                                    {
                                        data: [cluster.occupied, cluster.available],
                                        backgroundColor: [
                                            'rgba(34,197,94,0.7)',
                                            'rgba(156,163,175,0.5)',
                                        ],
                                        borderColor: [
                                            'rgba(34,197,94,1)',
                                            'rgba(156,163,175,1)',
                                        ],
                                        borderWidth: 1,
                                    },
                                ],
                            }"
                            :chartOptions="clusterDonutOptions"
                        />
                    </div>
                    <div class="text-center mt-4 text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-semibold">{{ cluster.occupied }}</span> / {{ cluster.total }} lots
                    </div>
                </div>
            </div>
            <div
                v-else
                class="dashboard-card text-center py-12 text-gray-500 dark:text-gray-400"
            >
                No clusters found
            </div>
        </div>
    </div>
</template>
