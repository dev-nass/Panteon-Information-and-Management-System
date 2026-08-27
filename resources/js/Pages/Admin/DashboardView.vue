<script setup>
import Button from "@/Components/Form/Button.vue";
import Dashboard from "@/Layouts/Dashboard.vue";
import StatCard from "@/Components/Dashboard/StatCard.vue";
import DashboardFiltersModal from "@/Components/Dashboard/DashboardFiltersModal.vue";

import BarChart from "@/Components/Charts/BarChart.vue";
import DoughnutChart from "@/Components/Charts/DoughnutChart.vue";
import HorizontalBarChart from "@/Components/Charts/HorizontalBarChart.vue";

import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    stats: { type: Object, required: true },
    disposal_stats: { type: Object, required: true },
    total_stats: { type: Object, required: true },
    total_disposal_stats: { type: Object, required: true },
    activity_data: { type: Object, default: null },
    demographic_data: { type: Object, default: null },
    geographic_data: { type: Object, default: null },
    filter_options: { type: Object, default: () => ({ barangays: [] }) },
    active_filters: {
        type: Object,
        default: () => ({ age_range: null, barangay: null }),
    },
    phase_data: { type: Object, default: null },
    cluster_data: { type: Object, default: null },
    phases: { type: Array, default: () => [] },
    selected_phase_id: { type: Number, default: null },
    selected_type: { type: String, default: "" },
    current_tab: { type: String, default: "summary" },
    current_filter: { type: String, default: "monthly" },
    selected_year: { type: Number, default: new Date().getFullYear() },
});

const activeTab = ref(props.current_tab);
const activeFilter = ref(props.current_filter);
const selectedYear = ref(props.selected_year);
const selectedPhaseId = ref(props.selected_phase_id);
const selectedType = ref(props.selected_type);

const activeAgeRange = ref(props.active_filters?.age_range ?? null);
const activeBarangay = ref(props.active_filters?.barangay ?? null);

const currentYear = new Date().getFullYear();
const yearOptions = Array.from(
    { length: currentYear - 2013 + 1 },
    (_, i) => 2013 + i,
).reverse();

const hasActiveDashboardFilters = computed(() => {
    return activeAgeRange.value !== null || activeBarangay.value !== null;
});

const changeTab = (tab) => {
    activeTab.value = tab;
    router.get(route("admin.dashboard"), { tab }, { preserveState: true });
};

const changeFilter = (filter) => {
    activeFilter.value = filter;
    router.get(
        route("admin.dashboard"),
        {
            tab: activeTab.value,
            filter,
            year: selectedYear.value,
            age_range: activeAgeRange.value,
            barangay: activeBarangay.value,
        },
        { preserveState: true },
    );
};

const changeYear = () => {
    router.get(
        route("admin.dashboard"),
        {
            tab: activeTab.value,
            filter: activeFilter.value,
            year: selectedYear.value,
            age_range: activeAgeRange.value,
            barangay: activeBarangay.value,
        },
        { preserveState: true },
    );
};

const applyDashboardFilters = (filters) => {
    activeAgeRange.value = filters.age_range;
    activeBarangay.value = filters.barangay;
    router.get(
        route("admin.dashboard"),
        {
            tab: activeTab.value,
            filter: activeFilter.value,
            year: selectedYear.value,
            age_range: filters.age_range,
            barangay: filters.barangay,
        },
        { preserveState: true },
    );
};

const resetDashboardFilters = () => {
    activeAgeRange.value = null;
    activeBarangay.value = null;
    router.get(
        route("admin.dashboard"),
        {
            tab: activeTab.value,
            filter: activeFilter.value,
            year: selectedYear.value,
        },
        { preserveState: true },
    );
};

const removeFilter = (key) => {
    if (key === "age_range") activeAgeRange.value = null;
    if (key === "barangay") activeBarangay.value = null;
    router.get(
        route("admin.dashboard"),
        {
            tab: activeTab.value,
            filter: activeFilter.value,
            year: selectedYear.value,
            age_range: activeAgeRange.value,
            barangay: activeBarangay.value,
        },
        { preserveState: true },
    );
};

const formatAgeRange = (range) => {
    const map = {
        "0-12": "Child (0-12)",
        "13-19": "Teen (13-19)",
        "20-39": "Young Adult (20-39)",
        "40-59": "Adult (40-59)",
        "60-74": "Senior (60-74)",
        "75+": "Elderly (75+)",
    };
    return map[range] || range;
};

const changePhase = () => {
    router.get(
        route("admin.dashboard"),
        {
            tab: activeTab.value,
            phase_id: selectedPhaseId.value,
            cluster_type: selectedType.value,
            cluster_page: 1,
        },
        { preserveState: true },
    );
};

