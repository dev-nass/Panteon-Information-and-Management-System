<script setup>
import { useSearchBurialRecords } from "@/composables/useSearchBurialRecords";

import { Link, router } from "@inertiajs/vue3";
import Input from "@/Components/Form/Input.vue";
import Button from "@/Components/Form/Button.vue";
import Dashboard from "@/Layouts/Dashboard.vue";
import TableHeader from "@/Components/Table/TableHeader.vue";
import TableData from "@/Components/Table/TableData.vue";

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
// console.log(props.burial_records);

const { search } = useSearchBurialRecords("clerk.burial_records.index");

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
            sort_field: field,
            sort_direction: direction,
        },
        {
            preserveState: true,
            replace: true,
        }
    );
};

window.addEventListener("load", () => {
    setTimeout(() => {
        document
            .querySelectorAll(".hs-overlay")
            .forEach((el) => HSOverlay.open(el));
    });
});

defineOptions({
    layout: Dashboard,
});
</script>

<template>
    <!-- Table Section -->
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-6 mx-auto">
        <!-- Card -->
        <div class="flex flex-col" data-aos="zoom-out">
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
                                        <Button
                                            id="hs-as-table-table-export-dropdown"
                                            aria-haspopup="menu"
                                            aria-expanded="false"
                                            aria-label="Dropdown"
                                        >
                                            <svg
                                                class="shrink-0 size-3.5"
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="24"
                                                height="24"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            >
                                                <path
                                                    d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"
                                                />
                                                <polyline
                                                    points="7 10 12 15 17 10"
                                                />
                                                <line
                                                    x1="12"
                                                    x2="12"
                                                    y1="15"
                                                    y2="3"
                                                />
                                            </svg>
                                            Import
                                        </Button>
                                        <div
                                            class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-48 z-10 bg-white dark:bg-neutral-900 border border-transparent divide-y divide-gray-200 dark:divide-neutral-800 shadow-md rounded-lg p-2 mt-2"
                                            role="menu"
                                            aria-orientation="vertical"
                                            aria-labelledby="hs-as-table-table-export-dropdown"
                                        >
                                            <div
                                                class="py-2 first:pt-0 last:pb-0"
                                            >
                                                <span
                                                    class="block py-2 px-3 text-xs font-medium uppercase text-gray-400 dark:text-neutral-500"
                                                >
                                                    Options
                                                </span>
                                                <a
                                                    class="flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-gray-800 dark:text-neutral-200 hover:bg-gray-100 dark:hover:bg-neutral-800 focus:outline-hidden focus:bg-gray-100 dark:focus:bg-neutral-800"
                                                    href="#"
                                                >
                                                    <svg
                                                        class="shrink-0 size-4"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        width="24"
                                                        height="24"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                    >
                                                        <rect
                                                            width="8"
                                                            height="4"
                                                            x="8"
                                                            y="2"
                                                            rx="1"
                                                            ry="1"
                                                        />
                                                        <path
                                                            d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"
                                                        />
                                                    </svg>
                                                    Copy
                                                </a>
                                                <a
                                                    class="flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-gray-800 dark:text-neutral-200 hover:bg-gray-100 dark:hover:bg-neutral-800 focus:outline-hidden focus:bg-gray-100 dark:focus:bg-neutral-800"
                                                    href="#"
                                                >
                                                    <svg
                                                        class="shrink-0 size-4"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        width="24"
                                                        height="24"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                    >
                                                        <polyline
                                                            points="6 9 6 2 18 2 18 9"
                                                        />
                                                        <path
                                                            d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"
                                                        />
                                                        <rect
                                                            width="12"
                                                            height="8"
                                                            x="6"
                                                            y="14"
                                                        />
                                                    </svg>
                                                    Print
                                                </a>
                                            </div>
                                            <div
                                                class="py-2 first:pt-0 last:pb-0"
                                            >
                                                <span
                                                    class="block py-2 px-3 text-xs font-medium uppercase text-gray-400 dark:text-neutral-500"
                                                >
                                                    Download options
                                                </span>
                                                <a
                                                    class="flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-gray-800 dark:text-neutral-200 hover:bg-gray-100 dark:hover:bg-neutral-800 focus:outline-hidden focus:bg-gray-100 dark:focus:bg-neutral-800"
                                                    href="#"
                                                >
                                                    <svg
                                                        class="shrink-0 size-4"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        width="16"
                                                        height="16"
                                                        fill="currentColor"
                                                        viewBox="0 0 16 16"
                                                    >
                                                        <path
                                                            d="M5.884 6.68a.5.5 0 1 0-.768.64L7.349 10l-2.233 2.68a.5.5 0 0 0 .768.64L8 10.781l2.116 2.54a.5.5 0 0 0 .768-.641L8.651 10l2.233-2.68a.5.5 0 0 0-.768-.64L8 9.219l-2.116-2.54z"
                                                        />
                                                        <path
                                                            d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"
                                                        />
                                                    </svg>
                                                    Excel
                                                </a>
                                                <a
                                                    class="flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-gray-800 dark:text-neutral-200 hover:bg-gray-100 dark:hover:bg-neutral-800 focus:outline-hidden focus:bg-gray-100 dark:focus:bg-neutral-800"
                                                    href="#"
                                                >
                                                    <svg
                                                        class="shrink-0 size-4"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        width="16"
                                                        height="16"
                                                        fill="currentColor"
                                                        viewBox="0 0 16 16"
                                                    >
                                                        <path
                                                            fill-rule="evenodd"
                                                            d="M14 4.5V14a2 2 0 0 1-2 2h-1v-1h1a1 1 0 0 0 1-1V4.5h-2A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v9H2V2a2 2 0 0 1 2-2h5.5L14 4.5ZM3.517 14.841a1.13 1.13 0 0 0 .401.823c.13.108.289.192.478.252.19.061.411.091.665.091.338 0 .624-.053.859-.158.236-.105.416-.252.539-.44.125-.189.187-.408.187-.656 0-.224-.045-.41-.134-.56a1.001 1.001 0 0 0-.375-.357 2.027 2.027 0 0 0-.566-.21l-.621-.144a.97.97 0 0 1-.404-.176.37.37 0 0 1-.144-.299c0-.156.062-.284.185-.384.125-.101.296-.152.512-.152.143 0 .266.023.37.068a.624.624 0 0 1 .246.181.56.56 0 0 1 .12.258h.75a1.092 1.092 0 0 0-.2-.566 1.21 1.21 0 0 0-.5-.41 1.813 1.813 0 0 0-.78-.152c-.293 0-.551.05-.776.15-.225.099-.4.24-.527.421-.127.182-.19.395-.19.639 0 .201.04.376.122.524.082.149.2.27.352.367.152.095.332.167.539.213l.618.144c.207.049.361.113.463.193a.387.387 0 0 1 .152.326.505.505 0 0 1-.085.29.559.559 0 0 1-.255.193c-.111.047-.249.07-.413.07-.117 0-.223-.013-.32-.04a.838.838 0 0 1-.248-.115.578.578 0 0 1-.255-.384h-.765ZM.806 13.693c0-.248.034-.46.102-.633a.868.868 0 0 1 .302-.399.814.814 0 0 1 .475-.137c.15 0 .283.032.398.097a.7.7 0 0 1 .272.26.85.85 0 0 1 .12.381h.765v-.072a1.33 1.33 0 0 0-.466-.964 1.441 1.441 0 0 0-.489-.272 1.838 1.838 0 0 0-.606-.097c-.356 0-.66.074-.911.223-.25.148-.44.359-.572.632-.13.274-.196.6-.196.979v.498c0 .379.064.704.193.976.131.271.322.48.572.626.25.145.554.217.914.217.293 0 .554-.055.785-.164.23-.11.414-.26.55-.454a1.27 1.27 0 0 0 .226-.674v-.076h-.764a.799.799 0 0 1-.118.363.7.7 0 0 1-.272.25.874.874 0 0 1-.401.087.845.845 0 0 1-.478-.132.833.833 0 0 1-.299-.392 1.699 1.699 0 0 1-.102-.627v-.495Zm8.239 2.238h-.953l-1.338-3.999h.917l.896 3.138h.038l.888-3.138h.879l-1.327 4Z"
                                                        />
                                                    </svg>
                                                    .CSV
                                                </a>
                                                <a
                                                    class="flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-gray-800 dark:text-neutral-200 hover:bg-gray-100 dark:hover:bg-neutral-800 focus:outline-hidden focus:bg-gray-100 dark:focus:bg-neutral-800"
                                                    href="#"
                                                >
                                                    <svg
                                                        class="shrink-0 size-4"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        width="16"
                                                        height="16"
                                                        fill="currentColor"
                                                        viewBox="0 0 16 16"
                                                    >
                                                        <path
                                                            d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"
                                                        />
                                                        <path
                                                            d="M4.603 14.087a.81.81 0 0 1-.438-.42c-.195-.388-.13-.776.08-1.102.198-.307.526-.568.897-.787a7.68 7.68 0 0 1 1.482-.645 19.697 19.697 0 0 0 1.062-2.227 7.269 7.269 0 0 1-.43-1.295c-.086-.4-.119-.796-.046-1.136.075-.354.274-.672.65-.823.192-.077.4-.12.602-.077a.7.7 0 0 1 .477.365c.088.164.12.356.127.538.007.188-.012.396-.047.614-.084.51-.27 1.134-.52 1.794a10.954 10.954 0 0 0 .98 1.686 5.753 5.753 0 0 1 1.334.05c.364.066.734.195.96.465.12.144.193.32.2.518.007.192-.047.382-.138.563a1.04 1.04 0 0 1-.354.416.856.856 0 0 1-.51.138c-.331-.014-.654-.196-.933-.417a5.712 5.712 0 0 1-.911-.95 11.651 11.651 0 0 0-1.997.406 11.307 11.307 0 0 1-1.02 1.51c-.292.35-.609.656-.927.787a.793.793 0 0 1-.58.029zm1.379-1.901c-.166.076-.32.156-.459.238-.328.194-.541.383-.647.547-.094.145-.096.25-.04.361.01.022.02.036.026.044a.266.266 0 0 0 .035-.012c.137-.056.355-.235.635-.572a8.18 8.18 0 0 0 .45-.606zm1.64-1.33a12.71 12.71 0 0 1 1.01-.193 11.744 11.744 0 0 1-.51-.858 20.801 20.801 0 0 1-.5 1.05zm2.446.45c.15.163.296.3.435.41.24.19.407.253.498.256a.107.107 0 0 0 .07-.015.307.307 0 0 0 .094-.125.436.436 0 0 0 .059-.2.095.095 0 0 0-.026-.063c-.052-.062-.2-.152-.518-.209a3.876 3.876 0 0 0-.612-.053zM8.078 7.8a6.7 6.7 0 0 0 .2-.828c.031-.188.043-.343.038-.465a.613.613 0 0 0-.032-.198.517.517 0 0 0-.145.04c-.087.035-.158.106-.196.283-.04.192-.03.469.046.822.024.111.054.227.09.346z"
                                                        />
                                                    </svg>
                                                    .PDF
                                                </a>
                                            </div>
                                        </div>
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
                                            <svg
                                                class="shrink-0 size-3.5"
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="24"
                                                height="24"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            >
                                                <path d="M3 6h18" />
                                                <path d="M7 12h10" />
                                                <path d="M10 18h4" />
                                            </svg>
                                            Filter

                                            <span
                                                class="ps-2 text-xs font-semibold text-green-600 dark:text-green-500 border-s border-gray-200 dark:border-neutral-700"
                                            >
                                                1
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
                                                    for="hs-as-filters-dropdown-all"
                                                    class="flex items-center py-2.5 px-3"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        class="shrink-0 size-4 bg-transparent border-gray-300 dark:border-neutral-600 rounded-sm shadow-2xs text-green-600 dark:text-green-500 focus:ring-0 focus:ring-offset-0 checked:bg-green-600 dark:checked:bg-green-500 checked:border-green-600 dark:checked:border-green-500 disabled:opacity-50 disabled:pointer-events-none"
                                                        id="hs-as-filters-dropdown-all"
                                                        checked
                                                    />
                                                    <span
                                                        class="ms-3 text-sm text-gray-800 dark:text-neutral-200"
                                                        >All</span
                                                    >
                                                </label>

                                                <label
                                                    for="hs-as-filters-dropdown-published"
                                                    class="flex items-center py-2.5 px-3"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        class="shrink-0 size-4 bg-transparent border-gray-300 dark:border-neutral-600 rounded-sm shadow-2xs text-green-600 dark:text-green-500 focus:ring-0 focus:ring-offset-0 checked:bg-green-600 dark:checked:bg-green-500 checked:border-green-600 dark:checked:border-green-500 disabled:opacity-50 disabled:pointer-events-none"
                                                        id="hs-as-filters-dropdown-published"
                                                    />
                                                    <span
                                                        class="ms-3 text-sm text-gray-800 dark:text-neutral-200"
                                                        >Published</span
                                                    >
                                                </label>

                                                <label
                                                    for="hs-as-filters-dropdown-pending"
                                                    class="flex items-center py-2.5 px-3"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        class="shrink-0 size-4 bg-transparent border-gray-300 dark:border-neutral-600 rounded-sm shadow-2xs text-green-600 dark:text-green-500 focus:ring-0 focus:ring-offset-0 checked:bg-green-600 dark:checked:bg-green-500 checked:border-green-600 dark:checked:border-green-500 disabled:opacity-50 disabled:pointer-events-none"
                                                        id="hs-as-filters-dropdown-pending"
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
                                            class="flex items-center gap-2 px-3 py-2.5 text-base w-full max-w-md rounded-lg border transition bg-white dark:bg-neutral-900 border-gray-300 dark:border-neutral-700 focus-within:border-green-500 focus-within:ring-2 focus-within:ring-green-500 focus:text-green-400"
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
                                    <TableHeader> Precinct Num </TableHeader>
                                </tr>
                            </thead>

                            <tbody
                                class="divide-y divide-gray-200 dark:divide-neutral-700"
                            >
                                <tr
                                    v-for="record in burial_records.data"
                                    :key="record.id"
                                    class="bg-white dark:bg-neutral-800 hover:bg-gray-50 dark:hover:bg-neutral-700 cursor-pointer"
                                    @click="
                                        () =>
                                            $inertia.visit(
                                                route(
                                                    'clerk.burial_records.show',
                                                    record.id
                                                )
                                            )
                                    "
                                >
                                    <TableData>{{ record.id }}</TableData>
                                    <TableData>
                                        {{ record.deceased.first_name }}
                                        {{ record.deceased.last_name }}
                                    </TableData>
                                    <TableData>
                                        {{ record.deceased.birth.date }}
                                    </TableData>
                                    <TableData>
                                        {{ record.deceased.death.date }}
                                    </TableData>

                                    <TableData>
                                        {{
                                            record.deceased.burial.date ?? "N/A"
                                        }}
                                    </TableData>

                                    <TableData :isHighlighted="true">
                                        {{ record.deceased.precinct_num }}
                                    </TableData>
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
