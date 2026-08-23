<script setup>
import { ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useToast } from "vue-toast-notification";

import Button from "@/Components/Form/Button.vue";
import Input from "@/Components/Form/Input.vue";
import Modal from "@/Components/Modal.vue";
import Dashboard from "@/Layouts/Dashboard.vue";

defineOptions({
    layout: Dashboard,
});

const props = defineProps({
    templates: Array,
});

const page = usePage();
const toast = useToast();
const templateName = ref("");
const selectedFile = ref(null);
const fileName = ref("");
const fileError = ref("");
const isDragging = ref(false);
const isUploading = ref(false);
const isDeleting = ref(null);
const previewTemplate = ref(null);

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
    event.target.value = "";
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

    if (!file) return;

    const hasValidExtension = file.name.toLowerCase().endsWith(".pdf");

    if (!hasValidExtension) {
        fileError.value = "Only .pdf files are allowed";
        removeFile();
        return;
    }

    if (file.size > 10 * 1024 * 1024) {
        fileError.value = "File size must be less than 10MB";
        removeFile();
        return;
    }

    selectedFile.value = file;
    fileName.value = file.name;
};

const removeFile = () => {
    selectedFile.value = null;
    fileName.value = "";
    fileError.value = "";
};

const triggerFileInput = () => {
    document.getElementById("pdf-file-input").click();
};

const startUpload = () => {
    if (!templateName.value.trim()) {
        toast.error("Please enter a template name first", {
            position: "top-right",
        });
        return;
    }

    if (!selectedFile.value) {
        toast.error("Please select a .pdf file first", {
            position: "top-right",
        });
        return;
    }

    isUploading.value = true;

    const formData = new FormData();
    formData.append("name", templateName.value.trim());
    formData.append("file", selectedFile.value);

    router.post(route("clerk.certificate_templates.store"), formData, {
        onSuccess: () => {
            isUploading.value = false;
            templateName.value = "";
            removeFile();
        },
        onError: (errors) => {
            isUploading.value = false;
            const errorMsg =
                errors.file || errors.name || "Failed to upload template";
            fileError.value = errorMsg;
            toast.error(errorMsg, {
                position: "top-right",
                duration: 5000,
            });
        },
    });
};

const deleteTemplate = (template) => {
    if (!window.confirm(`Delete template "${template.name}"?`)) return;

    isDeleting.value = template.id;

    router.delete(route("clerk.certificate_templates.destroy", template.id), {
        onSuccess: () => {
            isDeleting.value = null;
        },
        onError: () => {
            isDeleting.value = null;
            toast.error("Failed to delete template", {
                position: "top-right",
                duration: 5000,
            });
        },
    });
};

const formatDate = (date) =>
    new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
</script>

