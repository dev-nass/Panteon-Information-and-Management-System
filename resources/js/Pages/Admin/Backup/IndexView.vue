<script setup>
import { ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { useToast } from "vue-toast-notification";
import { route } from "ziggy-js";

import Button from "@/Components/Form/Button.vue";
import Modal from "@/Components/Modal.vue";
import TableData from "@/Components/Table/TableData.vue";
import TableHeader from "@/Components/Table/TableHeader.vue";
import Dashboard from "@/Layouts/Dashboard.vue";

defineOptions({
    layout: Dashboard,
});

const props = defineProps({
    backups: Array,
});

const page = usePage();
const toast = useToast();
const isCreating = ref(false);
const backupToDelete = ref(null);

const closeOverlay = (el) => {
    if (typeof HSOverlay !== "undefined" && window.$hsOverlayCollection) {
        HSOverlay.close(el);
        return;
    }

    el.classList.remove("open", "opened");
    if (!el.classList.contains("hidden")) {
        el.classList.add("hidden");
    }
};

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            toast.success(flash.success, {
                duration: 5000,
            });
        } else if (flash?.error) {
            toast.error(flash.error, {
                duration: 5000,
            });
        }
    },
    { deep: true },
);

const createBackup = () => {
    isCreating.value = true;

    router.post(
        route("admin.backup.store"),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isCreating.value = false;
            },
        },
    );
};

const downloadBackup = (filename) => {
    window.open(route("admin.backup.download", filename), "_blank");
};

const openDeleteModal = (backup) => {
    backupToDelete.value = backup;
};

const confirmDeleteBackup = () => {
    if (!backupToDelete.value) return;

    const filename = backupToDelete.value.filename;

    router.delete(route("admin.backup.destroy", filename), {
        preserveScroll: true,
        onSuccess: () => {
            backupToDelete.value = null;
            closeOverlay(document.getElementById("delete-backup-modal"));
        },
    });
};

const formatSize = (bytes) => {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(2)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
};

const formatDate = (timestamp) => {
    return new Date(timestamp * 1000).toLocaleString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
    });
};
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
                            <div class="flex gap-x-4 items-center">
                                <div
                                    class="flex items-center justify-center size-12 rounded-full bg-green-500/10 text-green-600 dark:text-green-400"
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
                                        class="lucide lucide-database-icon lucide-database"
                                    >
                                        <ellipse cx="12" cy="5" rx="9" ry="3" />
                                        <path d="M3 5V19A9 9 0 0 0 21 19V5" />
                                        <path d="M3 12A9 9 0 0 0 21 12" />
                                    </svg>
                                </div>

                                <article>
                                    <h1
                                        class="text-2xl font-bold text-green-600 dark:text-green-400"
                                    >
                                        Database Backups
                                    </h1>

                                    <p
                                        class="text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        Create and manage database backup
                                        archives.
                                    </p>
                                </article>
                            </div>

                            <div class="flex justify-end">
                                <Button
                                    type="button"
                                    @click="createBackup"
                                    :highlighted="true"
                                    :disabled="isCreating"
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
                                        <path d="M12 5v14" />
                                        <path d="m5 12 7 7 7-7" />
                                    </svg>
                                    <span v-if="isCreating"> Creating... </span>
                                    <span v-else> Backup Now </span>
                                </Button>
                            </div>
                        </div>

                        <table
                            class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700"
                        >
                            <thead class="bg-gray-50 dark:bg-neutral-800">
                                <tr>
                                    <TableHeader> Filename </TableHeader>
                                    <TableHeader> Date Created </TableHeader>
                                    <TableHeader> Size </TableHeader>
                                    <TableHeader> Actions </TableHeader>
                                </tr>
                            </thead>

                            <tbody
                                class="divide-y divide-gray-200 dark:divide-neutral-700"
                            >
                                <tr
                                    v-if="backups.length > 0"
                                    v-for="backup in backups"
                                    :key="backup.filename"
                                    class="bg-white dark:bg-neutral-800 hover:bg-gray-50 dark:hover:bg-neutral-700"
                                >
                                    <TableData>
                                        <span
                                            class="inline-flex items-center gap-x-2"
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
                                                class="text-green-500 shrink-0"
                                            >
                                                <path
                                                    d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"
                                                />
                                                <polyline
                                                    points="14 2 14 8 20 8"
                                                />
                                            </svg>
                                            <span
                                                class="font-medium text-gray-800 dark:text-gray-200 break-all"
                                            >
                                                {{ backup.filename }}
                                            </span>
                                        </span>
                                    </TableData>
                                    <TableData>
                                        {{ formatDate(backup.last_modified) }}
                                    </TableData>
                                    <TableData>
                                        {{ formatSize(backup.size) }}
                                    </TableData>
                                    <TableData>
                                        <div
                                            class="flex justify-center gap-x-2"
                                        >
                                            <button
                                                type="button"
                                                @click="
                                                    downloadBackup(
                                                        backup.filename,
                                                    )
                                                "
                                                class="inline-flex items-center gap-x-1.5 px-2 py-1.5 text-sm rounded-lg border transition duration-200 bg-green-500/10 text-green-400 hover:bg-green-500/20 border-green-500/30"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    width="14"
                                                    height="14"
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
                                                <span
                                                    class="text-green-600 dark:text-green-400"
                                                >
                                                    Download
                                                </span>
                                            </button>

                                            <button
                                                type="button"
                                                :data-hs-overlay="'#delete-backup-modal'"
                                                @click="openDeleteModal(backup)"
                                                class="inline-flex items-center gap-x-1.5 px-2 py-1.5 text-sm rounded-lg border transition duration-200 bg-red-500/10 text-red-400 hover:bg-red-500/20 border-red-500/30"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    width="14"
                                                    height="14"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                >
                                                    <path d="M3 6h18" />
                                                    <path
                                                        d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"
                                                    />
                                                    <path
                                                        d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"
                                                    />
                                                </svg>
                                                <span
                                                    class="text-red-500 dark:text-red-400"
                                                >
                                                    Delete
                                                </span>
                                            </button>
                                        </div>
                                    </TableData>
                                </tr>

                                <tr v-else>
                                    <td
                                        colspan="4"
                                        class="px-6 py-8 text-center"
                                    >
                                        <span
                                            class="text-sm text-gray-500 dark:text-neutral-400"
                                        >
                                            No backups yet. Click "Backup Now"
                                            to create your first backup.
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Backup Confirmation Modal -->
    <Modal id="delete-backup-modal" size="sm">
        <template #header>
            <svg
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
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
            </svg>
        </template>

        <template #main>
            <h3
                id="delete-backup-modal-label"
                class="-mt-2 text-2xl font-bold text-green-600 dark:text-green-400"
            >
                Delete Backup
            </h3>

            <p class="text-gray-600 dark:text-neutral-300 max-w-sm">
                Are you sure you want to delete
                <span
                    class="font-medium text-gray-800 dark:text-neutral-100 break-all"
                >
                    "{{ backupToDelete?.filename }}"
                </span>
                ? This action cannot be undone.
            </p>
        </template>

        <template #footer>
            <button
                type="button"
                class="w-full py-3 text-sm font-semibold text-green-600 dark:text-green-400 hover:bg-green-500/10 transition"
                data-hs-overlay="#delete-backup-modal"
            >
                Cancel
            </button>
            <button
                type="button"
                class="w-full py-3 text-sm font-semibold text-red-500 hover:bg-red-500/10 transition"
                @click="confirmDeleteBackup"
            >
                Delete
            </button>
        </template>
    </Modal>
</template>
