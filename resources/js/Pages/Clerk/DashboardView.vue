<script setup>
import Dashboard from "@/Layouts/Dashboard.vue";
import StatCard from "@/Components/Dashboard/StatCard.vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    stats: { type: Object, required: true },
    today_schedules: { type: Array, default: () => [] },
    recent_activities: { type: Array, default: () => [] },
    cluster_stats: { type: Array, default: () => [] },
});

defineOptions({
    layout: Dashboard,
});
</script>

<template>
    <div class="p-6 space-y-6">
        <!-- HEADER -->
        <div class="flex items-center justify-between">
            <div>
                <h1
                    class="text-3xl font-bold text-green-600 dark:text-green-400"
                >
                    Operational Dashboard
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Welcome back! Manage daily cemetery check-ins, bookings, and
                    lookups here.
                </p>
            </div>
            <div
                class="text-sm text-right font-medium text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-neutral-800 px-4 py-2 rounded-lg shadow-sm border border-gray-200 dark:border-neutral-700"
            >
                <svg
                    class="w-4 h-4 inline mr-1"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                    />
                </svg>
                Today is:
                <span class="text-green-500 dark:text-green-400 font-semibold">
                    {{
                        new Date().toLocaleDateString("en-US", {
                            month: "long",
                            day: "numeric",
                            year: "numeric",
                        })
                    }}
                </span>
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

        <!-- QUICK ACTIONS -->
        <section>
            <h2
                class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3"
            >
                Quick Actions
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <Link
                    :href="route('clerk.burial_records.create')"
                    class="flex items-center p-4 bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 hover:border-green-500 dark:hover:border-green-400 rounded-xl shadow-sm hover:shadow transition group text-left"
                >
                    <div
                        class="p-3 bg-gray-100 dark:bg-neutral-700 text-gray-600 dark:text-gray-400 rounded-lg group-hover:bg-green-500 group-hover:text-white transition mr-4"
                    >
                        <svg
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                    </div>
                    <div>
                        <p
                            class="font-semibold text-gray-900 dark:text-neutral-100"
                        >
                            New Burial Record
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Register a newly deceased profile
                        </p>
                    </div>
                </Link>
                <Link
                    :href="route('clerk.burial_records.index')"
                    class="flex items-center p-4 bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 hover:border-green-500 dark:hover:border-green-400 rounded-xl shadow-sm hover:shadow transition group text-left"
                >
                    <div
                        class="p-3 bg-gray-100 dark:bg-neutral-700 text-gray-600 dark:text-gray-400 rounded-lg group-hover:bg-green-600 group-hover:text-white transition mr-4"
                    >
                        <svg
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                    </div>
                    <div>
                        <p
                            class="font-semibold text-gray-900 dark:text-neutral-100"
                        >
                            View Burial Records
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Browse and manage burial records
                        </p>
                    </div>
                </Link>
                <Link
                    :href="route('clerk.lot_management.index')"
                    class="flex items-center p-4 bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 hover:border-green-500 dark:hover:border-green-400 rounded-xl shadow-sm hover:shadow transition group text-left"
                >
                    <div
                        class="p-3 bg-gray-100 dark:bg-neutral-700 text-gray-600 dark:text-gray-400 rounded-lg group-hover:bg-green-600 group-hover:text-white transition mr-4"
                    >
                        <svg
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                    </div>
                    <div>
                        <p
                            class="font-semibold text-gray-900 dark:text-neutral-100"
                        >
                            Find Lot/Grave
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Instantly locate a lot layout for families
                        </p>
                    </div>
                </Link>
            </div>
        </section>

        <!-- MAIN DASHBOARD GRID -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- LEFT COLUMN -->
            <div class="lg:col-span-2 space-y-6">
                <!-- TODAY'S FIELD SCHEDULE -->
                <div class="dashboard-card bg-gray-50 dark:bg-neutral-800">
                    <div
                        class="px-6 py-4 bg-gray-50 dark:bg-neutral-900/50 border-b border-gray-200 dark:border-neutral-700 flex justify-between items-center"
                    >
                        <h3
                            class="font-bold text-gray-800 dark:text-neutral-100 flex items-center"
                        >
                            <svg
                                class="w-5 h-5 mr-2"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                                />
                            </svg>
                            Today's Field Schedule
                        </h3>
                        <span
                            class="px-2.5 py-1 bg-gray-100 dark:bg-neutral-700 text-gray-800 dark:text-gray-300 text-xs font-semibold rounded-full"
                        >
                            {{ today_schedules.length }} Scheduled Today
                        </span>
                    </div>
                    <div
                        class="divide-y divide-gray-100 dark:divide-neutral-700"
                    >
                        <div
                            v-if="today_schedules.length === 0"
                            class="p-6 text-center text-gray-500 dark:text-gray-400"
                        >
                            No schedules for today
                        </div>
                        <div
                            v-for="schedule in today_schedules"
                            :key="schedule.id"
                            class="p-6 flex items-start justify-between hover:bg-gray-50 dark:hover:bg-neutral-900/30 transition"
                        >
                            <div class="flex items-start space-x-4">
                                <div
                                    class="text-center bg-gray-100 dark:bg-neutral-700 text-gray-700 dark:text-neutral-200 font-bold p-2.5 rounded-lg w-20 shadow-inner border border-gray-200 dark:border-neutral-600"
                                >
                                    <p
                                        class="text-xs tracking-wide uppercase font-semibold text-gray-500 dark:text-gray-400"
                                    >
                                        Time
                                    </p>
                                    <p class="text-sm">{{ schedule.time }}</p>
                                </div>
                                <div>
                                    <h4
                                        class="font-bold text-gray-900 dark:text-neutral-100"
                                    >
                                        {{ schedule.deceased_name }}
                                    </h4>
                                    <p
                                        class="text-sm text-gray-600 dark:text-gray-400"
                                    >
                                        Lot:
                                        <span
                                            class="font-mono bg-gray-100 dark:bg-neutral-700 px-1 py-0.5 rounded text-xs"
                                        >
                                            {{ schedule.lot_number }}
                                        </span>
                                    </p>
                                    <p
                                        class="text-xs text-gray-400 dark:text-gray-500 mt-0.5"
                                    >
                                        Contact: {{ schedule.contact_name }} ({{
                                            schedule.contact_relationship
                                        }}) • {{ schedule.contact_phone }}
                                    </p>
                                </div>
                            </div>
                            <span
                                class="px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-100 dark:bg-neutral-700 text-gray-600 dark:text-gray-300"
                            >
                                {{ schedule.status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="space-y-6">
                <!-- RECENT ACTIVITIES -->
                <div class="dashboard-card">
                    <h3
                        class="font-bold text-gray-800 dark:text-neutral-100 mb-4 flex items-center"
                    >
                        <svg
                            class="w-5 h-5 mr-2"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                        Your Recent Encoding Work
                    </h3>
                    <div class="space-y-4">
                        <div
                            v-if="recent_activities.length === 0"
                            class="text-sm text-gray-500 dark:text-gray-400"
                        >
                            No recent activities
                        </div>
                        <div
                            v-for="(activity, index) in recent_activities"
                            :key="index"
                            class="flex items-start text-sm"
                        >
                            <div
                                class="w-2 h-2 rounded-full mt-1.5 mr-3 flex-shrink-0 bg-green-500"
                            ></div>
                            <div>
                                <p
                                    class="text-gray-800 dark:text-neutral-200 font-medium"
                                >
                                    {{ activity.action }}
                                </p>
                                <span
                                    class="text-xs text-gray-400 dark:text-gray-500"
                                >
                                    {{ activity.time }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CLUSTER STATS -->
                <div class="dashboard-card">
                    <h3
                        class="font-bold text-gray-800 dark:text-neutral-100 mb-2 flex items-center"
                    >
                        <svg
                            class="w-5 h-5 mr-2"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"
                            />
                        </svg>
                        Fast Section Lookup
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                        Quickly check available space for immediate inquiries.
                    </p>
                    <div class="space-y-3">
                        <div
                            v-if="cluster_stats.length === 0"
                            class="text-sm text-gray-500 dark:text-gray-400"
                        >
                            No cluster data available
                        </div>
                        <div
                            v-for="cluster in cluster_stats"
                            :key="cluster.name"
                        >
                            <div
                                class="flex justify-between text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                            >
                                <span
                                    >{{ cluster.name }} ({{
                                        cluster.type
                                    }})</span
                                >
                                <span class="text-gray-600 dark:text-gray-400">
                                    {{ cluster.available_lots }} Available Lots
                                </span>
                            </div>
                            <div
                                class="w-full bg-gray-100 dark:bg-neutral-700 rounded-full h-2"
                            >
                                <div
                                    class="h-2 rounded-full bg-green-500"
                                    :style="{
                                        width: cluster.occupancy_rate + '%',
                                    }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
