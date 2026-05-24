<script setup>
import { ref, computed } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import Input from "@/Components/Form/Input.vue";
import Button from "@/Components/Form/Button.vue";
import Dashboard from "@/Layouts/Dashboard.vue";
import TableHeader from "@/Components/Table/TableHeader.vue";
import TableData from "@/Components/Table/TableData.vue";

const props = defineProps({
    users: Object,
    filters: Object,
});

const page = usePage();
const search = ref(props.filters.search || "");
const deleteUserId = ref(null);

const applyFilter = (filterValue) => {
    router.get(
        route("admin.user_management.index"),
        {
            search: props.filters.search,
            filter: filterValue,
            sort_field: props.filters.sort_field,
            sort_direction: props.filters.sort_direction,
        },
        {
            preserveState: true,
            replace: true,
        }
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
        route("admin.user_management.index"),
        {
            search: props.filters.search,
            filter: props.filters.filter,
            sort_field: field,
            sort_direction: direction,
        },
        {
            preserveState: true,
            replace: true,
        }
    );
};

const openDeleteModal = (userId) => {
    deleteUserId.value = userId;
    HSOverlay.open("#delete-user-modal");
};

const confirmDelete = () => {
    router.delete(route("admin.user_management.destroy", deleteUserId.value), {
        onSuccess: () => {
            HSOverlay.close("#delete-user-modal");
            deleteUserId.value = null;
        },
    });
};

const cancelDelete = () => {
    HSOverlay.close("#delete-user-modal");
    deleteUserId.value = null;
};

const searchUsers = () => {
    router.get(
        route("admin.user_management.index"),
        {
            search: search.value,
            filter: props.filters.filter,
            sort_field: props.filters.sort_field,
            sort_direction: props.filters.sort_direction,
        },
        {
            preserveState: true,
            replace: true,
        }
    );
};

defineOptions({
    layout: Dashboard,
});
</script>