<template>
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-6 mx-auto">
        <div class="flex flex-col items-center" data-aos="zoom-out">
            <div
                class="w-full sm:max-w-[650px] flex flex-col gap-y-6 bg-white/60 dark:bg-neutral-900/60 backdrop-blur-xl px-6 py-6 border border-white/20 dark:border-neutral-700 rounded-xl shadow-lg overflow-hidden"
            >
                <!-- Header -->
                <div class="flex flex-wrap gap-x-4">
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
                            class="lucide lucide-file-text-icon lucide-file-text"
                        >
                            <path
                                d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"
                            />
                            <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                            <path d="M10 9H8" />
                            <path d="M16 13H8" />
                            <path d="M16 17H8" />
                        </svg>
                    </div>

                    <article>
                        <h1
                            class="text-2xl font-bold text-green-600 dark:text-green-400"
                        >
                            Certificate Templates
                        </h1>

                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Upload a .pdf layout of the certificate, then mark
                            the boxes where each field will be filled in.
                        </p>
                    </article>
                </div>

                <!-- Upload Form -->
                <div
                    class="flex flex-col gap-y-4 p-4 rounded-xl border border-gray-200 dark:border-neutral-700 bg-white/50 dark:bg-neutral-900/40"
                >
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Template Name
                            <span class="text-red-400">*</span>
                        </label>
                        <Input
                            v-model="templateName"
                            placeholder="e.g. Certificate of Service - Standard"
                        />
                    </div>

                    <!-- Hidden File Input -->
                    <input
                        id="pdf-file-input"
                        type="file"
                        accept=".pdf"
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
                            'cursor-pointer p-8 sm:p-[3rem] flex justify-center bg-white/50 dark:bg-neutral-900/40 border border-dashed rounded-xl transition',
                            isDragging
                                ? 'border-green-500 bg-green-50/50 dark:bg-green-900/10'
                                : 'border-gray-300 dark:border-neutral-700 hover:border-green-500',
                            fileError ? 'border-red-500' : '',
                        ]"
                    >
                        <div class="text-center flex flex-col items-center">
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
                                class="lucide lucide-upload-icon lucide-upload text-green-500"
                            >
                                <path
                                    d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"
                                />
                                <polyline points="17 8 12 3 7 8" />
                                <line x1="12" x2="12" y1="3" y2="15" />
                            </svg>

                            <div
                                class="mt-4 text-sm text-gray-600 dark:text-gray-300"
                            >
                                <span
                                    class="font-medium text-gray-800 dark:text-gray-200"
                                >
                                    Drop your .pdf template here
                                </span>
                                or
                                <span
                                    class="text-green-600 font-semibold hover:underline"
                                >
                                    browse
                                </span>
                            </div>

                            <p class="mt-1 text-xs text-gray-500">
                                Supported format: .pdf • Max size 10MB
                            </p>
                        </div>
                    </div>

                    <!-- Error Message -->
                    <p
                        v-if="fileError"
                        class="text-sm text-red-600 dark:text-red-400"
                    >
                        {{ fileError }}
                    </p>

                    <!-- File Preview -->
                    <div
                        v-if="selectedFile"
                        class="p-4 bg-gray-50 dark:bg-neutral-800 rounded-lg border border-gray-200 dark:border-neutral-700"
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
                                        <polyline points="14 2 14 8 20 8" />
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
                                            (selectedFile.size / 1024).toFixed(
                                                2,
                                            )
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

                    <!-- Upload Action -->
                    <div class="flex flex-wrap gap-2 justify-end">
                        <Button
                            @click="startUpload"
                            :highlighted="true"
                            :disabled="!selectedFile || isUploading"
                        >
                            <span v-if="isUploading">Uploading...</span>
                            <span v-else>Upload Template</span>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Template List -->
            <div
                class="w-full mt-6 bg-white/60 dark:bg-neutral-900/60 backdrop-blur-xl border border-white/20 dark:border-neutral-700 rounded-xl shadow-lg overflow-hidden"
            >
                <div
                    class="px-6 py-4 flex flex-wrap justify-between items-center gap-2 border-b border-gray-200 dark:border-neutral-700"
                >
                    <h2
                        class="text-lg font-semibold text-gray-800 dark:text-gray-200"
                    >
                        Uploaded Templates
                    </h2>
                    <span
                        class="px-2 py-1 text-xs font-medium rounded-full bg-green-500/10 text-green-600 dark:text-green-400"
                    >
                        {{ templates.length }} total
                    </span>
                </div>

                <div v-if="!templates || templates.length === 0" class="p-8">
                    <p
                        class="text-center text-sm text-gray-500 dark:text-gray-400"
                    >
                        No templates uploaded yet. Upload a .pdf template above.
                    </p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-[880px] lg:min-w-full w-full text-sm">
                        <thead>
                            <tr
                                class="border-b border-gray-200 dark:border-neutral-700 text-start"
                            >
                                <th
                                    class="px-6 py-3 text-start font-medium text-gray-500 dark:text-neutral-400"
                                >
                                    Name
                                </th>
                                <th
                                    class="px-6 py-3 text-start font-medium text-gray-500 dark:text-neutral-400"
                                >
                                    Uploaded By
                                </th>
                                <th
                                    class="px-6 py-3 text-start font-medium text-gray-500 dark:text-neutral-400"
                                >
                                    Uploaded At
                                </th>
                                <th
                                    class="px-6 py-3 text-end font-medium text-gray-500 dark:text-neutral-400"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="template in templates"
                                :key="template.id"
                                class="border-b border-gray-100 dark:border-neutral-800 last:border-0"
                            >
                                <td
                                    class="px-6 py-4 font-medium text-gray-800 dark:text-gray-200"
                                >
                                    {{ template.name }}
                                </td>
                                <td
                                    class="px-6 py-4 text-gray-600 dark:text-gray-300"
                                >
                                    {{
                                        template.uploaded_by
                                            ? `${template.uploaded_by.first_name} ${template.uploaded_by.last_name}`
                                            : "—"
                                    }}
                                </td>
                                <td
                                    class="px-6 py-4 text-gray-600 dark:text-gray-300"
                                >
                                    {{ formatDate(template.created_at) }}
                                </td>
                                <td class="px-6 py-4 text-end">
                                    <div class="flex gap-2 justify-end">
                                        <button
                                            type="button"
                                            :data-hs-overlay="'#preview-template-modal'"
                                            @click="previewTemplate = template"
                                            class="inline-flex items-center gap-1.5 px-3 py-1 text-sm rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 border border-blue-500/30 transition-all duration-200"
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
                                                <path
                                                    d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"
                                                />
                                                <circle cx="12" cy="12" r="3" />
                                            </svg>
                                            Preview
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteTemplate(template)"
                                            :disabled="
                                                isDeleting === template.id
                                            "
                                            class="inline-flex items-center gap-1.5 px-3 py-1 text-sm rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/30 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
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
                                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"
                                                />
                                                <path
                                                    d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"
                                                />
                                            </svg>
                                            <span
                                                v-if="
                                                    isDeleting === template.id
                                                "
                                                >Deleting...</span
                                            >
                                            <span v-else>Delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Template Preview Modal -->
    <Modal id="preview-template-modal" size="screen" no-padding>
        <template #main>
            <div class="w-full h-full">
                <h3
                    :id="'preview-template-modal-label'"
                    class="px-6 pt-4 pb-2 text-lg font-semibold text-gray-800 dark:text-gray-200 text-start"
                >
                    {{ previewTemplate?.name }}
                </h3>
                <iframe
                    v-if="previewTemplate"
                    :src="
                        route(
                            'clerk.certificate_templates.file',
                            previewTemplate.id,
                        )
                    "
                    class="w-full h-[80vh] rounded-xl"
                    title="Template preview"
                />
            </div>
        </template>
    </Modal>
</template>
