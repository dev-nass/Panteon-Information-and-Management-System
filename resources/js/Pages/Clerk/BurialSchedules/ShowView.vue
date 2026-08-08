<script setup>
import { computed, ref, watch } from "vue";
import { debounce } from "lodash";
import { Link, router } from "@inertiajs/vue3";

import Input from "@/Components/Form/Input.vue";
import TableData from "@/Components/Table/TableData.vue";
import TableHeader from "@/Components/Table/TableHeader.vue";
import Dashboard from "@/Layouts/Dashboard.vue";

const props = defineProps({
    burials: { type: Object, required: true },
    date: { type: String, required: true },
    filters: { type: Object, required: true },
});

const search = ref(props.filters.search || "");

watch(
    search,
    debounce(function (value) {
        router.get(
            route("clerk.burial_schedules.date", { date: props.date }),
            { search: value },
            {
                preserveState: true,
                replace: true,
            },
        );
    }, 500),
);

const formattedDate = computed(() => {
    return new Date(props.date + "T00:00:00").toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
});

const totalBurials = computed(() => props.burials?.total ?? 0);

const back = () => {
    router.visit(route("clerk.burial_schedules.index"));
};

const viewBurial = (burial) => {
    router.visit(route("clerk.burial_records.show", burial.id));
};

const isAssigned = (burial) => burial.lot !== "Unassigned";

defineOptions({
    layout: Dashboard,
});
</script>

<template>
    <div class="max-w-6xl mx-auto p-6">
        <!-- Back button -->
        <button
            @click="back"
            class="flex items-center gap-1 mb-6 text-sm text-green-600 dark:text-green-400 hover:underline"
        >
            ← Back to Calendar
        </button>

        <!-- Header -->
        <div class="mb-6 flex gap-x-3">
            <div
                class="flex items-center justify-center size-13 rounded-full bg-green-500/10 text-green-600 dark:text-green-400"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="22"
                    height="22"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                    <line x1="16" y1="2" x2="16" y2="6" />
                    <line x1="8" y1="2" x2="8" y2="6" />
                    <line x1="3" y1="10" x2="21" y2="10" />
                </svg>
            </div>
            <article>
                <h1
                    class="text-2xl font-bold text-green-600 dark:text-green-400"
                >
                    {{ formattedDate }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Scheduled Burials
                </p>
            </article>
        </div>

        <!-- Summary -->
        <div class="mb-4 text-sm text-gray-500 dark:text-gray-400">
            {{ totalBurials }} burial(s) scheduled for this date
        </div>

        <!-- Table -->
        <div
            class="bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl shadow-md overflow-hidden"
        >
            <!-- Header -->
            <div
                class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-b border-gray-200 dark:border-neutral-700"
            >
                <Input
                    class="max-w-md"
                    placeholder="Search full name"
                    v-model="search"
                />
                <template v-if="search">
                    <span
                        class="text-sm text-gray-500 dark:text-gray-400 md:ml-auto"
                    >
                        {{ totalBurials }} result(s)
                    </span>
                </template>
            </div>
            <!-- End Header -->

            <div class="overflow-x-auto">
                <table
                    v-if="burials.data.length > 0"
                    class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700"
                >
                    <thead class="bg-gray-50 dark:bg-neutral-800">
                        <tr>
                            <TableHeader>ID</TableHeader>
                            <TableHeader>Full Name</TableHeader>
                            <TableHeader>Lot</TableHeader>
                            <TableHeader>Time</TableHeader>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-gray-200 dark:divide-neutral-700"
                    >
                        <tr
                            v-for="burial in burials.data"
                            :key="burial.id"
                            @click="viewBurial(burial)"
                            class="cursor-pointer bg-white dark:bg-neutral-800 hover:bg-gray-50 dark:hover:bg-neutral-700"
                        >
                            <TableData>{{ burial.id }}</TableData>
                            <TableData>{{ burial.full_name }}</TableData>
                            <TableData>
                                <span
                                    v-if="isAssigned(burial)"
                                    class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-500"
                                >
                                    <svg
                                        class="size-2.5"
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="16"
                                        height="16"
                                        fill="currentColor"
                                        viewBox="0 0 16 16"
                                    >
                                        <path
                                            d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"
                                        />
                                    </svg>
                                    {{ burial.lot }}
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-500"
                                >
                                    <svg
                                        class="size-2.5"
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="16"
                                        height="16"
                                        fill="currentColor"
                                        viewBox="0 0 16 16"
                                    >
                                        <path
                                            d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"
                                        />
                                    </svg>
                                    Unassigned
                                </span>
                            </TableData>
                            <TableData>{{ burial.time }}</TableData>
                        </tr>
                    </tbody>
                </table>

                <!-- Empty state -->
                <div
                    v-else
                    class="text-center py-12 text-gray-500 dark:text-neutral-400"
                >
                    No burials scheduled for this date.
                </div>
            </div>

            <!-- Footer / Pagination -->
            <div
                v-if="burials.links?.length > 3"
                class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200 dark:border-neutral-700"
            >
                <div class="max-w-md space-y-3 space-x-1.5">
                    <component
                        v-for="link in burials.links"
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
                </div>
            </div>
            <!-- End Footer -->
        </div>
    </div>
</template>
