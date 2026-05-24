<script setup>
import { ref } from "vue";
import { Link, router } from "@inertiajs/vue3";
import Input from "@/Components/Form/Input.vue";
import Button from "@/Components/Form/Button.vue";
import Dashboard from "@/Layouts/Dashboard.vue";
import TableHeader from "@/Components/Table/TableHeader.vue";
import TableData from "@/Components/Table/TableData.vue";

const props = defineProps({
    invitations: Object,
    filters: Object,
});

const search = ref(props.filters.search || "");

const applyFilter = (filterValue) => {
    router.get(
        route("admin.clerk_invitations.index"),
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

const sort = (field) => {
    let direction = "asc";

    if (
        props.filters.sort_field === field &&
        props.filters.sort_direction === "asc"
    ) {
        direction = "desc";
    }

    router.get(
        route("admin.clerk_invitations.logs"),
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

const searchInvitations = () => {
    router.get(
        route("admin.clerk_invitations.logs"),
        {
            search: search.value,
            filter: props.filters.filter,
            sort_field: props.filters.sort_field,
            sort_direction: props.filters.sort_direction,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

defineOptions({
    layout: Dashboard,
});
</script>

<template>
    <div class="max-w-340 px-4 py-10 sm:px-6 lg:px-8 lg:py-6 mx-auto">
        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    <div
                        class="bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl shadow-2xs overflow-hidden"
                    >
                        <div
                            class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-b border-gray-200 dark:border-neutral-700"
                        >
                            <Input
                                class="max-w-md"
                                placeholder="Search by email"
                                v-model="search"
                                @keyup.enter="searchInvitations"
                            />

                            <div class="sm:col-span-2 md:grow">
                                <div class="flex justify-end gap-x-2">
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
                                                    filters.filter === "used"
                                                        ? "Used"
                                                        : filters.filter ===
                                                            "unused"
                                                          ? "Unused"
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
                                                    for="filter-used"
                                                    class="flex items-center py-2.5 px-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-neutral-800"
                                                >
                                                    <input
                                                        type="radio"
                                                        name="filter"
                                                        value="used"
                                                        class="shrink-0 size-4 bg-transparent border-gray-300 dark:border-neutral-600 rounded-full shadow-2xs text-green-600 dark:text-green-500 focus:ring-0 focus:ring-offset-0 checked:bg-green-600 dark:checked:bg-green-500 checked:border-green-600 dark:checked:border-green-500"
                                                        id="filter-used"
                                                        :checked="
                                                            filters.filter ===
                                                            'used'
                                                        "
                                                        @change="
                                                            applyFilter('used')
                                                        "
                                                    />
                                                    <span
                                                        class="ms-3 text-sm text-gray-800 dark:text-neutral-200"
                                                        >Used</span
                                                    >
                                                </label>

                                                <label
                                                    for="filter-unused"
                                                    class="flex items-center py-2.5 px-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-neutral-800"
                                                >
                                                    <input
                                                        type="radio"
                                                        name="filter"
                                                        value="unused"
                                                        class="shrink-0 size-4 bg-transparent border-gray-300 dark:border-neutral-600 rounded-full shadow-2xs text-green-600 dark:text-green-500 focus:ring-0 focus:ring-offset-0 checked:bg-green-600 dark:checked:bg-green-500 checked:border-green-600 dark:checked:border-green-500"
                                                        id="filter-unused"
                                                        :checked="
                                                            filters.filter ===
                                                            'unused'
                                                        "
                                                        @change="
                                                            applyFilter(
                                                                'unused',
                                                            )
                                                        "
                                                    />
                                                    <span
                                                        class="ms-3 text-sm text-gray-800 dark:text-neutral-200"
                                                        >Unused</span
                                                    >
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <table
                            class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700"
                        >
                            <thead class="bg-gray-50 dark:bg-neutral-800">
                                <tr>
                                    <TableHeader @sort="sort('id')">
                                        ID
                                    </TableHeader>
                                    <TableHeader @click="sort('email')">
                                        Email
                                    </TableHeader>
                                    <TableHeader @click="sort('created_at')">
                                        Sent At
                                    </TableHeader>
                                    <TableHeader @click="sort('expires_at')">
                                        Expires At
                                    </TableHeader>
                                    <TableHeader @click="sort('used_at')">
                                        Used At
                                    </TableHeader>
                                    <TableHeader> Status </TableHeader>
                                </tr>
                            </thead>

                            <tbody
                                class="divide-y divide-gray-200 dark:divide-neutral-700"
                            >
                                <tr
                                    v-if="invitations.data.length > 0"
                                    v-for="invitation in invitations.data"
                                    :key="invitation.id"
                                    class="bg-white dark:bg-neutral-800 hover:bg-gray-50 dark:hover:bg-neutral-700"
                                >
                                    <TableData>{{ invitation.id }}</TableData>
                                    <TableData>
                                        {{ invitation.email }}
                                    </TableData>
                                    <TableData>
                                        {{
                                            new Date(
                                                invitation.created_at,
                                            ).toLocaleString()
                                        }}
                                    </TableData>
                                    <TableData>
                                        {{
                                            new Date(
                                                invitation.expires_at,
                                            ).toLocaleString()
                                        }}
                                    </TableData>
                                    <TableData>
                                        {{
                                            invitation.used_at
                                                ? new Date(
                                                      invitation.used_at,
                                                  ).toLocaleString()
                                                : "N/A"
                                        }}
                                    </TableData>
                                    <TableData>
                                        <span
                                            class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium"
                                            :class="{
                                                'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-500':
                                                    invitation.used_at,
                                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-500':
                                                    !invitation.used_at &&
                                                    new Date(
                                                        invitation.expires_at,
                                                    ) > new Date(),
                                                'bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-500':
                                                    !invitation.used_at &&
                                                    new Date(
                                                        invitation.expires_at,
                                                    ) <= new Date(),
                                            }"
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
                                                    v-if="invitation.used_at"
                                                    d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"
                                                />
                                                <path
                                                    v-else-if="
                                                        new Date(
                                                            invitation.expires_at,
                                                        ) > new Date()
                                                    "
                                                    d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"
                                                />
                                                <path
                                                    v-else
                                                    d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"
                                                />
                                            </svg>
                                            {{
                                                invitation.used_at
                                                    ? "Used"
                                                    : new Date(
                                                            invitation.expires_at,
                                                        ) > new Date()
                                                      ? "Pending"
                                                      : "Expired"
                                            }}
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
                                            >No invitations found</span
                                        >
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200 dark:border-neutral-700"
                    >
                        <div class="max-w-md space-y-3 space-x-1.5">
                            <template v-if="invitations?.meta?.links">
                                <component
                                    v-for="link in invitations.meta.links"
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
    </div>
</template>
