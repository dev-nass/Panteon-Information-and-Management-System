<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { debounce } from "lodash";

import Dashboard from "@/Layouts/Dashboard.vue";
import StatCard from "@/Components/Dashboard/StatCard.vue";
import BarChart from "@/Components/Charts/BarChart.vue";
import DoughnutChart from "@/Components/Charts/DoughnutChart.vue";
import TableHeader from "@/Components/Table/TableHeader.vue";
import TableData from "@/Components/Table/TableData.vue";
import Input from "@/Components/Form/Input.vue";
import Modal from "@/Components/Modal.vue";

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

// --- Details Modal ---
const selectedLog = ref(null);
const DETAILS_MODAL_ID = "activity-details-modal";

const detailsModalEl = () => document.getElementById(DETAILS_MODAL_ID);

/**
 * Initialise the Preline overlay instance for this modal and re-bind every
 * [data-hs-overlay] toggle Vue rendered after Preline collected them, so the
 * close buttons always have a working click listener.
 */
const detailsOverlay = () => {
    const el = detailsModalEl();
    const overlay =
        typeof window !== "undefined" ? window.HSOverlay : undefined;
    if (!el || !overlay) return null;

    overlay.autoInit();
    overlay.getInstance(el, true)?.element?.updateToggles();

    return overlay;
};

const removeStrayBackdrops = () => {
    document
        .querySelectorAll(`#${DETAILS_MODAL_ID}-backdrop`)
        .forEach((node) => node.remove());
};

const openDetailsModal = (log) => {
    selectedLog.value = log;

    nextTick(() => {
        const el = detailsModalEl();
        if (!el) return;

        removeStrayBackdrops();

        const overlay = detailsOverlay();
        if (overlay) {
            // HSOverlay.open() no-ops while the modal is already visible, so a
            // second click can never open it twice (duplicate backdrops).
            overlay.open(el);
        } else {
            el.classList.remove("hidden");
            el.classList.add("open", "opened");
        }
    });
};

const closeDetailsModal = () => {
    const el = detailsModalEl();
    if (!el) return;

    const overlay = detailsOverlay();
    if (overlay) {
        overlay.close(el);
    } else {
        el.classList.remove("open", "opened");
        el.classList.add("hidden");
        el.removeAttribute("aria-overlay");
    }

    // Preline only clears the backdrop it tracked — drop anything it leaves.
    window.setTimeout(() => {
        if (el.classList.contains("open")) return;
        removeStrayBackdrops();
        if (!document.querySelector(".hs-overlay.opened")) {
            document.body.style.overflow = "";
        }
    }, 500);
};

/**
 * Drop every leftover Preline artefact (backdrop layer, scroll lock, stale
 * open classes) so the page can never end up covered by an orphaned overlay.
 */
const resetDetailsModal = () => {
    const el = detailsModalEl();
    if (el) {
        el.classList.remove("open", "opened");
        el.classList.add("hidden");
        el.removeAttribute("aria-overlay");
    }

    removeStrayBackdrops();

    if (!document.querySelector(".hs-overlay.opened")) {
        document.body.style.overflow = "";
    }
};

onMounted(resetDetailsModal);

// Inertia unmounts this page while the modal may still be open — its backdrop
// lives on <body> and would otherwise stay behind, blocking the next page.
onUnmounted(resetDetailsModal);

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
    { value: "generated", label: "Generated" },
    { value: "archived", label: "Archived" },
    { value: "restored", label: "Restored" },
    { value: "terminated", label: "Terminated" },
    { value: "reinstated", label: "Reinstated" },
];

const actionBadgeColors = {
    created:     "bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-400",
    updated:     "bg-blue-100 text-blue-800 dark:bg-blue-500/10 dark:text-blue-400",
    deleted:     "bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400",
    role_changed:"bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-400",
    imported:    "bg-purple-100 text-purple-800 dark:bg-purple-500/10 dark:text-purple-400",
    generated:   "bg-cyan-100 text-cyan-800 dark:bg-cyan-500/10 dark:text-cyan-400",
    archived:    "bg-orange-100 text-orange-800 dark:bg-orange-500/10 dark:text-orange-400",
    restored:    "bg-teal-100 text-teal-800 dark:bg-teal-500/10 dark:text-teal-400",
    terminated:  "bg-rose-100 text-rose-800 dark:bg-rose-500/10 dark:text-rose-400",
    reinstated:  "bg-indigo-100 text-indigo-800 dark:bg-indigo-500/10 dark:text-indigo-400",
};

