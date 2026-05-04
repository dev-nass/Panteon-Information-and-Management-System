<script setup>
import { useSearchBurialRecords } from "@/composables/burial_records/useSearchBurialRecords";

import { Link, router } from "@inertiajs/vue3";
import Input from "@/Components/Form/Input.vue";
import Button from "@/Components/Form/Button.vue";
import Dashboard from "@/Layouts/Dashboard.vue";
import TableHeader from "@/Components/Table/TableHeader.vue";
import TableData from "@/Components/Table/TableData.vue";
import { onMounted } from "vue";

// NOTE:(out for now since we ended up separating the MAP and TABLE)
// const emit = defineEmits(["toggleTable"]);
//
// const toggleTableEvent = () => {
//     emit("toggleTable");
// };

const props = defineProps({
    burial_records: {
        type: Object,
    },
    filters: Object,
});

// TODO: Remove this
console.log(props.burial_records);

const { search } = useSearchBurialRecords("clerk.burial_records.index");

const applyFilter = (filterValue) => {
    router.get(
        route("clerk.burial_records.index"),
        {
            search: props.filters.search,
            filter: filterValue,
            sort_field: props.filters.sort_field,
            sort_direction: props.filters.sort_direction,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

/**
 * Description:
 * @param {field}
 */
const sort = (field) => {
    let direction = "asc";

    if (
        props.filters.sort_field === field &&
        props.filters.sort_direction === "asc"
    ) {
        direction = "desc";
    }

    router.get(
        route("clerk.burial_records.index"),
        {
            search: props.filters.search,
            filter: props.filters.filter,
            sort_field: field,
            sort_direction: direction,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

window.addEventListener("load", () => {
    setTimeout(() => {
        document
            .querySelectorAll(".hs-overlay")
            .forEach((el) => HSOverlay.open(el));
    });
});

onMounted(() => {
    router.reload({ only: ["burial_records"] });
});

defineOptions({
    layout: Dashboard,
});
</script>

<template>
    <!-- Table Section -->
    <div class="max-w-340 px-4 py-10 sm:px-6 lg:px-8 lg:py-6 mx-auto">
        <!-- Card -->
        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    <div
                        class="bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl shadow-2xs overflow-hidden"
                    >
                        <!-- Header -->
                        <div
                            class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-b border-gray-200 dark:border-neutral-700"
                        >
                            <!-- Input -->
                            <Input placeholder="Full Name" v-model="search" />
                            <!-- End Input -->

                            <div class="sm:col-span-2 md:grow">
                                <div class="flex justify-end gap-x-2">
                                    <div
                                        class="hs-dropdown [--placement:bottom-right] relative inline-block"
                                    >
                                        <Link
                                            :href="
                                                route(
                                                    'clerk.burial_records.create',
                                                )
                                            "
                                            class="flex items-center gap-2 px-3 py-2.5 text-base w-full max-w-md rounded-lg border transition bg-white dark:bg-neutral-900 border-gray-300 dark:border-neutral-700 dark:text-white focus-within:border-green-500 focus-within:ring-2 focus-within:ring-green-500 focus:text-green-400"
                                        >
                                            Create

                                            <span
                                                class="ps-2 text-xs font-semibold text-green-600 dark:text-green-500 border-s border-gray-200 dark:border-neutral-700"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    width="16"
                                                    height="16"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    class="lucide lucide-plus-icon lucide-plus"
                                                >
                                                    <path d="M5 12h14" />
                                                    <path d="M12 5v14" />
                                                </svg>
                                            </span>
                                        </Link>
                                    </div>

                                    <div
                                        class="hs-dropdown [--placement:bottom-right] relative inline-block"
                                        data-hs-dropdown-auto-close="inside"
                                    >
                                        <Button
                                            id="hs-as-table-table-filter-dropdown"
                                            type="button"
                                            aria-haspopup="menu"
                                            aria-expanded="false"
                                            aria-label="Dropdown"
                                        >
                                            <span class="dark:text-white">
                                                Filter
                                            </span>

                                            <span
                                                class="ps-2 text-xs font-semibold text-green-600 dark:text-green-500 border-s border-gray-200 dark:border-neutral-700"
                                            >
                                                {{
                                                    filters.filter === "buried"
                                                        ? "Buried"
                                                        : filters.filter ===
                                                            "pending"
                                                          ? "Pending"
                                                          : "All"
                                                }}
                                            </span>
                                        </Button>

                                        <div
                                            class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-48 z-10 bg-white dark:bg-neutral-900 border border-transparent divide-y divide-gray-200 dark:divide-neutral-800 shadow-md rounded-lg mt-2"
                                            role="menu"
                                            aria-orientation="vertical"
                                            aria-labelledby="hs-as-table-table-filter-dropdown"
                                        >
                                            <div
                                                class="divide-y divide-gray-200 dark:divide-neutral-800"
                                            >
                                                <label
                                                    for="filter-all"
                                                    class="flex items-center py-2.5 px-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-neutral-800"
                                                >
                                                    <input
                                                        type="radio"
                                                        name="filter"
                                                        value="all"
                                                        class="shrink-0 size-4 bg-transparent border-gray-300 dark:border-neutral-600 rounded-full shadow-2xs text-green-600 dark:text-green-500 focus:ring-0 focus:ring-offset-0 checked:bg-green-600 dark:checked:bg-green-500 checked:border-green-600 dark:checked:border-green-500"
                                                        id="filter-all"
                                                        :checked="
                                                            !filters.filter ||
                                                            filters.filter ===
                                                                'all'
                                                        "
                                                        @change="
                                                            applyFilter('all')
                                                        "
                                                    />
                                                    <span
                                                        class="ms-3 text-sm text-gray-800 dark:text-neutral-200"
                                                        >All</span
                                                    >
                                                </label>

                                                <label
                                                    for="filter-buried"
                                                    class="flex items-center py-2.5 px-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-neutral-800"
                                                >
                                                    <input
                                                        type="radio"
                                                        name="filter"
                                                        value="buried"
                                                        class="shrink-0 size-4 bg-transparent border-gray-300 dark:border-neutral-600 rounded-full shadow-2xs text-green-600 dark:text-green-500 focus:ring-0 focus:ring-offset-0 checked:bg-green-600 dark:checked:bg-green-500 checked:border-green-600 dark:checked:border-green-500"
                                                        id="filter-buried"
                                                        :checked="
                                                            filters.filter ===
                                                            'buried'
                                                        "
                                                        @change="
                                                            applyFilter(
                                                                'buried',
                                                            )
                                                        "
                                                    />
                                                    <span
                                                        class="ms-3 text-sm text-gray-800 dark:text-neutral-200"
                                                        >Buried</span
                                                    >
                                                </label>

                                                <label
                                                    for="filter-pending"
                                                    class="flex items-center py-2.5 px-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-neutral-800"
                                                >
                                                    <input
                                                        type="radio"
                                                        name="filter"
                                                        value="pending"
                                                        class="shrink-0 size-4 bg-transparent border-gray-300 dark:border-neutral-600 rounded-full shadow-2xs text-green-600 dark:text-green-500 focus:ring-0 focus:ring-offset-0 checked:bg-green-600 dark:checked:bg-green-500 checked:border-green-600 dark:checked:border-green-500"
                                                        id="filter-pending"
                                                        :checked="
                                                            filters.filter ===
                                                            'pending'
                                                        "
                                                        @change="
                                                            applyFilter(
                                                                'pending',
                                                            )
                                                        "
                                                    />
                                                    <span
                                                        class="ms-3 text-sm text-gray-800 dark:text-neutral-200"
                                                        >Pending</span
                                                    >
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!--- NOTE: Toggle table button --->
                                    <div>
                                        <Link
                                            :href="route('clerk.map.index')"
                                            class="flex items-center gap-2 px-3 py-2.5 text-base w-full max-w-md rounded-lg border transition bg-white dark:bg-neutral-900 border-gray-300 dark:border-neutral-700 dark:text-white focus-within:border-green-500 focus-within:ring-2 focus-within:ring-green-500 focus:text-green-400"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="18"
                                                height="18"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                class="lucide lucide-arrow-right-left-icon lucide-arrow-right-left text-green-500 dark:text-green-600"
                                            >
                                                <path d="m16 3 4 4-4 4" />
                                                <path d="M20 7H4" />
                                                <path d="m8 21-4-4 4-4" />
                                                <path d="M4 17h16" />
                                            </svg>
                                            Toggle
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End Header -->

                        <!-- Table -->
                        <table
                            class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700"
                        >
                            <thead class="bg-gray-50 dark:bg-neutral-800">
                                <tr>
                                    <TableHeader @sort="sort('id')">
                                        ID
                                    </TableHeader>
                                    <TableHeader
                                        @click="sort('deceased_first_name')"
                                    >
                                        Full Name
                                    </TableHeader>
                                    <TableHeader
                                        @click="sort('deceased_date_of_birth')"
                                    >
                                        Birth Date
                                    </TableHeader>
                                    <TableHeader
                                        @click="sort('deceased_date_of_death')"
                                    >
                                        Death Date
                                    </TableHeader>
                                    <TableHeader
                                        @click="
                                            sort('deceased_date_of_depository')
                                        "
                                    >
                                        Burial Date
                                    </TableHeader>
                                    <TableHeader> Lot Status </TableHeader>
                                </tr>
                            </thead>

                            <tbody
                                class="divide-y divide-gray-200 dark:divide-neutral-700"
                            >
                                <tr
                                    v-if="burial_records.data.length > 0"
                                    v-for="record in burial_records.data"
                                    :key="record.id"
                                    class="bg-white dark:bg-neutral-800 hover:bg-gray-50 dark:hover:bg-neutral-700 cursor-pointer"
                                    @click="
                                        () =>
                                            $inertia.visit(
                                                route(
                                                    'clerk.burial_records.show',
                                                    record.burial.id,
                                                ),
                                            )
                                    "
                                >
                                    <TableData>{{
                                        record.burial.id
                                    }}</TableData>
                                    <TableData>
                                        {{ record.deceased.first_name }}
                                        {{ record.deceased.last_name }}
                                    </TableData>
                                    <TableData>
                                        {{
                                            record.deceased.birth.date ?? "N/A"
                                        }}
                                    </TableData>
                                    <TableData>
                                        {{
                                            record.deceased.death.date ?? "N/A"
                                        }}
                                    </TableData>

                                    <TableData>
                                        {{
                                            record.deceased.burial.date ?? "N/A"
                                        }}
                                    </TableData>

                                    <TableData>
                                        <span
                                            v-if="
                                                record.lot?.lot?.properties
                                                    ?.lot_id
                                            "
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
                                            Assigned
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
                                </tr>

                                <tr v-else>
                                    <td
                                        colspan="6"
                                        class="px-6 py-8 text-center"
                                    >
                                        <span
                                            class="text-sm text-gray-500 dark:text-neutral-400"
                                            >No data found</span
                                        >
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <!-- End Table -->
                    </div>

                    <!-- Footer / Pagination -->
                    <div
                        class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200 dark:border-neutral-700"
                    >
                        <div class="max-w-md space-y-3 space-x-1.5">
                            <template v-if="burial_records?.meta?.links">
                                <component
                                    v-for="link in burial_records.meta.links"
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
                    <!-- End Footer -->
                </div>
            </div>
        </div>
        <!-- End Card -->
    </div>
    <!-- End Table Section -->
</template>
