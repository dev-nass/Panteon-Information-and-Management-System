<script setup>
import { ref, watch, computed } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import { isEqual } from "lodash";
import { useToast } from "vue-toast-notification";

import Display from "@/Components/Display.vue";
import Dashboard from "@/Layouts/Dashboard.vue";
import TableHeader from "@/Components/Table/TableHeader.vue";
import TableData from "@/Components/Table/TableData.vue";

const props = defineProps({
    user_data: { type: Object, required: true },
});

const page = usePage();
const $toast = useToast();
const errors = computed(() => page.props.errors || {});

const currentUser = computed(() => page.props.auth.user);

const activeTab = ref("profile");
const tabs = [
    { key: "profile", label: "Profile" },
    { key: "account", label: "Account" },
    { key: "records", label: "Records" },
];

const back = () => {
    router.visit(route("admin.user_management.index"));
};

const editing = ref(false);
const hasChanges = ref(false);

const originalData = ref(JSON.parse(JSON.stringify(props.user_data)));
const localData = ref(JSON.parse(JSON.stringify(originalData.value)));

watch(
    localData,
    (newData) => {
        hasChanges.value = !isEqual(newData, originalData.value);
    },
    { deep: true },
);

const discardChanges = () => {
    if (
        hasChanges.value &&
        !confirm(
            "Are you sure you want to discard your changes? This action cannot be undone.",
        )
    ) {
        return;
    }

    localData.value = JSON.parse(JSON.stringify(originalData.value));
    hasChanges.value = false;
    editing.value = false;
};

const saveChanges = () => {
    router.post(
        route("admin.user_management.update", localData.value.id),
        {
            first_name: localData.value.first_name,
            middle_name: localData.value.middle_name,
            last_name: localData.value.last_name,
            email: localData.value.email,
            contact_number: localData.value.contact_number,
            role: localData.value.role,
        },
        {
            onSuccess: () => {
                originalData.value = JSON.parse(
                    JSON.stringify(localData.value),
                );
                hasChanges.value = false;
                editing.value = false;
                $toast.success("User updated successfully!");
            },
            onError: () => {
                $toast.error(
                    "Failed to update user. Please check the form for errors.",
                );
            },
            preserveScroll: true,
            preserveState: false,
        },
    );
};

const openDeleteModal = () => {
    HSOverlay.open("#delete-user-modal");
};

const confirmDelete = () => {
    router.delete(route("admin.user_management.destroy", localData.value.id), {
        onSuccess: () => {
            HSOverlay.close("#delete-user-modal");
            $toast.success("User deleted successfully!");
            router.visit(route("admin.user_management.index"));
        },
        onError: () => {
            $toast.error("Failed to delete user.");
        },
    });
};

const cancelDelete = () => {
    HSOverlay.close("#delete-user-modal");
};

const viewRecord = (record) => {
    router.visit(route("admin.burial_records.show", record.id));
};

const formatDate = (date) => {
    if (!date) return "Not verified";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
};

const canEdit = computed(() => localData.value.id !== currentUser.value.id);