const actionChartColors = {
    created:     "rgba(34,197,94,0.8)",
    updated:     "rgba(59,130,246,0.8)",
    deleted:     "rgba(239,68,68,0.8)",
    role_changed:"rgba(245,158,11,0.8)",
    imported:    "rgba(168,85,247,0.8)",
    generated:   "rgba(6,182,212,0.8)",
    archived:    "rgba(249,115,22,0.8)",
    restored:    "rgba(20,184,166,0.8)",
    terminated:  "rgba(244,63,94,0.8)",
    reinstated:  "rgba(99,102,241,0.8)",
};

const actionLabels = {
    created:     "Created",
    updated:     "Updated",
    deleted:     "Deleted",
    role_changed:"Role Changed",
    imported:    "Imported",
    generated:   "Generated",
    archived:    "Archived",
    restored:    "Restored",
    terminated:  "Terminated",
    reinstated:  "Reinstated",
};

const fieldLabels = {
    first_name:         "First Name",
    middle_name:        "Middle Name",
    last_name:          "Last Name",
    address:            "Address",
    date_of_birth:      "Date of Birth",
    date_of_death:      "Date of Death",
    date_of_depository: "Date of Depository",
    phase_name:         "Phase Name",
    cluster_name:       "Cluster Name",
    cluster_type:       "Cluster Type",
    total_capacity:     "Total Capacity",
    column:             "Lot Number",
    row:                "Lot Letter",
    contact_number:     "Contact Number",
    role:               "Role",
    deceased_name:      "Deceased Name",
    date_of_depository: "Date of Depository",
    plot:               "Plot",
    applicant:          "Applicant",
    relationship:       "Relationship",
    email:              "Email",
    expires_at:         "Expires At",
    file_name:          "File Name",
    import_type:        "Import Type",
    status:             "Status",
    imported:           "Imported",
    skipped:            "Skipped",
    template:           "Template",
    generated_at:       "Generated At",
    filename:           "Filename",
    size:               "File Size",
    disk:               "Storage Disk",
    lot_count:          "Lots Created",
    type:               "Type",
    capacity:           "Capacity",
    name:               "Name",
};

function humanLabel(key) {
    return fieldLabels[key] ?? key.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());
}

function formatBytes(bytes) {
    if (!bytes) return "—";
    if (bytes < 1024) return bytes + " B";
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " KB";
    return (bytes / (1024 * 1024)).toFixed(1) + " MB";
}

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
        x: { ticks: { font: { size: 14 } } },
        y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } },
    },
};