const changeType = () => {
    router.get(
        route("admin.dashboard"),
        {
            tab: "clusters",
            phase_id: selectedPhaseId.value,
            cluster_type: selectedType.value,
            cluster_page: 1,
        },
        { preserveState: true },
    );
};

const changeClusterPage = (page) => {
    const lastPage = props.cluster_data?.last_page ?? 1;
    if (page < 1 || page > lastPage) return;
    router.get(
        route("admin.dashboard"),
        {
            tab: "clusters",
            phase_id: selectedPhaseId.value,
            cluster_type: selectedType.value,
            cluster_page: page,
        },
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
    scales: {
        x: {
            title: { display: true, text: "Time Period", font: { size: 12 } },
        },
        y: {
            title: { display: true, text: "Number of Records", font: { size: 12 } },
            beginAtZero: true,
        },
    },
};

/* DOUGHNUT DATA */
const attendanceData = computed(() => {
    const burial = props.total_disposal_stats.burial || 0;
    const cremation = props.total_disposal_stats.cremation || 0;
    const occupied = props.total_stats.occupied_lots || 0;
    const available = props.total_stats.available_lots || 0;

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

/* AGE DISTRIBUTION DATA */
const ageDistributionData = computed(() => {
    if (!props.demographic_data) return null;
    return {
        labels: props.demographic_data.labels,
        datasets: [
            {
                label: "Records",
                data: props.demographic_data.values,
                backgroundColor: [
                    "rgba(59,130,246,0.7)",
                    "rgba(99,102,241,0.7)",
                    "rgba(34,197,94,0.7)",
                    "rgba(234,179,8,0.7)",
                    "rgba(249,115,22,0.7)",
                    "rgba(239,68,68,0.7)",
                    "rgba(156,163,175,0.5)",
                ],
                borderWidth: 0,
            },
        ],
    };
});

const ageDistributionOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        x: {
            title: { display: true, text: "Age Group", font: { size: 12 } },
        },
        y: {
            title: { display: true, text: "Number of Records", font: { size: 12 } },
            beginAtZero: true,
        },
    },
};

/* GEOGRAPHIC DISTRIBUTION DATA */
const geographicDistributionData = computed(() => {
    if (!props.geographic_data) return null;
    return {
        labels: props.geographic_data.labels,
        datasets: [
            {
                label: "Records",
                data: props.geographic_data.values,
                backgroundColor: "rgba(34,197,94,0.7)",
                borderColor: "rgba(34,197,94,1)",
                borderWidth: 1,
            },
        ],
    };
});

const geographicDistributionOptions = {
    indexAxis: "y",
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        x: {
            title: { display: true, text: "Number of Records", font: { size: 12 } },
            beginAtZero: true,
        },
        y: {
            title: { display: true, text: "Barangay", font: { size: 12 } },
        },
    },
};

const geoChartHeight = computed(() => {
    const count = props.geographic_data?.labels?.length ?? 0;
    return `${Math.max(count * 44, 176)}px`;
});

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
        x: {
            stacked: true,
            title: { display: true, text: "Number of Lots", font: { size: 12 } },
            beginAtZero: true,
        },
        y: {
            stacked: true,
            title: { display: true, text: "Phase", font: { size: 12 } },
        },
    },
};

/* CLUSTER OCCUPANCY DATA */
const clusterOccupancyData = computed(() => {
    if (!props.cluster_data) return null;

    const types = props.cluster_data.types ?? {};
    const occupied = [];
    const available = [];

    for (let i = 0; i < props.cluster_data.labels.length; i++) {
        let occ = 0;
        let avail = 0;
        for (const type of Object.values(types)) {
            occ += type.occupied[i] ?? 0;
            avail += type.available[i] ?? 0;
        }
        occupied.push(occ);
        available.push(avail);
    }

    return {
        labels: props.cluster_data.labels,
        datasets: [
            {
                label: "Occupied",
                data: occupied,
                backgroundColor: "rgba(34,197,94,0.7)",
                borderColor: "rgba(34,197,94,1)",
                borderWidth: 1,
            },
            {
                label: "Available",
                data: available,
                backgroundColor: "rgba(156,163,175,0.5)",
                borderColor: "rgba(156,163,175,1)",
                borderWidth: 1,
            },
        ],
    };
});

const clusterOccupancyOptions = {
    indexAxis: "y",
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: true, position: "top" },
    },
    scales: {
        x: {
            stacked: true,
            title: { display: true, text: "Number of Lots", font: { size: 12 } },
            beginAtZero: true,
        },
        y: {
            stacked: true,
            title: { display: true, text: "Cluster", font: { size: 12 } },
        },
    },
};