const canDelete = computed(
    () =>
        localData.value.id !== currentUser.value.id &&
        localData.value.role !== "admin",
);

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
            aria-labelledby="delete-user-modal-label"
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
                                <path d="M3 6h18" />
                                <path
                                    d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"
                                />
                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                            </svg>
                        </div>

                        <h3
                            id="delete-user-modal-label"
                            class="-mt-2 text-2xl font-bold text-red-600 dark:text-red-400"
                        >
                            Delete User
                        </h3>

                        <p class="text-gray-600 dark:text-neutral-300 max-w-sm">
                            Are you sure you want to delete
                            <span
                                class="font-semibold text-gray-900 dark:text-white"
                            >
                                {{
                                    `${localData.first_name} ${localData.last_name}`
                                }}
                            </span>
                            ? This action cannot be undone, and attribution to
                            any burial records created by this user will be
                            cleared.
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

    <div class="max-w-6xl mx-auto p-6">
        <!-- Back button -->
        <button
            @click="back"
            class="flex items-center gap-1 mb-6 text-sm text-green-600 dark:text-green-400 hover:underline"
        >
            ← Back
        </button>

        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div class="flex gap-x-3">
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
                        <circle cx="12" cy="8" r="5" />
                        <path d="M20 21a8 8 0 0 0-16 0" />
                    </svg>
                </div>
                <article>
                    <h1
                        class="text-2xl font-bold text-green-600 dark:text-green-400"
                    >
                        {{ localData.first_name }} {{ localData.last_name }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        User Account Details
                    </p>
                </article>
            </div>

            <div class="flex gap-x-3">
                <template v-if="!editing">
                    <button
                        v-if="canEdit"
                        @click="editing = true"
                        class="flex items-center justify-center gap-x-2 px-4 py-2 bg-green-500/10 text-green-400 rounded-xl border border-transparent hover:bg-green-500/20 hover:border-green-500/40 hover:text-green-600 dark:hover:text-green-300 transition-all duration-200"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"
                            />
                            <path d="m15 5 4 4" />
                        </svg>
                        Edit
                    </button>

                    <button
                        v-if="canDelete"
                        @click="openDeleteModal"
                        class="flex items-center justify-center gap-x-2 px-4 py-2 rounded-xl border border-transparent bg-red-500/10 text-red-500 hover:bg-red-500/20 hover:border-red-500/40 transition-all duration-200"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M3 6h18" />
                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                        </svg>
                        Delete
                    </button>
                </template>

                <template v-else>
                    <button
                        :disabled="!hasChanges"
                        @click="saveChanges"
                        class="flex items-center justify-center gap-x-2 px-4 py-2 bg-green-500/10 text-green-400 rounded-xl border border-transparent hover:bg-green-500/20 hover:border-green-500/40 hover:text-green-600 dark:hover:text-green-300 transition-all duration-200"
                        :class="{
                            'opacity-50 cursor-not-allowed': !hasChanges,
                        }"
                    >
                        Save Changes
                    </button>
                    <button
                        @click="discardChanges"
                        class="flex items-center justify-center gap-x-2 px-4 py-2 rounded-xl border border-transparent dark:bg-neutral-800 hover:dark:bg-neutral-600 transition-all duration-200"
                    >
                        Discard
                    </button>
                </template>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex border-b border-gray-200 dark:border-neutral-700 mb-6">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                @click="activeTab = tab.key"
                class="px-4 py-2 text-sm font-medium transition"
                :class="
                    activeTab === tab.key
                        ? 'border-b-2 border-green-500 text-green-600 dark:text-green-400'
                        : 'text-gray-500 dark:text-gray-400 hover:text-green-500'
                "
            >
                {{ tab.label }}
            </button>
        </div>

        <!-- Card Container -->
        <div
            class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-700 rounded-xl shadow-md p-6 transition"
        >
            <!-- Profile Tab -->
            <div
                v-if="activeTab === 'profile'"
                class="grid grid-cols-1 md:grid-cols-2 gap-6"
            >
                <Display
                    label="First Name"
                    :modelValue="localData.first_name"
                    :editing="editing"
                    :error="errors.first_name"
                    @update:modelValue="(val) => (localData.first_name = val)"
                />

                <Display
                    label="Middle Name"
                    :modelValue="localData.middle_name"
                    :editing="editing"
                    :error="errors.middle_name"
                    @update:modelValue="(val) => (localData.middle_name = val)"
                />

                <Display
                    label="Last Name"
                    :modelValue="localData.last_name"
                    :editing="editing"
                    :error="errors.last_name"
                    @update:modelValue="(val) => (localData.last_name = val)"
                />

                <div>
                    <label class="text-sm text-gray-500 dark:text-gray-400">
                        Email
                    </label>
                    <input
                        type="email"
                        :value="localData.email"
                        @input="(e) => (localData.email = e.target.value)"
                        :disabled="!editing"
                        placeholder="—"
                        :class="{
                            'border-red-500 focus:ring-red-500':
                                editing && errors.email,
                        }"
                        class="mt-1 w-full rounded-lg border border-gray-300 dark:border-neutral-700 bg-gray-100 dark:bg-neutral-800 text-gray-800 dark:text-white px-3 py-2 text-sm transition disabled:border-none disabled:bg-white disabled:dark:bg-neutral-900 disabled:dark:text-gray-200 disabled:hover:text-green-600 disabled:hover:dark:text-green-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    />
                    <p
                        v-if="editing && errors.email"
                        class="mt-1 text-sm text-red-500"
                    >
                        {{ errors.email }}
                    </p>
                </div>

                <Display
                    label="Contact Number"
                    :modelValue="localData.contact_number"
                    :editing="editing"
                    :error="errors.contact_number"
                    @update:modelValue="
                        (val) => (localData.contact_number = val)
                    "
                />

                <div>
                    <label class="text-sm text-gray-500 dark:text-gray-400">
                        Email Verified At
                    </label>
                    <div class="mt-1.5 flex items-center gap-x-2">
                        <span
                            class="inline-flex items-center gap-x-1.5 py-1 px-2.5 rounded-full text-xs font-medium"
                            :class="
                                localData.email_verified_at
                                    ? 'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-500'
                                    : 'bg-amber-100 text-amber-800 dark:bg-amber-800/30 dark:text-amber-500'
                            "
                        >
                            {{
                                localData.email_verified_at
                                    ? "Verified"
                                    : "Pending"
                            }}
                        </span>
                        <p
                            v-if="localData.email_verified_at"
                            class="text-sm text-gray-800 dark:text-gray-200"
                        >
                            {{ formatDate(localData.email_verified_at) }}
                        </p>
                    </div>
                </div>

                <div>
                    <label class="text-sm text-gray-500 dark:text-gray-400">
                        Member Since
                    </label>
                    <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">
                        {{ formatDate(localData.created_at) }}
                    </p>
                </div>
            </div>

            <!-- Account Tab -->
            <div
                v-if="activeTab === 'account'"
                class="grid grid-cols-1 md:grid-cols-2 gap-6"
            >
                <div>
                    <label class="text-sm text-gray-500 dark:text-gray-400">
                        Role
                    </label>
                    <select
                        v-if="editing"
                        v-model="localData.role"
                        class="mt-1 w-full rounded-lg border border-gray-300 dark:border-neutral-700 bg-gray-100 dark:bg-neutral-800 text-gray-800 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    >
                        <option value="clerk">Clerk</option>
                        <option value="head">Head</option>
                        <option value="admin">Admin</option>
                    </select>
                    <div v-else class="mt-1.5">
                        <span
                            class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium"
                            :class="{
                                'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-500':
                                    localData.role === 'admin',
                                'bg-blue-100 text-blue-800 dark:bg-blue-800/30 dark:text-blue-500':
                                    localData.role === 'head',
                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-500':
                                    localData.role === 'clerk',
                            }"
                        >
                            {{ localData.role }}
                        </span>
                    </div>
                </div>

                <div>
                    <label class="text-sm text-gray-500 dark:text-gray-400">
                        Burial Records Created
                    </label>
                    <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">
                        {{ localData.burial_records_count }}
                    </p>
                </div>
            </div>

            <!-- Records Tab -->
            <div v-if="activeTab === 'records'">
                <table
                    v-if="localData.burial_records?.length > 0"
                    class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700"
                >
                    <thead class="bg-gray-50 dark:bg-neutral-800">
                        <tr>
                            <TableHeader>ID</TableHeader>
                            <TableHeader>Deceased Name</TableHeader>
                            <TableHeader>Date of Burial</TableHeader>
                            <TableHeader>Phase</TableHeader>
                            <TableHeader>Cluster</TableHeader>
                            <TableHeader>Lot</TableHeader>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-gray-200 dark:divide-neutral-700"
                    >
                        <tr
                            v-for="record in localData.burial_records"
                            :key="record.id"
                            @click="viewRecord(record)"
                            class="cursor-pointer bg-white dark:bg-neutral-800 hover:bg-gray-50 dark:hover:bg-neutral-700"
                        >
                            <TableData>{{ record.id }}</TableData>
                            <TableData>
                                {{ record.deceased_record?.first_name }}
                                {{ record.deceased_record?.last_name }}
                            </TableData>
                            <TableData>
                                {{ record.deceased_record?.date_of_depository }}
                            </TableData>
                            <TableData>
                                {{ record.lot?.cluster?.phase?.phase_name }}
                            </TableData>
                            <TableData>
                                {{ record.lot?.cluster?.cluster_name }}
                            </TableData>
                            <TableData>
                                {{ record.lot?.column }}{{ record.lot?.row }}
                            </TableData>
                        </tr>
                    </tbody>
                </table>

                <div
                    v-else
                    class="text-center py-12 text-gray-500 dark:text-neutral-400"
                >
                    No burial records created by this user.
                </div>
            </div>
        </div>
    </div>
</template>