const actionBreakdownData = computed(() => {
    const breakdown = props.chart_data?.action_breakdown ?? [];
    return {
        labels: breakdown.map((item) => actionLabels[item.action] ?? item.action),
        datasets: [
            {
                data: breakdown.map((item) => item.count),
                backgroundColor: breakdown.map(
                    (item) => actionChartColors[item.action] ?? "rgba(156,163,175,0.7)",
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
    plugins: { legend: { position: "bottom" } },
};

const perUserView = ref("table");
const perUserData = computed(() => props.per_user ?? []);

const perUserBarData = computed(() => ({
    labels: perUserData.value.map((item) => `${item.user?.first_name} ${item.user?.last_name}`),
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
}));

const perUserBarOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        x: { ticks: { maxRotation: 45, minRotation: 0, font: { size: 14 } } },
        y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } },
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

function diffKeys(properties) {
    if (!properties) return [];
    const oldKeys = Object.keys(properties.old ?? {});
    const newKeys = Object.keys(properties.new ?? {});
    return Array.from(new Set([...oldKeys, ...newKeys]));
}

function getReason(properties) {
    if (!properties) return null;
    return properties.reason ?? properties.new?.terminated_reason ?? properties.new?.reinstated_reason ?? properties.new?.reason ?? null;
}

function getNotes(properties) {
    if (!properties) return null;
    return properties.notes ?? properties.new?.notes ?? null;
}

const applyFilters = () => {
    router.get(
        route("admin.activity_log.index"),
        { range: range.value, action: action.value, user_search: userSearch.value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const changeRange = (value) => { range.value = value; applyFilters(); };
const changeAction = () => applyFilters();

watch(userSearch, debounce(() => applyFilters(), 500));

defineOptions({ layout: Dashboard });
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
                                    <button
                                        type="button"
                                        @click="openDetailsModal(log)"
                                        class="whitespace-nowrap rounded-lg bg-green-500/10 px-3 py-1.5 text-xs font-semibold text-green-600 transition hover:bg-green-500/20 dark:bg-green-500/10 dark:text-green-400 dark:hover:bg-green-500/20"
                                    >
                                        View Details
                                    </button>
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

        <!-- ACTIVITY DETAILS MODAL -->
        <Modal id="activity-details-modal" size="lg">
            <template #header>
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="size-6"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                </svg>
            </template>

            <template #main>
                <h3
                    id="activity-details-modal-label"
                    class="-mt-2 text-2xl font-bold text-green-600 dark:text-green-400"
                >
                    Activity Details
                </h3>

                <div
                    v-if="selectedLog"
                    class="w-full text-left space-y-4 max-h-[60vh] overflow-y-auto pr-1"
                >
                    <!-- Header Info Box (Action, Date, Actor) -->
                    <div
                        class="space-y-2 pb-3 border-b border-gray-200 dark:border-neutral-700"
                    >
                        <div
                            class="flex items-center justify-between flex-wrap gap-2"
                        >
                            <div class="flex items-center gap-2">
                                <span
                                    class="text-sm font-medium text-gray-700 dark:text-neutral-300"
                                    >Action:
                                </span>
                                <span
                                    class="inline-flex items-center py-1 px-3 rounded-full text-xs font-medium"
                                    :class="actionBadge(selectedLog.action)"
                                >
                                    {{
                                        actionLabels[selectedLog.action] ??
                                        selectedLog.action
                                    }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-neutral-400">
                                {{ formatTimestamp(selectedLog.created_at) }}
                            </p>
                        </div>

                        <!-- Below the Action: Actor -->
                        <div class="text-sm">
                            <span
                                class="font-medium text-gray-700 dark:text-neutral-300"
                                >Actor:
                            </span>
                            <Link
                                v-if="selectedLog.user"
                                :href="
                                    route(
                                        'admin.user_management.show',
                                        selectedLog.user.id,
                                    )
                                "
                                class="text-green-600 dark:text-green-400 hover:underline font-medium"
                            >
                                {{ selectedLog.user.first_name }}
                                {{ selectedLog.user.last_name }}
                            </Link>
                            <span
                                v-else
                                class="text-gray-500 dark:text-neutral-400"
                                >System</span
                            >
                        </div>
                    </div>

                    <!-- Subject Banner -->
                    <div
                        v-if="selectedLog.subject_id"
                        class="flex items-center justify-between gap-3 rounded-xl px-4 py-3 text-sm"
                        :class="
                            selectedLog.subject_exists
                                ? 'bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/20'
                                : 'bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20'
                        "
                    >
                        <span
                            :class="
                                selectedLog.subject_exists
                                ? 'text-green-700 dark:text-green-400'
                                : 'text-red-600 dark:text-red-400'
                            "
                            class="font-medium"
                        >
                            {{ selectedLog.subject_title }} #{{
                                selectedLog.subject_id
                            }}
                            <span
                                v-if="!selectedLog.subject_exists"
                                class="ml-2 text-xs font-semibold"
                                >(Record Deleted)</span
                            >
                        </span>
                        <Link
                            v-if="
                                selectedLog.subject_exists &&
                                selectedLog.subject_url
                            "
                            :href="selectedLog.subject_url"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-green-600 dark:text-green-400 hover:underline whitespace-nowrap"
                        >
                            Open Record
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="size-3.5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M15 3h6v6" />
                                <path d="M10 14 21 3" />
                                <path
                                    d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"
                                />
                            </svg>
                        </Link>
                    </div>

                    <!-- Description -->
                    <p class="text-sm text-gray-700 dark:text-neutral-300">
                        {{ selectedLog.description }}
                    </p>

                    <!-- Context-Aware Body -->

                    <!-- Updated / Role Changed: diff table -->
                    <div
                        v-if="
                            ['updated', 'role_changed'].includes(
                                selectedLog.action,
                            ) && diffKeys(selectedLog.properties).length > 0
                        "
                        class="space-y-2"
                    >
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-neutral-400"
                        >
                            Changes
                        </p>
                        <div
                            class="overflow-x-auto rounded-xl border border-gray-200 dark:border-neutral-700"
                        >
                            <table
                                class="min-w-full text-sm divide-y divide-gray-200 dark:divide-neutral-700"
                            >
                                <thead class="bg-gray-50 dark:bg-neutral-800">
                                    <tr>
                                        <th
                                            class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-neutral-400"
                                        >
                                            Field
                                        </th>
                                        <th
                                            class="px-4 py-2.5 text-left text-xs font-medium text-red-500"
                                        >
                                            Previous
                                        </th>
                                        <th
                                            class="px-4 py-2.5 text-left text-xs font-medium text-green-500"
                                        >
                                            New
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-gray-100 dark:divide-neutral-700"
                                >
                                    <tr
                                        v-for="key in diffKeys(
                                            selectedLog.properties,
                                        )"
                                        :key="key"
                                        class="bg-white dark:bg-neutral-900"
                                    >
                                        <td
                                            class="px-4 py-2 font-medium text-gray-700 dark:text-neutral-300"
                                        >
                                            {{ humanLabel(key) }}
                                        </td>
                                        <td
                                            class="px-4 py-2 text-red-600 dark:text-red-400 font-mono text-xs"
                                        >
                                            {{
                                                selectedLog.properties.old?.[
                                                    key
                                                ] ?? "—"
                                            }}
                                        </td>
                                        <td
                                            class="px-4 py-2 text-green-600 dark:text-green-400 font-mono text-xs"
                                        >
                                            {{
                                                selectedLog.properties.new?.[
                                                    key
                                                ] ?? "—"
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Created / Deleted / Restored: snapshot grid -->
                    <div
                        v-else-if="
                            ['created', 'deleted', 'restored'].includes(
                                selectedLog.action,
                            ) &&
                            (selectedLog.properties?.snapshot ||
                                selectedLog.properties?.new)
                        "
                        class="space-y-2"
                    >
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-neutral-400"
                        >
                            {{
                                selectedLog.action === "deleted"
                                    ? "Record Snapshot (Before Deletion)"
                                    : "Record Snapshot"
                            }}
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div
                                v-for="[key, value] in Object.entries(
                                    selectedLog.properties.snapshot ??
                                        selectedLog.properties.new,
                                )"
                                :key="key"
                                class="rounded-lg bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 px-3 py-2"
                            >
                                <p
                                    class="text-xs text-gray-500 dark:text-neutral-400"
                                >
                                    {{ humanLabel(key) }}
                                </p>
                                <p
                                    class="text-sm font-medium text-gray-800 dark:text-neutral-200 break-words"
                                >
                                    {{
                                        key === "size"
                                            ? formatBytes(value)
                                            : (value ?? "—")
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Terminated / Reinstated / Archived: reason + notes -->
                    <div
                        v-else-if="
                            ['terminated', 'reinstated', 'archived'].includes(
                                selectedLog.action,
                            ) &&
                            (getReason(selectedLog.properties) ||
                                getNotes(selectedLog.properties))
                        "
                        class="space-y-3"
                    >
                        <div
                            v-if="getReason(selectedLog.properties)"
                            class="rounded-xl bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 px-4 py-3"
                        >
                            <p
                                class="text-xs text-gray-500 dark:text-neutral-400 mb-1"
                            >
                                Reason
                            </p>
                            <p
                                class="text-sm font-medium text-gray-800 dark:text-neutral-200"
                            >
                                {{ getReason(selectedLog.properties) }}
                            </p>
                        </div>
                        <div
                            v-if="getNotes(selectedLog.properties)"
                            class="rounded-xl bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 px-4 py-3"
                        >
                            <p
                                class="text-xs text-gray-500 dark:text-neutral-400 mb-1"
                            >
                                Notes
                            </p>
                            <p
                                class="text-sm text-gray-800 dark:text-neutral-200 whitespace-pre-line"
                            >
                                {{ getNotes(selectedLog.properties) }}
                            </p>
                        </div>
                    </div>

                    <!-- Imported: summary stats -->
                    <div
                        v-else-if="
                            selectedLog.action === 'imported' &&
                            (selectedLog.properties?.snapshot ||
                                selectedLog.properties?.new)
                        "
                        class="space-y-3"
                    >
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-neutral-400"
                        >
                            Import Summary
                        </p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <div
                                v-for="[key, value] in Object.entries(
                                    selectedLog.properties.snapshot ??
                                        selectedLog.properties.new,
                                )"
                                :key="key"
                                class="rounded-lg px-3 py-2 border"
                                :class="
                                    key === 'skipped' && value > 0
                                        ? 'bg-amber-50 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/20'
                                        : 'bg-gray-50 dark:bg-neutral-800 border-gray-200 dark:border-neutral-700'
                                "
                            >
                                <p
                                    class="text-xs text-gray-500 dark:text-neutral-400"
                                >
                                    {{ humanLabel(key) }}
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-800 dark:text-neutral-200"
                                >
                                    {{
                                        key === "size"
                                            ? formatBytes(value)
                                            : (value ?? "—")
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Generated: certificate details -->
                    <div
                        v-else-if="
                            selectedLog.action === 'generated' &&
                            (selectedLog.properties?.snapshot ||
                                selectedLog.properties?.new)
                        "
                        class="space-y-2"
                    >
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-neutral-400"
                        >
                            Certificate Details
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div
                                v-for="[key, value] in Object.entries(
                                    selectedLog.properties.snapshot ??
                                        selectedLog.properties.new,
                                )"
                                :key="key"
                                class="rounded-lg bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 px-3 py-2"
                            >
                                <p
                                    class="text-xs text-gray-500 dark:text-neutral-400"
                                >
                                    {{ humanLabel(key) }}
                                </p>
                                <p
                                    class="text-sm font-medium text-gray-800 dark:text-neutral-200 break-words"
                                >
                                    {{ value ?? "—" }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Fallback: raw key-value -->
                    <div
                        v-else-if="
                            selectedLog.properties &&
                            Object.keys(selectedLog.properties).length > 0
                        "
                        class="space-y-2"
                    >
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-neutral-400"
                        >
                            Properties
                        </p>
                        <div
                            class="rounded-xl bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 px-4 py-3 space-y-2"
                        >
                            <div
                                v-for="[key, value] in Object.entries(
                                    selectedLog.properties,
                                )"
                                :key="key"
                                class="flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-3 text-sm"
                            >
                                <span
                                    class="font-medium text-gray-700 dark:text-neutral-300 min-w-32"
                                    >{{ humanLabel(key) }}:</span
                                >
                                <span
                                    class="text-gray-600 dark:text-neutral-400 break-all"
                                    >{{
                                        typeof value === "object"
                                            ? JSON.stringify(value, null, 2)
                                            : (value ?? "—")
                                    }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- No properties -->
                    <p
                        v-else
                        class="text-sm text-gray-400 dark:text-neutral-500 italic"
                    >
                        No additional details recorded for this action.
                    </p>
                </div>

                <div v-else class="text-center py-8">
                    <p class="text-gray-500 dark:text-neutral-400">
                        No activity selected
                    </p>
                </div>
            </template>

            <template #footer>
                <button
                    type="button"
                    class="w-full py-3 text-sm font-semibold text-green-600 dark:text-green-400 hover:bg-green-500/10 transition"
                    @click="closeDetailsModal"
                >
                    Close
                </button>
            </template>
        </Modal>
    </div>
</template>
