<script setup>
import { ref, computed, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { debounce } from "lodash";

import Dashboard from "@/Layouts/Dashboard.vue";
import StatCard from "@/Components/Dashboard/StatCard.vue";
import BarChart from "@/Components/Charts/BarChart.vue";
import DoughnutChart from "@/Components/Charts/DoughnutChart.vue";
import TableHeader from "@/Components/Table/TableHeader.vue";
import TableData from "@/Components/Table/TableData.vue";
import Input from "@/Components/Form/Input.vue";

const props = defineProps({
    logs: Object,
    filters: Object,
    stats: Object,
    per_user: { type: Array, default: () => [] },
    chart_data: Object,
});

const range = ref(props.filters.range || "all");
const action = ref(props.filters.action || "all");
const userSearch = ref(props.filters.user_search || "");

const rangeOptions = [
    { value: "today", label: "Today" },
    { value: "7days", label: "7 Days" },
    { value: "30days", label: "30 Days" },
    { value: "all", label: "All" },
];

const actionOptions = [
    { value: "created", label: "Created" },
    { value: "updated", label: "Updated" },
    { value: "deleted", label: "Deleted" },
    { value: "role_changed", label: "Role Changed" },
    { value: "imported", label: "Imported" },
];

const actionBadgeColors = {
    created:
        "bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-400",
    updated: "bg-blue-100 text-blue-800 dark:bg-blue-500/10 dark:text-blue-400",
    deleted: "bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400",
    role_changed:
        "bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-400",
    imported:
        "bg-purple-100 text-purple-800 dark:bg-purple-500/10 dark:text-purple-400",
};

const actionChartColors = {
    created: "rgba(34,197,94,0.8)",
    updated: "rgba(59,130,246,0.8)",
    deleted: "rgba(239,68,68,0.8)",
    role_changed: "rgba(245,158,11,0.8)",
    imported: "rgba(168,85,247,0.8)",
};

const actionLabels = {
    created: "Created",
    updated: "Updated",
    deleted: "Deleted",
    role_changed: "Role Changed",
    imported: "Imported",
};

const actionsPerDayData = computed(() => {
    const days = props.chart_data?.actions_per_day ?? [];
    return {
        labels: days.map((day) => day.date),
        datasets: [
            {
                label: "Actions",
                data: days.map((day) => day.count),
                backgroundColor: "rgba(34,197,94,0.15)",
                borderColor: "rgba(34,197,94,0.7)",
                borderWidth: 2,
                tension: 0.4,
                fill: true,
            },
        ],
    };
});

const actionsPerDayOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        x: {
            ticks: {
                font: { size: 14 },
            },
        },
        y: {
            beginAtZero: true,
            ticks: {
                stepSize: 1,
                precision: 0,
            },
        },
    },
};

const actionBreakdownData = computed(() => {
    const breakdown = props.chart_data?.action_breakdown ?? [];
    return {
        labels: breakdown.map(
            (item) => actionLabels[item.action] ?? item.action,
        ),
        datasets: [
            {
                data: breakdown.map((item) => item.count),
                backgroundColor: breakdown.map(
                    (item) =>
                        actionChartColors[item.action] ??
                        "rgba(156,163,175,0.7)",
                ),
                borderWidth: 0,
            },
        ],
    };
});

const actionBreakdownOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: "70%",
    plugins: {
        legend: { position: "bottom" },
    },
};

const perUserView = ref("table");

const perUserData = computed(() => props.per_user ?? []);

const perUserBarData = computed(() => {
    return {
        labels: perUserData.value.map(
            (item) => `${item.user?.first_name} ${item.user?.last_name}`,
        ),
        datasets: [
            {
                label: "Actions",
                data: perUserData.value.map((item) => item.count),
                backgroundColor: "rgba(34,197,94,0.15)",
                borderColor: "rgba(34,197,94,0.7)",
                borderWidth: 2,
                tension: 0.4,
                fill: true,
            },
        ],
    };
});

const perUserBarOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        x: {
            ticks: {
                maxRotation: 45,
                minRotation: 0,
                font: { size: 14 },
            },
        },
        y: {
            beginAtZero: true,
            ticks: {
                stepSize: 1,
                precision: 0,
            },
        },
    },
};

const actionBadge = (value) => actionBadgeColors[value] ?? "";

const formatTimestamp = (value) => {
    if (!value) return "";
    return new Date(value).toLocaleString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
        hour: "numeric",
        minute: "2-digit",
    });
};

const propertyEntries = (properties, key) => {
    if (!properties || !properties[key]) return [];
    return Object.entries(properties[key]);
};