<template>
    <Teleport to="body">
        <div
            id="delete-user-modal"
            class="hs-overlay hidden size-full fixed top-0 start-0 z-2000 overflow-x-hidden overflow-y-auto bg-black/40 backdrop-blur-sm"
            role="dialog"
            tabindex="-1"
        >
            <div
                class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto"
            >
                <div
                    class="relative w-full max-h-full flex flex-col bg-white/70 dark:bg-neutral-900/70 backdrop-blur-xl border border-white/20 dark:border-white/10 rounded-2xl shadow-lg shadow-gray-200/50 dark:shadow-black/50"
                >
                    <div class="absolute top-3 end-3">
                        <button
                            type="button"
                            class="size-8 inline-flex justify-center items-center rounded-full bg-white/40 dark:bg-neutral-800/40 backdrop-blur-md border border-white/20 dark:border-white/10 text-gray-700 dark:text-neutral-200 hover:bg-white/60 dark:hover:bg-neutral-700/60 transition"
                            @click="cancelDelete"
                        >
                            <svg
                                class="size-4"
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

                    <div
                        class="p-10 flex flex-col items-center gap-y-4 text-center"
                    >
                        <div
                            class="flex items-center justify-center size-14 rounded-full bg-red-500/10 text-red-600 dark:text-red-400"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="60"
                                height="60"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <circle cx="12" cy="12" r="10" />
                                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
                                <path d="M12 17h.01" />
                            </svg>
                        </div>

                        <h3
                            class="-mt-2 text-2xl font-bold text-red-600 dark:text-red-400"
                        >
                            Delete User
                        </h3>

                        <p class="text-gray-600 dark:text-neutral-300 max-w-sm">
                            Are you sure you want to delete this user? This
                            action cannot be undone.
                        </p>
                    </div>

                    <div
                        class="flex border-t border-white/20 dark:border-white/10"
                    >
                        <button
                            type="button"
                            class="w-full py-3 text-sm font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-500/10 transition"
                            @click="cancelDelete"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            class="w-full py-3 text-sm font-semibold text-red-500 hover:bg-red-500/10 transition"
                            @click="confirmDelete"
                        >
                            Delete User
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

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
                                placeholder="Search by name or email"
                                v-model="search"
                                @keyup.enter="searchUsers"
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
                                                    filters.filter === "clerk"
                                                        ? "Clerk"
                                                        : filters.filter ===
                                                            "head"
                                                          ? "Head"
                                                          : filters.filter ===
                                                              "admin"
                                                            ? "Admin"
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
                                                    for="filter-admin"
                                                    class="flex items-center py-2.5 px-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-neutral-800"
                                                >
                                                    <input
                                                        type="radio"
                                                        name="filter"
                                                        value="admin"
                                                        class="shrink-0 size-4 bg-transparent border-gray-300 dark:border-neutral-600 rounded-full shadow-2xs text-green-600 dark:text-green-500 focus:ring-0 focus:ring-offset-0 checked:bg-green-600 dark:checked:bg-green-500 checked:border-green-600 dark:checked:border-green-500"
                                                        id="filter-admin"
                                                        :checked="
                                                            filters.filter ===
                                                            'admin'
                                                        "
                                                        @change="
                                                            applyFilter('admin')
                                                        "
                                                    />
                                                    <span
                                                        class="ms-3 text-sm text-gray-800 dark:text-neutral-200"
                                                        >Admin</span
                                                    >
                                                </label>

                                                <label
                                                    for="filter-head"
                                                    class="flex items-center py-2.5 px-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-neutral-800"
                                                >
                                                    <input
                                                        type="radio"
                                                        name="filter"
                                                        value="head"
                                                        class="shrink-0 size-4 bg-transparent border-gray-300 dark:border-neutral-600 rounded-full shadow-2xs text-green-600 dark:text-green-500 focus:ring-0 focus:ring-offset-0 checked:bg-green-600 dark:checked:bg-green-500 checked:border-green-600 dark:checked:border-green-500"
                                                        id="filter-head"
                                                        :checked="
                                                            filters.filter ===
                                                            'head'
                                                        "
                                                        @change="
                                                            applyFilter('head')
                                                        "
                                                    />
                                                    <span
                                                        class="ms-3 text-sm text-gray-800 dark:text-neutral-200"
                                                        >Head</span
                                                    >
                                                </label>

                                                <label
                                                    for="filter-clerk"
                                                    class="flex items-center py-2.5 px-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-neutral-800"
                                                >
                                                    <input
                                                        type="radio"
                                                        name="filter"
                                                        value="clerk"
                                                        class="shrink-0 size-4 bg-transparent border-gray-300 dark:border-neutral-600 rounded-full shadow-2xs text-green-600 dark:text-green-500 focus:ring-0 focus:ring-offset-0 checked:bg-green-600 dark:checked:bg-green-500 checked:border-green-600 dark:checked:border-green-500"
                                                        id="filter-clerk"
                                                        :checked="
                                                            filters.filter ===
                                                            'clerk'
                                                        "
                                                        @change="
                                                            applyFilter('clerk')
                                                        "
                                                    />
                                                    <span
                                                        class="ms-3 text-sm text-gray-800 dark:text-neutral-200"
                                                        >Clerk</span
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
                                    <TableHeader @click="sort('first_name')">
                                        Full Name
                                    </TableHeader>
                                    <TableHeader @click="sort('email')">
                                        Email
                                    </TableHeader>
                                    <TableHeader @click="sort('contact_number')">
                                        Contact Number
                                    </TableHeader>
                                    <TableHeader @click="sort('role')">
                                        Role
                                    </TableHeader>
                                    <TableHeader> Actions </TableHeader>
                                </tr>
                            </thead>

                            <tbody
                                class="divide-y divide-gray-200 dark:divide-neutral-700"
                            >
                                <tr
                                    v-if="users.data.length > 0"
                                    v-for="user in users.data"
                                    :key="user.id"
                                    class="bg-white dark:bg-neutral-800 hover:bg-gray-50 dark:hover:bg-neutral-700"
                                >
                                    <TableData>{{ user.id }}</TableData>
                                    <TableData>
                                        {{ user.first_name }}
                                        {{ user.middle_name }}
                                        {{ user.last_name }}
                                    </TableData>
                                    <TableData>
                                        {{ user.email }}
                                    </TableData>
                                    <TableData>
                                        {{ user.contact_number }}
                                    </TableData>
                                    <TableData>
                                        <span
                                            class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium"
                                            :class="{
                                                'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-500':
                                                    user.role === 'admin',
                                                'bg-blue-100 text-blue-800 dark:bg-blue-800/30 dark:text-blue-500':
                                                    user.role === 'head',
                                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-500':
                                                    user.role === 'clerk',
                                            }"
                                        >
                                            {{ user.role }}
                                        </span>
                                    </TableData>
                                    <TableData>
                                        <button
                                            @click="openDeleteModal(user.id)"
                                            class="inline-flex items-center gap-x-1 px-3 py-1.5 text-sm font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
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
                                            >
                                                <path d="M3 6h18" />
                                                <path
                                                    d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"
                                                />
                                                <path
                                                    d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"
                                                />
                                            </svg>
                                            Delete
                                        </button>
                                    </TableData>
                                </tr>

                                <tr v-else>
                                    <td
                                        colspan="6"
                                        class="px-6 py-8 text-center"
                                    >
                                        <span
                                            class="text-sm text-gray-500 dark:text-neutral-400"
                                            >No users found</span
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
                            <template v-if="users?.meta?.links">
                                <component
                                    v-for="link in users.meta.links"
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