const clusterChartHeight = computed(() => {
    const count = props.cluster_data?.labels?.length ?? 0;
    return `${Math.max(count * 44, 176)}px`;
});

defineOptions({
    layout: Dashboard,
});
</script>

<template>
    <div class="p-6 space-y-6">
        <!-- HEADER WITH TABS -->
        <div
            class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
        >
            <div>
                <h1
                    class="text-3xl font-bold text-green-600 dark:text-green-400"
                >
                    Dashboard
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Overview of burial records and lot occupancy
                </p>
            </div>
            <div
                class="flex gap-2 bg-gray-100 dark:bg-neutral-800 p-1 rounded-xl w-full md:w-fit"
            >
                <button
                    v-for="tab in ['summary', 'phases', 'clusters']"
                    :key="tab"
                    @click="changeTab(tab)"
                    class="flex-1 md:flex-none px-4 py-2 rounded-lg text-sm font-medium transition"
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

        <!-- FILTER TABS AND CONTROLS (Only for Summary) -->
        <div
            v-if="activeTab === 'summary'"
            class="space-y-3"
        >
            <div
                class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
            >
                <div
                    class="flex flex-wrap gap-2 bg-gray-100 dark:bg-neutral-800 p-1 rounded-xl w-full md:w-fit"
                >
                    <button
                        v-for="filter in ['today', 'weekly', 'monthly', 'yearly']"
                        :key="filter"
                        @click="changeFilter(filter)"
                        class="flex-1 md:flex-none px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition"
                        :class="
                            activeFilter === filter
                                ? 'bg-green-500 text-white'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-green-500/10'
                        "
                    >
                        {{ filter.charAt(0).toUpperCase() + filter.slice(1) }}
                    </button>
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    <!-- YEAR SELECTOR (only on yearly) -->
                    <div
                        v-if="activeFilter === 'yearly'"
                        class="flex items-center gap-2"
                    >
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

                    <!-- FILTERS BUTTON -->
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition"
                        :class="
                            hasActiveDashboardFilters
                                ? 'bg-green-500/10 text-green-600 dark:text-green-400 border border-green-500/30'
                                : 'bg-gray-100 dark:bg-neutral-800 text-gray-600 dark:text-gray-400 hover:bg-green-500/10 border border-transparent'
                        "
                        data-hs-overlay="#dashboard-filters-modal"
                    >
                        <svg
                            class="size-4"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z" />
                        </svg>
                        Filters
                        <span
                            v-if="hasActiveDashboardFilters"
                            class="size-5 inline-flex items-center justify-center rounded-full bg-green-500 text-white text-xs font-bold"
                        >
                            {{ (activeAgeRange ? 1 : 0) + (activeBarangay ? 1 : 0) }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- ACTIVE FILTER CHIPS -->
            <div
                v-if="hasActiveDashboardFilters"
                class="flex flex-wrap items-center gap-2"
            >
                <span
                    class="text-xs text-gray-500 dark:text-gray-400"
                >
                    Active filters:
                </span>

                <button
                    v-if="activeAgeRange"
                    @click="removeFilter('age_range')"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-green-500/10 text-green-600 dark:text-green-400 border border-green-500/30 hover:bg-green-500/20 transition"
                >
                    Age: {{ formatAgeRange(activeAgeRange) }}
                    <svg
                        class="size-3"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>

                <button
                    v-if="activeBarangay"
                    @click="removeFilter('barangay')"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/30 hover:bg-blue-500/20 transition"
                >
                    {{ activeBarangay.charAt(0).toUpperCase() + activeBarangay.slice(1) }}
                    <svg
                        class="size-3"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- STAT CARDS -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
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
        <div v-if="activeTab === 'summary'" class="space-y-6">
            <!-- TOP ROW: Activity + Doughnut -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- LEFT COLUMN -->
                <div class="lg:col-span-2 space-y-3">
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
                    <BarChart
                        v-if="performanceData"
                        :chartData="performanceData"
                        :chartOptions="performanceOptions"
                    />
                </div>

                <!-- RIGHT COLUMN -->
                <div class="space-y-3">
                    <h3 class="font-semibold">Statistics Overview</h3>
                    <DoughnutChart
                        :chartData="attendanceData"
                        :chartOptions="attendanceOptions"
                    />
                </div>
            </div>

            <!-- BOTTOM ROW: Demographics + Geography -->
            <div class="space-y-6">
                <!-- AGE DISTRIBUTION -->
                <div class="space-y-3">
                    <h3 class="font-semibold">Age Distribution</h3>
                    <BarChart
                        v-if="
                            ageDistributionData &&
                            ageDistributionData.datasets[0].data.some(
                                (v) => v > 0,
                            )
                        "
                        :chartData="ageDistributionData"
                        :chartOptions="ageDistributionOptions"
                    />
                    <div
                        v-else
                        class="h-64 flex items-center justify-center rounded-xl bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 text-sm text-gray-500 dark:text-gray-400"
                    >
                        No age data available
                    </div>
                </div>

                <!-- GEOGRAPHIC DISTRIBUTION -->
                <div class="space-y-3">
                    <h3 class="font-semibold">Residence by Barangay</h3>
                    <HorizontalBarChart
                        v-if="
                            geographicDistributionData &&
                            geographicDistributionData.labels.length > 0
                        "
                        :chartData="geographicDistributionData"
                        :chartOptions="geographicDistributionOptions"
                        :height="geoChartHeight"
                    />
                    <div
                        v-else
                        class="h-64 flex items-center justify-center rounded-xl bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 text-sm text-gray-500 dark:text-gray-400"
                    >
                        No geographic data available
                    </div>
                </div>
            </div>
        </div>

        <!-- PHASES TAB CONTENT -->
        <div v-if="activeTab === 'phases'" class="space-y-3">
            <h3 class="font-semibold">Phase Occupancy</h3>
            <HorizontalBarChart
                v-if="phaseOccupancyData"
                :chartData="phaseOccupancyData"
                :chartOptions="phaseOccupancyOptions"
            />
        </div>

        <!-- CLUSTERS TAB CONTENT -->
        <div v-if="activeTab === 'clusters'" class="space-y-3">
            <div class="flex items-center gap-6 flex-wrap">
                <div class="flex items-center gap-2">
                    <label
                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Phase:
                    </label>
                    <select
                        v-model="selectedPhaseId"
                        @change="changePhase"
                        class="px-4 py-2 border bg-white dark:bg-neutral-800 border-gray-300 dark:border-neutral-600 rounded-lg text-sm font-medium text-gray-800 dark:text-neutral-200 focus:border-green-500 focus:ring-2 focus:ring-green-500"
                    >
                        <option
                            v-for="phase in phases"
                            :key="phase.id"
                            :value="phase.id"
                        >
                            {{ phase.phase_name }}
                        </option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <label
                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Type:
                    </label>
                    <select
                        v-model="selectedType"
                        @change="changeType"
                        class="px-4 py-2 border bg-white dark:bg-neutral-800 border-gray-300 dark:border-neutral-600 rounded-lg text-sm font-medium text-gray-800 dark:text-neutral-200 focus:border-green-500 focus:ring-2 focus:ring-green-500"
                    >
                        <option value="">All</option>
                        <option value="underground">Underground</option>
                        <option value="apartment">Apartment</option>
                        <option value="columbarium">Columbarium</option>
                    </select>
                </div>
            </div>

            <h3 class="font-semibold">Cluster Occupancy</h3>

            <HorizontalBarChart
                v-if="
                    clusterOccupancyData &&
                    clusterOccupancyData.labels.length > 0
                "
                :chartData="clusterOccupancyData"
                :chartOptions="clusterOccupancyOptions"
                :height="clusterChartHeight"
            />
            <div
                v-else
                class="h-100 flex items-center justify-center rounded-xl bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 text-sm text-gray-500 dark:text-gray-400"
            >
                No clusters found
            </div>

            <div
                v-if="cluster_data && cluster_data.last_page > 1"
                class="flex items-center justify-center gap-3"
            >
                <button
                    @click="changeClusterPage(cluster_data.current_page - 1)"
                    :disabled="cluster_data.current_page <= 1"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition disabled:opacity-40 disabled:cursor-not-allowed bg-gray-100 dark:bg-neutral-800 text-gray-600 dark:text-gray-400 hover:bg-green-500/10"
                >
                    Prev
                </button>
                <span
                    class="text-sm font-medium text-gray-600 dark:text-gray-400"
                >
                    Page {{ cluster_data.current_page }} of
                    {{ cluster_data.last_page }}
                </span>
                <button
                    @click="changeClusterPage(cluster_data.current_page + 1)"
                    :disabled="
                        cluster_data.current_page >= cluster_data.last_page
                    "
                    class="px-4 py-2 rounded-lg text-sm font-medium transition disabled:opacity-40 disabled:cursor-not-allowed bg-gray-100 dark:bg-neutral-800 text-gray-600 dark:text-gray-400 hover:bg-green-500/10"
                >
                    Next
                </button>
            </div>
        </div>

        <!-- FILTERS MODAL -->
        <DashboardFiltersModal
            :filterOptions="filter_options"
            :activeFilters="active_filters"
            @apply="applyDashboardFilters"
            @reset="resetDashboardFilters"
        />
    </div>
</template>