const applyFilters = () => {
    router.get(
        route("admin.activity_log.index"),
        {
            range: range.value,
            action: action.value,
            user_search: userSearch.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const changeRange = (value) => {
    range.value = value;
    applyFilters();
};

const changeAction = () => {
    applyFilters();
};

watch(
    userSearch,
    debounce(() => applyFilters(), 500),
);

defineOptions({
    layout: Dashboard,
});
</script>

<template>
    <div class="max-w-7xl mx-auto p-6 space-y-6">
        <!-- HEADER -->
        <div
            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
        >
            <div>
                <h1
                    class="text-3xl font-bold text-green-600 dark:text-green-400"
                >
                    Activity Log
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Track admin actions across the system
                </p>
            </div>
        </div>

        <!-- FILTER BAR -->
        <div
            class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
        >
            <div
                class="flex flex-wrap gap-2 bg-gray-100 dark:bg-neutral-800 p-1 rounded-xl w-full md:w-fit"
            >
                <button
                    v-for="option in rangeOptions"
                    :key="option.value"
                    @click="changeRange(option.value)"
                    class="flex-1 md:flex-none px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition"
                    :class="
                        range === option.value
                            ? 'bg-green-500 text-white'
                            : 'text-gray-600 dark:text-gray-400 hover:bg-green-500/10'
                    "
                >
                    {{ option.label }}
                </button>
            </div>

            <select
                v-model="action"
                @change="changeAction"
                class="h-10 px-4 border bg-white dark:bg-neutral-800 border-gray-200 dark:border-neutral-700 rounded-xl text-sm text-gray-800 dark:text-neutral-200 focus:border-green-500 focus:ring-2 focus:ring-green-500"
            >
                <option value="all">All Actions</option>
                <option
                    v-for="option in actionOptions"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>
        </div>

        <!-- STAT CARDS -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
            <StatCard title="Actions Today" :value="stats.today_count" />
            <StatCard title="Actions This Week" :value="stats.week_count" />
            <StatCard title="Total Actions" :value="stats.total_count" />
        </div>

        <!-- CHARTS -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            <div class="space-y-3">
                <h3 class="font-semibold text-gray-800 dark:text-neutral-200">
                    Actions Per Day
                </h3>
                <BarChart
                    v-if="actionsPerDayData.labels.length > 0"
                    :chartData="actionsPerDayData"
                    :chartOptions="actionsPerDayOptions"
                />
                <div
                    v-else
                    class="h-100 flex items-center justify-center rounded-xl bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 text-sm text-gray-500 dark:text-neutral-400"
                >
                    No activity recorded in the last 30 days
                </div>
            </div>

            <div class="space-y-3">
                <h3 class="font-semibold text-gray-800 dark:text-neutral-200">
                    Action Breakdown
                </h3>
                <DoughnutChart
                    v-if="actionBreakdownData.labels.length > 0"
                    :chartData="actionBreakdownData"
                    :chartOptions="actionBreakdownOptions"
                />
                <div
                    v-else
                    class="h-100 flex items-center justify-center rounded-xl bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 text-sm text-gray-500 dark:text-neutral-400"
                >
                    No activity recorded yet
                </div>
            </div>
        </div>

        <!-- LOG TABLE / BAR GRAPH TOGGLE -->
        <div class="space-y-3">
            <div
                class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
            >
                <h3 class="font-semibold text-gray-800 dark:text-neutral-200">
                    Actions Per User
                </h3>
                <div
                    class="flex gap-2 bg-gray-100 dark:bg-neutral-800 p-1 rounded-xl w-full md:w-fit"
                >
                    <button
                        v-for="view in ['table', 'bar']"
                        :key="view"
                        @click="perUserView = view"
                        class="flex-1 md:flex-none px-4 py-2 rounded-lg text-sm font-medium transition"
                        :class="
                            perUserView === view
                                ? 'bg-green-500 text-white'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-green-500/10'
                        "
                    >
                        {{ view === "bar" ? "Bar Graph" : "Table" }}
                    </button>
                </div>
            </div>

            <!-- BAR GRAPH VIEW -->
            <div v-if="perUserView === 'bar'">
                <BarChart
                    v-if="perUserData.length > 0"
                    :chartData="perUserBarData"
                    :chartOptions="perUserBarOptions"
                />
                <div
                    v-else
                    class="h-100 flex items-center justify-center rounded-xl bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 text-sm text-gray-500 dark:text-neutral-400"
                >
                    No user activity recorded yet
                </div>
            </div>

            <!-- TABLE VIEW -->
            <div
                v-else
                class="bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl shadow-sm overflow-hidden"
            >
                <div
                    class="px-6 py-4 border-b border-gray-200 dark:border-neutral-700"
                >
                    <Input
                        class="w-full md:max-w-md [&_input::-webkit-search-cancel-button]:hidden"
                        v-model="userSearch"
                        type="search"
                        placeholder="Search user..."
                    />
                </div>
                <div class="overflow-x-auto">
                    <table
                        class="min-w-[880px] lg:min-w-full divide-y divide-gray-200 dark:divide-neutral-700"
                    >
                        <thead class="bg-gray-50 dark:bg-neutral-800">
                            <tr>
                                <TableHeader> Timestamp </TableHeader>
                                <TableHeader> User </TableHeader>
                                <TableHeader> Action </TableHeader>
                                <TableHeader> Description </TableHeader>
                                <TableHeader> Details </TableHeader>
                            </tr>
                        </thead>

                        <tbody
                            class="divide-y divide-gray-200 dark:divide-neutral-700"
                        >
                            <tr
                                v-if="logs.data.length > 0"
                                v-for="log in logs.data"
                                :key="log.id"
                                class="bg-white dark:bg-neutral-800"
                            >
                                <TableData>
                                    {{ formatTimestamp(log.created_at) }}
                                </TableData>
                                <TableData>
                                    <Link
                                        v-if="log.user"
                                        :href="
                                            route(
                                                'admin.user_management.show',
                                                log.user.id,
                                            )
                                        "
                                        class="text-green-600 dark:text-green-400 hover:underline"
                                    >
                                        {{ log.user.first_name }}
                                        {{ log.user.last_name }}
                                    </Link>
                                    <span
                                        v-else
                                        class="text-gray-500 dark:text-neutral-400"
                                    >
                                        System
                                    </span>
                                </TableData>
                                <TableData>
                                    <span
                                        class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium"
                                        :class="actionBadge(log.action)"
                                    >
                                        {{
                                            actionLabels[log.action] ??
                                            log.action
                                        }}
                                    </span>
                                </TableData>
                                <TableData>
                                    {{ log.description }}
                                </TableData>
                                <TableData>
                                    <details class="group">
                                        <summary
                                            class="cursor-pointer list-none text-sm font-medium text-green-600 dark:text-green-400 hover:underline"
                                        >
                                            View
                                        </summary>
                                        <div
                                            v-if="log.properties"
                                            class="mt-2 w-full max-w-80 p-3 rounded-lg bg-gray-50 dark:bg-neutral-900 border border-gray-200 dark:border-neutral-700 text-xs space-y-3"
                                        >
                                            <div
                                                v-if="
                                                    propertyEntries(
                                                        log.properties,
                                                        'old',
                                                    ).length > 0
                                                "
                                            >
                                                <p
                                                    class="mb-1 font-semibold text-gray-700 dark:text-neutral-300"
                                                >
                                                    Old values
                                                </p>
                                                <ul
                                                    class="space-y-0.5 text-gray-600 dark:text-neutral-400"
                                                >
                                                    <li
                                                        v-for="[
                                                            key,
                                                            value,
                                                        ] in propertyEntries(
                                                            log.properties,
                                                            'old',
                                                        )"
                                                        :key="key"
                                                    >
                                                        <span
                                                            class="font-medium text-gray-700 dark:text-neutral-300"
                                                            >{{ key }}:</span
                                                        >
                                                        {{ value ?? "—" }}
                                                    </li>
                                                </ul>
                                            </div>
                                            <div
                                                v-if="
                                                    propertyEntries(
                                                        log.properties,
                                                        'new',
                                                    ).length > 0
                                                "
                                            >
                                                <p
                                                    class="mb-1 font-semibold text-gray-700 dark:text-neutral-300"
                                                >
                                                    New values
                                                </p>
                                                <ul
                                                    class="space-y-0.5 text-gray-600 dark:text-neutral-400"
                                                >
                                                    <li
                                                        v-for="[
                                                            key,
                                                            value,
                                                        ] in propertyEntries(
                                                            log.properties,
                                                            'new',
                                                        )"
                                                        :key="key"
                                                    >
                                                        <span
                                                            class="font-medium text-gray-700 dark:text-neutral-300"
                                                            >{{ key }}:</span
                                                        >
                                                        {{ value ?? "—" }}
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <p
                                            v-else
                                            class="mt-2 text-xs text-gray-500 dark:text-neutral-400"
                                        >
                                            No property changes recorded
                                        </p>
                                    </details>
                                </TableData>
                            </tr>

                            <tr v-else>
                                <td colspan="5" class="px-6 py-8 text-center">
                                    <span
                                        class="text-sm text-gray-500 dark:text-neutral-400"
                                        >No activity logs found</span
                                    >
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION -->
                <div
                    v-if="logs?.meta?.links"
                    class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200 dark:border-neutral-700"
                >
                    <div class="max-w-md space-y-3 space-x-1.5">
                        <template v-if="logs.meta.links">
                            <component
                                v-for="link in logs.meta.links"
                                :key="link.url ?? link.label"
                                :is="link.url ? Link : 'span'"
                                :href="link.url"
                                v-html="link.label"
                                preserve-scroll
                                :class="[
                                    'py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-neutral-400',
                                    link.active
                                        ? 'text-green-500'
                                        : 'text-gray-800 dark:text-neutral-400',
                                ]"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
