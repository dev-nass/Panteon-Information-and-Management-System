<script setup>
import { ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useToast } from "vue-toast-notification";

import Button from "@/Components/Form/Button.vue";
import Modal from "@/Components/Modal.vue";
import Dashboard from "@/Layouts/Dashboard.vue";

defineOptions({
    layout: Dashboard,
});

const props = defineProps({
    importLogs: Array,
});

const page = usePage();
const toast = useToast();
const selectedFile = ref(null);
const fileName = ref("");
const fileError = ref("");
const isDragging = ref(false);
const isUploading = ref(false);
const importResult = ref(null);

// Watch for flash messages
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

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    validateAndSetFile(file);
};

const handleDrop = (event) => {
    event.preventDefault();
    isDragging.value = false;
    const file = event.dataTransfer.files[0];
    validateAndSetFile(file);
};

const handleDragOver = (event) => {
    event.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = () => {
    isDragging.value = false;
};

const validateAndSetFile = (file) => {
    fileError.value = "";
    importResult.value = null;

    if (!file) return;

    // Check file type (CSV or XLSX)
    const validTypes = [
        "text/csv",
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        "application/vnd.ms-excel",
    ];
    const validExtensions = [".csv", ".xlsx", ".xls"];
    const hasValidType = validTypes.includes(file.type);
    const hasValidExtension = validExtensions.some((ext) =>
        file.name.toLowerCase().endsWith(ext),
    );

    if (!hasValidType && !hasValidExtension) {
        fileError.value = "Only CSV and XLSX files are allowed";
        return;
    }

    // Check file size (2MB)
    if (file.size > 2 * 1024 * 1024) {
        fileError.value = "File size must be less than 2MB";
        return;
    }

    selectedFile.value = file;
    fileName.value = file.name;
};

const removeFile = () => {
    selectedFile.value = null;
    fileName.value = "";
    fileError.value = "";
    importResult.value = null;
};

const triggerFileInput = () => {
    document.getElementById("csv-file-input").click();
};

const startImport = () => {
    if (!selectedFile.value) {
        fileError.value = "Please select a CSV or XLSX file first";
        toast.error("Please select a CSV or XLSX file first", {
            position: "top-right",
        });
        return;
    }

    isUploading.value = true;
    importResult.value = null;

    const formData = new FormData();
    formData.append("file", selectedFile.value);

    router.post(route("clerk.import.store"), formData, {
        onSuccess: () => {
            isUploading.value = false;
            removeFile();
        },
        onError: (errors) => {
            isUploading.value = false;
            const errorMsg = errors.file || "Failed to import file";
            fileError.value = errorMsg;
            toast.error(errorMsg, {
                position: "top-right",
                duration: 5000,
            });
        },
    });
};
</script>

<template>
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-6 mx-auto">
        <div class="flex flex-col items-center" data-aos="zoom-out">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-[650px] inline-block align-middle">
                    <div
                        class="flex flex-col gap-y-6 bg-white/60 dark:bg-neutral-900/60 backdrop-blur-xl px-6 py-6 border border-white/20 dark:border-neutral-700 rounded-xl shadow-lg overflow-hidden"
                    >
                        <!-- Header -->
                        <div class="flex gap-x-4">
                            <div
                                class="flex items-center justify-center size-12 rounded-full bg-green-500/10 text-green-600 dark:text-green-400"
                            >
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
                                    class="lucide lucide-download-icon lucide-download"
                                >
                                    <path d="M12 15V3" />
                                    <path
                                        d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"
                                    />
                                    <path d="m7 10 5 5 5-5" />
                                </svg>
                            </div>

                            <article>
                                <h1
                                    class="text-2xl font-bold text-green-600 dark:text-green-400"
                                >
                                    Import Records
                                </h1>

                                <p
                                    class="text-sm text-gray-500 dark:text-gray-400"
                                >
                                    Upload CSV or XLSX files to import burial
                                    and deceased records into the system.
                                </p>
                            </article>
                        </div>

                        <!-- File Upload -->
                        <div>
                            <!-- Hidden File Input -->
                            <input
                                id="csv-file-input"
                                type="file"
                                accept=".csv,.xlsx,.xls"
                                class="hidden"
                                @change="handleFileSelect"
                            />

                            <!-- Drop Area -->
                            <div
                                @click="triggerFileInput"
                                @drop="handleDrop"
                                @dragover="handleDragOver"
                                @dragleave="handleDragLeave"
                                :class="[
                                    'cursor-pointer p-[4rem] flex justify-center bg-white/50 dark:bg-neutral-900/40 border border-dashed rounded-xl transition',
                                    isDragging
                                        ? 'border-green-500 bg-green-50/50 dark:bg-green-900/10'
                                        : 'border-gray-300 dark:border-neutral-700 hover:border-green-500',
                                    fileError ? 'border-red-500' : '',
                                ]"
                            >
                                <div
                                    class="text-center flex flex-col items-center"
                                >
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
                                        class="lucide lucide-import-icon lucide-import text-green-500"
                                    >
                                        <path d="M12 3v12" />
                                        <path d="m8 11 4 4 4-4" />
                                        <path
                                            d="M8 5H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-4"
                                        />
                                    </svg>

                                    <div
                                        class="mt-4 text-sm text-gray-600 dark:text-gray-300"
                                    >
                                        <span
                                            class="font-medium text-gray-800 dark:text-gray-200"
                                        >
                                            Drop your CSV or XLSX file here
                                        </span>
                                        or
                                        <span
                                            class="text-green-600 font-semibold hover:underline"
                                        >
                                            browse
                                        </span>
                                    </div>

                                    <p class="mt-1 text-xs text-gray-500">
                                        Supported formats: CSV, XLSX • Max size
                                        2MB
                                    </p>
                                </div>
                            </div>

                            <!-- Error Message -->
                            <p
                                v-if="fileError"
                                class="mt-2 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ fileError }}
                            </p>

                            <!-- Import Errors Display -->
                            <div
                                v-if="
                                    page.props.flash?.importErrors &&
                                    page.props.flash.importErrors.length > 0
                                "
                                class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800"
                            >
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex items-center justify-center size-8 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 flex-shrink-0"
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
                                        >
                                            <circle cx="12" cy="12" r="10" />
                                            <line
                                                x1="12"
                                                y1="8"
                                                x2="12"
                                                y2="12"
                                            />
                                            <line
                                                x1="12"
                                                y1="16"
                                                x2="12.01"
                                                y2="16"
                                            />
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p
                                            class="text-sm font-medium text-red-800 dark:text-red-200 mb-2"
                                        >
                                            Import Errors ({{
                                                page.props.flash.importErrors
                                                    .length
                                            }})
                                        </p>
                                        <div
                                            class="max-h-48 overflow-y-auto space-y-1"
                                        >
                                            <p
                                                v-for="(error, index) in page
                                                    .props.flash.importErrors"
                                                :key="index"
                                                class="text-xs text-red-700 dark:text-red-300 font-mono bg-red-100/50 dark:bg-red-900/20 px-2 py-1 rounded"
                                            >
                                                {{ error }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Success Message -->
                            <div
                                v-if="page.props.flash?.success"
                                class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800"
                            >
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex items-center justify-center size-8 rounded-lg bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400"
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
                                        >
                                            <path d="M20 6 9 17l-5-5" />
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p
                                            class="text-sm font-medium text-green-800 dark:text-green-200"
                                        >
                                            {{ page.props.flash.success }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- File Preview -->
                            <div
                                v-if="selectedFile"
                                class="mt-4 p-4 bg-gray-50 dark:bg-neutral-800 rounded-lg border border-gray-200 dark:border-neutral-700"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex items-center justify-center size-10 rounded-lg bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400"
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
                                                    d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"
                                                />
                                                <polyline
                                                    points="14 2 14 8 20 8"
                                                />
                                            </svg>
                                        </div>
                                        <div>
                                            <p
                                                class="text-sm font-medium text-gray-800 dark:text-gray-200"
                                            >
                                                {{ fileName }}
                                            </p>
                                            <p
                                                class="text-xs text-gray-500 dark:text-gray-400"
                                            >
                                                {{
                                                    (
                                                        selectedFile.size / 1024
                                                    ).toFixed(2)
                                                }}
                                                KB
                                            </p>
                                        </div>
                                    </div>
                                    <button
                                        @click.stop="removeFile"
                                        type="button"
                                        class="flex items-center justify-center size-8 rounded-lg text-gray-500 hover:bg-gray-200 dark:hover:bg-neutral-700 transition"
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
                                        >
                                            <path d="M18 6 6 18" />
                                            <path d="m6 6 12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-between items-center pt-2">
                            <Button data-hs-overlay="#import-logs-modal">
                                <span class="dark:text-white">
                                    View Import Logs
                                </span>
                            </Button>

                            <Button
                                @click="startImport"
                                :highlighted="true"
                                :disabled="!selectedFile || isUploading"
                            >
                                <span v-if="isUploading">Importing...</span>
                                <span v-else>Start Import</span>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Logs Modal -->
    <Modal id="import-logs-modal" size="lg">
        <template #header>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <path
                    d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"
                />
                <polyline points="14 2 14 8 20 8" />
                <line x1="16" y1="13" x2="8" y2="13" />
                <line x1="16" y1="17" x2="8" y2="17" />
                <polyline points="10 9 9 9 8 9" />
            </svg>
        </template>

        <template #main>
            <h3
                id="import-logs-modal-label"
                class="-mt-2 text-2xl font-bold text-green-600 dark:text-green-400"
            >
                Import Logs
            </h3>

            <div class="w-full max-h-96 overflow-y-auto">
                <div
                    v-if="!importLogs || importLogs.length === 0"
                    class="text-center py-8"
                >
                    <p class="text-gray-500 dark:text-gray-400">
                        No import logs found
                    </p>
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="log in importLogs"
                        :key="log.id"
                        class="p-4 bg-white/50 dark:bg-neutral-800/50 rounded-lg border border-white/30 dark:border-neutral-700 text-left"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p
                                    class="font-medium text-gray-800 dark:text-gray-200"
                                >
                                    {{ log.file_name }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{
                                        new Date(
                                            log.created_at,
                                        ).toLocaleString()
                                    }}
                                </p>
                            </div>
                            <span
                                :class="[
                                    'px-2 py-1 text-xs font-medium rounded-full',
                                    log.status === 'successful'
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                        : log.status === 'failed'
                                          ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                          : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                ]"
                            >
                                {{ log.status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <button
                type="button"
                class="w-full py-3 text-sm font-semibold text-green-600 dark:text-green-400 hover:bg-green-500/10 transition"
                data-hs-overlay="#import-logs-modal"
            >
                Close
            </button>
        </template>
    </Modal>
</template>
